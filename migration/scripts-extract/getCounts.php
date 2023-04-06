<?php

$string = file_get_contents(dirname(__FILE__) . "/../output/output-extract/onr-urls-jsdom.json");
$json = json_decode($string, TRUE);

$images = 0; $files = 0; $pages = 0;
foreach ($json as $current) {
  $pages++;
  if ($current) {
    if (array_key_exists('files', $current)) {
      $filesArray = $current['files'];
      foreach ($filesArray as $item) {
        switch ($item['type']) {
          case 'image':
            $images++;
            break;
          case 'file':
            $files++;
            break;
          default:
            break;
        }
      }
    }
  }
}

echo 'images: ' . $images . PHP_EOL;
echo 'files: ' . $files . PHP_EOL;
echo 'pages: ' . $pages . PHP_EOL;
