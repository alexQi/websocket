<?php
$serv = new swoole_server('127.0.0.1',8088,$mode = SWOOLE_PROCESS,$sock_type = SWOOLE_SOCK_TCP);


$serv->on('connect', function ($serv, $fd){
    echo "Client:Connect.\n";
    $serv->tick(1000, function() use ($serv, $fd) {
        $serv->send($fd, "这是一条定时消息\n");
    });
});
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $reqAry = explode("\r\n",$data);

    if (stripos($reqAry[0],"Hello.php") !== FALSE )
    {
        echo "用户想调用Hello.php".PHP_EOL;
        $serv->send($fd,"你调用了Hello.php方法");
    }
    else if (stripos($reqAry[0],"World.php") !== FALSE )
    {
        echo "用户想调用World.php".PHP_EOL;
        $serv->send($fd,"你调用了World.php方法");
    }
    else
    {
        echo "用户想请求了一个不支持的方法".PHP_EOL;
        $data = "404，你调用的方法我们不支持。";
        response($serv,$fd,$data);//封装并发送HTTP响应报文
//        $serv->send($fd,"404，你调用的方法我们不支持。");
    }
});
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

$serv->set(array(
    'reactor_num'   => 2, //reactor thread num
    'worker_num'    => 2,    //worker process num
    'backlog'       => 128,   //listen backlog
    'max_request'   => 2000,
    'dispatch_mode' => 1,
    'daemonize'     => 0,
    'reactor_num'   => 4,
    'work_num'      => 16,
    'backlog'       => 128,
    'log_file'      => './swoole.log',
    'heartbeat_check_interval' => 30,
    'heartbeat_idle_time'      => 60,
    'dispatch_mode' => 1,
    'open_eof_split' => true,
    'package_eof' => "\r\n",
));

/**
 * 发送内容
 * @param \swoole_server $serv
 * @param int $fd
 * @param string $respData
 * @return void
 */
function response($serv,$fd,$respData){
    //响应行
    $response = array(
        'HTTP/1.1 200',
    );
    //响应头
    $headers = array(
        'Server'=>'SwooleServer',
        'Content-Type'=>'text/html;charset=utf8',
        'Content-Length'=>strlen($respData),
    );
    foreach($headers as $key=>$val){
        $response[] = $key.':'.$val;
    }
    //空行
    $response[] = '';
    //响应体
    $response[] = $respData;
    $send_data = join("\r\n",$response);
    $serv->send($fd, $send_data);
}

$serv->start();

?>


