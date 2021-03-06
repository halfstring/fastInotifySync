<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$config  = require(dirname(__DIR__) . '/configs/main.php');
$inotify = new \fastInotifySync\Inotify();
$inotify->addDirs($config['watchDirs'])->run();



$serv = new Swoole\Server("0.0.0.0", 1027);
$serv->set(array(
    'worker_num' => 8,   //工作进程数量
    'daemonize'  => FALSE, //是否作为守护进程
));
$serv->on('connect', function($serv, $fd) {
    echo "Client:Connect.\n";
});
$serv->on('receive', function($serv, $fd, $from_id, $data) {
    $serv->send($fd, 'Swoole: ' . $data);
    $serv->close($fd);
});
$serv->on('close', function($serv, $fd) {
    echo "Client: Close.\n";
});
$serv->start();
