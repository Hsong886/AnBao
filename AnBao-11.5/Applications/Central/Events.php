<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Worker;
use Workerman\Lib\Timer;

// 自动加载类


class Events
{
    public static function onWorkerStart($worker)
    {
        // 初始化DB实例
        DBInstance::Init(Config::$dbConfig);

        Config::$openredis ? RedisServer::init() : false;
//        Config::$MongoDB ? MongoDBserver::init(Config::$MongoDBConfig['databasename']) : false;
        var_dump(RedisServer::GetHash('user'));
        Timer::add(Config::$dbpingInterval, function ()
        {
            DBInstance::$db->table('ry_sysconfig')->select('val')->where(array('key' => "GAME_LOGIN_PORT"))->one(true);
        });


         Central::RotAdd();
    }

    public static function onMessage($connection, $message)
    {

        $msg = json_decode($message, true);
        if (!isset($msg["event"])) {
            echo "Recv : Event = UnKnown \n";
            return;
        } else {
            $uid = -11111;
            if (isset($msg["uid"])) {
                $uid = $msg["uid"];
            }
            echo "uid : " . $uid . "        RecvFrom-"  . $msg["event"] . "\n";

        }

        $callback = $msg['event'];


        //消息回调
        if (is_callable(array('Central', $callback))) {
            call_user_func_array(array('Central', $callback), array($connection, $msg));
        } else {
            echo "Central onMessage() Unknown message $message \n";
            //$connection->send('Central Unknown message');
        }
    }

    public static function onConnect($connection)
    {
        echo "Central New Connect! $connection->id \n";
    }

    public static function onClose($connection)
    {
        echo "Central Close Connect! $connection->id \n";
        Central::ClientClose($connection); //服务断开
    }

    public static function onWorkerStop($worker)
    {
        echo "Central WorkerStop\n";
    }
}