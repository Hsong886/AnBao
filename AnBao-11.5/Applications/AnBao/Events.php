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
        Config::$MongoDB ? MongoDBserver::init(Config::$MongoDBConfig['databasename']) : false;
        Timer::add(Config::$dbpingInterval, function ()
        {
            DBInstance::$db->table('ry_sysconfig')->select('val')->where(array('key' => 'GAME_LOGIN_PORT'))->one(true);
        });

        Logic::$businessWorkerID = "$businessWorker->lanIp:$businessWorker->name:$businessWorker->id";
        Logic::$centralConnection = new AsyncTcpConnection('text://' . $businessWorker->centralAddress);
        Logic::$centralConnection->onConnect = function ($connection)
        {
            echo 'Logic centralConnection connect success' . "\n";

            $connection->send(json_encode(array(
                'event' => 'LogicRegister',
                'area' => GAME_AnBao,
                'businessid' => Logic::$businessWorkerID,
            )));
        };
        Logic::$centralConnection->onClose = function ($connection)
        {
            echo 'Logic centralConnection connection closed' . "\n";
            $connection->reconnect(1); //断线重连
        };
        Logic::$centralConnection->onError = function ($connection, $code, $msg)
        {
            echo "Logic centralConnection Error code:$code msg:$msg\n";
        };
        Logic::$centralConnection->onMessage = array('Events', 'onCentralMessage');
        Logic::$centralConnection->connect();


    }

    /**
     * 中心服务器消息UserHead
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

//        if(isset(Config::$LogicCenterCallBackList[$callback])) {
//            $callback = Config::$LogicCenterCallBackList[$callback];
//        }
        if (is_callable(array('Logic', $callback))) {
            call_user_func_array(array('Logic', $callback), array($connection, $msg));
        } else {
            echo 'Logic onCentralMessage Unknown message : ' . $message;
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
            if (empty(Logic::$HallList)) {
                Gateway::closeClient($client_id, json_encode(array(
                    'event' => 'Msg_Hall_BreakPlayer',
                    'area' => GAME_HALL,
                    'uid' => -11111,
                    'status' => 1,
                    'data' => array(
                        'state' => 2,
                        'info' => '连接服务器失败',
                    )
                )));

                return;
            }
            $_SESSION['router'] = array_rand(Logic::$HallList);
        }

        $msg = json_decode($message, true);
        if (!isset($msg['event'])) {
            echo 'RecvFromClient : UnKnown' . "\n";
            var_dump($msg);
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

        if ($msg['event'] == 'Msg_Hall_CreateRoom' || $msg['event'] == 'Msg_Hall_EnterRoom' || $msg['event'] == 'Msg_Hall_UpdateCard' ||$msg['event'] == 'Msg_GAME_QuickMsg') {

            Logic::SendToCentral($client_id, $msg);
            return;
        }

        if (isset(Config::$LogicClientCallBackList[$msg['event']])) {
            $callback = Config::$LogicClientCallBackList[$msg['event']];
            if (is_callable(array('Logic', $callback))) {
                call_user_func_array(array('Logic', $callback), array($client_id, $msg));
            } else {
                echo 'Logic onClientMessage Unknown message : ' . $message;
                //Gateway::sendToClient($client_id, '未知消息');
            }
        } else {
//            if (!isset(Logic::$nUserList[$msg['uid']])) {
//                //用户退出房间 不在逻辑服 进行重连
//                Gateway::closeClient($client_id, json_encode(array(
//                    'event' => 'Msg_Hall_BreakPlayer',
//                    'area' => GAME_HALL,
//                    'uid' => -11111,
//                    'status' => 1,
//                    'data' => array(
//                        'state' => 2,
//                        'info' => '房间已解散',
//                    )
//                )));
//
//                return;
//            }
            $rid = Logic::$nUserList[$msg['uid']];
            if (is_callable(array(Logic::$RoomList[$rid], 'All_RECV'))) {
                call_user_func_array(array(Logic::$RoomList[$rid], 'All_RECV'), array($client_id, $msg));
            } else {
                echo 'Logic onClientMessage Unknown message : ' . $message;
                //Gateway::sendToClient($client_id, '未知消息');
            }
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
        if (empty(Logic::$HallList)) {
            return Gateway::closeClient($client_id, '连接服务器失败');
        }
        $_SESSION['router'] = array_rand(Logic::$HallList);
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        echo "Close Connect! $client_id \n";
        var_dump("qqqqpppppp-----\n");
        Logic::UserClose($client_id);
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
