<?php

use Drupal\media\Entity\Media;

require __DIR__ . '/../log.php';
require __DIR__ . '/../scripts-drupal-imports/ImportMedia.php';

// should be run after import-files
$string = file_get_contents(__DIR__ . "/../output/output-extract/onr-urls-jsdom.json");
$pages = json_decode($string, TRUE);

$import = new ImportMediaFiles();


$scriptId = "fix-media-urls";


// <drupal-media data-align="center" data-caption="" data-entity-type="media" data-entity-uuid="84911dc4-c086-4781-afc3-eb49b7380ff5"></drupal-media>
foreach ($pages as &$page) {
  $pageUrl = $page['url'];
  if (!isset($page['files'])) continue;

  $alreadyInPage = [];

  foreach ($page['files'] as &$file) {
    if (in_array($file['originalSrc'], $alreadyInPage)) {
      continue;
    }

    $alreadyInPage[] = $file['originalSrc'];


    $url = $file['src'];
    switch($url) {
      case str_contains($url, 'media/Images/News/'):
        echo 'Not importing Article Image' . PHP_EOL;
        $skip = TRUE;
        break;
      case str_contains($url, 'media/Files/Funding-Announcements/RFP/'):
        echo 'Not importing Request Proposal Files' . PHP_EOL;
        $skip = TRUE;
        break;
      case str_contains($url, 'media/Files/Funding-Announcements/RFQ/'):
        echo 'Not importing Request for Quotes Files' . PHP_EOL;
        $skip = TRUE;
        break;
      case str_contains($url, 'media/Files/Funding-Announcements/RFI/'):
        echo 'Not importing Request for Information Files' . PHP_EOL;
        $skip = TRUE;
        break;
      case str_contains($url, 'media/Files/Funding-Announcements/Special-Notice/'):
        echo 'Not importing Special Notiece Files' . PHP_EOL;
        $skip = TRUE;
        break;
      case str_contains($url, 'media/Files/Funding-Announcements/BAA/'):
        if (!str_contains($url, 'BAA/2022') && !str_contains($url, 'BAA/2021') && !str_contains($url, 'BAA/2020')) {
          echo 'Not importing Old Funding Announcement Files' . PHP_EOL;
          $skip = TRUE;
        }
        break;
      default:
        break;
    }

    $mediaId = $import->getMediaId($file['src']);

    $oldUrl = $file['originalSrc'];


    switch ($file['type']) {
      case 'image':
        if (!$mediaId) {
          echo "[missing media] " . $file['src'] . " couldn't be found!" . PHP_EOL;
          $page['body'] = str_replace(htmlentities("{{IMG:$oldUrl}}"), '<img src="404.png" alt="' . htmlspecialchars($file['alt'] ?? '') . '">', $page['body'], $count);
        } else {
          $media = Media::load($mediaId);
          if ($media !== NULL) {
            $uuid = $media->uuid();
            $count = 0;
            $page['body'] = str_replace(htmlentities("{{IMG:$oldUrl}}"), '<drupal-media data-align="center" data-caption="' . htmlspecialchars($file['alt'] ?? '') . '" data-entity-type="media" data-entity-uuid="' . $uuid . '"></drupal-media>', $page['body'], $count);
            if ($count < 1) {
              echo htmlentities($oldUrl) . PHP_EOL;
              echo "[replacement error] $pageUrl -- url not found in body: $oldUrl" . PHP_EOL;
            }
          }
        }
        break;
      default:
        $newUrl = $import->getDrupalUrl($mediaId);
        if ($newUrl === NULL) {
          echo "[missing media] " . $file['src'] . " couldn't be found!" . PHP_EOL;
        } else {
          $count = 0;
          $page['body'] = str_replace(htmlentities($oldUrl), $newUrl, $page['body'], $count);
          if ($count < 1) {
            echo htmlentities($oldUrl) . PHP_EOL;
            echo "[replacement error] $pageUrl -- url not found in body: $oldUrl" . PHP_EOL;
          }
        }
    }
  }
}

if (file_put_contents(__DIR__ . "/../output/output-transform/onr-urls-jsdom-images.json",  json_encode($pages, JSON_PRETTY_PRINT)))
  echo "JSON file created successfully..." . PHP_EOL;
else
  echo "Oops! Error creating json file..." . PHP_EOL;
