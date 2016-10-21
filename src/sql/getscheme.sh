#!/bin/sh

echo -n "Enter username[`whoami`]: "
read u
if [ "xxx$u" = "xxx" ]
then
				u=`whoami`
fi

echo -n "Enter hostname[localhost]: "
read h
if [ "xxx$h" = "xxx" ]
then
				h=localhost
fi

echo -n "Enter database name[netstore]: "
read d
if [ "xxx$d" = "xxx" ]
then
				d=netstore
fi

mysqldump --opt --quote-names --no-data -u $u -p -h $h $d > scheme.sql

echo "Current SQL scheme is saved in scheme.sql"
