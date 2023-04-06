#!/bin/bash

fapolicyd-cli --file add /usr/bin/php
sed -i '/^deny_audit perm=any all : ftype=%languages/i allow perm=open exe=/usr/bin/php trust=1 : dir=/var/www/ ftype=text/x-php trust=0' /etc/fapolicyd/fapolicyd.rules
systemctl restart fapolicyd

# ignore nginx cache in aide
echo '!/var/cache/httpd/proxy/' >> /etc/aide.conf

# clam stuff
echo "TCPSocket 3310" >> /etc/clamd.d/scan.conf
echo "LogFile /var/log/clamd.scan" >> /etc/clamd.d/scan.conf

cp /usr/share/doc/clamd/clamd.logrotate /etc/logrotate.d/clamd.logrotate

sed -i 's/pm.max_spare_servers = 35/pm.max_spare_servers = 10/' /etc/php-fpm.d/www.conf

setsebool -P antivirus_can_scan_system 1
systemctl enable clamd@scan.service
systemctl start clamd@scan.service