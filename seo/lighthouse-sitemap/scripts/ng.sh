#!/bin/sh

# set -x

CWD=$(cd "$(dirname "$0")/.."; pwd)
NUM_URLS_TO_CHECK=${1:-0}

# prepare files and directories
CONFIG_FILE=$CWD/config/config.sh
INPUT_DIR=$CWD/input
INPUT_SITEMAP_URL=
INPUT_SITEMAP=$INPUT_DIR/sitemap.xml
INPUT_URLLIST=$INPUT_DIR/sitemap-urls.txt
REPORTS_DIR=$CWD/reports
INDEX_REPORT=$REPORTS_DIR/index.html

if [ -f $CONFIG_FILE ]; then
    source $CONFIG_FILE
fi

function fetchSitemap() {
    local FORCE=${1}
    if [ ! -f "$INPUT_SITEMAP" ] || [ ! -s "$INPUT_SITEMAP" ] || [ ! -z "$FORCE" ]; then
        if [ -z "$INPUT_SITEMAP_URL" ]; then
            echo "No sitemap url provided"
            return 1
        fi
        echo "Downloading new sitemap < $INPUT_SITEMAP_URL"
        wget $INPUT_SITEMAP_URL -qO $INPUT_SITEMAP
    else 
        echo "Sitemap already exists > $INPUT_SITEMAP"
    fi
    return 0
}

function extractUrlsFromSitemap() {
    local FORCE=${1}
    if [ ! -f "$INPUT_URLLIST" ] || [ ! -s "$INPUT_URLLIST" ] || ! grep -q '[^[:space:]]' < "$INPUT_URLLIST" || [ ! -z "$FORCE" ]; then
        if [ -z "$INPUT_SITEMAP_URL" ]; then
            echo "No sitemap to extract urls from"
            return 1
        fi
        echo "Extracting urls from sitemap > $INPUT_URLLIST"
        sed -E 's/(https?\:\/\/)/\n\1/g' $INPUT_SITEMAP | grep http | grep -v http://www.w3.org | sed -E 's/([^<]*)<.*/\1/g' > $INPUT_URLLIST
    else 
        echo "URL List already exists > $INPUT_URLLIST"
    fi
    return 0
}

function setupIndex() {
    echo  "Generating Table Templates compatible with this run > $CWD/reports/sortable-table/table-*.html"
    HEADER_COL_TEMPLATE=$(grep %ColName% $CWD/sortable-table/table-start.tmpl.html)
    mkdir -p $REPORTS_DIR/sortable-table
    cp $CWD/sortable-table/*.css $REPORTS_DIR/sortable-table/
    cp $CWD/sortable-table/*.js $REPORTS_DIR/sortable-table/

    if [ -z "$HEADER_COL_TEMPLATE" ]; then
        cp $CWD/sortable-table/*.tmpl.html $CWD/sortable-table/table-start.html
    else
        COLS=""
        for F in ${FORM_FACTORS//,/ }; do
            FORM=$(echo ${F} | xargs)
            FORM=$(echo ${FORM:0:1} | tr [a-z] [A-Z])${FORM:1}
            for C in ${CATEGORIES//,/ }; do
                COL_NAME="$FORM "$(echo ${C} | xargs)
                NEW_COL=$(echo $HEADER_COL_TEMPLATE | sed -E "s/%ColName%/${COL_NAME}/g")
                COLS="${COLS}${NEW_COL}"
            done
        done
        awk -v r="$COLS" '{gsub(/^.*%ColName%.*$/,r)}1' $CWD/sortable-table/table-start.tmpl.html > $REPORTS_DIR/sortable-table/table-start.html
    fi
}

function checkUrls() {
    local NUM_URLS_TO_CHECK=${1}

    local URL_COUNT=$(wc -l $INPUT_URLLIST | awk '{print $1}')

    echo
    echo "Processing next $NUM_URLS_TO_CHECK urls out of $URL_COUNT"
    echo

    echo > $INDEX_REPORT
    cat $REPORTS_DIR/sortable-table/table-start.html >> $INDEX_REPORT

    local I=0
    while IFS="" read -r URL || [ -n "$URL" ]
    do
        if [[ -z "${URL// }" ]]; then
            continue
        fi

        if checkUrl $URL; then
            let I=I+1
        fi

        if [ "$I" -ge "$NUM_URLS_TO_CHECK" ]
        then
            break
        fi
    done < $INPUT_URLLIST

    cat $CWD/sortable-table/table-end.html >> $INDEX_REPORT
}

function checkUrl() {
    local URL=${1}
    local URL_PATH=$(echo $URL | sed -E 's/https?\:\/\/[^\/]+\///g')
    URL_PATH=${URL_PATH:-"homepage"}
    URL_DIR=$(dirname $REPORTS_DIR/$URL_PATH)
    
    # echo "checkUrl $URL : $URL_PATH : $URL_DIR"
    mkdir -p $URL_DIR

    local TOOK_ACTION=0
    for F in ${FORM_FACTORS//,/ }; do
        FORM=$(echo "$F" | xargs)
        FORM_FACTOR="--form-factor=$FORM"
        if [ "$FORM" == "desktop" ]; then
            FORM_FACTOR="--preset=desktop"
        fi
        ONLY_CATEGORIES="--only-categories=$CATEGORIES"
        if urlNeedsChecking $URL_PATH $FORM; then
            echo "✅ $URL $FORM"
            lighthouse $URL \
                --output-path=$REPORTS_DIR/$URL_PATH.$FORM \
                --output=json,html \
                --quiet --max-wait-for-load=120000 \
                --chrome-flags="--headless --no-sandbox --disable-gpu" \
                --no-enable-error-reporting \
                --disable-storage-reset \
                $ONLY_CATEGORIES \
                $FORM_FACTOR
            TOOK_ACTION=1
        else
            echo "👍 $URL $FORM"
        fi
    done

    saveResultToIndex $URL $URL_PATH

    if [ "$DELAY" -gt 0 ]; then
        sleep $DELAY
    fi

    # return code 0 : url actively processed
    if [ "$TOOK_ACTION" -eq 1 ]; then
        return 0
    fi

    # return code 2 : url already processed, nothing done
    return 2
}

function urlNeedsChecking() {
    local URL_PATH=$1
    local FORM=$2

    local NEEDS_CHECK=0
    local NO_CHECK_NEEDED=1

    local JSON_FILE="$REPORTS_DIR/$URL_PATH.$FORM.report.json"
    local HTML_FILE="$REPORTS_DIR/$URL_PATH.$FORM.report.html"

    if [ ! -f "$JSON_FILE" ] || [ ! -f "$HTML_FILE" ]; then
        # echo "Missing files for $URL_PATH"
        return $NEEDS_CHECK
    fi

    if [ ! -s "$JSON_FILE" ] || [ ! -s "$HTML_FILE" ]; then
        # echo "Empty files for $URL_PATH"
        return $NEEDS_CHECK
    fi

    local PREV_ERROR=$(jq -r '.runtimeError' < $JSON_FILE)
    if [ -n "$PREV_ERROR" ] && [ "$PREV_ERROR" != "null" ]; then
        # echo "Previous errors for $URL_PATH"
        return $NEEDS_CHECK
    fi
    return $NO_CHECK_NEEDED
}

function saveResultToIndex() {
    local URL=$1
    local URL_PATH=$2
    
    COL_TEMPLATE=$(grep %Score% $CWD/sortable-table/table-col.tmpl.html)

    local COLS=""
    for F in ${FORM_FACTORS//,/ }; do
        FORM=$(echo ${F} | xargs)
        for C in ${CATEGORIES//,/ }; do
            HTML_REPORT=$URL_PATH.$FORM.report.html
            JSON_REPORT=$URL_PATH.$FORM.report.json
            SCORE=$(jq -r '.categories | .'$C' | .score' < $REPORTS_DIR/$URL_PATH.$FORM.report.json) 
            NEW_COL=$(echo $COL_TEMPLATE | sed -E "s|%HtmlReport%|${HTML_REPORT}|g" | sed -E "s|%Score%|${SCORE}|g" | sed -E "s|%JsonReport%|${JSON_REPORT}|g" )
            COLS="${COLS}${NEW_COL}"
        done
    done
    awk -v r="$COLS" '{gsub(/%COLS%/,r)}1' $CWD/sortable-table/table-row.tmpl.html | sed -E "s|%URL%|${URL}|g" >> $INDEX_REPORT
}

fetchSitemap
extractUrlsFromSitemap
setupIndex
checkUrls $NUM_URLS_TO_CHECK