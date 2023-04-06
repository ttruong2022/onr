<?php

use Drupal\block_content\Entity\BlockContent;
use Drupal\file\Entity\File;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;


class DiffTracker {

  function __construct($type) {
    $this->outputDir = dirname(__FILE__) . "/../output";
    $this->tmpTableName = 'import_track_diff';
    $this->database = \Drupal::database();
    $this->createTempTable();
    $this->type = $type;

    $this->tracked = [];
  }

  public function createTempTable() {
    $table = $this->tmpTableName;
    $sql = <<<SQL
      create table if not exists `$table` (
        `nid` int unsigned,
        `url` varchar(512) PRIMARY KEY,
        `type` varchar(512),
        `data` TEXT
      )
    SQL;
    $this->database->query($sql);
  }

  public function dropTempTable() {
    $table = $this->tmpTableName;
    $this->database->query("DROP TABLE `$table`");
  }

  public function saveEntry($url, $data, $contactInfo = NULL) {
    $serialized = serialize($data);
    $nid = $this->getNodeId($url);

    $isNew = !$nid;
    $updated = FALSE;

    if (preg_match('/\/science-technology\/departments\/code-\d\d\/all-programs\/.*/', strtolower($url)) && isset($contactInfo[0])) {
      $block_content = $contactInfo[0];
      $all_blocks = BlockContent::loadMultiple();
      $program_blocks = [];
      if (count($block_content) > 1) {
        $test = 0;
      }
      foreach($block_content as $item) {
        $program_block = '';
        if (isset($item['name'])) {
          foreach ($all_blocks as $block) {
            $str = $block->get('info')->getValue();
            if (isset($str) && isset($str[0]) && isset($str[0]['value'])) {
              $name = $str[0]['value'];
              if ($name === $item['name']) {
                $program_block = $block;
                break;
              }
            }
          }
        }

        if ($program_block === '' && isset($item['name'])) {
          $program_block = BlockContent::create([
            'type' => 'contact_block',
            'info' => $item['name'],
            'field_department_list' => ['target_id' => getTidByNameLoop($item['department'], 'departments')],
            'field_email' => $item['email'],
            'field_program_contact' => TRUE,
            'field_title' => $item['title'],
            'field_name' => $item['name'],
          ]);

          echo 'Created block with name: ' . $item['name'] . PHP_EOL;
          $program_block->save();
        }
        else {
          if ($program_block !== '') {
            $program_block->set('field_department_list',
              ['target_id' => getTidByNameLoop($item['department'], 'departments')]);
            $program_block->save();
            echo 'Updating block with name: ' . $item['name'] . PHP_EOL;
          }
        }
        $program_blocks[] = $program_block;
      }
    }

    if ($isNew) {
      $node = Node::create($data);
      $node->save();

      if (preg_match('/\/science-technology\/departments\/code-\d\d\/all-programs\/.*/', $url)) {
        $configuration = [
          'breakpoints' => [
            'desktop' => 'blb_col_9_3',
            'mobile' => 'blb_col_12',
            'tablet' => 'blb_col_9_3',
          ],
          'layout_regions_classes' => [
            'blb_region_col_1' =>
              [
                0 => 'desktop:grid-col-9',
                1 => 'grid-col-12',
                2 => 'tablet:grid-col-9',
              ],
            'blb_region_col_2' =>
              [
                0 => 'desktop:grid-col-3',
                1 => 'grid-col-12',
                2 => 'tablet:grid-col-3',
              ],
          ],
        ];

        $section[0] = new Section('bootstrap_layout_builder:blb_col_2', $configuration, []);
        $pluginConfiguration = [
          'id' => 'field_block:node:page:body',
          'provider' => 'layout_builder',
          'label_display' => FALSE,
          'view_mode' => 'default',
          'entity' => $node->id(),
          'context_mapping' => [
            'entity' => 'layout_builder.entity',
          ],
          'formatter' => [
            'label' => 'hidden',
          ],
        ];
        $component = new SectionComponent(\Drupal::service('uuid')->generate(), 'blb_region_col_1', $pluginConfiguration);
        $section[0]->appendComponent($component);

        if (!empty($program_blocks)) {
          $submit_block = BlockContent::load(20);
          $block_plugin_id = 'block_content:' . $submit_block->uuid();
          $pluginConfiguration = [
            'id' => $block_plugin_id,
            'provider' => 'layout_builder',
            'label' => $submit_block->get('info')->getValue()[0]['value'],
            'label_display' => 'visible',
          ];
          $component = new SectionComponent(\Drupal::service('uuid')->generate(), 'blb_region_col_2', $pluginConfiguration);

          // Add the component to the section.
          $section[0]->appendComponent($component);

          $section[1] = new Section('bootstrap_layout_builder:blb_col_1');

          // Create a new section component using the node and plugin config.
          foreach($program_blocks as $block) {
            $program_block = BlockContent::load($block->id());
            $plugin_id = 'block_content:' . $program_block->uuid();
            $component = new SectionComponent(\Drupal::service('uuid')->generate(), 'blb_region_col_1', ['id' => $plugin_id]);
            $section[1]->appendComponent($component);
          }

        }
        $node->layout_builder__layout->setValue($section);
        $node->save();
      }

      $nid = $node->id();
      $this->track($url, $serialized, $nid, $isNew);
      return [
        'nid' => $nid,
        'new' => $isNew,
        'updated' => TRUE,
        'node' => $node,
      ];
    }

    $node = Node::load($nid);
    $oldFields = $this->getStale($url);

    $changed = arrayRecursiveDiff($oldFields, $data);

    if (empty($changed)) {
      echo "skipping identical: $url" . PHP_EOL;
      $this->tracked[] = $url;
      return [
        'nid' => $nid,
        'new' => $isNew,
        'updated' => FALSE,
        'node' => $node,
      ];
    }

    if ($node !== NULL) {
      if ($node->getRevisionUserId() != 1) {
        echo "skipping update, edited by a user: $url" . PHP_EOL;
        $this->tracked[] = $url;
        return [
          'nid' => $nid,
          'new' => $isNew,
          'updated' => FALSE,
          'node' => $node,
        ];
      }
    }

    // todo script is not updating
    foreach ($changed as $field => $values) {
      if (is_array($values)) {
        $node->{$field} = [
          $values[$field]
        ];
      }
      else {
        $node->{$field} = $values;
      }
    }
    $node->save();

    $this->track($url, $serialized, $nid, $isNew);
    return ['nid' => $nid, 'new' => $isNew, 'updated' => TRUE, 'node' => $node];
  }

  private function track($url, $data, $nid, $isNew) {
    $this->tracked[] = $url;

    if ($isNew) {
      $this->database->insert($this->tmpTableName)
        ->fields([
          'nid' => $nid,
          'url' => $url,
          'data' => $data,
          'type' => $this->type,
        ])
        ->execute();
    }
    else {
      $this->database->update($this->tmpTableName)
        ->fields([
          'nid' => $nid,
          'data' => $data,
        ])
        ->condition('url', $url)
        ->execute();
    }
  }

  private function getStale($url) {
    $result = $this->database->select($this->tmpTableName)
      ->condition('url', $url)
      ->condition('type', $this->type)
      ->fields($this->tmpTableName, ['data'])
      ->execute();
    $media_id = $result ? $result->fetchCol(0) : NULL;
    if ($media_id[0] ?? NULL) {
      return unserialize($media_id[0]);
    }
    return [];
  }

  public function getNodeId($url) {
    $result = $this->database->select($this->tmpTableName)
      ->condition('url', $url)
      ->condition('type', $this->type)
      ->fields($this->tmpTableName, ['nid'])
      ->execute();
    $media_id = $result ? $result->fetchCol(0) : NULL;
    return $media_id[0] ?? NULL;
  }

  public function cleanupRemovedUrls() {
    $knownUrls = $this->database->select($this->tmpTableName)
      ->fields($this->tmpTableName, ['url', 'nid'])
      ->condition('type', $this->type)
      ->execute()
      ->fetchAllKeyed();

    $deleted = array_diff(array_keys($knownUrls), $this->tracked);
    foreach ($deleted as $url) {
      $node = Node::load($knownUrls[$url]);

      if ($node && $node->getRevisionUserId() != 1) {
        echo "skipping deletion, edited by a user: $url" . PHP_EOL;
        continue;
      }

      echo 'Removing node for deleted url: ' . $url . PHP_EOL;
      $node && $node->delete();
      $this->database->delete($this->tmpTableName)
        ->condition('url', $url)
        ->execute();
    }
  }

}


function arrayRecursiveDiff($arr1, $arr2, $parent_value = '') {
  $diff = [];
  foreach ($arr1 as $key => $value) {
    if (array_key_exists($key, $arr2)) {
      if (is_array($value)) {
        $recursiveDiff = arrayRecursiveDiff($value, $arr2[$key], $key);
        if (count($recursiveDiff)) {
          $diff[$key] = $recursiveDiff;
        }
      }
      elseif ($value != $arr2[$key]) {
        if ($parent_value !== "") {
          $diff[$parent_value] = $arr2;
        }
        else {
          $diff[$key] = $arr2[$key];
        }
      }
    }
    else {
      $diff[$key] = $value;
    }
  }
  return $diff;
}
