#!/bin/sh

# $Id: makedet.sh,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $

user=netstore
passwd=
db=netflow

TMPDIR=/tmp
PATH=$PATH:/usr/local/bin:/home/collector/bin:/usr/sbin; export PATH

LOCKFILE=/var/run/netflowstaff.lock

DATAFILE=/store/T3

if ! test -f $LOCKFILE
then

touch $LOCKFILE
rs=`echo "select id from order_report where finished = 'no'" | mysql -u $user -p$passwd $db| grep -v id`


for r in $rs
do
        echo "`date` : Generating report id $r"
        report_type=`echo "select report_type from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v report_type`
        result_email=`echo "select result_email from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v result_email`
        notify_email=`echo "select notify_email from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v notify_email`
        publish=`echo "select publish from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v publish`
        cell_operator=`echo "select cell_operator from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v cell_operator`
        cell_phone=`echo "select cell_phone from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v cell_phone`
        arch_type=`echo "select arch_type from order_report where id = $r" | mysql -u $user -p$passwd $db| grep -v arch_type`
        case $report_type in
          daily|hourly)
            report -u $user -p $passwd -d $db -r $r -f $DATAFILE > $TMPDIR/$r.body
            if [ "xxx$result_email" != "xxx" ] 
            then
              echo "To: $result_email" > $TMPDIR/$r
              echo "Subject: Detailed report on Your request" >> $TMPDIR/$r
              echo "From: NBI Support Team<support@nbi.com.ua>" >> $TMPDIR/$r
              echo "Content-Type: text/plain; charset=koi8-r" >> $TMPDIR/$r
              echo "Content-Transfer-Encoding: 8bit" >> $TMPDIR/$r
              echo "" >> $TMPDIR/$r
              #report -u $user -p $passwd -d $db -r $r > $TMPDIR/$r.body
              cat $TMPDIR/$r.body >> $TMPDIR/$r
              sendmail $result_email < $TMPDIR/$r
            fi
            if [ "xxx$notify_email" != "xxx" ] 
            then
              echo "To: $notify_email" > $TMPDIR/$r
              echo "Subject: Detailed report on Your request is ready" >> $TMPDIR/$r
              echo "From: NBI Support Team<support@nbi.com.ua>" >> $TMPDIR/$r
              echo "Content-Type: text/plain; charset=koi8-r" >> $TMPDIR/$r
              echo "Content-Transfer-Encoding: 8bit" >> $TMPDIR/$r
              echo "" >> $TMPDIR/$r
              sendmail $notify_email < $TMPDIR/$r
            fi
            if [ "xxx$cell_phone" != "xxx" ] 
            then
              case $cell_operator in
                +38050)
                  sms2=38050$cell_phone@sms.umc.com.ua
                  ;;
                +38067)
                  sms2=38067$cell_phone@sms.kyivstar.net
                  ;;
              esac
              echo "To: $sms2" > $TMPDIR/$r
              echo "Subject: Detailed report on Your request is ready" >> $TMPDIR/$r
              echo "From: NBI Support Team<support@nbi.com.ua>" >> $TMPDIR/$r
              echo "Content-Type: text/plain; charset=koi8-r" >> $TMPDIR/$r
              echo "Content-Transfer-Encoding: 8bit" >> $TMPDIR/$r
              echo "" >> $TMPDIR/$r
              sendmail $sms2 < $TMPDIR/$r
            fi
            if [ "xxx$publish" = "xxxyes" ]
            then
              report_body=`cat $TMPDIR/$r.body`
              echo "insert into report(id, report_body) values($r, '$report_body')" | mysql -u $user -p$passwd $db;
            fi
            ;;
          flows)
            if [ "xxx$result_email" != "xxx" ]
            then
              report -u $user -p $passwd -d $db -r $r -f $DATAFILE >> $TMPDIR/$r
              case $arch_type in
                zip)
                  zip $TMPDIR/$r $TMPDIR/$r
                  sendattach.pl $result_email $TMPDIR/$r.zip
                  ;;
                rar)
                  cd $TMPDIR
                  rar a $r.rar $TMPDIR/$r
                  sendattach.pl $result_email $TMPDIR/$r.rar
                  ;;
                gz)
                  gzip $TMPDIR/$r
                  sendattach.pl $result_email $TMPDIR/$r.gz
                  ;;
              esac
              if [ "xxx$notify_email" != "xxx" ] 
              then
                echo "To: $notify_email" > $TMPDIR/$r
                echo "Subject: Detailed report on Your request is ready" >> $TMPDIR/$r
                echo "From: NBI Support Team<support@nbi.com.ua>" >> $TMPDIR/$r
                echo "Content-Type: text/plain; charset=koi8-r" >> $TMPDIR/$r
                echo "Content-Transfer-Encoding: 8bit" >> $TMPDIR/$r
                echo "" >> $TMPDIR/$r
                sendmail $notify_email < $TMPDIR/$r
              fi
              if [ "xxx$cell_phone" != "xxx" ] 
              then
                case $cell_operator in
                  +38050)
                    sms2=38050$cell_phone@sms.umc.com.ua
                    ;;
                  +38067)
                    sms2=38067$cell_phone@sms.kyivstar.net
                    ;;
                esac
                echo "To: $sms2" > $TMPDIR/$r
                echo "Subject: Detailed report on Your request is ready" >> $TMPDIR/$r
                echo "From: NBI Support Team<support@nbi.com.ua>" >> $TMPDIR/$r
                echo "Content-Type: text/plain; charset=koi8-r" >> $TMPDIR/$r
                echo "Content-Transfer-Encoding: 8bit" >> $TMPDIR/$r
                echo "" >> $TMPDIR/$r
                sendmail $sms2 < $TMPDIR/$r
              fi
            fi
            ;;
        esac
        echo "update order_report set finished = 'yes' where id = $r" | mysql -u $user -p$passwd $db
        rm -f $TMPDIR/[1234567890]*
        echo "`date` : done."
done
rm -f $LOCKFILE
fi
