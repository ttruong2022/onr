#!/usr/bin/php
<?php

$sitemap = "https://www.onr.navy.mil/sitemap.xml";
if ( !empty($argc) && $argc > 1 ) {
	$sitemap = $argv[1];
}

$sm = file_get_contents($sitemap);
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->preserveWhiteSpace = false;
$doc->loadXML($sm);
$query = '//ns:urlset/ns:url/ns:loc';
$xpath = new DOMXPath($doc);
$xpath->registerNamespace("ns","http://www.sitemaps.org/schemas/sitemap/0.9");
$entries = $xpath->query($query);

echo "    urls:\n";
for( $i=0; $i<$entries->length; $i++ ) {
	$url = $entries->item($i)->nodeValue;
    $path=parse_url($url,PHP_URL_PATH);
    echo "      - $path\n";
}
