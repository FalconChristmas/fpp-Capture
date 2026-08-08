#include <fpp-pch.h>

#include <unistd.h>
#include <ifaddrs.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <cstring>
#include <fstream>
#include <list>
#include <vector>
#include <sstream>
#include <SysSocket.h>
#include <cmath>


#include "FPPCapture.h"

#include "commands/Commands.h"
#include "common.h"
#include "settings.h"
#include "Plugin.h"
#include "log.h"
#include "channeloutput/ChannelOutputSetup.h"
#include "fseq/FSEQFile.h"


class FPPStartCaptureCommand;
class FPPStopCaptureCommand;

class FPPCapturePlugin : public FPPPlugins::Plugin, public FPPPlugins::ChannelDataPlugin, public FPPPlugins::APIProviderPlugin {
public:
    
    FPPCapturePlugin() : FPPPlugins::Plugin("fpp-Capture"), FPPPlugins::ChannelDataPlugin(), FPPPlugins::APIProviderPlugin() {
        LogInfo(VB_PLUGIN, "Initializing FPP Capture Plugin\n");
    }
    virtual ~FPPCapturePlugin();

    // The two Command subclasses are declared in this plugin, so their vtables
    // live in its .so and they hold a back-pointer to this object; both are gone
    // once it is unloaded, so it takes them back itself.
    void addOwnedCommand(Command* c) {
        myCommands.push_back(c);
        CommandManager::INSTANCE.addCommand(c);
    }

    // Finish a capture that is still running. Unloading mid-capture would
    // otherwise abandon a half-written .capture file with its header still
    // claiming 48000 frames, losing the recording; this is the same work the
    // "FSEQ Capture Stop" command does. Nothing here is asynchronous, so no
    // readiness predicate is needed.
    virtual std::function<bool()> shutdown() override {
        stopCapturing();
        for (Command* c : myCommands) {
            // removeCommand() only unregisters - CommandManager deletes what is
            // still in its registry at shutdown, so taking one back means
            // owning it again.
            CommandManager::INSTANCE.removeCommand(c);
            delete c;
        }
        myCommands.clear();
        return nullptr;
    }

    virtual void modifyChannelData(int ms, uint8_t* seqData) override {
        if (capturing) {
            if (frame == 0) {
                startMS = GetTimeMS();
            }
            captureFile->addFrame(frame++, seqData);
        }
    }
    void stopCapturing() {
        if (capturing) {
            capturing = false;
            std::string fname = captureFile->getFilename();
            captureFile->finalize();            
            delete captureFile;
            captureFile = nullptr;

            if (frame) {
                uint64_t endMS = GetTimeMS();
                float timing = (endMS - startMS);
                timing /= frame;

                FSEQFile *src = FSEQFile::openFSEQFile(fname);
                V2FSEQFile *dest = (V2FSEQFile*)FSEQFile::createFSEQFile(fname.substr(0, fname.length() - 8),  2, FSEQFile::CompressionType::zstd, 1);
                dest->initializeFromFSEQ(*src);
                dest->setStepTime(std::round(timing));
                dest->setNumFrames(frame);
                dest->enableMinorVersionFeatures(2);
                for (auto &r : GetOutputRanges(false)) {
                    dest->m_sparseRanges.push_back(r);
                }
                dest->writeHeader();

                int max = src->getMaxChannel() + 10;
                uint8_t *data = new uint8_t[max];
                std::vector<std::pair<uint32_t, uint32_t>> ranges;
                src->prepareRead(ranges, 0);
                for (int x = 0; x < frame; x++) {
                    src->getFrame(x)->readFrame(data,  max);
                    dest->addFrame(x, data);
                }
                dest->finalize();
                delete dest;
                delete src;

                unlink(fname.c_str());
            }
        }
    }
    bool startCapturing(const std::string &filename) {
        if (capturing) {
            return false;
        }
        frame = 0;
        std::string file =  FPP_DIR_SEQUENCE("/" + filename + ".capture");
        captureFile = (V2FSEQFile*)FSEQFile::createFSEQFile(file, 2, FSEQFile::CompressionType::zstd, -1);
        captureFile->enableMinorVersionFeatures(2);
        startMS = GetTimeMS();
        captureFile->setStepTime(25);
        captureFile->setNumFrames(48000); //20 minutes of frames, we'll adjust at end
        FSEQFile::VariableHeader header;
        header.code[0] = 's';
        header.code[1] = 'p';
        std::string ver = "FPP Capture Plugin";
        std::vector<uint8_t> &data = header.getData();
        data.assign(ver.begin(), ver.end());
        data.push_back('\0');
        captureFile->addVariableHeader(header);

        uint32_t max = INT32_MAX;
        for (auto &r : GetOutputRanges(false)) {
            max = std::max(max, r.second);
            captureFile->m_sparseRanges.push_back(r);
        }
        captureFile->setChannelCount(max);
        captureFile->writeHeader();
        capturing = true;
        return true;
    }

    virtual void addControlCallbacks(std::map<int, std::function<bool(int)>> &callbacks) override;

    bool capturing = false;
    V2FSEQFile *captureFile = nullptr;
    uint32_t frame = 0;
    uint64_t startMS = 0;
    std::vector<Command*> myCommands;
};

class FPPStartCaptureCommand : public Command {
public:
    FPPStartCaptureCommand(FPPCapturePlugin *p) : Command("FSEQ Capture Start"), plugin(p) {
        args.push_back(CommandArg("FSEQ", "string", "FSEQ"));
    }
    
    virtual std::unique_ptr<Command::Result> run(const std::vector<std::string> &args) override {
        if (!plugin->startCapturing(args[0])) {
            return std::make_unique<Command::ErrorResult>("Failed to start capture");
        }
        return std::make_unique<Command::Result>("FSEQ Capture Started");
    }
    FPPCapturePlugin *plugin;
};
class FPPStopCaptureCommand : public Command {
public:
    FPPStopCaptureCommand(FPPCapturePlugin *p) : Command("FSEQ Capture Stop"), plugin(p) {
    }
    
    virtual std::unique_ptr<Command::Result> run(const std::vector<std::string> &args) override {
        plugin->stopCapturing();
        return std::make_unique<Command::Result>("FSEQ Capture Stopped");
    }    
    FPPCapturePlugin *plugin;
};

FPPCapturePlugin::~FPPCapturePlugin() {
}

void FPPCapturePlugin::addControlCallbacks(std::map<int, std::function<bool(int)>> &callbacks) {
    addOwnedCommand(new FPPStartCaptureCommand(this));
    addOwnedCommand(new FPPStopCaptureCommand(this));
}


// Safe to dlclose() on unload: no threads, no timers, no CurlManager requests,
// no epoll descriptors and no HTTP routes. shutdown() closes out any capture
// still running and withdraws the two commands.
FPP_PLUGIN_SUPPORTS_UNLOAD()

extern "C" {
    FPPPlugins::Plugin *createPlugin() {
        return new FPPCapturePlugin();
    }
}
