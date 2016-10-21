# $Id: Makefile,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $

include ../../netstore.mk

INC_PATH += -I$(dir $(realpath Makefile))../../include
LIB_PATH += -L$(dir $(realpath Makefile))../../lib

OBJECTS = arch2str.o
TARGETS = arch2str
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


# clean targets
clean: 
	rm -f $(OBJECTS) $(TARGETS)
	rm -f *.core

install: $(TARGETS)
	$(INSTALL) $(INSTALLFLAGS) $(TARGETS) $(BINDIR)/$(TARGETS)


