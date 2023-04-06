#!/bin/bash

cat >/etc/security/limits.d/498-solr.conf << EOF
solr    hard    nofile  65000
solr    hard    nproc   65000
solr    soft    nofile  65000
solr    soft    nproc   65000
EOF