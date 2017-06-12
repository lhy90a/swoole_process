<?php
/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/6/8
 * Time: 下午5:15
 */

$op=$argv[1];
$pid=$argv[2];
switch ($op){
    case 'start':
        shell_exec('/usr/local/php/bin/php  '.dir(__DIR__).'/index.php');
        break;
    case 'reload':
        shell_exec('/usr/bin/curl -s http://127.0.0.1:9595?action=stopall');
        do{
            $res=shell_exec('/usr/bin/curl -s http://127.0.0.1:9595?action=list');
            $res=json_decode($res,true);
        }while(!empty($res['data']));

        shell_exec("/bin/kill -USR1 $pid");


        break;
    case 'stop':
        shell_exec('/usr/bin/curl -s http://127.0.0.1:9595?action=stopall');
        do{
            $res=shell_exec('/usr/bin/curl -s http://127.0.0.1:9595?action=list');
            $res=json_decode($res,true);
        }while(!empty($res['data']));

        shell_exec("/bin/kill $pid");
        break;

}

