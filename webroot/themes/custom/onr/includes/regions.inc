<?php

/**
 * @file
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 *
 * Provide region based menu suggestions.
 */
function onr_theme_suggestions_region_alter(&$suggestions, array $variables) {
  $is_front = \Drupal::service('path.matcher')->isFrontPage();
  if ($is_front && isset($suggestions[0]) && $suggestions[0] === 'region__hero') {
    $suggestions[] = 'region__hero_front';
  }
}
