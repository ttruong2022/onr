<?php

function removeIds($dom) {
  $divs = $dom->getElementsByTagName('div');
  foreach ($divs as $div) {
    $id = $div->getAttribute('id');
    if ($id) {
      $div->removeAttribute('id');
    }
  }
  return $dom;
}

function removeGrids($dom) {
  $divs = $dom->getElementsByTagName('div');
  foreach ($divs as $div) {
    $class = $div->getAttribute('class');
    if ($class && str_starts_with($class, 'grid')) {
      $div->removeAttribute('class');
    }
  }
  return $dom;
}

function removeComments($dom) {
  $xpath = new DOMXPath($dom);
  foreach ($xpath->query('//comment()') as $comment) {
    $comment->parentNode->removeChild($comment);
  }
  return $dom;
}

function uswdsTable($dom) {
  $tables = $dom->getElementsByTagName('table');
  foreach ($tables as $table) {
    $table->setAttribute('class', 'usa-table');
  }
  return $dom;
}

function uswdsAccordion($dom) {
  $xpath = new DOMXpath($dom);

  $elements = $xpath->query('//div | //h3 | //h2');
  $length = $elements->length;
  $accordionStart = FALSE;
  $count = 1;
  $accordion = '';
  for ($i = 0; $i < $length; $i++) {
    $element = $elements->item($i);

    $class = $element->getAttribute('class');
    if ($class === 'faq') {
      if (!$accordionStart) {
        $accordionStart = TRUE;
        $accordion = $dom->createElement("div");
        $accordion->setAttribute("class", "usa-accordion");
      }

      $aria = 'a' . $count;
      $nodeButton = $dom->createElement("button", $element->nodeValue);
      $nodeButton->setAttribute('class', 'usa-accordion__button');
      $nodeButton->setAttribute('aria-expanded', 'false');
      $nodeButton->setAttribute('aria-controls', $aria);

      $nodeHeader = $dom->createElement("h4");
      $nodeHeader->setAttribute('class', 'usa-accordion__heading');
      $nodeHeader->append($nodeButton);
      $accordion->append($nodeHeader);
      $element->parentNode->replaceChild($accordion, $element);
    }
    elseif ($class === 'answer') {
      $aria = 'a' . $count;
      $element->setAttribute('class', 'usa-accordion__content usa-prose');
      $element->removeAttribute('style');
      $element->setAttribute('hidden', 'hidden');
      $element->setAttribute('id', $aria);
      $accordion->append($element);
      $count++;
    }
    else {
      $accordionStart = FALSE;
      $accordion = '';
    }
  }

  return $dom;
}


// Copied from Internet

function removeElementsByTagName($tagName, $document) {
  $nodeList = $document->getElementsByTagName($tagName);
  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
    $node = $nodeList->item($nodeIdx);
    $node->parentNode->removeChild($node);
  }
  return $document;
}