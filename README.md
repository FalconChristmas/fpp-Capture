# fpp-Capture

Record incoming E1.31 / ArtNet / DDP channel data to FSEQ sequence files, for
[Falcon Player (FPP)](https://github.com/FalconChristmas/fpp).

Point another controller or sequencer at this FPP instance, start a capture, and the channel data
it receives is written out as a normal FSEQ file. That file can then be played back standalone —
useful for turning a live/streamed show into something FPP can run on its own, or for capturing
what a sequencer is actually sending for debugging.

## Features

- Taps FPP's channel-data path each frame, so in bridge mode it records exactly the E1.31 / ArtNet /
  DDP data being received.
- Writes V2 FSEQ with zstd compression, restricted to the channel ranges actually configured for
  output (sparse ranges), so captures stay reasonably small.
- Timing is measured from the incoming data, and the finished file is rewritten with the observed
  step time and frame count rather than a guessed one.

## Installation

Install from **Content Setup → Plugins** in the FPP web UI, then restart FPPD.

FPP should be in a mode where it is receiving bridged channel data for there to be anything to
capture.

## Commands

The plugin has no settings page — it is driven entirely by two FPP Commands, which you can trigger
from a playlist, a scheduled event, a GPIO button, or the API:

- **FSEQ Capture Start** — begin capturing. Takes the name to save the sequence under.
- **FSEQ Capture Stop** — stop capturing and finalize the FSEQ file.

The resulting sequence appears in the normal FPP sequence list.

## Resource notes

Capturing compresses full-width channel data in real time and then rewrites the file when the
capture stops. `pluginInfo.json` declares `minMemoryMB: 1024` and `minCpuCores: 2` so the Plugin
Manager can warn on very small boards; these are advisory hints, not hard requirements.

## License

GPLv2 — see [LICENSE](LICENSE).
