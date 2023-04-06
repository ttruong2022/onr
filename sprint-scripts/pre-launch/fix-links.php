<?php

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

$nids = \Drupal::entityQuery('node')->execute();
$nodes =  Node::loadMultiple($nids);

foreach($nodes as $node) {
  if ($node->hasField('body')) {
    $body = $node->get('body')->getValue();
    if (isset($body[0]) && isset($body[0]['value'])) {
      preg_match_all('/<a.*? href="([^"]+)".*?>/', $body[0]['value'], $matches);
      $body_new = $body[0]['value'];
      $updated = FALSE;

      if (isset($matches[1]) && is_array($matches[1])) {
        foreach ($matches[1] as $match) {
          if (str_starts_with($match, '/sites/default/files/')) {
            $original_value = 'href="' . $match . '"';

            $url = str_replace('/sites/default/files/', 'public://', $match);
            $query = \Drupal::database()->select('file_managed', 'fm');
            $query->fields('fm', ['fid']);
            $query->condition('uri', $url);
            $result = $query->execute()->fetchCol();

            if ($result && is_array($result)) {
              $fid = $result[0];
              $file = \Drupal\file\Entity\File::load($fid);

              $references = file_get_file_references($file);

              if ($references) {
                $media = reset(reset($references)['media']);
                $mid = $media->id();
                $uuid = $media->uuid();

                if ($mid && $uuid) {
                  $linkit_string = 'data-entity-substitution="canonical"';
                  $linkit_string .= ' data-entity-type="media"';
                  $linkit_string .= ' data-entity-uuid="' . $uuid . '"';
                  $linkit_string .= ' href="/media/' . $mid . '"';

                  $body_new = str_replace($original_value, $linkit_string, $body_new);
                  $updated = TRUE;
                }
              }
            }
          }

          if ((str_starts_with($match, 'http://www.onr.navy.mil') || str_starts_with($match, 'https://www.onr.navy.mil')) &&
          ($match !== 'https://www.onr.navy.mil' || $match !== 'http://www.onr.navy.mil')) {
//            $body_new = str_replace('http://www.onr.navy.mil', '', $body_new);
//            $body_new = str_replace('https://www.onr.navy.mil', '', $body_new);
            $body_new = str_replace('.aspx', '', $body_new);
            $updated = TRUE;
          }
        }
      }
      if ($updated) {
        $node->body->value = $body_new;
        try {
          $node->save();
          echo 'Saved node ' . $node->id() . PHP_EOL;
        } catch (EntityStorageException $e) {
          echo 'Error saving node ' . $node->id() . PHP_EOL;
        }
      }
    }
  }
}