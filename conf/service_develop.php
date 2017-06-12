<?php
/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/6/5
 * Time: 下午6:23
 */


class ServiceConf
{
    static $service=[
//        'TestService'   =>  1,
        'Test1Service'   => 1,

    ];
    const kafka=[
        'brokerList'    =>  '10.110.32.193:9092',
        'brokerVer'     =>  '0.9.0.1',
        'topic'         =>  'stream_t',
        'offset'        =>  'latest',
    ];

    const heart_time=5;


    const ERROR=[
        'CREATE_PROCESS_CLASS_NOT_EXIST' =>     [
                                                    'errno' =>1001,
                                                    'msg'   =>'创建服务类不存在',
                                                ],
        'CREATE_PROCESS_EXIST'           =>     [
                                                    'errno' =>1002,
                                                    'msg'   =>'服务已经被创建',
                                                ],

        'CREATE_PROCESS_FAILED'          =>     [
                                                    'errno' =>1003,
                                                    'msg'   =>'服务创建失败',
                                                ],

        'CREATE_PROCESS_FILE_NOT_FOUND'  =>     [
                                                    'errno' =>1004,
                                                    'msg'   =>'服务文件不存在',
                                                ],

        'DROP_PROCESS_NOT_EXIST'         =>     [
                                                    'errno' =>1011,
                                                    'msg'   =>'关闭服务不存在',
                                                ],

        'DROP_PROCESS_FAILED'            =>     [
                                                    'errno' =>1012,
                                                    'msg'   =>'关闭服务失败',
                                                ],

        'ACTION_NOT_FOUND'               =>     [
                                                    'errno' =>1100,
                                                    'msg'   =>'action行为不存在',
                                                ],

    ];
}

