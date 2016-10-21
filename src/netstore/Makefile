# $Id: Makefile,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $

include ../../netstore.mk

INC_PATH += -I$(dir $(realpath Makefile))../../include
LIB_PATH += -L$(dir $(realpath Makefile))../../lib

SEMAPHORE = /var/run/netstore.sem
OBJECTS = netstore.o
TARGETS = netstore
SPEC_LIBS =
LIBS = $(COMMON_LIBS) $(SPEC_LIBS)

all: $(TARGETS)

.SUFFIXES:
.SUFFIXES: .cpp .o

%.o: %.cpp
	$(CC) $(CFLAGS) $(INC_PATH) -c -o $@ $<

all: $(TARGETS)


$(TARGETS): % : $(OBJECTS)
	$(CC) $(CFLAGS) $(INC_PATH) $(LIB_PATH) $(LIBS) $? -o $@ ../../lib/$(NETSTORE_LIB)


install: $(TARGETS) install-semaphore
	$(INSTALL) $(INSTALLFLAGS) $(TARGETS) $(BINDIR)/$(TARGETS)

install-semaphore:
	$(INSTALL) $(INSTALLFLAGS) /dev/null $(SEMAPHORE)

clean:
	rm -f $(OBJECTS) $(TARGETS)
	rm -f *.o *.core

