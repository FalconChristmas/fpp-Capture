SRCDIR ?= /opt/fpp/src
include $(SRCDIR)/makefiles/common/setup.mk
include $(SRCDIR)/makefiles/platform/*.mk

all: libfpp-Capture.$(SHLIB_EXT)
debug: all

CFLAGS+=-I.
OBJECTS_fpp_capture_so += src/FPPCapture.o
LIBS_fpp_capture_so += -L$(SRCDIR) -lfpp -ljsoncpp -lhttpserver
CXXFLAGS_src/FPPCapture.o += -I$(SRCDIR)


%.o: %.cpp Makefile
	$(CCACHE) $(CC) $(CFLAGS) $(CXXFLAGS) $(CXXFLAGS_$@) -c $< -o $@

libfpp-Capture.$(SHLIB_EXT): $(OBJECTS_fpp_capture_so) $(SRCDIR)/libfpp.$(SHLIB_EXT)
	$(CCACHE) $(CC) -shared $(CFLAGS_$@) $(OBJECTS_fpp_capture_so) $(LIBS_fpp_capture_so) $(LDFLAGS) -o $@

clean:
	rm -f libfpp-Capture.so $(OBJECTS_fpp_capture_so)

