#!/bin/bash

export $(echo $(cat /etc/php-fpm.d/env.conf | sed 's/\r//g' | sed 's/\[www\]//g' | sed 's/env\["//g' | sed 's/"\]//g' | sed 's/ = /=/g' | xargs) | envsubst)

php -c /etc/php-cli.ini /var/www/vendor/bin/drush --root=/var/www/webroot "$@"