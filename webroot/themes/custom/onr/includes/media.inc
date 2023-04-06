<?php

/**
 * Prepares variables for media templates.
 *
 * Default template: media.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 *   - media: The media item.
 *   - name: The label for the media item.
 *   - view_mode: View mode; e.g., 'full', 'teaser', etc.
 *
 * @throws InvalidArgumentException
 */
function onr_preprocess_media(array &$variables) {
  $mid = $variables['media']->id();
  if ($mid) {
    $alias = \Drupal::service('path_alias.manager')
      ->getAliasByPath('/media/' . $mid);
    if ($alias) {
      $variables['alias'] = $alias;
    }
  }
}
