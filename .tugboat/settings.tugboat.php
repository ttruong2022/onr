<?php
$databases['default']['default'] = array (
  'database' => 'tugboat',
  'username' => 'tugboat',
  'password' => 'tugboat',
  'prefix' => '',
  'host' => 'mysql',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
// Use the TUGBOAT_REPO_ID to generate a hash salt for Tugboat sites.
$settings['hash_salt'] = hash('sha256', getenv('TUGBOAT_REPO_ID'));

if (PHP_SAPI === 'cli') {
  ini_set('memory_limit', '-1');
}

/**
 * Trusted host configuration.
 *
 * See full description in default.settings.php.
 */
$settings['trusted_host_patterns'] = [
  '^.+$',
];
