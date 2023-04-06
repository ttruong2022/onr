<?php

require_once __DIR__ . '/../general-helper-functions/general-transform-functions.php';

function transformOpportunityItem($json): array {
  $url = $json['url'];
  $opportunity_items = [];

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

  $items = getItems($dom);

  foreach($items as $item) {
    $skip = FALSE;
    $title = 'Default';
    $published = '';
    $full_proposals = '';
    $white_papers = '';
    $funding_number = '';
    $type = '';

    $divs = $item->getElementsByTagName('div');

    $top_classes = $item->getAttribute('class');
    if ($top_classes === 'contentBox notice') {
      $highlight = TRUE;
    }
    else {
      $highlight = FALSE;
    }

    foreach($divs as $div) {

      $class = $div->getAttribute('class');
      if ($class === 'contentBoxNote') {
        $text = $div->textContent;
        $dates = explode("|", $text);

        foreach($dates as $date) {
          switch($date) {
            case str_contains($date, 'Published'):
              $published = strtotime(strip_tags(str_replace(['Published:', ' local Eastern time '], '', $date)));
              $published = strtotime('+1day', $published);
              break;
            case str_contains($date, 'Full Proposals'):
              $full_proposals = strtotime(strip_tags(str_replace(['Full Proposals will be accepted until ', ' local Eastern time'], '', $date)));
              break;
            case str_contains($date, 'White Papers'):
              $white_papers = strtotime(strip_tags(str_replace(['White Papers due no later than ', ' local Eastern time'], '', $date)));
              break;
            default:
              break;
          }
        }
      }
      elseif($class === 'contentBoxInner') {
        $filenames = [];
        foreach($div->childNodes as $childNode) {
          if($childNode->nodeName == 'h3') {
            $title = strip_tags($childNode->textContent);
            if(!str_contains($title, '-22-') && !str_contains($title, '-21-') && !str_contains($title, '-20-')) {
              $skip = TRUE;
              break;
            }

            $funding_number = trim(substr($title, strrpos($title, ' '), strlen($title)));

            $character = substr($title, -4, 1);
            switch($character) {
              case 'B':
                $type = 'baa';
                break;
              case 'C':
                $type = 'foa';
                break;
              case 'F':
                $type = 'baac';
                break;
              default:
                break;
            }
          }
          elseif($childNode->nodeName == 'p') {
            if(str_contains($childNode->textContent, '(PDF - ') || str_contains($childNode->textContent, '(Excel Spreadsheet - ')) {
              $filenames[] = str_replace(' ', '-', trim(strtok($childNode->textContent, '(')));
            }
            else {
              $body = strip_tags($childNode->textContent);
            }
          }
        }
      }
    }
    if (!$skip) {
      $opportunity_items[] = [
        'title' => $title,
        'body' => $body,
        'field_files' => $filenames,
        'published' => $published,
        'field_full_proposals' => $full_proposals,
        'field_funding_numbers' => $funding_number,
        'field_highlight' => $highlight,
        'field_opportunity_type' => $type,
        'field_white_papers' => $white_papers,
      ];
    }
  }

  return $opportunity_items;
}


function getItems($dom) {
  $finder = new DomXPath($dom);
  $classname="contentBox";
  return $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
}
