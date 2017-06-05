<?php
$serv = new swoole_server('127.0.0.1',8088,$mode = SWOOLE_PROCESS,$sock_type = SWOOLE_SOCK_TCP);


$serv->on('connect', function ($serv, $fd){
    echo "Client:Connect.\n";
    $serv->tick(1000, function() use ($serv, $fd) {
        $serv->send($fd, "这是一条定时消息\n");
    });
});
$serv->on('receive', function ($serv, $fd, $from_id, $data) {

    # 获取phpinfo
    ob_start();
    echo "<pre>";
    echo phpinfo();
    $result = ob_get_contents();

    ob_end_clean();

    $argv = explode("\r\n",$data);
    $uri  = explode(' ',$argv[0]);
    $method       = $uri[0];
    $paramsString = explode('?',$uri[1]);
    if (isset($paramsString[1])){
        $paramUri     = $paramsString[1];

        $paramArray   = array();
        foreach(explode('&',$paramUri) as $param)
        {
            $param = explode('=',$param);
            $paramArray[$param[0]] = $param[1];
        }
        $result =  json_encode($paramArray)."\n".$result;
    }

    $content = "request method is $method \n".$result;

    ob_start();
    echo $content;
    require_once './view/index.php';
    $result = ob_get_contents();
    ob_end_clean();

    response($serv,$fd,$result);//封装并发送HTTP响应报文
    $serv->close($fd);
});
$serv->on('close', function ($serv, $fd) {
    echo "Client $fd: Close.\n";
});

$serv->set(array(
    'reactor_num'   => 2, //reactor thread num
    'worker_num'    => 2,    //worker process num
    'backlog'       => 128,   //listen backlog
    'max_request'   => 2000,
    'dispatch_mode' => 1,
    'daemonize'     => 0,
    'open_eof_split'=> true,
//    'package_eof'   => "\r\n",

    #心跳检测   websocket tcp 适用
//    'heartbeat_check_interval' => 30,
//    'heartbeat_idle_time'      => 60,

    #日志
    'log_file'      => './swoole.log',

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


