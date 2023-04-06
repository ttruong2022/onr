<?php

function logInfo($type, $message) {
  echo $message . PHP_EOL;
  $file = __DIR__ . '/output/logs/' . $type . '.txt';
  if (!file_put_contents($file,  $message . PHP_EOL, FILE_APPEND | LOCK_EX)) {
    echo "could not write to info log $file";
  }
}

function logError($type, $message) {
  echo $message . PHP_EOL;
  $file = __DIR__ . '/output/logs/' . $type . '-error.txt';
  if (!file_put_contents($file,  $message . PHP_EOL, FILE_APPEND | LOCK_EX)) {
    echo "could not write to error log $file";
  }
}
