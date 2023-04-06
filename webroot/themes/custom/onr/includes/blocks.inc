<?php

/**
 * @file
 */

use Drupal\block_content\BlockContentInterface;

/**
 * Implements theme_preprocess_block().
 */
function onr_preprocess_block(&$variables) {
  // Remove page title block on 'article' content type.
  if (isset($variables['elements']['#id'])) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($variables['elements']['#id'] == 'onr_page_title') {
      if ($node && in_array($node->getType(), ['landing_page', 'person'])) {
        $variables['content'] = [];
      }
    }
    elseif ($variables['elements']['#id'] == 'onr_breadcrumbs') {
      $current_uri = \Drupal::request()->getRequestUri();
      $parts = explode('/', $current_uri);
      if ($node && $node->getType() === 'landing_page' && is_array($parts) && count($parts) <= 2) {
        $variables['content'] = [];
      }
    }
  }

  $content = $variables['content'];
  if (isset($content['#block_content']) && $content['#block_content'] instanceof BlockContentInterface) {
    if ($content['#block_content']->bundle() == 'basic') {
      $variables['#attached']['library'][] = 'onr/basic-block';
    }
  }

  if (isset($variables['plugin_id'])) {
    if ($variables['plugin_id'] === 'views_block:division_program_blocks-block_1') {
      $variables['#attached']['library'][] = 'onr/division-block';
    }
  }

  if (isset($variables['plugin_id']) && $variables['plugin_id'] === 'menu_block:timeline-sub-menu') {
    $variables['#attached']['library'][] = 'onr/timeline';
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter() for form templates.
 *
 * @param array $suggestions
 * @param array $variables
 */
function onr_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  // Block suggestions for custom block bundles.
  if (isset($variables['elements']['content']['#block_content'])) {
    array_splice($suggestions, 1, 0, 'block__bundle__' . $variables['elements']['content']['#block_content']->bundle());
  }
}
