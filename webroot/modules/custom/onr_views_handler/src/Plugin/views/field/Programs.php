<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\NodeTypeFlagger
 */

namespace Drupal\onr_views_handler\Plugin\views\field;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("programs")
 */
class Programs extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   *
   * @throws \Drupal\Core\Database\InvalidQueryException|\Drupal\Core\Entity\EntityMalformedException|\Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function render(ResultRow $values) {
    $block = $this->getEntity($values);
    $id = $block->id();

    $database = \Drupal::database();
    $query = $database->select('entity_usage', 'eu')
      ->fields('nfd', ['title', 'nid'])
      ->condition('target_type', 'block_content')
      ->condition('method', 'layout_builder')
      ->condition('target_id', $id);
    $query->join('node_field_data', 'nfd', 'nfd.nid = eu.source_id');
    $result = $query->execute();

    $output = '';
    foreach ($result as $record) {
      $node = Node::load($record->nid);

      if ($node) {
        $url = $node->toUrl()->toString();
        $this->options['alter']['path'] = $url;
      }

      if ($record->title) {
        return $this->sanitizeValue($record->title);
      }
    }
    unset($this->options['alter']['path']);
    return 'NA';
  }

}
