#!/bin/bash

export $(echo $(cat /etc/php-fpm.d/env.conf | sed 's/\r//g' | sed 's/\[www\]//g' | sed 's/env\["//g' | sed 's/"\]//g' | sed 's/ = /=/g' | xargs) | envsubst)

redis-cli --tls -h $ELASTICACHE_HOST -p $ELASTICACHE_PORT -a $ELASTICACHE_PASS FLUSHDB