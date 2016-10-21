include netstore.mk

TOOLS = acc2str arch2str charge chkdata.0 collector dumpmonth esort getrnum graphupdate loader make_dbf netstore purgeflows refilter repairdata.0 report support web
CLEAN_TOOLS = $(TOOLS)
TMPCFG1=/tmp/netstore_scripts.cfg
TMPCFG2=/tmp/netstore_binaries.cfg

all: libs $(TOOLS) script_config binary_config

libs:
	cd lib && \
	$(MAKE) all && \
	cd ..

$(TOOLS):
	cd src/$@ && \
		$(MAKE) all && \
		cd ../..

script_config:
	@echo "Building script config" &&\
		echo "# Here are options for netstore scripts" > $(TMPCFG1) && \
		echo "PREFIX=$(PREFIX)" >> $(TMPCFG1) && \
		echo "CONFIG=\$$PREFIX/etc/$(notdir $(TMPCFG1))" >> $(TMPCFG1) && \
		echo "PATH=\$$PATH:\$$PREFIX/bin:/usr/local/bin:/usr/sbin:/usr/bin; export PATH" >> $(TMPCFG1) && \
		echo "DBPATH=/var/db/netstore" >> $(TMPCFG1) && \
		echo "DATAFILE=\$$DBPATH/data.0" >> $(TMPCFG1) && \
		echo "flows=\$$DBPATH/flows" >> $(TMPCFG1) && \
		echo "flows_filter=$$DBPATH/flows_filter" >> $(TMPCFG1) && \
		echo "partition=/var" >> $(TMPCFG1) && \
		echo "TMPDIR=/var/tmp" >> $(TMPCFG1) && \
                echo "T1=\$$DBPATH/T1" >> $(TMPCFG1) &&   \
                echo "T2=\$$DBPATH/T2" >> $(TMPCFG1) &&   \
                echo "T3=\$$DBPATH/T3.tmp" >> $(TMPCFG1) &&   \
                echo "RRDDATA=\$$TMPDIR/T3.rrd" >> $(TMPCFG1) && \
                echo "PURGED=\$$TMPDIR/T3" >> $(TMPCFG1) && \
                echo "dataarch=\$$DBPATH/T3" >> $(TMPCFG1) && \
                echo "SEMAPHORE=/var/run/netstore.sem" >> $(TMPCFG1) && \
                echo "LOCKFILE=/var/run/netflowstaff.lock" >> $(TMPCFG1) && \
                echo "mysql_user=$(mysql_user)" >> $(TMPCFG1) && \
                echo "mysql_password=$(mysql_password)" >> $(TMPCFG1) && \
                echo "mysql_host=$(mysql_host)" >> $(TMPCFG1) && \
                echo "database=$(database)" >> $(TMPCFG1)

binary_config:
	@echo "Building config for binaries" &&\
		echo "# Here are options for collector and other netstore binaries" > $(TMPCFG2) && \
		echo "# That file contains process id (pid) of flowd daemon." >> $(TMPCFG2) && \
		echo "pid_file	/var/run/collector.pid" >> $(TMPCFG2) && \
		echo "# " >> $(TMPCFG2) && \
		echo "# flowd must be binded for that ip address and UDP port" >> $(TMPCFG2) && \
		echo "# listen_address  [address:]port" >> $(TMPCFG2) && \
		echo "# default address: 0.0.0.0" >> $(TMPCFG2) && \
		echo "listen 0.0.0.0" >> $(TMPCFG2) && \
		echo "port 9999" >> $(TMPCFG2) && \
		echo "# Mysql credintals" >> $(TMPCFG2) && \
		echo "mysql_host	$(mysql_host)" >> $(TMPCFG2) && \
		echo "mysql_user	$(mysql_user)" >> $(TMPCFG2) && \
		echo "mysql_password	$(mysql_password)" >> $(TMPCFG2) && \
		echo "mysql_database	$(mysql_database)" >> $(TMPCFG2) && \
		echo "# " >> $(TMPCFG2) && \
		echo "# Maximum size of cache used by collector for geatehered traffic" >> $(TMPCFG2) && \
		echo "# allowed suffixes are kB, MB and GB or none of them" >> $(TMPCFG2) && \
		echo "# which mean size in kilobytes, megabytes, gigabytes or bytes respectively" >> $(TMPCFG2) && \
		echo "cache_size	10MB" >> $(TMPCFG2) && \
		echo "# " >> $(TMPCFG2) && \
		echo "# data_file contains traffic accounting information dump" >> $(TMPCFG2) && \
		echo "data	/var/db/netstore/data" >> $(TMPCFG2) && \
		echo "# " >> $(TMPCFG2) && \
		echo "# Routers" >> $(TMPCFG2) && \
		echo "router barracuda.nbi.com.ua {" >> $(TMPCFG2) && \
		echo "address                 barracuda.nbi.com.ua" >> $(TMPCFG2) && \
		echo "community               public" >> $(TMPCFG2) && \
		echo "}" >> $(TMPCFG2)


install: install_directories install_config install_dsa
	@ for i in $(TOOLS) ; do \
		cd src/$$i && $(MAKE) install && cd ../.. ; done

install_directories:
	mkdir -p $(PREFIX)
	mtree -dU -p $(PREFIX) -f .mtree

install_config: script_config binary_config install_config1 install_config2

install_config1:
	$(INSTALL) -m 600 $(TMPCFG1) $(CONFDIR) && rm -f $(TMPCFG1)

install_config2:
	$(INSTALL) -m 600 $(TMPCFG2) $(CONFDIR) && rm -f $(TMPCFG2)

install_dsa:
	@echo "Generate id_dsa to go to mail server and put it to $(PREFIX)/.ssh/" && \
		echo "See man ssh(1) for details"

clean: clean_lib
	@ for i in $(TOOLS) ; do \
		cd src/$$i && $(MAKE) clean && cd ../.. ; done

clean_lib:
	cd lib && $(MAKE) clean && cd ..


