<?php

require_once __DIR__ . '/../general-helper-functions/general-transform-functions.php';

function transformPerson($json): array {
  $url = $json['url'];
  $metaDescription = $json['metaDescription'] ?? '';
  $metaKeywords = $json['metaKeywords'] ?? '';

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

  $title = getPersonName($dom, $json['title']);

  $photo = null;
  foreach ($json['files'] as $file) {
    if (str_contains($file['src'], 'high-res')) {
      $photo = $file;
      break;
    }
  }
  if (!$photo) {
    $photo = $json['files'][0] ?? null;
  }

  $role = getPersonJob($dom, $url);

  // Clean body
  $dom = cleanDomBody($dom);
  // remove photo from body
  $dom = removeDomNode($dom, 'drupal-media', 1);
  $dom = removeDomNode($dom, 'div', 1);

  $done = $dom->saveHTML();
  $body = cleanBody($done);
  $body = fixBodyLinks($body);

  return [
    'title' => $title,
    'url' => $url,
    'body' => $body,
    'role' => $role,
    'photo' => $photo,
    'metaDescription' => $metaDescription,
    'metaKeywords' => $metaKeywords
  ];
}

function getPersonName($dom, $url): string {
  $tag = $dom->getElementsByTagName("h1");
  if ($tag->length > 0) {
    $title = $tag->item(0)->firstChild ?? null;
    $title = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $title->nodeValue ?? '');
    if ($title) {
      return strip_tags($title);
    }
  }
  $title = strip_tags($url);
  return str_replace(array("\r", "\n", "\t"), '', $title);
}


function getPersonJob($dom, $url): string {
  $tag = $dom->getElementsByTagName("h1");
  if ($tag->length > 0) {
    $title = $tag->item(0)->lastChild ?? null;
    $title = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $title->nodeValue ?? '');
    if ($title) {
      return strip_tags($title);
    }
  }
  $parts = explode('/', $url);
  $title = end($parts);
  return str_replace(array('-'), ' ', $title);
}
