#!/bin/sh

NUM=${1:-0}

echo "<html><head></head><body>" > /app/output/index-$NUM.html
echo "<table><thead><tr><td>SEO</td><td>Data</td><td>Full Results</td></tr></thead><tbody>" >> /app/output/index-$NUM.html

ls /app/output/index-$NUM-*.html | xargs cat >> /app/output/index-$NUM.html

echo "" >> /app/output/index-$NUM.html
echo "</tbody></table>" >> /app/output/index-$NUM.html
echo "</body></html>" >> /app/output/index-$NUM.html
