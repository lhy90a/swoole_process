<?php
/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/5/30
 * Time: 下午5:17
 */


class Loader
{
    public static function autoLoader($class)
    {
        $file=dirname(__DIR__).'/lib/'.ucfirst($class).'.php';
        if(is_file($file)){
            require_once $file;
        }else{
            $file=dirname(__DIR__).'/conf/service_'.get_cfg_var('yaf.environ').'.php';
            if(is_file($file)){
                require_once $file;
            }
        }
    }

}


spl_autoload_register(array('Loader', 'autoLoader'),true);
