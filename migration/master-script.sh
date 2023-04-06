#!/bin/bash

DIRECTORY=$(dirname "$0")

# Extract Phase
node "$DIRECTORY"/scripts-extract/read-sitemap.js
node "$DIRECTORY"/scripts-extract/read-urls.js
php "$DIRECTORY"/scripts-extract/getCounts.php

# Drupal Import Phase
drush -d scr "$DIRECTORY"/scripts-drupal-imports/import-files.php
drush -d scr "$DIRECTORY"/scripts-transform/fix-media-urls.php

# Transform Phase
php "$DIRECTORY"/scripts-transform/read-extract-json.php

# Drupal Import Content types
drush -d scr "$DIRECTORY"/scripts-drupal-imports/import-articles.php
drush -d scr "$DIRECTORY"/scripts-drupal-imports/import-basic-pages.php
drush -d scr "$DIRECTORY"/scripts-drupal-imports/import-people.php
drush -d scr "$DIRECTORY"/scripts-drupal-imports/import-opportunity.php
drush -d scr "$DIRECTORY"/scripts-drupal-imports/import-menu-links.php


# Current Order scripts should be ran
# 1. scripts-extract/read-sitemap.js = read the live sitemap for url list
# 2. scripts-extract/read-urls.js = read the site
# 3. scripts-extract/getCounts.php = Get counts from extracted json
# 4. scripts-drupal-imports/import-files.php = Creates media objects in Drupal
# 5. scripts-transform/fix-media-urls.php = updates json with drupal links
# 6. scripts-transform/read-extract-json.php = get jsons ready for importing into Drupal
# 7. scripts-drupal-imports/import-articles.php = create articles
# 8. scripts-drupal-imports/import-people.php = create people pages
