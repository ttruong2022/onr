const cheerio = require("cheerio");
const got = require("got");
const fs = require("fs");
const path = require("path");
const http = require("http");
const https = require("https");
// const imgdownload = require("image-downloader");
const mime = require("mime-types");

const sanitize = require("sanitize-filename");

const jsonArray = [];
const outputDirectory = __dirname + "/../output";

let knownDownloads;

try {
  knownDownloads = require(outputDirectory + "/output-extract/files.json");
} catch (e) {
  // pass, this file won't exist first run
  if (e.code !== "MODULE_NOT_FOUND") log(e);
  knownDownloads = {};
}

async function cachedGot(url) {
  const filename = outputDirectory + "/html-cache/" + sanitize(url);
  let cached = fs.existsSync(filename);
  if (!cached) {
    log(`[page] found new ${url}`);
    const response = await got(url);
    fs.writeFileSync(filename, response.body);
  } else {
    log(`[page] ${url} local cache found, skipping...`);
  }
  return { cached: cached, body: fs.readFileSync(filename) };
}

async function scrapeUrls(url) {
  try {
    const response = await cachedGot(url);
    const $ = cheerio.load(response.body);
    const data = await getData($, url);
    data.url = url;

    writeDeepJson(url, data);
    jsonArray.push(data);
    writeJson(jsonArray);

    return { cached: response.cached && data.files.every((f) => f.cached) };
  } catch (error) {
    logError("Failed to get: " + url);
    logError(error);
    return {};
  }
}

async function getData($, url) {
  const leftNav = $("#navSection").html();
  const breadcrumbs = $("#navBreadcrumbs").html();

  const metaDescription = $('meta[name="description"]').attr("content");
  const metaKeywords = $('meta[name="keywords"]').attr("content");
  const titleTag = $("title").html();

  const bodyHtml = $("#colContent").html();
  let images;

  if (bodyHtml && bodyHtml.trim()) {
    images = $("#colContent img");
  } else {
    images = $("#content img");
  }

  let pendingDownloads = [];
  let filesArray = [];
  for (let image of images) {
    const file = downloadImage(image, url);
    pendingDownloads.push(file);
    $(image).replaceWith(`{{IMG:${image.attribs.src}}}`);
  }
  const files = $(
    '#colContent a[href*="-/media/"], #colContent a[href*="~/media/"]'
  );

  let count = 0;
  for (let anchor of files) {
    const singleFile = downloadLinkedFile(anchor, url);
    pendingDownloads.push(singleFile);
    count++;
    if (count % 25 === 0) {
      filesArray = filesArray.concat(await Promise.all(pendingDownloads));
      pendingDownloads = [];
    }
  }
  // also handle less than 50 / remaining items
  if (pendingDownloads.length) {
    filesArray = filesArray.concat(await Promise.all(pendingDownloads));
  }


  let body = $("#colContent").html();
  if (!body) {
    body = $("#content").html();
  }

  return {
    body: body,
    leftNav: leftNav,
    breadcrumbs: breadcrumbs,
    title: titleTag,
    metaDescription: metaDescription,
    metaKeywords: metaKeywords,
    files: filesArray.filter(Boolean),
  };
}

function downloadImage(image, pageUrl, otherData) {
  const src = image["attribs"]["src"];
  const alt = image["attribs"]["alt"];

  return download(src, pageUrl, {
    type: "image",
    pageURl: pageUrl,
    alt: alt,
    ...otherData,
  });
}

function downloadLinkedFile(anchor, pageUrl, otherData) {
  const src = anchor["attribs"]["href"];
  return download(src, pageUrl, {
    type: "file",
    pageURl: pageUrl,
    ...otherData,
  });
}

const PENDING = {};
function download(originalSrc, pageUrl, otherData) {
  const fileParts = originalSrc.split("/").pop();
  const filename = fileParts.substr(0, fileParts.lastIndexOf("."));
  // this normalizes a relative-or-absolute url given a relative origin (pageUrl)
  const urlObject = new URL(originalSrc, pageUrl);
  if (urlObject.host === "www.onr.navy.mil") {
    urlObject.protocol = "https:";
  }
  const src = urlObject.toString();

  if (PENDING[src]) return PENDING[src];

  const current = new Promise(function (resolve, reject) {
    if (knownDownloads[src]) {
      return resolve({ ...knownDownloads[src], cached: true });
    }
    (urlObject.protocol === "http:" ? http : https).get(
      src,
      function (response) {
        const extension =
          "." + mime.extension(response.headers["content-type"]);

        const sep = handleMultipleDuplicateFiles(
          outputDirectory + "/files/" + filename,
          extension
        );
        const dest = filename + sep + extension;
        const result = {
          ...otherData,
          dest: dest,
          src: src,
          originalSrc: originalSrc,
        };
        const fileStream = fs.createWriteStream(
          outputDirectory + "/files/" + dest
        );

        function handleError(err) {
          logError(`[download error] ${dest}`);
          logError(err);
          result.status = err;
          delete PENDING[src];
          resolve(result);
        }

        fileStream
          .on("finish", function () {
            log(`[download] ${dest}`);
            fileStream.close();
            result.status = "downloaded";
            knownDownloads[src] = result;
            writeKnownFiles(knownDownloads);
            delete PENDING[src];
            resolve(result);
          })
          .on("error", handleError);
        response.on("error", handleError);
        response.pipe(fileStream);
      }
    );
  }).catch(function (error) {
    logError(`[download error] Failed to get src: ${src}`);
    logError(error);
    result.status = error;
    delete PENDING[src];
    resolve(result);
  });
  PENDING[src] = current;
  return current;
}

function writeDeepJson(url, data) {
  let deepPath = path.join(
    outputDirectory + "/output-extract/website/",
    path.dirname(url.split(".mil/").pop())
  );
  fs.mkdirSync(deepPath, { recursive: true });
  fs.writeFileSync(
    deepPath + "/" + url.substring(url.lastIndexOf("/") + 1) + ".json",
    JSON.stringify(data, null, 2)
  );
}

function writeJson(jsonArray) {
  return fs.writeFileSync(
    outputDirectory + "/output-extract/onr-urls-jsdom.json",
    JSON.stringify(jsonArray, null, 2)
  );
}

function writeKnownFiles(files) {
  return fs.writeFileSync(
    outputDirectory + "/output-extract/files.json",
    JSON.stringify(files, null, 2)
  );
}

function log(message) {
  console.log(message);
  fs.appendFileSync(outputDirectory + "/logs/scrape.txt", message + "\n");
}

function logError(message) {
  console.log(message);
  fs.appendFileSync(
    outputDirectory + "/logs/scrape-errors.txt",
    message + "\n"
  );
}

function handleMultipleDuplicateFiles(path, extension) {
  let num = 0;
  let sep = "";
  while (fs.existsSync(path + sep + extension)) {
    sep = "_" + num;
    num++;
  }
  return sep;
}

module.exports = {
  scrapeUrls,
  log,
  logError,
};
