<?php
date_default_timezone_set("Asia/Shanghai");

require_once __DIR__ . '/../Common/Config.php';
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/6 0006
 * Time: 16:38
 */

class RedisServer {

    public static $Reids;

    public static function init()
    {
        if (self::$Reids) {
            return true;
        } else {
            $redis = new Redis();
            $redis->connect(Config::$RedisConfig['host'] , Config::$RedisConfig['port']);
            //每次重启清空现有的redis缓存
            //$redis->flushdb();
            self::$Reids = $redis;

            echo "redisServer is ready start \n";
        }
    }

    /*redis  设置一个string*/
    public static function StringSet ($string , $value) {
        return self::$Reids->set($string , $value);
    }

    /*redis   获得一个string的值*/
    public static function StringGet ($string) {
        return self::$Reids->get($string);
    }

    /*redis   放进一个list表单中 没有就新建，有就从右侧加入一个
        string  表名
        value   值

        存一个索引数组
    */
    public static function SetList ($string , $value) {
        $arr = self::$Reids->lrange($string, 0, -1);
        if (!is_array($value)) {
            return false;
        }
        if (empty($arr)) {
            foreach ($value as $k => $v) {
                self::$Reids->lpush($string, $v);
            }
        } else {
            foreach ($value as $k => $v) {
                self::$Reids->rpush($string, $v);
            }
        }
        return true;
    }

    /*redis  从list表单取出管道中取出一个数据
        left = [
                    true从左边
                    false 从右边
               ]
    */
    public static function GetList ($string , $left = true) {
        $list = self::$Reids->lrange($string, 0, -1);
        if (empty($list)) {
            return false;
        }
        if ($left) {
            return self::$Reids->lpop($string);
        } else {
            return self::$Reids->rpop($string);
        }
    }

    //---------------------存一个关联数组
    /*hash 关联数组*/
    public static function SetHash ($name , $key , $value) {

        if (is_array($value)) {
            return self::$Reids->hset($name , $key , json_encode($value));
        } else {
            return self::$Reids->hset($name , $key , $value);
        }
    }

    //获得name名 下面的关联数组的表单
    public static function GetHash ($name) {

        return self::$Reids->hgetall($name);
    }

    /*删除name  键为多少的hash表单*/
    public static function DelHash ($name , $key) {
        return self::$Reids->hdel($name, $key);
    }


}