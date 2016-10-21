#!/bin/sh

user=netstore
passwd=
db=netflow

TMPDIR=/tmp
PATH=$PATH:/usr/local/bin:/home/collector/bin:/usr/sbin; export PATH

LOCKFILE=/var/run/netflowstaff.lock
dataarch=/store/T3

rs=`echo "select id from maillist" | mysql -u $user -p$passwd $db| grep -v id`
#rs=`echo "select id from order_report where id = 143" | mysql -u $user -p$passwd $db| grep -v id`


for r in $rs
do
        client_id=`echo "select client_id from maillist where id = $r" | mysql -u $user -p$passwd $db| grep -v client_id`
        result_email=`echo "select email from maillist where id = $r" | mysql -u $user -p$passwd $db| grep -v email`
        report -u $user -p $passwd -d $db -c $client_id -f $dataarch > $TMPDIR/$r.body
            if [ "xxx$result_email" != "xxx" ] 
            then
              echo "To: $result_email" > $TMPDIR/$r
              echo "Subject: Daily traffic usage report" >> $TMPDIR/$r
              echo "From: NBI Support Team<support@nbi.com.ua>" >> $TMPDIR/$r
              echo "Content-Type: text/plain; charset=koi8-r" >> $TMPDIR/$r
              echo "Content-Transfer-Encoding: 8bit" >> $TMPDIR/$r
              echo "" >> $TMPDIR/$r
              cat $TMPDIR/$r.body >> $TMPDIR/$r
              sendmail $result_email < $TMPDIR/$r
            fi
done
