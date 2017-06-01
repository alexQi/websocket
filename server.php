<?php

class Server
{
    private $serv;
    private $redis;
    private static $map = array();

    /**
     * Server constructor.
     */
    public function __construct() {
        #初始化redis连接
        $this->connectRedis();

        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'worker_num' => 8,
            'daemonize' => false,
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'debug_mode'=> 1,
            'task_worker_num' => 1
        ));

        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));
        // bind callback
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));

        $this->serv->start();
    }

    /**
     * 连接redis
     * @param $config
     */
    public function connectRedis($config=array()){
        $redis = new redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('6da192c7dd56a5ba917c59d2e723911a');
        return $this->redis = $redis;
    }

    /**
     * 开启服务
     * @param $serv
     */
    public function onStart( $serv ) {
        echo "----swoole server start----\n";
    }

    /**
     * 链接时存储客户端id
     * @param $serv
     * @param $fd
     * @param $from_id
     */
    public function onConnect( $serv, $fd, $from_id ) {
        $this->redis->sAdd('messages',$fd);
        echo "Client $fd has been connected\n";
    }

    /**
     * 收到消息时开启task
     * @param swoole_server $serv
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public function onReceive( swoole_server $serv, $fd, $from_id, $data ) {
        $param = array(
            'fd' => $fd,
            'data'=>$data,
        );
        $serv->task(json_encode($param));
    }

    /**
     * 客户端关闭
     * @param $serv
     * @param $fd
     * @param $from_id
     */
    public function onClose( $serv, $fd, $from_id ) {
        $this->redis->srem('messages',$fd);
        echo "Client {$fd} close connection\n";
    }

    /**
     * 开启任务发送消息
     * @param $serv
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask($serv,$task_id,$from_id, $data) {
        $fd_array = $this->redis->smembers('messages');
        foreach($fd_array as $fd){
            $serv->send( (int)$fd , $data);
        }
    }

    /**
     * 当结束时
     * @param $serv
     * @param $task_id
     * @param $data
     */
    public function onFinish($serv,$task_id, $data) {
        echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";
    }

    public function onShutDown($serv){
        echo "server shutting down";
    }
}

$server = new Server();
