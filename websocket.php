<?php
//官网demo
$server = new swoole_websocket_server("0.0.0.0", 9501);
$server->on('open', function (swoole_websocket_server $server, $request) {
    echo "server: handshake success with fd{$request->fd}\n";//$request->fd 是客户端id
});
$server->on('message', function (swoole_websocket_server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    $server->push($frame->fd, "this is server");//$frame->fd 是客户端id，$frame->data是客户端发送的数据
    //服务端向客户端发送数据是用 $server->push( '客户端id' ,  '内容')
});
$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});
$server->start();