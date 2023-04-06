#!/bin/bash
LOGFILE="/var/log/clamav/clamav-$(date +'%Y-%m-%d').log";
EMAIL_MSG="Please see the log file attached.";
EMAIL_FROM="root";
EMAIL_TO="sysadmin@mobomo.com";
DIRTOSCAN="/home /tmp /var/tmp /var/www/storage /var/www/private-files /var/www/webroot/sites/default/files /var/lib/php /usr/share/httpd /var/solr";

for S in ${DIRTOSCAN}; do

  DIRSIZE=$(du -sh "$S" 2>/dev/null | cut -f1);

  if [ -d "$S" ]; then
    echo "Starting a daily scan of "$S" directory. \
    Amount of data to be scanned is "$DIRSIZE".";

    echo "Scanning folder $S" >> "$LOGFILE";
    clamscan -ri "$S" >> "$LOGFILE";
    echo "----------- END SCAN SUMMARY ----------- \
    " >> "$LOGFILE";
  fi

done

# get the value of "Infected lines"
MALWARE=$(tail "$LOGFILE"|grep Infected|cut -d" " -f3);

# if the value is not equal to zero, send an email with the log file attached
if [ "$MALWARE" -ne "0" ];then
  # using heirloom-mailx below
  echo "$EMAIL_MSG"|mail -a "$LOGFILE" -s "Malware Found" -r "$EMAIL_FROM" "$EMAIL_TO";
fi

exit 0