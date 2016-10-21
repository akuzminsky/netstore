# $Id: Makefile,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $

include ../../netstore.mk

all:
	@echo "support scripts are ready"

install:
	for i in `find . -type f | grep -vf .dninstall`; do \
		$(INSTALL) $(INSTALLFLAGS) $$i $(BINDIR)/$$i ; done

# clean targets
clean:
	@echo "Nothing to clean"
