<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$config = require('./config.php');
$inotify = new \fastInotifySync\Inotify();
$inotify->addDirs($config['watchDirs'])->run();
