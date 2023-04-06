mkdir -p ./saas/services/drupal/bin
cp /usr/local/bin/redis-cli ./saas/services/drupal/bin/redis-cli
composer install
./saas/scripts/motd.sh
vendor/bin/robo build:theme
vendor/bin/blt artifact:deploy \
  --environment ci \
  --commit-msg "Test build $APP_VERSION" \
  --tag "$APP_VERSION" \
  --ignore-dirty \
  --no-interaction \
  --verbose
cat saas/services/base/config/motd