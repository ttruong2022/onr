<?php

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;

require __DIR__ . '/helper-functions.php';
require __DIR__ . '/DiffTracker.php';

class ImportBasicPages {

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function run() {
    $json = file_get_contents(dirname(__FILE__) . "/../output/output-transform/basic.json");

    $this->tracker = new DiffTracker('basic');
    if ($json) {
      $basicPages = json_decode($json, TRUE);
      $this->import($basicPages);
      $this->tracker->cleanupRemovedUrls();
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import($basicPages) {

    // Loop through all the basic page items
    foreach($basicPages as $basic) {

      $tags = [];
      if (!empty($basic['tags'])) {
        $tmp = $basic['tags'][0];
        if (is_array($tmp)) {
          foreach ($tmp as $tag) {
            $tags[] = ['target_id' => getTidByNameLoop($tag, 'tags')];
          }
        }
      }

      $fields = [
        'type'        => 'page',
        'title'       => $basic['title'],
        'body' => array(
          'value' => utf8_decode($basic['body']),
          'summary' => $basic['metaDescription'],
          'format' => 'full_html',
        ),
        'field_group' => [ 'target_id' => getGroup($basic['url']), 'group'],
        'field_metatags' => serialize([
          'description' => $basic['metaDescription'],
          'keywords' => $basic['metaKeywords'],
        ]),
        'field_tags' => $tags,
        'path' => [
          'alias' => str_replace('https://www.onr.navy.mil', '', $basic['url']),
          'pathauto' => PathautoState::SKIP,
        ],
        'status' => 1,
        'moderation_state' => 'published',
        'uid' => 1
      ];

      $tracked = $this->tracker->saveEntry($basic['url'], $fields, $basic['contact']);

      if ($tracked['updated']) {
        echo 'Import successfully URL: ' . $basic['url'] . PHP_EOL;
      }
    }
  }
}


$import = new ImportBasicPages();
try {
  $import->run();
} catch (EntityStorageException $e) {
  echo 'Error running basic import';
}
