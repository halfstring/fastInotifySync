<?php

namespace fastInotifySync;

use fastInotifySync;

class Inotify {
    private $inotify   = NULL;
    private $watchDirs = array();
    private $wds       = array();

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

    public function run() {
        $timeLine = new TimeLine();
        swoole_event_add($this->inotify, function($inotify) use ($timeLine) {
            $events = inotify_read($this->inotify);
            if ($events) {
                $res = [];
                foreach ($events as $event) {
                    //var_dump($event);
                    if (!preg_match('/\.swp|\.swx|~|4913$/', $event['name'])) {
                        $file         = $this->_getDir($event['wd']) . '/' . $event['name'];
                        $res[ $file ] = \md5_file($file);
                    }
                }

                if (is_array($res)) {
                    foreach ($res as $key => $value) {
                        $timeLine->push(array(
                            'file'   => $key,
                            'finger' => $value
                        ));
                    }
                }
            }
        });
    }

    private function _getDir($wd) {
        if (is_array($this->watchDirs)) {
            foreach ($this->watchDirs as $item) {
                if ($wd == $item['wd']) {
                    return $item['path'];
                }
            }
        }

        return '';
    }
}
