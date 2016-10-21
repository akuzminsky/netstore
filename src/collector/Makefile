# $Id: Makefile,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $ 

include ../../netstore.mk

INC_PATH += -I$(dir $(realpath Makefile))../../include
LIB_PATH += -L$(dir $(realpath Makefile))../../lib

OBJECTS = collector.o
TARGETS = collector

SPEC_LIBS = -lnetsnmp -lcrypto
LIBS = $(COMMON_LIBS) $(SPEC_LIBS)


.SUFFIXES:
.SUFFIXES: .cpp .o

%.o: %.cpp
	$(CC) $(CFLAGS) $(INC_PATH) -c -o $@ $<

all: $(TARGETS)


$(TARGETS): % : $(OBJECTS)
	$(CC) $(CFLAGS) $(INC_PATH) $(LIB_PATH) $(LIBS) $? -o $@ ../../lib/$(NETSTORE_LIB)
	

# Install targets

install: install_dir install_collector

install_dir:
	mkdir -p $(PREFIX) $(BINDIR) $(CONFDIR) $(VARDIR)

install_collector: collector
	$(INSTALL) $(INSTALLFLAGS) $< $(BINDIR)/$<


# clean targets
clean: 
	rm -f $(OBJECTS) $(GEN_SRCS) $(TARGETS)
	rm -f *.o
	rm -f *.core

# deinstall targets

deinstall: uninstall

uninstall: uninstall_collector uninstall_loader uninstall_conf uninstall_dir

uninstall_collector:
	rm -f $(BINDIR)/collector

uninstall_loader:
	rm -f $(BINDIR)/loader

uninstall_conf:
	rm -f $(CONFDIR)/collector.conf

uninstall_dir:
	@for i in $(BINDIR) $(CONFDIR) $(VARDIR) $(PREFIX) ;\
		do \
		if test -d $$i ; \
		then (rmdir $$i 2>/dev/null || echo "$$i not empty. Remove it manually, if you don't need it") ;\
		fi;\
		done
