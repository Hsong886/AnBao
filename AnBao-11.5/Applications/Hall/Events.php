<?php

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;


// 自动加载类

class Events
{
    /**
     * 当businessWorker进程启动时触发
     * 如果业务不需此回调可以删除onWorkerStart
     *
     * @param Worker $businessWorker 当前进程
     */
    public static function onWorkerStart($businessWorker)
    {
        DBInstance::Init(Config::$dbConfig);

        //是否开启使用redis缓存
        if (Config::$openredis) {
            RedisServer::init();
        }



        Timer::add(Config::$dbpingInterval, function ()
        {
            DBInstance::$db->table('ry_sysconfig')->select('val')->where(array('key' => 'GAME_LOGIN_PORT'))->one(true);
        });
        DBInstance::$db->table('ry_user')->where('1')->update(array('online' => 0));
        Hall::$businessWorkerID = "$businessWorker->lanIp:$businessWorker->name:$businessWorker->id";
        Hall::$centralConnection = new AsyncTcpConnection('text://' . $businessWorker->centralAddress);
        Hall::$centralConnection->onConnect = function ($connection)
        {
            echo 'Hall centralConnection connect success' . "\n";

            $connection->send(json_encode(array(
                'event' => 'HallRegister',
                'area' => GAME_HALL,
                'businessid' => Hall::$businessWorkerID
            )));
        };
        Hall::$centralConnection->onClose = function ($connection)
        {
            echo 'Hall centralConnection connection closed' . "\n";
            $connection->reconnect(1); //断线重连
        };
        Hall::$centralConnection->onError = function ($connection, $code, $msg)
        {
            echo "Hall centralConnection Error code:$code msg:$msg\n";
        };
        Hall::$centralConnection->onMessage = array('Events', 'onCentralMessage');
        Hall::$centralConnection->connect();
    }

    /**
     * 中心服务器消息
     * @param mixed $connection 连接对象
     * @param mixed $message 具体消息
     */
    public static function onCentralMessage($connection, $message)
    {
        $msg = json_decode($message, true);
        if (!isset($msg['event'])) {
            echo 'RecvFromCentral : UnKnown' . "\n";
            return;
        } else {
            $uid = -11111;
            if (isset($msg['uid'])) {
                $uid = $msg['uid'];
            }
            echo 'uid : ' . $uid . '        RecvFromCentral : ' . $msg['event'] . "\n";
        }

        $callback = $msg['event'];
        if(isset(Config::$HallCenterCallBackList[$callback])) {
            $callback = Config::$HallCenterCallBackList[$callback];
        }
        if (is_callable(array('Hall', $callback))) {
            call_user_func_array(array('Hall', $callback), array($msg));
        } else {
            echo 'Hall onCentralMessage Unknown message : ' . $message;
        }
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        if (!isset($_SESSION['router']) || empty($_SESSION['router'])) {
            $_SESSION['router'] = Hall::$businessWorkerID;
        }

        $msg = json_decode($message, true);
        if (!isset($msg['event'])) {
            echo 'RecvFromClient : UnKnown' . "\n";

            Gateway::closeClient($client_id);
            return;
        } else {
            if ($msg['event'] != 'Msg_Hall_Heart') {
                $uid = -11111;
                if (isset($msg['uid']) && $msg['uid'] != -1) {
                    $uid = $msg['uid'];
                }
                echo 'uid : ' . $uid . '        RecvFromClient  : ' . $msg['event'] . "\n";
            }
        }

        $callback = $msg['event'];

        if (isset(Config::$HallClientCallBackList[$callback])) {
            $callback = Config::$HallClientCallBackList[$callback];
        }
        //大厅消息处理
        if (is_callable(array('Hall', $callback))) {
            call_user_func_array(array('Hall', $callback), array($client_id, $msg));
        } else {
            echo 'Hall onClientMessage Unknown message : ' . $message;
            //Gateway::sendToClient($client_id, '未知消息');
        }
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        echo "New Connect! $client_id \n";
        $_SESSION['router'] = Hall::$businessWorkerID;
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        echo "Close Connect! $client_id \n";
        Hall::UserClose($client_id);
    }

    /**
     * 当businessWorker进程退出时触发
     * 如果业务不需此回调可以删除onWorkerStop
     *
     * @param Worker $businessWorker 当前进程
     */
    public static function onWorkerStop($businessWorker)
    {
        echo "WorkerStop\n";
    }
}
