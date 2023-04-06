#!/bin/bash

ls /var/cache/httpd/proxy -1 | xargs -I %s rm -rf /var/cache/httpd/proxy/%s
ls /var/cache/httpd/fastcgi -1 | xargs -I %s rm -rf /var/cache/httpd/fastcgi/%s