<?php
$serv = new swoole_server('0.0.0.0','9501',$mode = SWOOLE_PROCESS,$sock_type = SWOOLE_SOCK_TCP);

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $serv->send($fd, 'Swoole: '.$data);
    $serv->close($fd);
});
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

$serv->set(array(
    'reactor_num'   => 2, //reactor thread num
    'worker_num'    => 4,    //worker process num
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
));

$serv->start();

?>


