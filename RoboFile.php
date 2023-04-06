<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

use Robo\Collection\CollectionBuilder;
use Robo\Task\Base\ExecStack;
use Robo\Tasks;

/**
 * Robo Tasks.
 */
class RoboFile extends Tasks {

  /**
   * The path to custom modules.
   *
   * @var string
   */
  const CUSTOM_MODULES = __DIR__ . '/webroot/modules/custom';

  /**
   * The path to custom themes.
   *
   * @var string
   */
  const CUSTOM_THEMES = __DIR__ . '/webroot/themes/custom';

  /**
   * New Project init.
   */
  public function projectInit() {
    $LOCAL_MYSQL_USER = getenv('DRUPAL_DB_USER');
    $LOCAL_MYSQL_PASSWORD = getenv('DRUPAL_DB_PASS');
    $LOCAL_MYSQL_DATABASE = getenv('DRUPAL_DB_NAME');
    $LOCAL_MYSQL_PORT = getenv('DRUPAL_DB_PORT');
    $LOCAL_MYSQL_HOST = getenv('DRUPAL_DB_HOST');
    $LOCAL_CONFIG_DIR = getenv('DRUPAL_CONFIG_DIR');

    $this->say("Initializing new project...");
    $collection = $this->collectionBuilder();
    $collection->taskComposerInstall()
      ->ignorePlatformRequirements()
      ->noInteraction()
      ->taskExec("drush si minimal -y --account-name=admin --account-pass=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL --db-url=mysql://$LOCAL_MYSQL_USER:$LOCAL_MYSQL_PASSWORD@$LOCAL_MYSQL_HOST:$LOCAL_MYSQL_PORT/$LOCAL_MYSQL_DATABASE")
      ->taskExec("cat ./config/default/system.site.yml | grep uuid | tail -c +7 | head -c 36 | vendor/bin/drush config-set -y system.site uuid - ;")
      ->taskExec('drush cim -y || drush cim -y')
      ->taskExec('drush cim -y')
      ->taskExec('drush cr');
      # ->taskExec($this->fixPerms());
    $this->say("New project initialized.");

    return $collection;
  }

  /**
   * Local Site install.
   */
//  public function localInstall() {
//    $LOCAL_MYSQL_USER = getenv('MYSQL_USER');
//    $LOCAL_MYSQL_PASSWORD = getenv('MYSQL_PASSWORD');
//    $LOCAL_MYSQL_DATABASE = getenv('MYSQL_DATABASE');
//    $LOCAL_MYSQL_PORT = getenv('MYSQL_PORT');
//
//    $this->say("Local site installation started...");
//    $collection = $this->collectionBuilder();
//    $collection->taskComposerInstall()->ignorePlatformRequirements()->noInteraction()
//      ->taskExec("drush si --account-name=admin --account-pass=admin --config-dir=/app/config --db-url=mysql://$LOCAL_MYSQL_USER:$LOCAL_MYSQL_PASSWORD@database:$LOCAL_MYSQL_PORT/$LOCAL_MYSQL_DATABASE -y")
//      ->taskExec('drush cim -y')
//      ->addTask($this->buildTheme())
//      ->taskExec('drush cr');
//    $this->say("Local site install completed.");
//
//    return $collection;
//  }

  /**
   * Local Site update.
   */
  public function localUpdate() {
    $this->say("Local site update starting...");
    $collection = $this->collectionBuilder();

    $collection->taskComposerInstall();

    if (file_exists(__DIR__ . "/git-info.txt")) {
      $collection->addTask($this->environmentTag());
    }

    $collection->taskExec('drush state:set system.maintenance_mode 1 -y')
      ->taskExec('drush updatedb --no-cache-clear -y')
      ->taskExec('drush cim -y || drush cim -y')
      ->taskExec('drush cim -y')
      ->taskExec('drush php-eval "node_access_rebuild();" -y')
      ->addTask($this->buildTheme())
      ->taskExec('drush cr')
      ->taskExec('drush simple-sitemap:generate')
      ->taskExec('drush search-api-clear')
      ->taskExec('drush search-api-index')
      ->taskExec('drush state:set system.maintenance_mode 0 -y')
      ->taskExec('drush cr');
    $this->say("local site Update Completed.");
    return $collection;
  }

  /**
   * Build theme.
   *
   * @param string $dir
   *  The directory to run the commands.
   *
   * @return CollectionBuilder
   */
  public function buildTheme(string $dir = "") {
    if ($dir === "") {
      $dir = self::CUSTOM_THEMES . '/onr';
    }
    $collection = $this->collectionBuilder();
    $collection->progressMessage('Building the theme...')
      ->taskNpmInstall()->dir($dir)
      ->taskExec('cd ' . $dir . ' && npm rebuild node-sass')
      ->taskGulpRun('default')->dir($dir);

    return $collection;
  }

  /**
   * Watch theme.
   */
  public function watchTheme() {
    $this->buildTheme();
    $this->taskGulpRun('watch')->dir(self::CUSTOM_THEMES . '/onr')->run();
  }

  /**
   * Update Styles.
   */
  public function updateStyles() {
    $this->taskGulpRun('sass')->dir(self::CUSTOM_THEMES . '/onr')->run();
    $this->taskExec('drush cc css-js')->run();
  }

  /**
   * Lint.
   */
  public function lint() {
    $this->say("parallel-lint checking custom modules and themes...");
    $this->taskExec('vendor/bin/parallel-lint -e php,module,inc,install,test,profile,theme')
      ->arg(self::CUSTOM_MODULES)
      ->arg(self::CUSTOM_THEMES)
      ->printOutput(TRUE)
      ->run();
    $this->say("parallel-lint finished.");
    $this->taskGulpRun('lint')->dir(self::CUSTOM_THEMES . '/onr')->run();
  }

  /**
   * Runs Codesniffer.
   */
  public function phpcs() {
    $this->say("php code sniffer (drupalStandards) started...");
    $task = $this->taskExec('vendor/bin/phpcs -s');
    if (file_exists(__DIR__ . '/.phpcs.xml')) {
      $task->arg('--standard=' . __DIR__ . '/.phpcs.xml');
    }
    elseif (file_exists(__DIR__ . '/phpcs.xml')) {
      $task->arg('--standard=' . __DIR__ . '/phpcs.xml');
    }
    elseif (file_exists(__DIR__ . '/.phpcs.xml.dist')) {
      $task->arg('--standard=' . __DIR__ . '/.phpcs.xml.dist');
    }
    elseif (file_exists(__DIR__ . '/phpcs.xml.dist')) {
      $task->arg('--standard=' . __DIR__ . '/phpcs.xml.dist');
    }
    else {
      // Default settings if no project or developer settings are found.
      $task->arg('--standard=Drupal,DrupalPractice')
        ->arg('--extensions=php,module,inc,install,test,profile,theme,info')
        ->arg('--ignore=*/node_modules/*,*/vendor/*');
    }
    $result = $task->arg(self::CUSTOM_MODULES)
      ->arg(self::CUSTOM_THEMES)
      ->printOutput(TRUE)
      ->run();
    $message = $result->wasSuccessful() ? 'No Drupal standards violations found :)' : 'Drupal standards violations found :( Please review the code.';
    $this->say("php code sniffer finished: " . $message);
  }

  /**
   * Runs phpstan.
   */
  public function analyze() {
    $this->say("Running Static Code Analysis...");
    $result = $this->taskExec('vendor/bin/phpstan')
      ->arg('analyse')
      ->printOutput(TRUE)
      ->run();
    $this->say("Complete.");
    return $result->getExitCode();
  }

  /**
   * Records phpstan baseline.
   */
  public function analyzeBaseline() {

    if (file_exists('/app/phpstan-baseline.neon')) {
      $continue = $this->confirm("This will update an existing baseline, are you sure?", FALSE);
    }
    else {
      $continue = TRUE;
    }

    if ($continue) {
      $this->say("Establishing Static Code Analysis Baseline...");
      $result = $this->taskExec('vendor/bin/phpstan')
        ->arg('analyse')
        ->arg('--generate-baseline')
        ->printOutput(TRUE)
        ->run();

      if ($result->wasSuccessful()) {
        $this->io()->success('Ensure that phpstan-baseline.neon is added to the includes section of phpstan.neon or phpstan.neon.dist configuration file.');
      }
    }
    $this->say("Complete.");
  }

  /**
   * Runs Beautifier.
   */
  public function codefix() {
    $this->say("PHP Code Beautifier (drupalStandards) started...");
    $this->taskExec('vendor/bin/phpcbf')
      ->arg('--standard=Drupal')
      ->arg('--extensions=php,module,inc,install,test,profile,theme,info')
      ->arg(self::CUSTOM_MODULES)
      ->arg(self::CUSTOM_THEMES)
      ->printOutput(TRUE)
      ->run();
    $this->say("PHP Code Beautifier finished.");
  }

  /**
   * Fixes files permissions.
   *
   * @return CollectionBuilder|ExecStack
   *   Exec chown and chmod.
   */
  public function fixPerms() {
    $this->say("Verifying filesystem permissions...");
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('chown $(id -u) ./')
      ->exec('chmod u=rwx,g=rwxs,o=rx ./')
      ->exec('find ./ -not -path "webroot/sites/default/files*" -exec chown $(id -u) {} \;')
      ->exec('find ./ -not -path "webroot/sites/default/files*" -exec chmod u=rwX,g=rwX,o=rX {} \;')
      ->exec('find ./ -type d -not -path "webroot/sites/default/files*" -exec chmod g+s {} \;')
      ->exec('chmod -R u=rwx,g=rwxs,o=rwx ./webroot/sites/default/files');
  }

  /**
   * Set/Unset maintenance mode.
   *
   * @param int $status
   *
   * @return CollectionBuilder|ExecStack
   */
  public function maintenanceMode(int $status) {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec("drush state:set system.maintenance_mode $status")
      ->exec("drush cr");
  }

  /**
   * If using environment indicator set git tag
   *
   * @return CollectionBuilder|ExecStack
   */
  public function environmentTag(): ExecStack|CollectionBuilder {
    $value = '';

    $file = file_get_contents(__DIR__ . "/git-info.txt");

    $lines=explode("\n",$file);

    foreach($lines as $line) {
      if(str_contains($line, 'BRANCH')) {
        $parts = explode("=", $line);
        if (isset($parts[1])) {
          $value .= $parts[1];
        }
      }
      if(str_contains($line, 'TAG')) {
        $parts = explode("=", $line);
        if (isset($parts[1]) && $value !== '') {
          $value .= ' ' . $parts[1] . '-';
        }
        else {
          $value .= $parts[1];
        }
      }
      if(str_contains($line, 'COMMITS_AHEAD')) {
        $parts = explode("=", $line);
        if (isset($parts[1])) {
          $value .= $parts[1] . '-';
        }
      }
      if(str_contains($line, 'COMMIT_ID')) {
        $parts = explode("=", $line);
        if (isset($parts[1])) {
          $value .= '-' . $parts[1];
        }
      }
    }

    return $this->taskExecStack()
      ->exec('drush sset environment_indicator.current_release "' .  $value . '"');
  }
}
