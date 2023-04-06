<?php
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

require __DIR__ . '/helper-functions.php';

function getFileName($path) {
  return substr($path, strrpos($path, '/' ));
}

class ImportMediaFiles {
  function __construct() {
    $this->outputDir = dirname(__FILE__) . "/../output";
    $this->tmpTableName = 'import_media_tmp';
    $this->database = \Drupal::database();
    $this->createTempTable();

    $this->sample404Page = file_get_contents(dirname(__FILE__) . "/../samples/404.html");
  }

  public function run() {
    $json = file_get_contents($this->outputDir . "/output-extract/files.json");
    $files = json_decode($json, TRUE);

    $this->import($files);
    // $this->dropTempTable();
  }

  public function import($files) {

    $current_list = $this->getMediaList();
    // check each file url against our tmp table to see if we've imported it already;
    // if no, create drupal media entity and add record in tmp table
    foreach ($files as $url => $data) {
      $skip = FALSE;

      switch($url) {
        case str_contains($url, 'media/Images/News/'):
          echo 'Not importing Article Image' . PHP_EOL;
          $skip = TRUE;
          break;
        case str_contains($url, 'media/Files/Funding-Announcements/RFP/'):
          echo 'Not importing Request Proposal Files' . PHP_EOL;
          $skip = TRUE;
          break;
        case str_contains($url, 'media/Files/Funding-Announcements/RFQ/'):
          echo 'Not importing Request for Quotes Files' . PHP_EOL;
          $skip = TRUE;
          break;
        case str_contains($url, 'media/Files/Funding-Announcements/RFI/'):
          echo 'Not importing Request for Information Files' . PHP_EOL;
          $skip = TRUE;
          break;
        case str_contains($url, 'media/Files/Funding-Announcements/Special-Notice/'):
          echo 'Not importing Special Notiece Files' . PHP_EOL;
          $skip = TRUE;
          break;
        case str_contains($url, 'media/Files/Funding-Announcements/BAA/'):
          if (!str_contains($url, 'BAA/2022') && !str_contains($url, 'BAA/2021') && !str_contains($url, 'BAA/2020')) {
            echo 'Not importing Old Funding Announcement Files' . PHP_EOL;
            $skip = TRUE;
          }
          break;
        default:
          break;
      }

      if ($skip) {
        $this->mediaDelete($url);
        continue;
      }
      // handle cases where an image or document was linked, but is really a 404 html page
      if ($this->isMissing($data['dest'])) {
        echo '!!! linked file missing: ' . $data['dest'] . PHP_EOL;
        $this->mediaDelete($url);
        continue;
      }

      if ($this->fileAlreadyTracked($url)) {
        echo 'known file: ' . $data['dest'] . PHP_EOL;
        if (($key = array_search($url, $current_list)) !== false) {
          unset($current_list[$key]);
        }

        $id = $this->getMediaId($url);
        if ($id) {
          $media = Media::load($id);
          if ($media) {
            $media->set('uid', 1);
            $media->set('field_group', [
              'target_id' => getGroup(strtolower($data['pageURl'])),
              'group'
            ]);
            $media->save();
          }
        }
        continue;
      }

      echo 'new imported file: ' . $data['dest'] . PHP_EOL;
      $file = $this->saveDrupalFile($data['dest']);

      switch ($data['type']) {
        case 'image':
          $fields = $this->getImageFields($data, $file);
          break;
        default:
          $fields = $this->getDocumentFields($data, $file);
      }

      $media = Media::create($fields);
      $media->save();

      $this->trackFile($url, $media);
    }

    // Delete any remaining Media
    foreach($current_list as $item) {
      $this->mediaDelete($item);
    }
  }

  private function getImageFields($data, $file) {
    $alt = preg_replace('/\s+/', ' ', strip_tags($data['alt'] ?? ''));
    if (strlen($alt) > 511) {
      echo "!!! image alt too long, will be truncated: " . getFileName($data['dest']) . PHP_EOL .
        $alt . PHP_EOL . PHP_EOL;
    }
    return [
      'bundle'=> 'image',
      'name' => getFileName($data['dest']),
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => substr($alt, 0, 511),
        'title' => strip_tags($data['alt'] ?? getFileName($data['dest'])),
      ],
      'field_group' => [ 'target_id' => getGroup(strtolower($data['originalSrc'])), 'group'],
      'status' => 1,
      'moderation_state' => 'published',
      'uid' => 1
    ];
  }

  private function isMissing($path) {
    if (strpos($path, '.html') !== false) {
      $contents = file_get_contents($this->outputDir . "/files/$path");
      if ($contents === $this->sample404Page) {
        return TRUE;
      }
    }
    return FALSE;
  }


  private function getDocumentFields($data, $file) {
    return [
      'bundle'=> 'document',
      'name' => getFileName($data['dest']),
      'field_media_document' => [
        'target_id' => $file->id(),
      ],
      'field_group' => [ 'target_id' => getGroup(strtolower($data['originalSrc'])), 'group'],
      'status' => 1,
      'moderation_state' => 'published',
      'uid' => 1
    ];
  }

  public function createTempTable() {
    $table = $this->tmpTableName;
    $sql = <<<SQL
      create table if not exists `$table` (
        `media_id` int,
        `url` varchar(512) PRIMARY KEY
      )
    SQL;
    $this->database->query($sql);
  }

  public function dropTempTable() {
    $table = $this->tmpTableName;
    $this->database->query("DROP TABLE `$table`");
  }

  private function fileAlreadyTracked($url) {
    return $this->database->select($this->tmpTableName)
      ->condition('url', $url)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  private function trackFile($url, $media) {
    $this->database->insert($this->tmpTableName)
      ->fields([
        'url' => $url,
        'media_id' => $media->id(),
      ])
      ->execute();
  }

  public function getMediaList(): array {
    $result = $this->database->select($this->tmpTableName)
      ->fields($this->tmpTableName, ['url'])
      ->execute();
    $list = [];
    foreach ($result as $item) {
      if (isset($item->url)) {
        $list[] = $item->url;
      }
    }
    return $list;
  }

  public function getMediaId($url) {
    $result = $this->database->select($this->tmpTableName)
      ->condition('url', $url)
      ->fields($this->tmpTableName, ['media_id'])
      ->execute();
    $media_id = $result ? $result->fetchCol(0) : NULL;
    return $media_id[0] ?? NULL;
  }

  public function getMediaIdByName($url) {
    $result = $this->database->select($this->tmpTableName)
      ->condition('url', '%' . $url . '%', 'LIKE')
      ->fields($this->tmpTableName, ['media_id'])
      ->execute();
    $media_id = $result?->fetchCol(0);
    return $media_id[0] ?? NULL;
  }

  public function getDrupalUrl($mid): ?string {
    if ($mid === NULL) return NULL;
    $media = Media::load($mid);
    if (!$media) return NULL;
    $file = File::load(($media->field_media_image ?? $media->field_media_document)->target_id);
    if (!$file) return NULL;
    return $file->createFileUrl();
  }

  private function saveDrupalFile($filepath) {
    $file_data = file_get_contents($this->outputDir . "/files/$filepath");
    $directory = 'public://migration/';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $file = \Drupal::service('file.repository')->writeData($file_data, $directory . getFileName($filepath), FileSystemInterface::EXISTS_REPLACE);
    return $file;
  }

  private function mediaDelete($url) {
    if ($this->fileAlreadyTracked($url)) {
      $mid = $this->getMediaId($url);
      // remove old junk files, if they were imported previously
      $media = Media::load($mid);
      if ($media !== NULL) {
        if ($media->getRevisionUserId() === 1 || $media->getRevisionUserId() === 0) {
          $source = $media->getSource();
          $config = $source->getConfiguration();
          $field = $config['source_field'];

          $fid = $media->{$field}->target_id;
          if ($fid) {
            $file = File::load($fid);
            if ($file) {
              $file->delete();
              echo 'Deleted associated file with media ID: ' . $mid . PHP_EOL;
            }
          }
          $media->delete();
          $this->database->delete($this->tmpTableName)
            ->condition('url', $url)
            ->execute();
          echo 'Delete files with URL: ' . $url . PHP_EOL;
        }
      }
    }
  }
}
