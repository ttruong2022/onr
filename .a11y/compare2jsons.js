
const fs = require('fs');
const path = require('path');

var ogFile = "";
var newFile = "";

process.argv.slice(2).forEach((val, index) => {
    switch (index) {
      case 0:
        ogFile = path.resolve(val);
        break;
      case 1:
        newFile = path.resolve(val);
        break;
    }
});

var ogData = require(ogFile);
var newData = require(newFile);

var diffData = {
	"total": 0,
	"passes": 0,
	"errors": 0,
	"results": {}
}

// walk og once and generate a unique "key" for each item (url+code+selector)
// walk new once and check for existance key match, if not found: its new error, keep it

var ogKeys = {};
for ( let fullurl in ogData.results ) {
	let url = stripHost(fullurl);
	for ( let i=ogData.results[fullurl].length-1; i>=0; i-- ) {
		let key = url +' '+ ogData.results[fullurl][i].code +' '+ ogData.results[fullurl][i].selector;
		ogKeys[key] = ogData.results[fullurl][i];
	}
}
// console.log(JSON.stringify(ogKeys));

var newKeys = {};
for ( let fullurl in newData.results ) {
	let url = stripHost(fullurl);
	for ( let i=newData.results[fullurl].length-1; i>=0; i-- ) {
		let key = url +' '+ newData.results[fullurl][i].code +' '+ newData.results[fullurl][i].selector;
		newKeys[key] = newData.results[fullurl][i];
		if ( ! ogKeys.hasOwnProperty(key) ) {
			if ( ! diffData.results.hasOwnProperty(url) ) {
				diffData.total += 1;
				diffData.results[url] = [];
			}
			diffData.results[url].push(newData.results[fullurl][i]);
			diffData.errors += 1;
		}
	}
}
// console.log(JSON.stringify(newKeys));

function stripHost(fullurl) {
	return fullurl.replace(/^[a-z]{4,5}\:\/\/[^\/]+(.*)/, '$1');
}

try {
  var diffFile = path.resolve(__dirname, '508-diff/results.json');
  fs.writeFileSync(diffFile, JSON.stringify(diffData,null,2));
} catch (err) {
  console.log(err);
}

//console.log(JSON.stringify(diffData));
