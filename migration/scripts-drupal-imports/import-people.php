<?php

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;

require __DIR__ . '/ImportMedia.php';
require __DIR__ . '/DiffTracker.php';

class ImportPeople {

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function run() {
    $json = file_get_contents(dirname(__FILE__) . "/../output/output-transform/people.json");

    $this->tracker = new DiffTracker('people');
    if ($json) {
      $entries = json_decode($json, TRUE);
      $this->import($entries);
      $this->tracker->cleanupRemovedUrls();
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import($entries) {
    $importMedia = new ImportMediaFiles();

    foreach($entries as $item) {

      $photoId = $item['photo'] ? $importMedia->getMediaId($item['photo']['src']) : null;

      $fields = [
        'type'        => 'person',
        'title'       => $item['title'],
        'body' => array(
          'value' => utf8_decode($item['body']),
          'summary' => $item['metaDescription'],
          'format' => 'full_html',
        ),
        'field_group' => ['target_id' => getGroup($item['url']), 'group'],
        'field_metatags' => serialize([
          'description' => $item['metaDescription'],
          'keywords' => $item['metaKeywords'],
        ]),
        'field_person_type' => getTidByName($item['role'], 'person_group'),
        'field_person_roles' => $item['role'],
        'field_photo' => $photoId,
        'path' => [
          'alias' => str_replace('https://www.onr.navy.mil', '', $item['url']),
          'pathauto' => PathautoState::SKIP,
        ],
        'status' => 1,
        'moderation_state' => 'published',
        'uid' => 1
      ];

      $tracked = $this->tracker->saveEntry($item['url'], $fields);

      if ($tracked['updated']) {
        echo 'Import successfully URL: ' . $item['url'] . PHP_EOL;
      }
    }
  }
}


$import = new ImportPeople();
try {
  $import->run();
} catch (EntityStorageException $e) {
  echo 'Error running people import';
}
