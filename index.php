<?php

/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/5/30
 * Time: 下午4:40
 */

date_default_timezone_set('PRC');

class Index
{
    private $http=null;

    private $service_map=[];

    private $start_time=null;

    /**
     * Index constructor.
     */
    public function __construct()
    {
        $this->http = new swoole_http_server("0.0.0.0",9595);
    }


    private function init()
    {

        $this->http->set([
            'reactor_num' => 1, //reactor thread num
            'worker_num' => 1,    //worker process num
            'daemonize'     => true,
            'pid_file' => '/tmp/lhy_process.pid',
            'log_file' => '/tmp/swoole.log',
            // 'backlog' => 128,   //listen backlog
            //  'max_request' => 50,
            // 'dispatch_mode' => 1,
        ]);



        $this->initWorker();


        $this->initHttp();

    }


    private function initService()
    {
        $services=ServiceConf::$service;
        array_walk($services,function($v,$k){
            if($v){
                $this->start($k);
            }
        });

    }





    /**
     * 初始化worker
     */
    private function initWorker()
    {
        $this->http->on('workerStart', function ($serv, $id) {
            function_exists('opcache_reset') && opcache_reset();

//            require_once __DIR__.'/conf/service_'.get_cfg_var('yaf.environ').'.php';  // 加载配置
            require_once __DIR__.'/conf/service_develop.php';  // 加载配置
            require_once __DIR__.'/lib/Loader.php';  // 加载配置

            $this->initService();

            $this->start_time=time();

            swoole_process::signal(SIGCHLD, function(){
                //表示子进程已关闭，回收它
                while($ret =  swoole_process::wait(false)) {
//                    echo "PID={$ret['pid']}\n";
                    $this->delServiceByPid($ret['pid']);
                    error_log("Worker Exit, kill_signal={$ret['signal']} PID=" . $ret['pid']."\n",3,"/tmp/lhy.log");
                }

            });




        });
    }


    /**
     * 根据子进程 清理 子进程
     * @param $pid
     */
    private function delServiceByPid($pid)
    {
        foreach ($this->service_map as $k=>$s){
            if($s['process']->pid == $pid ){
                unset($this->service_map[$k]);
                break;
            }
        }

    }


    /**
     * 初始化http
     */
    private function initHttp()
    {
        $this->http->on('request', function ($request, $response) {
            //请求过滤
            if($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico'){
                return $response->end();
            }

            $action = $request->get['action'] ?? '';
            $service = $request->get['service'] ?? '';

            $json=true;


            /**
             * 接口处理
             */
            try{
                $data=[];
                switch ($action){
                    case 'start' :   //创建服务
                        $this->start($service);
                        break;
                    case 'stop':    //结束服务
                        $this->stop($service);
                        break;      //强制结束服务
                    case 'force':
                        $this->force($service);
                        break;
                    case 'stopall':     //结束所有服务
                        $this->stopAll();
                        break;
                    case 'status':  //当前主进程状态
                        $data=['start_time'=>$this->start_time,'memory_use'=>memory_get_usage(),'time'=>time()];
                        break;
                    case 'list':    //服务列表及状态
                        foreach ($this->service_map as $k=>$item) {
                            $data[$k] = ['pid'=> $item['process']->pid,'heart_last_time'=>$item['heart_last_time'],'memory_use'=>$item['memory_use']];
                        }
                        break;
                    case 'list_service':  //服务列表
                        $data=ServiceConf::$service;
                        break;
                    case 'add_service':  //临时增加服务
                        $currentService=ServiceConf::$service;
                        !isset($currentService[$service]) && ServiceConf::$service[$service]=0;

                    case 'help':   //帮助
                        break;
                    default:
                        throw  new Exception(ServiceConf::ERROR['ACTION_NOT_FOUND']['msg'],ServiceConf::ERROR['ACTION_NOT_FOUND']['errno']);
                }

                $json && $response->header("Content-Type", "application/json; charset=utf-8");

                $response->end(json_encode(['errno'=>0,'msg'=>'success','data'=>$data],JSON_UNESCAPED_UNICODE));

            }catch (Exception $e){
                $json && $response->header("Content-Type", "application/json; charset=utf-8");
                $response->end(json_encode(['errno'=>$e->getCode(),'msg'=>$e->getMessage(),'data'=>[]],JSON_UNESCAPED_UNICODE));
            }


        });
    }



    public function run()
    {
        $this->init();
        $this->http->start();

    }



    private function stopAll()
    {
        foreach ($this->service_map as $k=>$item) {
            $this->stop($k);
        }
    }


    /**
     * 进程状态
     * @param $process
     * @return array
     */
    private function status($process)
    {

        $res=[];
        $res['status']=isset($this->service_map[$process]['process']) ? swoole_process::kill($this->service_map[$process]['process']->pid,0) : false;
        $res['heart_last_time'] = $this->service_map[$process]['heart_last_time'];
        isset($this->service_map[$process]['memory_use']) && $res['memory_use'] = $this->service_map[$process]['memory_use'];
        return $res;

    }


    /**
     * 启动进程
     * @param $process
     */
    private function start($process)
    {

        $this->create_process($process);

    }



    /**
     * 强杀
     * @param $process
     */
    private function force($process)
    {

        $this->drop_process($process,SIGKILL);
    }


    /**
     * 停止进程
     * @param $process
     */
    private function stop($process)
    {
        $this->drop_process($process);
    }


    /**
     * 创建进程
     * @param $pro
     * @return array
     * @throws Exception
     */
    private function create_process($pro)
    {

        if(in_array($pro,array_keys(ServiceConf::$service))){    //  process class is existed

            if(isset($this->service_map[$pro])){
                throw new Exception(ServiceConf::ERROR['CREATE_PROCESS_EXIST']['msg'],ServiceConf::ERROR['CREATE_PROCESS_EXIST']['errno']);
            }

            $file=__DIR__.'/service/'.ucfirst($pro).'.php';

            if(!is_file($file)){
                throw new Exception(ServiceConf::ERROR['CREATE_PROCESS_FILE_NOT_FOUND']['msg'],ServiceConf::ERROR['CREATE_PROCESS_FILE_NOT_FOUND']['errno']);
            }
            $process = new swoole_process(function($worker) use($pro,$file){

                $class=include $file;

                $class->run($worker);

            });

            $pid=$process->start();
            if(empty($pid)){ // 创建进程失败
                throw new Exception(ServiceConf::ERROR['CREATE_PROCESS_FAILED']['msg'],ServiceConf::ERROR['CREATE_PROCESS_FAILED']['errno']);
            }

//            $pid=1;
            swoole_event_add($process->pipe, function ($pipe) use($process,$pro){
                $data = $process->read();
                error_log("parent receive:$data \n",3,'/tmp/lhy.log');
                $data = explode("\r\n",$data);
                foreach ($data as $msg) {
                    $_msg_arr=explode('|',$msg);
//                    var_dump($_msg_arr);
                    if($_msg_arr[0] == 'heart'){   // heart

                        $this->service_map[$pro]['heart_last_time']=time();
                        $this->service_map[$pro]['memory_use']=$_msg_arr[2];

                    }
                }

//                error_log("process receive $data\n",3,"/tmp/lhy.log");
            });

            $this->service_map[$pro]=['process'=>$process,'heart_last_time'=>time()];
            error_log("add service_map $pro\n",3,'/tmp/lhy.log');
            return ['errno'=>0,'msg'=>'success','data'=>[]];
        }else{
            throw new Exception(ServiceConf::ERROR['CREATE_PROCESS_CLASS_NOT_EXIST']['msg'],ServiceConf::ERROR['CREATE_PROCESS_CLASS_NOT_EXIST']['errno']);
        }

    }


    /**
     * 停止 进程
     * @param $process
     * @param int $sig SIGUSR1 SIGKILL
     * @return bool
     * @throws Exception
     */
    private function drop_process($process,$sig=SIGUSR1)
    {
        if(isset($this->service_map[$process])){

            $kill_res=swoole_process::kill($this->service_map[$process]['process']->pid,$sig);  //发送信号
            error_log("kill process res $kill_res \n",3,"/tmp/lhy.log");

            return true;
        }else{
            throw new Exception(ServiceConf::ERROR['DROP_PROCESS_NOT_EXIST']['msg'],ServiceConf::ERROR['DROP_PROCESS_NOT_EXIST']['errno']);
        }

    }






}



(new Index())->run();
















