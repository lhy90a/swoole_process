<?php

/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/6/5
 * Time: 下午6:25
 */
return (
new class extends Base
{

    public function run($process)
    {

        $this->regHeartSignal($process);


        for (;;) {

            $this->heart();
//        parent::kafka_init();  // 初始化配置
              // add heart
        }
//
//        $this->k_instance->consume(function($msg){
//            $this->test($msg);
//        });

    }


    /**
     * 业务逻辑
     */
    private function test($msg)
    {
        error_log(__CLASS__." ".var_export($msg,true)."\n",3,'/tmp/lhy.log');
    }

}
);


