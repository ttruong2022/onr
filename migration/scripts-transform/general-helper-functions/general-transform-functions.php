<?php

require __DIR__ . '/../general-helper-functions/general-clean-body-functions.php';

function getCleanTitle($dom, $url): string {
  $tag = $dom->getElementsByTagName("h1");
  if ($tag->length > 0) {
    $title = $tag->item(0);
    $title = $title->nodeValue;
    $title = mb_ereg_replace("’","'", $title);
    $title = mb_ereg_replace("‘","'", $title);
    if ($title) {
      return strip_tags($title);
    } else {
      $title = strip_tags($url);
      return str_replace(array("\r", "\n", "\t"), '', $title);
    }
  } else {
    $title = strip_tags($url);
    return str_replace(array("\r", "\n", "\t"), '', $title);
  }
}

function removeEmptySpace($body): string {
  $body = preg_replace( '/\h+/', ' ',  $body);
  return preg_replace( '/\n \n/', ' ',  $body);
}

function removeDomNode($dom, $tag, $num) {
  $title = $dom->getElementsByTagName($tag);
  $count = 0;
  while ($title->length > 0) {
    $p = $title->item(0);
    $p->parentNode->removeChild($p);
    $count++;
    if ($count === $num) {
      break;
    }
  }
  return $dom;
}

function removeDomString($dom, $string) {
  domTextReplace('By ' . $string, '', $dom, FALSE);
  return $dom;
}

function cleanDomBody($dom) {
  $dom = removeDomNode($dom, 'h1', 1);
  $dom = removeElementsByTagName('style', $dom);
  $dom = removeIds($dom);
  $dom = removeGrids($dom);
  $dom = removeComments($dom);
  $dom = uswdsTable($dom);
  $dom = uswdsAccordion($dom);
  return $dom;
}

function cleanBody($body) {
  $body = removeEmptySpace($body);
  $body = str_replace("<?xml encoding=\"utf-8\" ?>", "", $body);
  $body = str_replace("<br /><br /><br />", "", $body);
  $body = str_replace("                                               ", "", $body);
  $body = str_replace("                    ", "", $body);
  $body = str_replace("<div></div>", "", $body);
  $body = str_replace("<div>&nbsp;</div>", "", $body);

  $body = str_replace("<br><br><br><br>", "", $body);

  $body = str_replace("<div>    <div class=\"clearfix\">  <div></div>", "", $body);

  $body = str_replace("<br><div>&nbsp;</div></div>  </div> </div>", "", $body);

  $body = str_replace("    <div class=\"clearfix\">\n <div>  ", "", $body);
  $body = str_replace("<div> \n <div class=\"clearfix\">\n ", "", $body);
  $body = str_replace("\n <h4></h4>\n<br>\n<br>\n", "", $body);
  $body = str_replace("<br><br>\n\n\n \n\n  \n\n \n\n   </div>    \n </div>  \n", "", $body);
  $body = str_replace("\n <h4></h4>\n<p></p>\n", "", $body);
  $body = str_replace("\n <h4></h4>\n<br><br>\n", "", $body);
  $body = str_replace("  <h4></h4>\n <p></p>\n ", "", $body);
  $body = str_replace("\n <p>\n</p><h4></h4>\n<p></p>\n", "", $body);
  $body = str_replace("\n <h4></h4>\n<br>\n<p></p>\n", "", $body);
  $body = str_replace("    <div class=\"clearfix\">\n <div>\n <h4></h4><br>\n ", "", $body);
  $body = str_replace("    <div class=\"clearfix\">\n <div>\n <h4></h4>\n<br>\n", "", $body);
  $body = str_replace("\n <h4></h4>\n <p></p>\n ", "", $body);
  $body = str_replace("  <h4><br><br></h4>\n <p></p>\n ", "", $body);
  $body = str_replace("<h4></h4>\n ", "", $body);
  $body = str_replace("\n <h4></h4>\n", "", $body);
  $body = str_replace("\n <p></p>\n<h4></h4>\n", "", $body);
  $body = str_replace("\n <h4> </h4>\n<p></p>\n", "", $body);
  $body = str_replace("    <div class=\"clearfix\">\n <div>\n <h4></h4><br>\n", "", $body);
  $body = str_replace("  <h4><br></h4>\n <p></p>\n ", "", $body);
  $body = str_replace("\n <h4> </h4>\n<p></p>\n", "", $body);
  $body = str_replace("\n <h4 style=\"background: white;\"></h4>\n<p></p>\n", "", $body);
  $body = str_replace("<h4></h4><br>\n ", "", $body);

  $body = str_replace(" </div>  </div>", "", $body);

  $body = str_replace("<strong></strong>", "", $body);
  $body = str_replace("\n <p></p>\n<p></p>\n", "", $body);
  $body = str_replace("<br>\n<br>\n<br>\n<br>\n", "", $body);
  $body = str_replace(" <p> </p>\n <p>--USN--</p>\n <p> </p>  \n ", "", $body);

  // Clean empty links
  $body = str_replace("<a href=\"/en\"></a>", "", $body);
  $body = str_replace("<a href=\"http:///www.onr.navy.mil/\"></a>", "", $body);
  $body = str_replace("<br>\n<div> </div>\n </div>    \n </div>  \n", "", $body);
  $body = str_replace("<span style=\"font-size: 14px; font-weight: bold; color: #000000;\"> </span></p>\n<p><span style=\"font-size: 14px; font-weight: bold; color: #000000;\">About </span></p>\nThe Department of the Navy&rsquo;s Office of Naval Research provides the science and technology necessary to maintain the Navy and Marine Corps&rsquo; technological advantage. Through its affiliates, ONR is a leader in science and technology with engagement in 50 states, 55 countries, 634 institutions of higher learning and nonprofit institutions, and more than 960 industry partners. ONR, through its commands, including headquarters, ONR Global and the Naval Research Laboratory in Washington, D.C., employs more than 3,800 people, comprising uniformed, civilian and contract personnel.", "", $body);
  $body = str_replace("<h4>About the Office of Naval Research </h4>\n<p>The Department of the Navy&rsquo;s Office of Naval Research provides the science and technology necessary to maintain the Navy and Marine Corps&rsquo; technological advantage. Through its affiliates, ONR is a leader in science and technology with engagement in 50 states, 55 countries, 634 institutions of higher learning and nonprofit institutions, and more than 960 industry partners. ONR, through its commands, including headquarters, ONR Global and the Naval Research Laboratory in Washington, D.C., employs more than 3,800 people, comprising uniformed, civilian and contract personnel.</p>\n </div>    \n </div>  \n", "", $body);

  $body = trim($body);
  return $body;
}

function fixBodyLinks($body) {
  preg_match_all('/<a\s[^>]*href=\"([^\"]*)\"[^>]*>(.*)<\/a>/siU', $body, $output_array);
  if (isset($output_array[1])) {
    foreach ($output_array[1] as $find) {
      $original = $find;
      if (str_starts_with($find, '/en/') || str_starts_with($find, 'https://www.onr.navy.mil/') || str_starts_with($find, 'http://www.onr.navy.mil/')) {
        $new = str_replace('/en/', '/', strtolower($find));
        $new = str_replace('https://www.onr.navy.mil', '', $new);
        $body = str_replace($original, $new, $body);
      }
    }
  }
  return $body;
}

// Functions copied from the web

function domTextReplace( $search, $replace, DOMNode &$domNode, $isRegEx = false ) {
  if ( $domNode->hasChildNodes() ) {
    $children = array();
    // since looping through a DOM being modified is a bad idea we prepare an array:
    foreach ( $domNode->childNodes as $child ) {
      $children[] = $child;
    }
    foreach ( $children as $child ) {
      if ( $child->nodeType === XML_TEXT_NODE ) {
        $oldText = $child->wholeText;
        if ( $isRegEx ) {
          $newText = preg_replace( $search, $replace, $oldText );
        } else {
          $newText = str_replace( $search, $replace, $oldText );
        }
        $newTextNode = $domNode->ownerDocument->createTextNode( $newText );
        $domNode->replaceChild( $newTextNode, $child );
      } else {
        domTextReplace( $search, $replace, $child, $isRegEx );
      }
    }
  }
}

function moveInner(DOMElement $from): DOMNode|bool|DOMElement {
  if (!$from->parentNode instanceof DOMElement) {
    echo 'DOMElement does not have a parent DOMElement node.';
    return FALSE;
  }

  /** @var DOMNode[] $children */
  $children = iterator_to_array($from->childNodes);
  foreach ($children as $child) {
    $from->parentNode->insertBefore($child, $from);
  }

  return $from->parentNode->removeChild($from);
}