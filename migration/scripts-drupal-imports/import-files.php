<?php

require __DIR__ . '/ImportMedia.php';

// should be run after read-urls
$import = new ImportMediaFiles();
$import->run();
