#!/bin/sh

echo "" >> ChangeLog.sql
echo "UPDATE db_version SET version = '\$Revision: 1.1.1.1 $';" >> ChangeLog.sql
echo "-- Commited at \$Date: 2007/11/29 15:29:45 $ by \$Author: ingoth $" >> ChangeLog.sql
cvs commit
