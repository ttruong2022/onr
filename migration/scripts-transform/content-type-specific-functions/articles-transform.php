<?php

require_once __DIR__ . '/../general-helper-functions/general-transform-functions.php';

function transformArticles($json, $articles_views): array {

  $url = $json['url'];
  $metaDescription = $json['metaDescription'];
  $metaKeywords = $json['metaKeywords'];

  $body = $json['body'];

  // Start Cleaning Body
  $dom = new DOMDocument();
  try {
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
  }
  catch (Exception $exception) {
    echo 'Failed to load body for: ' . $url;
    echo $exception->getMessage();
  }
  $title = trim(getCleanTitle($dom, $json['title']));

  // Article specific calls
  $dom = removeYellowInfoBoxes($dom);
  $dom = removeAboutOnr($dom);
  $dom = removeRightColumn($dom);
  $dom = cleanDomBody($dom);

  $releaseDate = getReleaseDate($title, $articles_views);
  $releaseDateString = getReleaseDateString($dom);
  $authoredBy = getAuthoredByInfo($dom);
  if ($authoredBy) {
    $dom = removeDomString($dom, $authoredBy);
  }
  $done = $dom->saveHTML();
  $body = str_replace($authoredBy, '', $done);
  $body = str_replace("&nbsp;", ' ', $body);
  $body = str_replace($releaseDateString, '', $body);
  $body = cleanBody($body);
  $body = fixBodyLinks($body);

  $body = removeImmediateReleseString($body);
  $year = getYearFromUrl($url);

  if (!$releaseDate) {
    $date =  '01-01-' . $year;
    $releaseDate = strtotime($date . '+1day');
  }

  $tags = [];
  if (str_contains($url, 'Science-Technology/ONR-Global')) {
    $tags[] = 'ONR Global';
  }

  return [
    'title' => $title,
    'url' => $url,
    'body' => $body,
    'year' => $year,
    'release_date' => $releaseDate,
    'authored_by' => $authoredBy,
    'tags' => $tags,
    'metaDescription' => $metaDescription,
    'metaKeywords' => $metaKeywords
  ];
}

// Article Content Type specific functions

function getReleaseDate($title, $articles_views): bool|int {
  foreach($articles_views as $page) {
    $page_title = mb_ereg_replace("’","'", $page['title']);
    $page_title = mb_ereg_replace("‘","'", $page_title);
    if (strtolower($page_title) === strtolower($title) && isset($page['release']) && $page['release'] !== "") {
      return strtotime($page['release'] . '+1day');
    }
  }
  return FALSE;
}

function getReleaseDateString($dom): string {
  $strong = $dom->getElementsByTagName("strong");
  if ($strong->length > 0 && (str_contains(strtolower($strong->item(0)->textContent), 'release') || str_contains(strtolower($strong->item(0)->textContent), 'media advisory'))) {
    return $strong->item(0)->textContent;
  }

  $h4 = $dom->getElementsByTagName("h4");
  if ($h4->length > 0 && (str_contains(strtolower($h4->item(0)->textContent), 'release') || str_contains(strtolower($h4->item(0)->textContent), 'media advisory'))) {
    return $h4->item(0)->textContent;
  }

  $b = $dom->getElementsByTagName("b");
  if ($b->length > 0 && (str_contains(strtolower($b->item(0)->textContent), 'release') || str_contains(strtolower($b->item(0)->textContent), 'media advisory'))) {
    return $b->item(0)->textContent;
  }

  $span = $dom->getElementsByTagName("span");
  if ($span->length > 0 && (str_contains(strtolower($span->item(0)->textContent), 'release') || str_contains(strtolower($span->item(0)->textContent), 'media advisory'))) {
    return $span->item(0)->textContent;
  }

  $p = $dom->getElementsByTagName("p");
  if ($p->length > 0 && (str_contains(strtolower($p->item(0)->textContent), 'release') || str_contains(strtolower($p->item(0)->textContent), 'media advisory'))) {
    return $p->item(0)->textContent;
  }

  return FALSE;
}

function getAuthoredByInfo($dom): array|string {

  // First I try and breakup by end of line.  Assuming the first line is the author
  $text = trim($dom->textContent);
  $lines = explode(PHP_EOL, $text);
  if (count($lines) > 1) {
    if (str_starts_with($lines[0], 'By') || str_starts_with($lines[0], 'Released by')) {
      if (str_contains($lines[0], 'ARLINGTON')) {
        return str_replace('By ', '', substr($lines[0], 0, strpos($lines[0], 'ARLINGTON')));
      } else {
        return str_replace('By ', '', $lines[0]);
      }
    } elseif(isset($lines[2]) && str_starts_with($lines[2], 'By')) {
      return str_replace('By ', '', $lines[2]);
    }
  }

  // Second I try breaking up \n character. I assume the first character is empty
  $lines = explode("\n", $text);
  if (count($lines) > 1) {
    if (str_starts_with($lines[1], 'By')) {
      return str_replace('By ', '', $lines[1]);
    }
  }

  // lastly I load all the p tags and check for "By" in the first
  $p = $dom->getElementsByTagName("p");
  if ($p->length > 0) {
    foreach ($p as $i) {
      if (str_starts_with($i->textContent, 'By')) {
        return str_replace('By ', '', $i->textContent);
      }
      break;
    }
  }
  return '';
}

function getYearFromUrl($url) {
  preg_match('/\/Media-Center\/Press-Releases\/(\d*)/', $url, $output_array);
  if (isset($output_array[1])) {
    return $output_array[1];
  }
  preg_match('/\/Science-Technology\/ONR-Global\/Press-Releases\/(\d*)/', $url, $output_array);
  if (isset($output_array[1])) {
    return $output_array[1];
  }
  return 0;
}

function removeYellowInfoBoxes($dom) {
  $finder = new DomXPath($dom);
  $classname="boxes clearfix";
  $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
  foreach ($nodes as $node) {
    $node->parentNode->removeChild($node);
  }
  return $dom;
}

function removeAboutOnr($dom) {
  $h3 = $dom->getElementsByTagName("h3");
  if ($h3->length > 0) {
    foreach ($h3 as $item) {
      if (str_contains(strtolower($item->textContent), strtolower('About the Office of Naval Research'))) {
        $aboutText = $item->nextElementSibling;
        $aboutText?->parentNode->removeChild($aboutText);
        $item->parentNode->removeChild($item);
        break;
      }
    }
    return $dom;
  }

  $h4 = $dom->getElementsByTagName("h4");
  if ($h4->length > 0) {
    foreach ($h4 as $item) {
      if (str_contains(strtolower($item->textContent), strtolower('About the Office of Naval Research'))) {
        $aboutText = $item->nextElementSibling;
        $aboutText?->parentNode->removeChild($aboutText);
        $item->parentNode->removeChild($item);
        break;
      }
    }
    return $dom;
  }

  $strong = $dom->getElementsByTagName("strong");
  if ($strong->length > 0) {
    foreach ($strong as $item) {
      if (str_contains(strtolower($item->textContent), strtolower('About the Office of Naval Research'))) {
        $aboutText = $item->nextElementSibling;
        $aboutText?->parentNode->removeChild($aboutText);
        $item->parentNode->removeChild($item);
        break;
      }
    }
    return $dom;
  }

  $p = $dom->getElementsByTagName("p");
  if ($p->length > 0) {
    foreach ($p as $item) {
      if (str_starts_with(strtolower($item->textContent), strtolower('About the Office of Naval Research'))) {
        $aboutText = $item->nextElementSibling;
        $aboutText?->parentNode->removeChild($aboutText);
        $item->parentNode->removeChild($item);
        break;
      }
    }
    return $dom;
  }
  return $dom;
}

function removeRightColumn($dom) {
  $divs = $dom->getElementsByTagName("div");
  if ($divs->length > 0) {
    foreach ($divs as $div) {
      $class = $div->getAttribute('class');
      if ($class === 'grid_4 omega') {
        $div->parentNode->removeChild($div);
      }
    }
  }
  return $dom;
}

function getArticleFromView($json): array {
  $body = $json['body'];

  // Start Cleaning Body
  $dom = new DOMDocument();
  try {
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
  }
  catch (Exception $exception) {
    echo 'Failed to load body for: ' . $url;
    echo $exception->getMessage();
  }

  $list = $dom->getElementsByTagName("li");
  $item = [];
  $items = [];
  if ($list->length > 0) {
    foreach ($list as $li) {
      foreach($li->getElementsByTagName('h3') as $h3) {
        $item['title'] = $h3->textContent;
        break;
      }
      foreach($li->getElementsByTagName('p') as $p) {
        $item['release'] = str_replace('Released: ', '', $p->textContent);
        break;
      }
      $items[] = $item;
    }
  }

  return $items;
}

function removeImmediateReleseString($body) {
  $body = str_replace("\n <h4>For immediate release: Nov. 7, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("\n <p>\n<strong>For Immediate Release: March 3, 2011</strong> </p>\n<p></p>\n", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Oct. 27, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Nov. 4, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Nov. 14, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Sept. 29, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("<p>\n <strong>For Immediate Release: March 10, 2011</strong>\n </p>\n ", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Dec. 2, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("<p>\n <strong>For Immediate Release: March 2, 2011<br></strong>\n </p>\n ", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Nov. 9, 2011</h4>\n <p></p>\n ", '', $body);
  $body = str_replace("<p>FOR IMMEDIATE RELEASE: March 8, 2010<br>By: Tammy White, ONR Corporate Communications</p>\n ", '', $body);
  $body = str_replace("\n <p>FOR IMMEDIATE RELEASE: March 4, 2010<br>\n</p>\n", '', $body);
  $body = str_replace("  <p></p>\n <p>\n <strong>FOR IMMEDIATE RELEASE: June 2, 2010</strong> </p>\n ", '', $body);
  $body = str_replace("<p>\n <strong>\n <em>For Immediate Release: March 25, 2010 </em>\n </strong>\n </p>\n ", '', $body);
  $body = str_replace("FOR IMMEDIATE RELEASE: Sept. 24<br><br>", '', $body);
  $body = str_replace("<p>\n <strong>FOR IMMEDIATE RELEASE: Nov. 8, 2010 </strong> </p>\n ", '', $body);
  $body = str_replace("<p>\n <strong>\n <em>For Immediate Release: January 22, 2010</em> </strong>\n </p>\n ", '', $body);
  $body = str_replace("<p>\n <strong>\n <em>For immediate release: Feb. 24, 2010</em> </strong>\n </p>\n ", '', $body);
  $body = str_replace("<p>\n <strong>\n <em>For Immediate Release: Mar. 23, 2010</em> </strong>\n </p>\n ", '', $body);
  $body = str_replace("<p>FOR IMMEDIATE RELEASE<br>Dec. 7, 2009</p>\n ", '', $body);
  $body = str_replace("\n <p><strong>For Immediate Release: Feb. 18, 2021<br>\n</strong></p>\n<p></p>\n", '', $body);
  $body = str_replace("<strong>For Immediate Release: Jan. 13, 2021<br>\n</strong><br>\n<br>\n<br>\n", '', $body);
  $body = str_replace("\n <p><strong>For Immediate Release: Feb. 24, 2021<br>\n</strong></p>\n<p></p>\n", '', $body);
  $body = str_replace("\n <p><strong>For Immediate Release: April 8, 2021<br>\n</strong></p>\n<p></p>\n", '', $body);
  $body = str_replace("\n <p><strong>For Immediate Release: Jan. 28, 2021<br>\n</strong></p>\n<p></p>\n", '', $body);
  $body = str_replace("<strong>For Immediate Release: Nov. 12, 2020<br>\n</strong><br>\n<br>\n<br>\n", '', $body);
  $body = str_replace("<strong>For Immediate Release: Dec. 22, 2020<br>\n</strong><br>\n<br>\n<br>\n", '', $body);
  $body = str_replace("\n <p><strong>For Immediate Release: June 4, 2015</strong></p>\n<p></p>\n", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Dec. 10, 2014<\/h4>\n<p></p>\n", '', $body);
  $body = str_replace("\n <h4>FOR IMMEDIATE RELEASE: Aug. 14, 2013</h4>\n<p></p>\n", '', $body);
  $body = str_replace("\n <p><strong>For Immediate Release: June 6, 2013<br>\n</strong></p>\n", '', $body);
  $body = str_replace("\n <h4>FOR IMMEDIATE RELEASE: Oct. 24, 2012 </h4>\n<br>\n", '', $body);
  $body = str_replace("\n <h4><b>For Immediate Release:</b> Dec. 13, 2012</h4>\n<p></p>\n", '', $body);
  $body = str_replace("\n <h4>For Immediate Release: Dec. 10, 2014</h4>\n<p></p>\n", '', $body);

  return $body;
}