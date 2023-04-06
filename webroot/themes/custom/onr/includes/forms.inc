<?php

/**
 * @file
 */

/**
 * Hook_suggestions_form_alter().
 */
function onr_theme_suggestions_form_alter(array &$suggestions, array $variables) {
  $suggestions[] = 'form__' . str_replace('-', '_', $variables['element']['#id']);
}

/**
 * Hook_suggestions_form_alter().
 */
function onr_theme_suggestions_input_alter(array &$suggestions, array $variables) {
  if (isset($variables['element']['#attributes']['data-twig-suggestion']) &&
    (str_contains($variables['element']['#attributes']['data-twig-suggestion'], 'views-exposed-form-press-releases-page',) ||
      str_contains($variables['element']['#attributes']['data-twig-suggestion'], 'views-exposed-form-site-search-page-1',)) &&
    $variables['element']['#type'] === 'textfield') {
    $suggestions[] = 'input__textfield__onr_keyword';
  }
}
