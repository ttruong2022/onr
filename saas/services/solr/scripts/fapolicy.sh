#!/bin/bash

fapolicyd-cli --file add $(readlink -f /bin/java)
sed -i "/^deny_audit perm=any all : ftype=%languages/i allow perm=open exe=$(readlink -f /bin/java) trust=1 : dir=/opt/solr-8.11.1/ ftype=application/java-archive trust=0" /etc/fapolicyd/fapolicyd.rules
sed -i "/^deny_audit perm=any all : ftype=%languages/i allow perm=open exe=$(readlink -f /bin/java) trust=1 : dir=/var/solr/ ftype=application/java-archive trust=0" /etc/fapolicyd/fapolicyd.rules
systemctl restart fapolicyd

restorecon -R -v /opt/solr