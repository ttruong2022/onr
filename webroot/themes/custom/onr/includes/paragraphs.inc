<?php

/**
 * @file
 */

/**
 * Implements template_preprocess_paragraph().
 **/
function onr_preprocess_paragraph(&$variables) {
  $elements = $variables['elements'];
  $paragraph = $elements['#paragraph'];
  if (isset($paragraph)) {
    $paragraph_bundle = $paragraph->getParagraphType()->id();
    if ($paragraph_bundle == 'a11y_paragraphs_tabs_wrapper') {
      $variables['#attached']['library'][] = 'onr/tabs';
    }
  }
}
