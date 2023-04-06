<?php
require __DIR__ . '/../log.php';

$scriptId = 'transform';

// should be run after fix-media-urls
$string = file_get_contents(__DIR__ . "/../output/output-transform/onr-urls-jsdom-images.json");
$pages = json_decode($string, TRUE);
logInfo($scriptId, "==============\nstarted " . date("Y/m/d"));

$allTypes = '';
$allTypesCount = 0;
$divs = '';
$divCount = 0;
foreach ($pages as $key => $page) {
  if (isset($page['body'])) {
    if (preg_match('/<[^>]+ (style=\\".*?")/i', $page['body'])) {
      $allTypes .= $page['url'] . PHP_EOL;
      $allTypesCount++;
    }

    if (preg_match('/<div+ (style=\\".*?")/i', $page['body'])) {
      $divs .= $page['url'] . PHP_EOL;
      $divCount++;
    }
  }
}

echo $allTypes . PHP_EOL;
echo $allTypesCount . PHP_EOL;

echo 'Divs with Inline Style' . PHP_EOL;

echo $divs . PHP_EOL;
echo $divCount . PHP_EOL;