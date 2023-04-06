<?php

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

$open = fopen(__DIR__ . "/../output/output-extract/sitemap-cleaned.csv", "r");
$pages = [];
if (($open = fopen(__DIR__ . "/../output/output-extract/sitemap-cleaned.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($open, 1000)) !== FALSE) {
    $pages[] = $data;
  }
  fclose($open);
}

$patterns_ignore = [
  'https://www.onr.navy.mil/en/About-ONR/History/tales-of-discovery',
  'History/tales-of-discovery',
  'Conference-Event-ONR',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/requests-for-proposals',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/requests-for-quotes',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/requests-for-information',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/special-notices',
];

$malformed = [
  'https://www.onr.navy.mil/en/_Apps/Download-Image',
  'https://www.onr.navy.mil/en/en/_Apps/Download-Image',
  'https://www.onr.navy.mil/en/_Apps/View-Contact',
  'https://www.onr.navy.mil/en/Contracts-Grants/manage-contract/contract-forms-download',
  'https://www.onr.navy.mil/en/Site-Map',
  'https://www.onr.navy.mil/en/Conference-Event-ONR/archived-events',
];

$existing_urls = [
  'About ONR' => [
    'mid' => '25',
    'parent' => '',
  ],
  'Leadership' => [
    'mid' => '45',
    'parent' => '25',
  ],
  'History' => [
    'mid' => '1436',
    'parent' => '25',
  ],
  'Naval Research Advisory Committee' => [
    'mid' => '1439',
    'parent' => '1436',
  ],
  'Timeline' => [
    'mid' => '1437',
    'parent' => '1436',
  ],
  'All ONR-Sponsored Nobel Laureates' => [
    'mid' => '1444',
    'parent' => '1436',
  ],
  'Historical Records' => [
    'mid' => '1443',
    'parent' => '1436',
  ],
  'Records Overview for Researchers' => [
    'mid' => '1442',
    'parent' => '1436',
  ],
  'ONR Locations' => [
    'mid' => '1441',
    'parent' => '25',
  ],
  'Office of the Inspector General' => [
    'mid' => '1440',
    'parent' => '25',
  ],
  'Organization' => [
    'mid' => '26',
    'parent' => '',
  ],
  'Departments' => [
    'mid' => '46',
    'parent' => '26',
  ],
  'Code 31' => [
    'mid' => '32',
    'parent' => '46',
  ],
  'Code 32' => [
    'mid' => '33',
    'parent' => '46',
  ],
  'Code 33' => [
    'mid' => '34',
    'parent' => '46',
  ],
  'Division 331 Advanced Naval Platforms' => [
    'mid' => '1438',
    'parent' => '34',
  ],
  'Code 34' => [
    'mid' => '35',
    'parent' => '46',
  ],
  'Code 35' => [
    'mid' => '36',
    'parent' => '46',
  ],
  'Code 36' => [
    'mid' => '36',
    'parent' => '46',
  ],
  'ONR Global' => [
    'mid' => '47',
    'parent' => '26',
  ],
  'Our Research' => [
    'mid' => '27',
    'parent' => '',
  ],
  'Work With Us' => [
    'mid' => '28',
    'parent' => '',
  ],
  'Careers' => [
    'mid' => '49',
    'parent' => '28',
  ],
  'Education & Outreach' => [
    'mid' => '29',
    'parent' => '',
  ],
  'News' => [
    'mid' => '30',
    'parent' => '',
  ],
];


try {
  $menu_link_storage = \Drupal::entityTypeManager()
    ->getStorage('menu_link_content');

  $links = [];
  $top_level = "";
  $weight = 0;
  foreach ($pages as $page) {
    $url = $page[0];
    $skip = FALSE;
    foreach ($patterns_ignore as $pattern) {
      if (str_contains($url, $pattern)) {
        $skip = TRUE;
        break;
      }
    }

    if ($skip) {
      continue;
    }

    if (in_array($url, $malformed)) {
      continue;
    }

    if (str_contains($url, 'Media-Center/Press-Releases') || str_contains($url, 'ONR-Global/Press-Releases')) {
      $url = preg_replace('/Media-Center\/Press-Releases\/\d*\//', 'media-center/news-releases/', $url);
      continue;
    }

    $url = str_replace("https://www.onr.navy.mil/en/", '/', $url);
    $url = strtolower($url);

    if ($url === '/about-onr/leadership/assistant-vice-chief-avcnr') {
      $test = 0;
    }

    $url_parts = explode('/', $url);

    $pid = '';
    $parent_part = "";
    $nid = '';
    if (count($url_parts) > 2) {
      // There is a parent
      $parent_part = $url_parts[count($url_parts) - 2];
      foreach ($links as $link) {
        if (str_ends_with($link['path'], $parent_part)) {
          $pid = $link['mid'];
          break;
        }
      }
    }

    $path = lookupByUrl($url);

    if ($path) {
      $path_parts = explode('/', $path);
      if (isset($path_parts[2])) {
        $nid = $path_parts[2];
      }
    }

    if ($nid) {
      $node = Node::load($nid);

      $existing = [];
      foreach ($existing_urls as $key => $existing_url) {
        if (strtolower($key) === strtolower($node->getTitle())) {
          $existing = $existing_url;
          break;
        }
      }

      if (!empty($existing)) {
        $existing_link = $menu_link_storage->load($existing['mid']);
        $links[] = [
          'parent' => $existing['parent'],
          'path' => $url,
          'mid' => $existing_link->getPluginId(),
        ];
        echo 'Skipping menu link for URL as it exists: ' . $url . PHP_EOL;

        continue;
      }

      $menu_link = $menu_link_storage->create([
        'title' => $node->getTitle(),
        'link' => ['uri' => 'entity:node/' . $nid],
        'menu_name' => 'main',
        'parent' => $pid,
        'expanded' => TRUE,
        'weight' => 0,
        'enabled' => FALSE,
      ]);

      try {
        $menu_link->save();
        echo 'Created menu link for URL: ' . $url . PHP_EOL;

      } catch (EntityStorageException $e) {
        echo 'Error saving menu link with URL: ' . $url . PHP_EOL;
      }
    }
    else {
      echo "Could not load NID for URL: " . $url . PHP_EOL;

      $menu_link = $menu_link_storage->create([
        'title' => 'Replace' . $url,
        'link' => ['uri' => 'internal:' . $url],
        'menu_name' => 'main',
        'parent' => $pid,
        'expanded' => TRUE,
        'weight' => 0,
        'enabled' => FALSE,
      ]);

      try {
        $menu_link->save();
        echo 'Created menu link for URL but not reference: ' . $url . PHP_EOL;
      } catch (EntityStorageException $e) {
        echo 'Error saving menu link with URL: ' . $url . PHP_EOL;
      }
    }

    if ($parent_part === "") {
      $parent_part = $url_parts[count($url_parts) - 1];
    }

    if (count($url_parts) > 2) {
      $path = $url_parts[count($url_parts) - 1];
    }
    else {
      $path = $parent_part;
    }

    $links[] = [
      'parent' => $parent_part,
      'path' => $path,
      'mid' => $menu_link->getPluginId(),
    ];
  }
} catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
  echo 'Error getting menu link storage' . PHP_EOL;
}

function lookupByUrl($url) {
  return \Drupal::database()->select('path_alias')
    ->fields('path_alias', ['path'])
    ->condition('alias', $url)
    ->execute()
    ->fetchField();
}

function lookupByName() {

}
