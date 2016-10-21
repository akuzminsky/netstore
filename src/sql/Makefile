# $Id: Makefile,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $

include ../../netstore.mk

all:
	@echo "mysql is ready"

install:
	mysql -u $(mysql_user) -p$(mysql_password) -h $(mysql_host) -e "DROP DATABASE IF EXISTS $(database)" &&\
		mysql -u $(mysql_user) -p$(mysql_password) -h $(mysql_host) -e "CREATE DATABASE $(database)" &&\
		mysql -u $(mysql_user) -p$(mysql_password) -h $(mysql_host) $(database) < scheme.sql && \
		mysql -u $(mysql_user) -p$(mysql_password) -h $(mysql_host) $(database) < patch.sql && \
		mysql -u $(mysql_user) -p$(mysql_password) -h $(mysql_host) $(database) < ChangeLog.sql

# clean targets
clean:
	@echo "Nothing to clean"
