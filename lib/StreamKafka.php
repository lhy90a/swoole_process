<?php

/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/5/29
 * Time: 下午10:07
 */
require dirname(__DIR__).'/vendor/autoload.php';

class StreamKafka
{
    use SingletonTrait;
    private $brokerList='10.110.32.193:9092';
    private $brokerVersion='0.9.0.1';
    private $topic='';
    private $groupId='test';
    private $offsetRest='latest';
    private $host='';  // 调度节点host
    private $port='';  //调度节点port

    private $offset=0;
    private $offset_file_dir='';
    private $offset_file='';

    private $group=[]; //同步组信息

    private $killed=false;



    /**
     * 初始化kafka
     * @param string $brokerList   10.110.32.193:9092,10.110.32.193:9092
     * @param string $brokerVersion   0.9.0.1
     * @param string $topic
     * @param string $groupId
     * @param string $offsetRest  latest
     */
    public function init($brokerList='',$brokerVersion='0.9.0.1',$topic='',$groupId='test',$offsetRest='latest')
    {
        $this->brokerList=$brokerList;
        $this->brokerVersion=$brokerVersion;
        $this->topic=$topic;
        $this->groupId=$groupId;
        $this->offsetRest=$offsetRest;
        $config = \Kafka\ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(100);
        $config->setMetadataBrokerList($brokerList);
        $config->setBrokerVersion($brokerVersion);
        $config->setGroupId($groupId);
        $config->setTopics(array($topic));
        $config->setOffsetReset($offsetRest);


        $this->initOffset();


    }

    /**
     * 初始化本地偏移量
     */
    private function initOffset()
    {
        $this->offset_file_dir=dirname(__DIR__).'/data/kafka_offset/';
        $this->offset_file=$this->offset_file_dir.$this->topic.'_'.$this->groupId;

        if(@is_file($this->offset_file)){
            $this->offset=intval(@file_get_contents($this->offset_file));
        }else{
            $this->offset=0;
        }
    }



    /**
     * 消费者
     * @param Closure $callback
     */
    public function consume(\Closure $callback)
    {
        $process = new \Kafka\Consumer\Process(function($topic, $part, $message) use($callback){
            if( $message['offset'] > $this->offset ){  //避免重复消费
                call_user_func($callback,$message);
                    $this->offset=$message['offset'];
            }

        });

        $watcher_kill = \Amp\onSignal(SIGUSR1, function() use($process){
            error_log("catch signal SIGTERM \n",3,"/tmp/lhy.log");
            !empty($process) && $process->stop();
            $this->createDir($this->offset_file_dir);
            file_put_contents($this->offset_file,$this->offset);
            posix_kill(getmypid(), SIGTERM);
        });

        $process->start();
        \Amp\run();

    }

    public function createDir($file)
    {
        if(!is_file($file)){
            return @mkdir($file,0777,true);
        }else{
            return false;
        }
    }


    /**
     * 生产者
     * @param $key
     * @param $msg
     */
    public function producer($key,$msg)
    {
        $producer = new \Kafka\Producer(function() use($key,$msg){
            return array(
                array(
                    'topic' => $this->topic,
                    'value' => $msg,
                    'key' => $key,
                ),
            );
        });
        $producer->success(function($result) {
            echo "success\n";
            return true;
        });
        $producer->error(function($errorCode, $context) {
            var_dump($errorCode);
            return false;
        });
        $producer->send();


    }

}