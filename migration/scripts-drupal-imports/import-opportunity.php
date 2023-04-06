<?php

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;

require __DIR__ . '/DiffTracker.php';
require __DIR__ . '/ImportMedia.php';

class ImportOpportunity {

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function run() {
    $json = file_get_contents(dirname(__FILE__) . "/../output/output-transform/opportunity item.json");

    $this->tracker = new DiffTracker('opportunity-item');
    if ($json) {
      $items = json_decode($json, TRUE);
      $this->import($items[0]);
      $this->tracker->cleanupRemovedUrls();
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import($items) {
    $importMedia = new ImportMediaFiles();

    // Loop through all the article items
    foreach($items as $item) {

      if ($item['title'] === 'Default') {
        continue;
      }

      $file_ids = [];
      foreach($item['field_files'] as $file) {
        $id = $importMedia->getMediaIdByName($file);
        if ($id) {
          $file_ids[] = ['target_id' => $id];
        }
      }

      if ($item['title'] === 'FY22 Long Range Broad Agency Announcement (BAA) for Navy and Marine Corps Science and Technology N00014-22-S-B001') {
        $test = 0;
      }

      $full = "";
      if ($item['field_full_proposals'] !== "") {
        $full = date('Y-m-d\TH:i:s', $item['field_full_proposals']);
      }

      $white_papers = "";
      if ($item['field_white_papers'] !== "") {
        $white_papers = \DateTime::createFromFormat('Y-m-d\TH:i:s', date('Y-m-d\TH:i:s', intval($item['field_white_papers'])));
      }

      $fields = [
        'type'        => 'opportunities_item',
        'title'       => $item['title'],
        'body' => array(
          'value' => utf8_decode($item['body']),
          'summary' => $item['title'],
          'format' => 'full_html',
        ),
        'field_files' => !empty($file_ids) ? $file_ids : NULL,
        'field_full_proposals' =>  isset($item['field_full_proposals']) && $item['field_full_proposals'] !== "" ? date('Y-m-d\TH:i:s', $item['field_full_proposals']) : '',
        'field_white_papers' => isset($item['field_white_papers']) && $item['field_white_papers'] !== "" ? date('Y-m-d\TH:i:s', $item['field_white_papers']) : '',
        'field_funding_numbers' => $item['field_full_proposals'],
        'field_highlight' => $item['field_highlight'],
        'field_opportunity_type' => $item['field_opportunity_type'],
        'field_group' => ['target_id' => getGroup('https://www.onr.navy.mil/work-with-us/funding-opportunities/announcements'), 'group'],
        'published_at' => date($item['published']),
        'path' => [
          'pathauto' => PathautoState::CREATE,
        ],
        'status' => 1,
        'moderation_state' => 'published',
        'uid' => 1
      ];

      $tracked = $this->tracker->saveEntry($item['title'], $fields);

      if ($tracked['updated']) {
        echo 'Import successfully URL: ' . $item['title'] . PHP_EOL;
      }
    }
  }
}


$import = new ImportOpportunity();
try {
  $import->run();
} catch (EntityStorageException $e) {
  echo 'Error running article import';
}
