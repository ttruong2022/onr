#!/bin/bash

if [ -z "$1" ]
  then
    echo "The base url must be provided, ex: http://dev.onr-research.com"
    exit 1
fi

npx linkinator -r -f csv --concurrency 20 "$1"
