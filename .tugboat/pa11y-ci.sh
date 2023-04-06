#!/bin/bash

echo 'Generate checks from Dev'
"${TUGBOAT_ROOT}"/.a11y/check \
    --sitemap "${TUGBOAT_BASE_PREVIEW_URL}"/sitemap.xml \
    --sitemap-find http://dev-1985719800.us-east-1.elb.amazonaws.com/sitemap.xml \
    --sitemap-replace "${TUGBOAT_BASE_PREVIEW_URL}" \
    --dir "${TUGBOAT_ROOT}"/.a11y/508-prod --csv results.csv --html \
    --runner axe --standard WCAG2AA --debug || true
chmod -R 777 "${TUGBOAT_ROOT}"/.a11y/508-prod
mkdir -p "${TUGBOAT_ROOT}"/webroot/508/508-prod
chmod 777 "${TUGBOAT_ROOT}"/webroot/508/508-prod
cp -R "${TUGBOAT_ROOT}"/.a11y/508-prod/* "${TUGBOAT_ROOT}"/webroot/508/508-prod/

echo 'Generate checks from Local'
"${TUGBOAT_ROOT}"/.a11y/check \
    --sitemap "${TUGBOAT_DEFAULT_SERVICE_URL}"/sitemap.xml \
    --sitemap-find http://dev-1985719800.us-east-1.elb.amazonaws.com/sitemap.xml \
    --sitemap-replace "${TUGBOAT_SERVICE_URL_HOST}" \
    --dir "${TUGBOAT_ROOT}"/.a11y/508-tugboat --csv results.csv --html \
    --runner axe --standard WCAG2AA --debug || true
chmod -R 777 "${TUGBOAT_ROOT}"/.a11y/508-tugboat
mkdir -p "${TUGBOAT_ROOT}"/webroot/508/508-tugboat
chmod 777 "${TUGBOAT_ROOT}"/webroot/508/508-tugboat
cp -R "${TUGBOAT_ROOT}"/.a11y/508-tugboat/* "${TUGBOAT_ROOT}"/webroot/508/508-tugboat/

echo 'Generate Diff between prod and tugboat.'
node "${TUGBOAT_ROOT}"/.a11y/compare2jsons.js "${TUGBOAT_ROOT}"/.a11y/508-prod/results.csv-WCAG2AA.json "${TUGBOAT_ROOT}"/.a11y/508-tugboat/results.csv-WCAG2AA.json || true

if test -f "${TUGBOAT_ROOT}/.a11y/508-diff/results.json"; then
  pa11y-ci-reporter-html \
    -s "${TUGBOAT_ROOT}"/.a11y/508-diff/results.json \
    -d "${TUGBOAT_ROOT}"/.a11y/508-diff
  "${TUGBOAT_ROOT}"/.a11y/pa11yciJsonToCsv "${TUGBOAT_ROOT}"/.a11y/508-diff/results.json "${TUGBOAT_ROOT}"/.a11y/508-diff/results.csv
  chmod -R 777 "${TUGBOAT_ROOT}"/.a11y/508-diff
  mkdir -p "${TUGBOAT_ROOT}"/webroot/508/508-diff
  chmod 777 "${TUGBOAT_ROOT}"/webroot/508/508-diff
  cp -R "${TUGBOAT_ROOT}"/.a11y/508-diff/* "${TUGBOAT_ROOT}"/webroot/508/508-diff/

else
  echo 'Failed to generate Diff'
fi

echo 'Generate Shared Index'
node "${TUGBOAT_ROOT}"/.a11y/generateIndexFile
cp "${TUGBOAT_ROOT}"/.a11y/index.html "${TUGBOAT_ROOT}"/webroot/508

chmod -R 777 "${TUGBOAT_ROOT}"/webroot/508
echo 'Finished pa11y-ci'
