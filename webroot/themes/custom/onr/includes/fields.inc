<?php

/**
 * @file
 */

/**
 * Implement hook_preprocess_field().
 */
function onr_preprocess_field(&$variables) {
  $element = $variables['element'];
  if (isset($element['#field_name'])) {
    if ($element['#field_name'] == 'field_media_document') {
      if ($element['#view_mode'] === 'download') {
        foreach ($variables['items'] as &$item) {
          $item['content']['#title'] = 'Download';
          $item['content']['#options']['attributes']['class'][] = 'usa-button';
        }
      }
    }
  }
}

function onr_preprocess_baguettebox_formatter(&$variables) {
  if (isset($variables['image'])) {
    unset($variables['image']['#width']);
    unset($variables['image']['#height']);
  }
}
