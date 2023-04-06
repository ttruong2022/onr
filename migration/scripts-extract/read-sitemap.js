const fs = require("fs");
const got = require("got");
const path = require("path");

const outputDirectory = __dirname + "/../output/output-extract/";
const endpoint = "https://www.onr.navy.mil/sitemap.aspx";

async function downloadSitemap() {
  console.log(`fetching ${endpoint}`);
  const response = await got(endpoint);
  const body = response.body;

  const originalFile = path.normalize(
    outputDirectory + "/sitemap-original.html"
  );
  console.log(`writing original copy ${originalFile}`);

  fs.writeFileSync(outputDirectory + "/sitemap-original.html", body);

  const cleaned = body
    // remove all html tags
    .replace(/<[^>]+>/gi, "\n")
    .split("\n")
    // remove extra whitespace
    .map((line) => line.trim())
    // remove blank and junk lines
    .filter((line) => line.startsWith("http"))
    .join("\n");

  const cleanFile = path.normalize(outputDirectory + "/sitemap-cleaned.csv");
  console.log(`writing cleaned copy ${cleanFile}`);
  fs.writeFileSync(cleanFile, cleaned);
}

downloadSitemap();
