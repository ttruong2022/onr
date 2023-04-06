#!/bin/sh

CWD=$(cd "$(dirname "$0")/.."; pwd)

# prepare files and directories
INPUT_DIR=$CWD/input
INPUT_SITEMAP=$INPUT_DIR/sitemap.html
INPUT_URLLIST=$INPUT_DIR/sitemap-urls.txt

echo $INPUT_DIR

# mkdir -p $INPUT_DIR
# touch $INPUT_SITEMAP
# touch $INPUT_URLLIST
# mkdir -p $REPORTS_DIR
# touch $INDEX_REPORT

# if [ ! -f "$INPUT_SITEMAP" ] || [ ! -s "$INPUT_SITEMAP" ]; then
#     # acquire value for INPUT_SITEMAP_URL
#     if [ -f $CWD/config/config.sh ]; then
#         source $CWD/config/config.sh
#     fi
#     if [ -z "$INPUT_SITEMAP_URL" ]; then
#         echo "No INPUT_SITEMAP_URL provided in config/config.sh"
#         exit 1
#     fi
#     echo "Downloading new sitemap > $INPUT_SITEMAP_URL"
#     wget $INPUT_SITEMAP_URL -qO $INPUT_SITEMAP
#     rm -f $INPUT_URLLIST
# fi
# if [ ! -f "$INPUT_URLLIST" ] || [ ! -s "$INPUT_URLLIST" ] || ! grep -q '[^[:space:]]' < "$INPUT_URLLIST"; then
#     echo "Parsing urls from sitemap > $INPUT_URLLIST"
#     sed -E 's/<br([ \s]*\/)?>/\n/g' $INPUT_SITEMAP | sed -E 's/<url>/\n/g' | sed -E 's/<\/?loc>//g' | sed -E 's/([^<]*)<.*/\1/g' | grep https > $INPUT_URLLIST
#     echo >> $INPUT_URLLIST
# fi

URL_COUNT=$(wc -l $INPUT_URLLIST | awk '{print $1}')

NUM_WORKERS=${1:-2}

URLS_PER_PROCESS=$(( ($URL_COUNT/$NUM_WORKERS)+1 ))

echo "$URL_COUNT urls split between $NUM_WORKERS processes of $URLS_PER_PROCESS urls each for a total of $(( $NUM_WORKERS * $URLS_PER_PROCESS )) urls"
echo

# parallel ./sm-parallel.sh {1} {2} ::: $(seq 0 $URLS_PER_PROCESS $URL_COUNT) ::: $URLS_PER_PROCESS
# ./sm-combiner.sh $URLS_PER_PROCESS
