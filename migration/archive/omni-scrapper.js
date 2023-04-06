'use strict';

const {
  Scraper,
  Root,
  DownloadContent,
  OpenLinks,
  CollectContent
} = require('nodejs-web-scraper');
const fs = require('fs');
const cheerio = require('cheerio');

function importURL(urls) {
  (async () => {

    const getHeadTag = (content, pageAddress) => {
      return cheerio.load(content)
    }

    let count = 0;
    const jsonArray = [];
    for (let url of urls) {
      if (count <= 1) {
        count++;
        const config = {
          baseSiteUrl: url,
          startUrl: url,
          filePath: './images',
          maxRetries: 3,//The scraper will try to repeat a failed request few
                        // times(excluding 404). Default is 5.
        //  logPath: './logs/',//Highly recommended: Creates a friendly JSON for
                             // each operation object, with all the relevant
                             // data.
        }

        const scraper = new Scraper(config);//Create a new Scraper instance,

        //Now we create the "operations" we need:
        const article = new Root();

        let body = new CollectContent('#colContent', {
          name: 'body',
          contentType: 'html'
        });

        if (body.data.length === 0) {
          body = new CollectContent('#content', {
            name: 'body',
            contentType: 'text/html'
          });
        }

        const headTag = new CollectContent('head', {
          getHeadTag,
          name: 'Head Tag',
          contentType: 'html'
        });

        const leftNav = new CollectContent('#navSection', {
          name: 'leftNav',
          contentType: 'html'
        });

        const breadcrumbs = new CollectContent('#navBreadcrumbs', {
          name: 'breadcrumbs',
          contentType: 'html'
        });

        const image = new DownloadContent('#colContent img', {
          name: 'image'
        });

        // root.addOperation(article);//Then we create a scraping "tree":
        article.addOperation(body);
        article.addOperation(leftNav);
        article.addOperation(breadcrumbs);
        article.addOperation(headTag);
        article.addOperation(image);

        await scraper.scrape(article);

        const articles = article.getData();
        jsonArray.push(articles);

      }
      else {
        // todo temp remove later
        break;
      }
    }

    fs.writeFile('./output-extract/onr-urls.json', JSON.stringify(jsonArray, null, 2), () => {
    });

  })();
}

module.exports = {
  importURL
}
