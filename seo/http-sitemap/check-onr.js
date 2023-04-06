const checkHttpStatus = require('check-http-status');

checkHttpStatus({
  'sitemaps':  [
    'https://stage.onr-research.com/sitemap.xml'
  ],
  'skip200': true, // Do not report the URLs having HTTP code 200.
  'export': {
    'format': 'xlsx',
    'location': './',
  },
  'options': {
    'headers': {
      'Accept': 'text/html',
    }
  }
});