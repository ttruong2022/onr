<?php

/**
 * @file
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 * For getting funding numbers into the page-title
 * for opportunities-item content type
 */
function onr_theme_suggestions_page_title_alter(array &$suggestions, array &$variables) {
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    $node_type = $node->getType();
    // Suggestion must use _ and __ as per convention.
    $suggestions[] = 'page_title__' . $node_type;
  }
}

/**
 *
 */
function onr_preprocess_page_title__opportunities_item(&$variables) {
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if ($node->get('field_funding_numbers') && !$node->get('field_funding_numbers')->isEmpty()) {
      $variables['funding_numbers'] = $node->get('field_funding_numbers')->get(0)->get('value')->getValue();
    }
  }
}
