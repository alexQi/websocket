<?php

class HttpServer
{
    public static $instance;
    public $http;
    public static $get;
    public static $post;
    public static $header;
    public static $mysql;
    public static $redis;
    public static $server;
    public static $httpServ;
    private $application;

    /**
     * 检测并创建项目运行时目录
     */
    private function checkRuntime(){
        $runtime_path = dirname(__DIR__).'/runtime';
        if(!is_dir($runtime_path)){
            mkdir($runtime_path,0755);
        }
        if(!is_dir($runtime_path.'/logs')){
            mkdir($runtime_path.'/logs',0755);
        }
    }

    public function __construct()
    {
        #初始化yii配置项
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_ENV') or define('YII_ENV', 'dev');

        require(__DIR__ . '/../vendor/autoload.php');
        require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
        require(__DIR__ . '/../common/config/bootstrap.php');
        require(__DIR__ . '/../application/config/bootstrap.php');

        $config = yii\helpers\ArrayHelper::merge(
            require(__DIR__ . '/../common/config/main.php'),
            require(__DIR__ . '/../common/config/main-local.php'),
            require(__DIR__ . '/../application/config/main.php'),
            require(__DIR__ . '/../application/config/main-local.php')
        );

        $this->checkRuntime();
        $http = new swoole_http_server('0.0.0.0', '9501');

        $http->set(
            array(
                'worker_num'  => 8,
                'daemonize'   => false,
                'max_request' => 10000,
                'debug_mode'  => 1,
                'worker_num'  =>3,
                'task_worker_num' => 1,
                'dispatch_mode'   => 1,
                'log_file'        => __DIR__."/../runtime/logs/swoole.log",
            )
        );
        $http->on('Start', array($this, 'onStart'));
        $http->on('WorkerStart', array($this, 'onWorkerStart'));
        $http->on('Receive', array($this, 'onReceive'));
        $http->on('Shutdown', array($this, 'onShutdown'));
        $http->on('Finish', function ($serv, $taskId, $data) {
            //TDDO 任务结束之后处理任务或者回调
            echo "$taskId task finish";
        });
        $http->on('task', function ($serv, $taskId, $fromId, $data) {
            static $link = null;
            $result = array();
            $serv->finish($result);
        });
        $http->on('request', function ($request, $response) use ($http) {
            if (isset($request->server)) {
                HttpServer::$server = $request->server;
            } else {
                HttpServer::$server = [];
            }
            if (isset($request->header)) {
                HttpServer::$header = $request->header;
            } else {
                HttpServer::$header = [];
            }
            if (isset($request->get)) {
                HttpServer::$get = $request->get;
            } else {
                HttpServer::$get = [];
            }
            if (isset($request->post)) {
                HttpServer::$post = $request->post;
            } else {
                HttpServer::$post = [];
            }

            HttpServer::$httpServ = $http;

            // TODO handle img


            ob_start();
            //开始执行yii
            try{
                $_SERVER = array_change_key_case(HttpServer::$server,CASE_UPPER);
                $this->application->ruwn();
            }catch (Exception $e){
                var_dump($e->getMessage());
            }

            $result = ob_get_contents();
            ob_end_clean();

            $response->end($result);
        });

        $this->application = new yii\web\Application($config);

        $http->start();
    }

    public function onStart($serv){
        swoole_set_process_name('swoole master process');
        echo '主进程ID:'.$serv->master_pid.' 管理进程ID:'.$serv->manager_pid."\n";
    }

    public function onReceive(\swoole_http_server $serv,$fd,$from_id,$data){
        var_dump($data);
    }

    public function onWorkerStart($serv , $worker_id)
    {
        echo "worker init.... workerId:$worker_id\n";

        swoole_timer_tick(1000, function ($interval) {

        });
    }

    public function onShutdown(\swoole_server $serv){
        echo "sever has been shutdown";
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new HttpServer;
        }
        return self::$instance;
    }
}

HttpServer::getInstance();
