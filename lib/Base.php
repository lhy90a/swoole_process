<?php

/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/6/5
 * Time: 下午6:38
 */
abstract class Base
{
    public $k_instance=null;  // kafka 实例

    private $heart=0;

    public function kafka_init($k_conf=null)
    {

        $cls=get_class($this);
//        cli_set_process_title('stream_service:'.$cls);
        $brokerList=$k_conf['brokerList'] ?? ServiceConf::kafka['brokerList'];
        $brokerVer=$k_conf['brokerVer'] ?? ServiceConf::kafka['brokerVer'];
        $topic=$k_conf['topic'] ?? ServiceConf::kafka['topic'];
        $offset=$k_conf['offset'] ?? ServiceConf::kafka['offset'];
        $groupId=$k_conf['groupId'] ?? $cls;
        $this->k_instance=StreamKafka::getInstance();
        $this->k_instance->init($brokerList,$brokerVer,$topic,$groupId,$offset);

    }


    /**
     * 注册定时器信号,并想主进程上报心跳。
     * @param $process
     */
    public function regHeartSignal($process)
    {
//        declare(ticks = 1);

        pcntl_signal(SIGALRM, function() use($process){

            $process->write("heart|{$process->pid}|".memory_get_usage()."\r\n");
            pcntl_alarm( ServiceConf::heart_time );
        },true);
    }


    /**
     * 心跳
     */
    public function heart()
    {
        if(empty($this->heart)){
            $this->heart=1;
            pcntl_alarm( ServiceConf::heart_time );
        }
        pcntl_signal_dispatch();







//        \Amp\repeat(function() use($process){
//            $process->write("heart|{$process->pid}|".memory_get_usage()."\r\n");
//            echo "heart|".getmypid()."|".memory_get_usage()."\r\n";
//                error_log("heart|".getmypid()."\n",3,'/tmp/lhy.log');
//        },ServiceConf::heart_time);
//        swoole_timer_tick(5000,function(){
//            echo "heart|".getmypid()."|".memory_get_usage()."\r\n";
////                $process->write("heart|{$process->pid}\r\n");
//            error_log("heart|".getmypid()."\n",3,'/tmp/lhy.log');
//
//        });

    }

    abstract public  function  run($process);

}