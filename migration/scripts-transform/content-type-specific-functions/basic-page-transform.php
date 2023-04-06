<?php

require_once __DIR__ . '/../general-helper-functions/general-transform-functions.php';

function transformBasicPages($json): array {
  $url = $json['url'];
  $contact = [];
  $metaDescription = '';
  if (isset($json['metaDescription'])) {
    $metaDescription = $json['metaDescription'];
  }

  if ($json['metaDescription'] === 'The Office of Naval Research provides resources for award recipients to manage their contracts.') {
    $tst = 0;
  }

  $metaKeywords = '';
  if (isset($json['metaKeywords'])) {
    $metaKeywords = $json['metaKeywords'];
  }

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

  if (preg_match('/\/science-technology\/departments\/code-\d\d\/all-programs\/.*/', strtolower($url))) {
    $contact[] = getContactBlock($dom);
    // Removes How to Submit + program contact headers
    $dom = removeByClass($dom, 'boxtop');
    // Removes How to Submit + program contact blocks
    $dom = removeByClass($dom, 'boxContact clearfix');
    $dom = removeByClass($dom, 'clear break');
    $dom = removeById($dom, 'phcontent_0_phcolumn02_0_fundHr');
  }

  // Clean body
  $dom = cleanDomBody($dom);

  $done = $dom->saveHTML();
  $body = cleanBody($done);
  $body = fixBodyLinks($body);

  $tags = [];
  switch(true) {
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-31':
      $tags[] = 'Code-31';
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-31/All-Programs/311-Mathematics-Computers-Research':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-31/All-Programs/312-Electronics-Sensors':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-31/All-Programs/313-applications-transitions':
    $tags[] = ['Code 31', 'Programs'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-31\/All-Programs\/311-Mathematics-Computers-Research\/\w*/', $url):
      $tags[] = ['Code 31', 'Division-311', 'Programs'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-31\/All-Programs\/312-Electronics-Sensors\/\w*/', $url):
      $tags[] = ['Code 31', 'Division-312', 'Programs'];
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-32':
      $tags[] = 'Code 32';
      break;
    case preg_match('/Science-Technology\/Departments\/Code-32\/all-programs\/\w*/', $url):
      $tags[] = ['Code 32'];
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-33':
      $tags[] = 'Code 33';
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-33/All-Programs/331-advanced-naval-platforms':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-33/All-Programs/332-naval-materials':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-33/All-Programs/333-weapons-and-payloads':
      $tags[] = ['Code 33', 'Program'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-33\/All-Programs\/331-advanced-naval-platforms\/\w*/', $url):
      $tags[] = ['Code 33', 'Division-331', 'Programs'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-33\/All-Programs\/332-naval-materials\/\w*/', $url):
      $tags[] = ['Code 33', 'Division-332', 'Programs'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-33\/All-Programs\/333-weapons-and-payloads\/\w*/', $url):
      $tags[] = ['Code 33', 'Division-333', 'Programs'];
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-34':
      $tags[] = 'Code 34';
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-34/All-Programs/human-bioengineered-systems-341':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-34/All-Programs/warfighter-protection-applications-342':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-34/All-Programs/research-protections-343':
      $tags[] = ['Code 34', 'Program'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-34\/All-Programs\/human-bioengineered-systems-341\/\w*/', $url):
      $tags[] = ['Code 34', 'Division-341', 'Programs'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-34\/All-Programs\/warfighter-protection-applications-342\/\w*/', $url):
      $tags[] = ['Code 34', 'Division-342', 'Programs'];
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-35':
      $tags[] = 'Code 35';
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-35/All-Programs/aerospace-science-research-351':
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-35/All-Programs/air-warfare-and-naval-applications-352':
      $tags[] = ['Code 35', 'Program'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-35\/All-Programs\/aerospace-science-research-351\/\w*/', $url):
      $tags[] = ['Code 35', 'Division-351', 'Programs'];
      break;
    case preg_match('/Science-Technology\/Departments\/Code-35\/All-Programs\/air-warfare-and-naval-applications-352\/\w*/', $url):
      $tags[] = ['Code 35', 'Division-352', 'Programs'];
      break;
    case $url === 'https://www.onr.navy.mil/Science-Technology/Departments/Code-36':
      $tags[] = 'Code 36';
      break;
    case preg_match('/Science-Technology\/ONR-Global\/\w*/', $url):
      $tags[] = 'ONR Global';
      break;
    default:
      break;
  }

  return [
    'title' => $title,
    'url' => $url,
    'body' => $body,
    'tags' => $tags,
    'metaDescription' => $metaDescription,
    'metaKeywords' => $metaKeywords,
    'contact' => $contact
  ];
}

function removeByClass($dom, $string) {
  $finder = new DomXPath($dom);
  $classname = $string;
  $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
  foreach ($nodes as $node) {
    $node->parentNode->removeChild($node);
  }
  return $dom;
}

function removeById($dom, $string) {
  $finder = new DomXPath($dom);
  $id = $string;
  $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@id), ' '), ' $id ')]");
  foreach ($nodes as $node) {
    $node->parentNode->removeChild($node);
  }
  return $dom;
}

function getContactBlock($dom): array {
  $divs = $dom->getElementsByTagName("div");
  $contacts = [];
  if ($divs->length > 0) {
    foreach ($divs as $nodes) {
      $class = $nodes->getAttribute('class');
      if ($class === 'grid_4 alpha') {
        $contact = [];
        foreach($nodes->childNodes as $child) {
          $text = $child->textContent;
          switch($text) {
            case str_contains($text, 'Name:'):
              $str = str_replace('Name:', '', $text);
              $contact['name'] = trim(preg_replace('/(\v|\s)+/', ' ', $str));
              break;
            case str_contains($text, 'Title:'):
              $contact['title'] = trim(str_replace('Title:', '', $text));
              break;
            case str_contains($text, 'Department:'):
              $contact['department'] = trim(str_replace('Department:', '', $text));
              break;
            case str_contains($text, 'Email for Questions:'):
              $contact['email'] = trim(str_replace('Email for Questions:', '', $text));
              break;
            default:
              break;
          }
        }
        $contacts[] = $contact;
      }
    }

  }
  return $contacts;
}