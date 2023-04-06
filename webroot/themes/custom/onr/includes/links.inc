<?php

/**
 * @file
 */

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK().
 */
function onr_preprocess_media_entity_download_link(array &$variables): void {
  $node = \Drupal::routeMatch()->getParameter('node');

  if ($node) {
    $typeName = $node->bundle();
    if ($typeName === 'person') {
      $variables['link']['#title'] = Markup::create(t('Download High Resolution') . "<div class='usa-sr-only'>" . $variables['link']['#title'] . "</div>");
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function onr_preprocess_file_link(&$variables) {
  if ($variables['link'] && $variables['link']['#url'] instanceof Url) {
    // Update title to include screen-reader div
    $variables['link']['#title'] = Markup::create($variables['link']['#title'] . "<div class='usa-sr-only'>Opens in New tab</div>");

    // Update the attribute to open in a new tab
    $options = $variables['link']['#url']->getOptions('attributes');
    $options['attributes'] += ['target' => '_blank'];

    // Links that open in a new tab are vulnerable to a crafty exploit known as
    // reverse tabnabbing. Thankfully it’s easily prevented by setting rel="noopener" on the link.
    $options['attributes'] += ['rel' => 'noopener'];
    $variables['link']['#url']->setOptions($options);
  }
}
