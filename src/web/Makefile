# $Id: Makefile,v 1.4 2009/11/02 15:32:08 ingoth Exp $

include ../../netstore.mk

WEBDIR=$(PREFIX)/htdocs
DEVDIR=$(PREFIX)/dev

WEBGID=www
WEBUID=www

CONFIG=config.inc.php
EXCLUDE=.mtree.exclude
DNINSTALL=.dninstall

#.if defined(DEVEL)
#WEBDIR=$(DEVDIR)
#CONFIG=config-dev.inc.php
#.endif

all: config
	@echo "Web site is ready"

clean: all

install: install-directories
	for i in `find . -type f | grep -vf $(DNINSTALL)`; do \
		install -g ${WEBGID} -o ${WEBUID} -m 0664 -v $$i $(WEBDIR)/`dirname $$i`; \
	done

install-directories:
	chown -R ${WEBUID}:${WEBGID} *
	mtree -cdi -X $(EXCLUDE) | mtree -Ur -p $(WEBDIR)

install_config: config
	$(INSTALL) -g ${WEBGID} -o ${WEBUID} -m 0640 -v $(CONFIG) $(CONFDIR)/config.inc.php && rm -f $(CONFIG)

config:
	@echo "Building web config" && \
		echo "<? // Here are options for web scripts" > $(CONFIG) && \
		echo "  \$$prefix=\"$(PREFIX)\";" >> $(CONFIG) && \
		echo "  \$$htdocs_dir=\"$(HTDOCS)\";" >> $(CONFIG) && \
		echo "  \$$bin_dir=\"$(BINDIR)\";" >> $(CONFIG) && \
		echo "  \$$start_url=\"Location: https://$(hostname)/\";" >> $(CONFIG) && \
		echo "  // Mysql section" >> $(CONFIG) && \
		echo "  \$$host=\"$(mysql_host)\";" >> $(CONFIG) && \
		echo "  \$$db=\"$(database)\";" >> $(CONFIG) && \
		echo "  \$$radius_db=\"$(radius_db)\";" >> $(CONFIG) && \
		echo "  \$$T3=\"$(VARDIR)/T3\";" >> $(CONFIG) && \
		echo "" >> $(CONFIG) && \
		echo "  \$$_SERVER[\"PATH\"] .= \":/usr/local/bin\";" >> $(CONFIG) && \
		echo "  \$$_SERVER[\"PATH\"] .= \":$(BINDIR)\";" >> $(CONFIG) && \
		echo "  \$$TMPDIR=\"$(TMPDIR)\";" >> $(CONFIG) && \
		echo "  define(\"FPDF_FONTPATH\",\"font/\");" >> $(CONFIG) && \
		echo "  // host where mailboxes are hosted" >> $(CONFIG) && \
		echo "  \$$mailhub=\"$(mailhub)\";" >> $(CONFIG) && \
		echo "  // MX networks where we can send detailed reports" >> $(CONFIG) && \
		echo "  \$$allowed_network[0] = \"80.78.32.0\";" >> $(CONFIG) && \
		echo "  \$$allowed_netmask[0] = \"255.255.224.0\";" >> $(CONFIG) && \
		echo "  \$$allowed_network[1] = \"81.21.0.0\";" >> $(CONFIG) && \
		echo "  \$$allowed_netmask[1] = \"255.255.240.0\";" >> $(CONFIG) && \
		echo "  \$$company_name=\"Company Name Inc.\";" >> $(CONFIG) && \
		echo "  \$$support_name=\"Support Team\";" >> $(CONFIG) && \
		echo "  \$$support_email=\"root@$(hostname)\";" >> $(CONFIG) && \
		echo "  \$$noc_email=\"root@$(hostname)\";" >> $(CONFIG) && \
		echo "  \$$support_phone=\"+380 44 201 02 03\";" >> $(CONFIG) && \
		echo "  setlocale(LC_TIME, \"uk_UA.KOI8-U\");" >> $(CONFIG) && \
		echo "  setlocale(LC_COLLATE, \"uk_UA.KOI8-U\");" >> $(CONFIG) && \
		echo "  header(\"Cache-Control: no-store, no-cache, must-revalidate\");" >> $(CONFIG) && \
		echo "?>" >> $(CONFIG)

dev:
	$(MAKE) -DDEVEL
