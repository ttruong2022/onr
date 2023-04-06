<?php

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\redirect\Entity\Redirect;


$nids = \Drupal::entityQuery('node')
  ->condition('type', ['article', 'page', 'landing_page', 'person'], 'IN')
  ->condition('status', 1, '=')
  ->execute();
$nodes = Node::loadMultiple($nids);
foreach ($nodes as $node) {

  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());

  $redirectSrcUrl = 'en' . $alias;

  $redirect = \Drupal::service('redirect.repository')
    ->findMatchingRedirect($redirectSrcUrl);

  if (!$redirect) {
    try {
      Redirect::create([
        'redirect_source' => $redirectSrcUrl,
        'redirect_redirect' => 'internal:/node/' . $node->id(),
        'language' => 'und',
        'status_code' => '301',
      ])->save();
      echo "Created redirect for node id " . $node->id() . PHP_EOL;
    } catch (EntityStorageException $e) {
      echo "Error saving node id " . $node->id() . PHP_EOL;
    }
  }
  echo "Redirect for en already exists for node " . $node->id() . PHP_EOL;
}

