# $Id: netstore.mk,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $

# Include netstore config parameters
PREFIX = /home/netstore
database = netstore
radius_db = radius
mysql_host = localhost
mysql_user = netstore
mysql_password = 1VJWI4vMB6
mysql_database = netstore
hostname = polynom.nbi.com.ua
VARDIR = /var/db/netstore
mailhub = mail.nbi.com.ua
# End of netstore config

INSTALL = install
INSTALLFLAGS = -m 755 -o root -g wheel
LEX = flex++
YACC = bison

CC = g++45
CFLAGS = -Wall -g
MAKE = gmake
SHELL = /bin/sh
AR = ar -r

BINDIR = $(PREFIX)/bin
CONFDIR = $(PREFIX)/etc
HTDOCS = $(PREFIX)/htdocs
TMPDIR = /tmp

MYSQL_DIR = /usr/local
INC_PATH = -I$(MYSQL_DIR)/include/mysql -I/usr/local/include -I/usr/local/include/mysql++
LIB_PATH = -L/usr/local/lib
COMMON_LIBS = -lmysqlpp
NETSTORE_LIB = libnetstore.a
