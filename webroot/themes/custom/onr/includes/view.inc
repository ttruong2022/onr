<?php

/**
 * @file
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_render().
 */
function onr_views_pre_render(ViewExecutable $view) {
  if ($view->storage->id() == 'press_releases') {
    $view->element['#attached']['library'][] = 'onr/news_releases';
  }
  elseif ($view->storage->id() == 'site_search') {
    $view->element['#attached']['library'][] = 'onr/search';
  }
}
