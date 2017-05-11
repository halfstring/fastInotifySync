<?php
namespace fastInotifySync;


Class Sync {

    protected $nodeName;
    protected $servers;
    protected $serv;

    public function __construct($nodeName) {
        $this->servers  = dirname(__DIR__) . '/configs/servers.php';
        $this->nodeName = $nodeName;

        $nodeServerConfig = $this->servers[ $this->nodeName ];

        $this->serv = new Swoole\Server($nodeServerConfig['host'], $nodeServerConfig['port']);
    }

    public function setNodeName($nodeName) {
        $this->nodeName = $nodeName;
        return $this;
    }

    private function _servers($node = FALSE) {
        $this->servers = dirname(__DIR__) . '/configs/servers.php';

        if ($node) {
            return $this->servers[ $this->nodeName ];
        }
    }
}
