#!/bin/sh

if [ ! -f "/app/input/onr.sitemap.html" ] || [ -s "/app/input/onr.sitemap.html" ] ; then
    wget https://www.onr.navy.mil/sitemap.aspx -qO /app/input/onr.sitemap.html
fi
if [ ! -f "/app/input/onr.sitemap.txt" ] || [ -s "/app/input/onr.sitemap.txt" ]; then
    sed 's/<br\s\/>/\n/g' ./onr.sitemap.html | grep https > /app/input/onr.sitemap.txt
fi

URLCOUNT=$(wc -l /app/input/onr.sitemap.txt | awk '{print $1}')
START=${1:-0}
SPAN=${2:-0}
END=$((START+SPAN))

INDEXFILE=index-$SPAN-$START.html
echo "" > /app/output/$INDEXFILE

I=0
if [ -s /app/input/onr.sitemap.txt ]; then
    while IFS="" read -r p || [ -n "$p" ]
    do
        if [ "$I" -lt "$START" ]; then
            let I=I+1
            continue
        fi
        pp=$(echo $p | sed 's/https\:\/\/www\.onr\.navy\.mil//g')
        pp=${pp:-homepage}
        mkdir -p $(dirname /app/output/$pp)
        if [ ! -f "/app/output/$pp.report.json" ] || [ ! -f "/app/output/$pp.report.html" ] || [ ! -s "/app/output/$pp.report.json" ] || [ ! -s "/app/output/$pp.report.html" ]; then
            PREV_ERROR=$(jq -r '.runtimeError' < /app/output/$pp.report.json)
            if [ -n "$PREV_ERROR" ] && [ "$PREV_ERROR" != "null" ]; then
                echo "Checking $p"
                touch /app/output/$pp.report.json
                touch /app/output/$pp.report.html
                lighthouse $p --quiet --max-wait-for-load=30000 --chrome-flags="--headless --no-sandbox" --output=json,html --output-path=/app/output/$pp --only-categories=seo,performance
            else
                echo "..skip.. $p"
            fi;
        else 
            echo "..skip.. $p"
        fi

        SEO=$(jq -r '.categories | .seo | .score' < /app/output/$pp.report.json) 
        printf '<tr><td>%s</td><td><a href=".%s">json</a></td><td><a href=".%s">%s</a></td></tr>' "$SEO" "$pp.report.json" "$pp.report.html" "$p" >> /app/output/$INDEXFILE
        echo "" >> /app/output/$INDEXFILE
        # sleep 1
        let I=I+1
        if [ "$I" -ge "$END" ]; then
            break
        fi
    done < /app/input/onr.sitemap.txt
fi
