<?php

require_once __DIR__ . '/src/Inotify.php';
$config = require_once __DIR__ . '/config.php';

$inotify = new Inotify();
$inotify->addDirs($config['watchDirs'])->run();

