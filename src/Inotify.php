<?php

class Inotify {
    private $path;
    private $inotify   = NULL;
    private $watchDirs = array();
    private $wds       = array();
    private $callbacks = array();

    public function __construct() {
        $this->inotify = inotify_init();
    }

    public function getFd() {
        return $this->inotify;
    }

    public function addDir($dir, $mask = IN_MODIFY | IN_CREATE | IN_DELETE, $r = FALSE) {
        $key = md5($dir);
        if (!isset($this->watchDirs[ $key ])) {
            $wd = inotify_add_watch($this->inotify, $dir, $mask);

            $this->wds[ $wd ]        = $dir;
            $this->watchDirs[ $key ] = array(
                'wd'   => $wd,
                'path' => $dir,
                'mask' => $mask,
            );
        }

        return $this;
    }

    public function addDirs($dirs) {
        foreach ($dirs as $dir) {
            $this->addDir($dir);
        }
        return $this;
    }

    public function removeDir($dir) {
        $key = md5($dir);
        if (isset($this->watchDirs[ $key ])) {
            $wd = $this->watchDirs[ $key ]['wd'];
            if (inotify_rm_watch($this->inotify, $wd)) {
                unset($this->watchDirs[ $key ]);
            }
        }

        return $this;
    }

    public function registerCallback($callback, $params = array()) {
        $key = md5(var_export($callback, TRUE));
        if (!isset($this->callbacks[ $key ])) {
            $this->callbacks[ $key ] = array(
                'func'   => $callback,
                'params' => $params
            );
        }

        return $this;
    }

    public function run() {
        while (TRUE) {
            echo microtime(), "\n";
            $events = inotify_read($this->inotify);
            if ($events) {
                var_dump($events);
                foreach ($events as $event) {
                    //$filepath = $this->wds[ $event['wd'] ] . '/' . $event['name'];
                    //echo $filepath, "<---filepath=---\n";
                    //echo md5_file($filepath), "<---filepath--md5=---\n";
                    //echo "inotify Event :" . var_export($event, 1) . "\n";
                }
            }
            echo "\n=====\n";
        }
    }
}
