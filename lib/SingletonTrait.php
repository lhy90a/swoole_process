<?php

/**
 * Created by PhpStorm.
 * User: fengli
 * Date: 2017/5/29
 * Time: 下午10:12
 */
trait SingletonTrait
{
    protected static $instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

}