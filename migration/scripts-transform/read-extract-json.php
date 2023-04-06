<?php
require __DIR__ . '/../log.php';
require __DIR__ . '/content-type-specific-functions/articles-transform.php';
require __DIR__ . '/content-type-specific-functions/basic-page-transform.php';
require __DIR__ . '/content-type-specific-functions/person-transform.php';
require __DIR__ . '/content-type-specific-functions/opportunity-item-transform.php';

$scriptId = 'transform';

// should be run after fix-media-urls
$string = file_get_contents(__DIR__ . "/../output/output-transform/onr-urls-jsdom-images.json");
$pages = json_decode($string, TRUE);
logInfo($scriptId, "==============\nstarted " . date("Y/m/d"));

$articles = [];
$landingPages = [];
$people = [];
$events = [];
$opportunity = [];
$basicPages = [];

// Press Release Views are checked later
$views = [
  'https://www.onr.navy.mil/en/Science-Technology/ONR-Global/Leadership',
  'https://www.onr.navy.mil/en/Search',
  'https://www.onr.navy.mil/en/About-ONR/History/Timeline',
  'https://www.onr.navy.mil/en/Media-Center/futureforce/issues',
  'https://www.onr.navy.mil/en/Conference-Event-ONR/distinguished-lecture-series',
  'https://www.onr.navy.mil/en/our-research/technology-areas',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-31/All-Programs',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-32/all-programs',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-33/All-Programs',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-34/All-Programs',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-35/All-Programs',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-31/Code-31-Contacts',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-32/Code-32-Contacts',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-33/Code-33-Contacts',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-34/Code-34-Contacts',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-35/Code-35-Contacts'
];

$malformed = [
  'https://www.onr.navy.mil/en/_Apps/Download-Image',
  'https://www.onr.navy.mil/en/en/_Apps/Download-Image',
  'https://www.onr.navy.mil/en/_Apps/View-Contact',
  'https://www.onr.navy.mil/en/Contracts-Grants/manage-contract/contract-forms-download',
  'https://www.onr.navy.mil/en/Site-Map',
  'https://www.onr.navy.mil/en/Conference-Event-ONR/archived-events'
];

$landing_page = [
  'https://www.onr.navy.mil/en/About-ONR',
  'https://www.onr.navy.mil/en/About-ONR/Leadership',
  'https://www.onr.navy.mil/en/About-ONR/compliance-protections',
  'https://www.onr.navy.mil/en/career-job-opportunity',
  'https://www.onr.navy.mil/en/Conference-Event-ONR',
  'https://www.onr.navy.mil/en/Contracts-Grants',
  'https://www.onr.navy.mil/en/coop-accountability-recall',
  'https://www.onr.navy.mil/en/Education-Outreach',
  'https://www.onr.navy.mil/en/freedom-of-information-act-foia',
  'https://www.onr.navy.mil/en/Media-Center',
  'https://www.onr.navy.mil/en/our-research',
  'https://www.onr.navy.mil/en/Science-Technology',
  'https://www.onr.navy.mil/en/Science-Technology/Departments',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-31',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-32',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-33',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-34',
  'https://www.onr.navy.mil/en/Science-Technology/Departments/Code-35',
  'https://www.onr.navy.mil/en/Science-Technology/ONR-Global',
  'https://www.onr.navy.mil/en/work-with-us',
  'https://www.onr.navy.mil/en/work-with-us/how-to-apply/compliance-protections',
  'https://www.onr.navy.mil/en/work-with-us/careers',
  'https://www.onr.navy.mil/en/Contracts-Grants/manage-contract'
];

$patterns_ignore = [
  'https://www.onr.navy.mil/en/About-ONR/History/tales-of-discovery',
  'History/tales-of-discovery',
  'Conference-Event-ONR',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/requests-for-proposals',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/requests-for-quotes',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/requests-for-information',
  'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/special-notices',
];

$articles_views = [];
foreach ($pages as $key => $page) {
  $url = $page['url'];

  if (str_contains($url, 'Media-Center/Press-Releases') || str_contains($url, 'ONR-Global/Press-Releases')) {
    preg_match('/Media-Center\/Press-Releases\/\d*$/', $url, $press_release_views);
    if ($press_release_views) {
      $articles_views = array_merge($articles_views, getArticleFromView($page));
    }
  }
}

$messages = '';
foreach ($pages as $key => $page) {
  $url = $page['url'];

  if (in_array($url, $malformed)) {
    logInfo($scriptId, 'Did not import this page as it is blank or malformed: ' . $url);
    continue;
  }

  $skip = FALSE;
  foreach($patterns_ignore as $pattern) {
    if (str_contains($url, $pattern)) {
      logInfo($scriptId, 'Did not import this page as we are skipping for migration: ' . $url);
      $skip = TRUE;
      break;
    }
  }

  if ($skip) {
    continue;
  }

  if (in_array($url, $views)) {
    logInfo($scriptId, 'Did not import this page as it will be a view: ' . $url);
    continue;
  }

  if ($skip) {
    continue;
  }

  // Check for Press Releases and Press Release Views
  if (str_contains($url, 'Media-Center/Press-Releases') || str_contains($url, 'ONR-Global/Press-Releases')) {
    // Don't import pages we have identified as views
    preg_match('/Media-Center\/Press-Releases\/\d*$/', $url, $press_release_views);
    preg_match('/ONR-Global\/Press-Releases\/\d*$/', $url, $onr_press_release_views);
    if ($url === 'https://www.onr.navy.mil/en/Media-Center/Press-Releases' || $press_release_views) {
      $messages .= 'Did not import this page as it will be a view: ' . $url . PHP_EOL;
      logInfo($scriptId, 'Did not import this page as it will be a view: ' . $url);
      continue;
    }

    if ($url === 'https://www.onr.navy.mil/en/Science-Technology/ONR-Global/Press-Releases' || $onr_press_release_views) {
      logInfo($scriptId, 'Did not import this page as it will be a view: ' . $url);
      continue;
    }

    try {
      $article = transformArticles($page, $articles_views);
      $articles[] = $article;
    }
    catch (Exception $exception) {
      logInfo($scriptId, 'Failed to transform: ' . $url);
    }
    continue;
  }

  // Check for people
  if (str_contains($url, '/Science-Technology/ONR-Global/Leadership/') || str_contains($url, '/About-ONR/Leadership/')) {
    $people[] = transformPerson($page);
    continue;
  }
  // check for events
  elseif(preg_match('/Conference-Event-ONR\/distinguished-lecture-series\/\w*/', $url)) {
    $events[] = $url;
  }

  // Check for Landing Pages
  elseif (in_array($url, $landing_page)) {
    $landingPages[] = $url;
  }

  elseif ($url === 'https://www.onr.navy.mil/en/work-with-us/funding-opportunities/announcements') {
    $opportunity[] = transformOpportunityItem($page);
  }

  else {
    $basic = transformBasicPages($page);
    $basicPages[] = $basic;
  }
}

function fixUrl($jsons): array {
  for($i =0; $i < count($jsons); $i++) {
    if (isset($jsons[$i]['url'])) {
      $jsons[$i]['url'] = strtolower($jsons[$i]['url']);
      $jsons[$i]['url'] = str_replace('/en', '', $jsons[$i]['url']);
    }
  }
  return $jsons;
}

function writeoutput($type, $data, $scriptId = 'transform') {
  $filename = __DIR__ . "/../output/output-transform/$type.json";
  if (file_put_contents($filename,  json_encode($data, JSON_PRETTY_PRINT))) {
    logInfo($scriptId, "Total $type pages transformed: " . count($data));
  } else {
    logInfo($scriptId, "Oops! Error creating json file $filename");
  }
}

writeoutput('articles', fixUrl($articles));
writeoutput('people', fixUrl($people));
writeoutput('events', fixUrl($events));
writeoutput('basic', fixUrl($basicPages));
writeoutput('opportunity item', fixUrl($opportunity));

// Don't need a JSON File since ONR will be recreating all landing pages.
logInfo($scriptId, 'Total Landing Pages skipped: ' . count($landingPages));
