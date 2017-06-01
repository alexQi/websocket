<?php

class Client
{
    private $client;
    private $user_id;
    private $nick_name;

    public function __construct() {
        $this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $this->client->on('Connect', array($this, 'onConnect'));
        $this->client->on('Receive', array($this, 'onReceive'));
        $this->client->on('Close', array($this, 'onClose'));
        $this->client->on('Error', array($this, 'onError'));
    }

    public function connect() {
        $fp = $this->client->connect("127.0.0.1", 9501 , 1);
        if( !$fp ) {
            echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
            return false;
        }
        //初始化
        $this->user_id   = rand(1000,9999);
        $this->nick_name = self::rangeString();
    }

    public function onReceive( $cli, $data ) {
        $data = json_decode($data,true);
        $detail = json_decode($data['data'],true);
        if ($detail['user_id']==$this->user_id){
            $data['fd'] = '当前计算机';
            $detail['nick_name'] = '我';
        }
        echo "\n来自客户端[".$data['fd']."] ".$detail['nick_name']." 说 : ".$detail['msg']."\n";
    }

    public function onConnect( $cli) {
        fwrite(STDOUT, "Enter Msg:");
        swoole_event_add(STDIN, function($fp){
            global $cli;
            fwrite(STDOUT, "Enter Msg:");
            $msg = trim(fgets(STDIN));
            if ($msg!=''){
                $data['user_id']   = $this->user_id;
                $data['nick_name'] = $this->nick_name;
                $data['msg']       = $msg;
                $data = json_encode($data);
                $cli->send( $data );
            }
        });
    }

    public function onClose( $cli) {
        echo "Client close connection\n";
    }

    public function onError() {
        echo 'connect failed';
    }

    public function send($data) {
        $this->client->send( $data );
    }

    public function isConnected() {
        return $this->client->isConnected();
    }

    public function rangeString($pw_length =4)
    {
        $rangeString ='';
        for ($i = 0; $i < $pw_length; $i++)
        {
            $rangeString .= chr(mt_rand(33, 126));
        }
        return $rangeString;
    }
}

$cli = new Client();
$cli->connect();