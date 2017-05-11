<?php

namespace fastInotifySync;

class TimeLine {

    private   $db;
    protected $config;
    protected $nodeName;
    protected $start;
    protected $end;
    private   $queue;
    private   $batch = NULL;

    function __construct() {
        $this->config = require dirname(__DIR__) . '/configs/main.php';
        $this->db     = dirname(__DIR__) . '/db';
        if (!is_dir($this->db)) {
            mkdir($this->db);
        }

        $this->queue = new \LevelDB($this->db, $this->config['db']['options'], $this->config['db']['r'], $this->config['db']['w']);
    }

    public function push($data) {
        $this->batch = new \LevelDBWriteBatch();
        $this->_moveNode($data['file'], array(
            'mask'   => 0,
            'finger' => $data['finger']
        ));
        $this->queue->write($this->batch);
        $this->batch = NULL;
        return $this;
    }

    /**
     * 消费时间轴(该方法使用前, 先调用$this->setNodeName('xxx') 设置客户端标志)
     *
     * @return mixed
     * @author Kalep ( kalepsong@gmail.com )
     */
    public function pop() {
        if ($this->nodeName) {
            $point       = $this->_nodeIndex();
            $this->batch = new \LevelDBWriteBatch();
            $node        = $this->queue->get($point);
            $this->_nodeIndex($node['pre']);
            $this->batch = NULL;
            return $node;
        }
    }

    /**
     * 设置客户端标志
     *
     * @param $nodeName
     *
     * @return $this
     * @author Kalep ( kalepsong@gmail.com )
     */
    public function setNodeName($nodeName) {
        $this->nodeName = $nodeName;
        return $this;
    }


    /**
     * 文件修改时间轴变更
     *
     * @param $file
     * @param $nodeInfo
     *
     * @return bool
     * @author Kalep ( kalepsong@gmail.com )
     */
    private function _moveNode($file, $nodeInfo) {
        $start = $this->_startIndex();

        $md5File = md5($file);

        $nodeInfo['pre']  = '';
        $nodeInfo['next'] = '';
        if (!$start) {
            $this->_node($md5File, $nodeInfo);
            $this->_startIndex($md5File);
            $this->_endIndex($md5File);
            return TRUE;
        }

        $node = $this->_node($md5File);

        if (!$node) {
            $nodeInfo['next'] = $start;
            $firstNode        = $this->_node($start);
            $firstNode['pre'] = $md5File;
            $this->_node($start, $firstNode);
            $this->_node($md5File, $nodeInfo);
            $this->_startIndex($md5File);
        } else {
            $node['mask']   = $nodeInfo['mask'];
            $node['finger'] = $nodeInfo['finger'];

            //无需移动
            if (!$node['pre']) {
                $this->_node($md5File, $node);
                return TRUE;
            }

            $preNode = $this->_node($node['pre']);
            if (!$node['next']) {
                $preNode['next'] = '';
                $this->_endIndex($node['pre']);
            } else {
                $nextNode        = $this->_node($node['next']);
                $preNode['next'] = $node['next'];
                $nextNode['pre'] = $node['pre'];
                //更新当前节点的后置节点
                $this->_node($node['next'], $nextNode);
            }

            //更新当前节点的前置节点
            $this->_node($node['pre'], $preNode);

            //当前首节点下沉
            $firstNode        = $this->_node($start);
            $firstNode['pre'] = $md5File;
            $this->_node($start, $firstNode);

            //当前节点入库
            $node['pre']  = '';
            $node['next'] = $start;
            $this->_node($md5File, $node);

            //TODO 当前移动节点的前置节点设置为nodename对应的偏移量
            $nodeNames = $this->_getAllNodes();

            //某服务节点的偏移量为当前节点时，更新至前置节点地址。
            $curName = $this->nodeName;
            foreach ($nodeNames as $nodename) {
                $this->nodeName = $nodename;
                $pt             = $this->_nodeIndex();
                if ($pt == $md5File) {
                    $this->_nodeIndex($node['pre']);
                }
            }
            $this->nodeName = $curName;

            //当前节点设置为首节点
            $this->_startIndex($md5File);
        }

        return TRUE;
    }

    /**
     * 获取文件时间轴起点
     *
     * @param string $md5file
     *
     * @return string
     * @author Kalep ( kalepsong@gmail.com )
     */
    private function _startIndex($md5file = '') {
        if ($md5file) {
            if ($this->batch) {
                $this->batch->set('_start_index', $md5file);
            } else {
                $this->queue->set('_start_index', $md5file);
            }

            return $md5file;
        } else {
            return $this->queue->get('_start_index');
        }
    }

    /**
     * 获取文件时间轴尾节点
     *
     * @param $md5file
     *
     * @return mixed
     * @author Kalep ( kalepsong@gmail.com )
     */
    private function _endIndex($md5file) {
        if ($md5file) {
            if ($this->batch) {
                $this->batch->set('_end_index', $md5file);
            } else {
                $this->queue->set('_end_index', $md5file);
            }
            return $md5file;
        } else {
            return $this->queue->get('_end_index');
        }
    }

    /**
     * 节点信息
     *
     * @param $md5File
     *
     * @return mixed
     * @author Kalep ( kalepsong@gmail.com )
     */
    private function _node($md5File, $data = '') {
        $key = 'files_' . $md5File;
        if ($data) {
            if ($this->batch) {
                return $this->batch->set($key, json_encode($data));
            } else {
                return $this->queue->set($key, json_encode($data));
            }
        } else {
            $node = $this->queue->get($key);
            return $node ? json_decode($node, TRUE) : FALSE;
        }
    }

    /**
     * 获取指定节点指针位置
     *
     * @param        $node
     * @param string $md5File
     *
     * @return mixed
     * @author Kalep ( kalepsong@gmail.com )
     */
    private function _nodeIndex($md5File = '') {
        $key = 'node_index_' . $this->nodeName;
        if ($md5File) {
            if ($this->batch) {
                $this->batch->set($key, $md5File);
            } else {
                $this->queue->set($key, $md5File);
            }
        } else {
            $index = $this->queue->get($key);
            return $index ? $index : $this->_endIndex();
        }
    }

    private function _getAllNodes() {
        return ['node1', 'node2'];
    }

}
