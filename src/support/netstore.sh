#!/usr/local/bin/bash

$include ../etc/netstore_scripts.cfg

while test -f $LOCKFILE
do
        sleep 1
done
touch $LOCKFILE
echo ""

echo -n "`date`: Check for free space..."
used=`df -h $partition | tail -1 | awk '{ print $5}' | awk -F% '{ print $1}'`

# we need at least 55% free space
if test $used -lt 48
then
	echo OK
else
	echo ""
	echo "Not enough space at $partition to process data!!!"
	echo "You should archive old flows"
	rm -f $LOCKFILE
	exit -1
fi

echo "`date`: Load started..."
rm -f $DATAFILE
echo -n "`date`: Rotating flows file..."
collector -f $CONFIG -k rotate || exit -1
echo " done."

while ! test -f $DATAFILE
do
        sleep 1
done

rm -f $T1
echo -n "`date`: Creating dump file..."
loader -f $CONFIG -d $DATAFILE -a $T1 -i $flows -j $flows_filter || exit -1

echo -n "`date`: Doing calculation..."
if ! test -f $SEMAPHORE
then
        touch $SEMAPHORE
fi
netstore -f $CONFIG -a $flows -b $flows_filter || exit -1


echo -n "`date`: Sending daily mail reports..."
maillist.sh

echo "`date`: Sorting:"
esort -f $T1 -t $T2 -o $T3 -p 4000000 || exit -1
rm -f $T1 $T2

echo -n "`date`: Updating RRD..."
graphupdate -c $CONFIG -f $T3 > /dev/null 2>&1 || exit -1

echo -n "`date`: Purging flows:"
purgeflows -f $CONFIG -s $T3 -d $PURGED || exit -1
mv $PURGED $T3

echo "`date`: Merging:"
esort -f $dataarch -t $T3 -o $T1 -s || exit -1
mv $T1 $dataarch

echo -n "`date`: Calculating money..."
charge -c $CONFIG || exit -1


df -h -t ufs
echo "`date`: Load finished."
rm -f $LOCKFILE

