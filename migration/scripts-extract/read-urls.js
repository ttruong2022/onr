const fs = require("fs");
const omni_jsdom = require("./omni-scrapper-jsdom");

fs.readFile(
  __dirname + "/../output/output-extract/sitemap-cleaned.csv",
  "utf8",
  function (err, data) {
    const urls = data.split(/\r?\n/).reverse();
    omni_jsdom.log("=================================");
    omni_jsdom.log(`[info] started scraper script at ${Date.now()}`);
    scrapeNext(urls);
  }
);

function scrapeNext(urls, index = 0) {
  if (urls.length === 0) {
    omni_jsdom.log(`[info] finished scraper script at ${Date.now()}`);
    return;
  }

  const nextUrl = urls.pop();
  omni_jsdom.scrapeUrls(nextUrl).then((result) => {
    let timeout = 5000;
    if (result.cached) timeout = 0;

    setTimeout(() => {
      scrapeNext(urls, index + 1);
    }, timeout);
  });
}
