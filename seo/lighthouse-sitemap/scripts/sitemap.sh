#!/bin/sh

# set -x

CWD=$(cd "$(dirname "$0")/.."; pwd)

# prepare files and directories
INPUT_DIR=$CWD/input
INPUT_SITEMAP=$INPUT_DIR/sitemap.xml
INPUT_URLLIST=$INPUT_DIR/sitemap-urls.txt
REPORTS_DIR=$CWD/reports
INDEX_REPORT=$REPORTS_DIR/index.html

mkdir -p $INPUT_DIR
mkdir -p $REPORTS_DIR

# download sitemap
if [ ! -f "$INPUT_SITEMAP" ] || [ ! -s "$INPUT_SITEMAP" ]; then
    # acquire value for INPUT_SITEMAP_URL
    if [ -f $CWD/config/config.sh ]; then
        source $CWD/config/config.sh
    fi
    if [ -z "$INPUT_SITEMAP_URL" ]; then
        echo "No INPUT_SITEMAP_URL provided in config/config.sh"
        exit 1
    fi
    echo "Downloading new sitemap > $INPUT_SITEMAP_URL"
    wget $INPUT_SITEMAP_URL -qO $INPUT_SITEMAP
    rm -f $INPUT_URLLIST
fi
# extract urls from sitemap
if [ ! -f "$INPUT_URLLIST" ] || [ ! -s "$INPUT_URLLIST" ] || ! grep -q '[^[:space:]]' < "$INPUT_URLLIST"; then
    echo "Parsing urls from sitemap > $INPUT_URLLIST"
    sed -E 's/<br([ \s]*\/)?>/\n/g' $INPUT_SITEMAP | sed -E 's/<url>/\n/g' | sed -E 's/<\/?loc>//g' | sed -E 's/([^<]*)<.*/\1/g' | grep https > $INPUT_URLLIST
    echo >> $INPUT_URLLIST
fi
# prepare index report template
if [ ! -d "$REPORTS_DIR/sortable-table" ]; then
    cp -R $CWD/sortable-table $REPORTS_DIR
fi

URL_COUNT=$(wc -l $INPUT_URLLIST | awk '{print $1}')

NUM_URLS_TO_CHECK=${1:-10}

echo "Processing next $NUM_URLS_TO_CHECK urls out of $URL_COUNT"
echo

I=0
if [ -s $INPUT_URLLIST ]; then
    echo > $INDEX_REPORT
    cat $REPORTS_DIR/sortable-table/sortable-table-start.html >> $INDEX_REPORT

    while IFS="" read -r p || [ -n "$p" ]
    do
        if [[ -z "${p// }" ]]; then
            continue
        fi
        pp=$(echo $p | sed -E 's/https?\:\/\/[^\/]+\///g')
        pp=${pp:-homepage}
        mkdir -p $(dirname $REPORTS_DIR/$pp)
        RUN_CHECK=0
        if [ -f "$REPORTS_DIR/$pp.desktop.report.json" ] && [ -f "$REPORTS_DIR/$pp.desktop.report.html" ] \
        && [ -f "$REPORTS_DIR/$pp.mobile.report.json" ] && [ -f "$REPORTS_DIR/$pp.mobile.report.html" ]
        then
            if [ ! -s "$REPORTS_DIR/$pp.desktop.report.json" ] || [ ! -s "$REPORTS_DIR/$pp.desktop.report.html" ] \
            || [ ! -s "$REPORTS_DIR/$pp.mobile.report.json" ] || [ ! -s "$REPORTS_DIR/$pp.mobile.report.html" ]
            then
                # echo "Empty files for $pp"
                RUN_CHECK=1
            else
                PREV_DESKTOP_ERROR=$(jq -r '.runtimeError' < $REPORTS_DIR/$pp.desktop.report.json)
                if [ -n "$PREV_DESKTOP_ERROR" ] && [ "$PREV_DESKTOP_ERROR" != "null" ]
                then
                    # echo "Desktop result errors for $pp"
                    RUN_CHECK=1
                fi
                PREV_MOBILE_ERROR=$(jq -r '.runtimeError' < $REPORTS_DIR/$pp.mobile.report.json)
                if [ -n "$PREV_MOBILE_ERROR" ] && [ "$PREV_MOBILE_ERROR" != "null" ]
                then
                    # echo "Mobile result errors for $pp"
                    RUN_CHECK=1
                fi
            fi
        else
            # echo "Missing files for $pp"
            RUN_CHECK=1
        fi

        if [ "$RUN_CHECK" -eq 1 ]
        then
            echo "Checking $p"
            touch $REPORTS_DIR/$pp.desktop.report.json
            touch $REPORTS_DIR/$pp.desktop.report.html
            lighthouse $p \
                --output-path=$REPORTS_DIR/$pp.desktop \
                --output=json,html \
                --only-categories=seo \
                --quiet --max-wait-for-load=120000 \
                --chrome-flags="--headless --no-sandbox --disable-gpu" \
                --no-enable-error-reporting \
                --disable-storage-reset \
                --form-factor=desktop \
                --preset=desktop
            touch $REPORTS_DIR/$pp.mobile.report.json
            touch $REPORTS_DIR/$pp.mobile.report.html
            lighthouse $p \
                --output-path=$REPORTS_DIR/$pp.mobile \
                --output=json,html \
                --only-categories=seo \
                --quiet --max-wait-for-load=120000 \
                --chrome-flags="--headless --no-sandbox --disable-gpu" \
                --no-enable-error-reporting \
                --disable-storage-reset \
                --form-factor=mobile
                # --only-categories=performance,seo \
            let I=I+1
        else
            echo "..exists $p"
        fi

        MOBILE_SEO=$(jq -r '.categories | .seo | .score' < $REPORTS_DIR/$pp.mobile.report.json) 
        DESKTOP_SEO=$(jq -r '.categories | .seo | .score' < $REPORTS_DIR/$pp.desktop.report.json) 
        printf '<tr><td><a href="./%s">%s</a><sub><a href="./%s">json</a></sub></td><td><a href="./%s">%s</a><sub><a href="./%s">json</a></sub></td><td><a href="%s">%s</a></td></tr>' \
                "$pp.mobile.report.html" "$MOBILE_SEO" "$pp.mobile.report.json" "$pp.desktop.report.html" "$DESKTOP_SEO" "$pp.desktop.report.json" "$p" "$p" >> $INDEX_REPORT
        echo >> $INDEX_REPORT

        if [ "$I" -ge "$NUM_URLS_TO_CHECK" ]
        then
            break
        fi
    done < $INPUT_URLLIST

    echo >> $INDEX_REPORT
    cat $REPORTS_DIR/sortable-table/sortable-table-end.html >> $INDEX_REPORT

fi