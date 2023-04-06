<?php

$manager = \Drupal::entityDefinitionUpdateManager();
if ($field = $manager->getFieldStorageDefinition('field_image', 'node')) {
  $manager->uninstallFieldStorageDefinition($field);
}