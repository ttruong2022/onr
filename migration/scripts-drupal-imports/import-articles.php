<?php

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;

require __DIR__ . '/helper-functions.php';
require __DIR__ . '/DiffTracker.php';

class ImportArticles {

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function run() {
    $json = file_get_contents(dirname(__FILE__) . "/../output/output-transform/articles.json");

    $this->tracker = new DiffTracker('articles');
    if ($json) {
      $articles = json_decode($json, TRUE);
      $this->import($articles);
      $this->tracker->cleanupRemovedUrls();
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import($articles) {
    // Loop through all the article items
    foreach($articles as $article) {

      if (isset($article['year'])) {
        $year = getTidByName($article['year'], 'years');
      }

      $tags = [];
      if (!empty($article['tags'])) {
        $tmp = $article['tags'][0];
        if (is_array($tmp)) {
          foreach ($tmp as $tag) {
            $tags[] = ['target_id' => getTidByName($tag, 'tags')];
          }
        } else {
          $tags[] = ['target_id' => getTidByName($tmp, 'tags')];
        }
      }

      $fields = [
        'type'        => 'article',
        'title'       => $article['title'],
        'field_article_author' => $article['authored_by'],
        'body' => array(
          'value' => utf8_decode($article['body']),
          'summary' => $article['metaDescription'],
          'format' => 'full_html',
        ),
        'field_group' => ['target_id' => getGroup($article['url']), 'group'],
        'field_metatags' => serialize([
          'description' => $article['metaDescription'],
          'keywords' => $article['metaKeywords'],
        ]),
        'field_tags' => !empty($tags) ? $tags : NULL,
        'field_year' => !empty($year) ? $year : '',
        'published_at' => date($article['release_date']),
        'path' => [
          'pathauto' => PathautoState::CREATE,
        ],
        'status' => 1,
        'moderation_state' => 'published',
        'uid' => 1
      ];

      $tracked = $this->tracker->saveEntry($article['url'], $fields);

      if ($tracked['updated']) {
        echo 'Import successfully URL: ' . $article['url'] . PHP_EOL;
      }
      $node = $tracked['node'];

      $redirectSrcUrl = str_replace('https://www.onr.navy.mil/', '', $article['url']);

      $redirect = \Drupal::service('redirect.repository')->findMatchingRedirect($redirectSrcUrl);
      if (!$redirect) {
        Redirect::create([
          'redirect_source' => $redirectSrcUrl,
          'redirect_redirect' => 'internal:/node/' . $node->id(),
          'language' => 'und',
          'status_code' => '301',
        ])->save();
      }
    }
  }
}


$import = new ImportArticles();
try {
  $import->run();
} catch (EntityStorageException $e) {
  echo 'Error running article import';
}
