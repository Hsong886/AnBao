<?php
/**
 * Created by PhpStorm.
 * User: 龚坤
 * Date: 2018/9/6
 * Time: 18:00
 */

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Worker;


class Hall
{
    /**
     * 本机 IP:Name:worker_id
     * @var string
     */
    public static $businessWorkerID = '';

    /**
     * 中心服务器连接
     * @var object
     */
    public static $centralConnection;

    /**
     * 每日任务
     * @var object
     */
    public static $dailymission;

    /**
     * 活跃宝箱奖励
     * @var object
     */
    public static $activebox;

    /**
     * 中心服消息处理
     */

    /**
     * 打印消息
     */
    public static function PrintMsg($data, $type)
    {
        if($data['event'] == 'Msg_Hall_Heart'){
            return;
        }

        $sendstr = 'uid : ' . (isset($data['uid']) ? $data['uid'] : -1) . '        SendToClient    : ';
        if($type == 1)
            $sendstr = 'uid : ' . $data['uid'] . '        SendToCentral   : ';
        elseif($type == 2){
            $sendstr = 'uid : ' . $data['uid'] . '        SendToHall      :    ';
        }
        if(isset($data['event']))
            echo $sendstr . $data['event']. "\n";
        else
            echo $sendstr . "UnKnown \n";
    }

    /**
     * 发送消息
     */
    public static function SendData($con, $data, $type)
    {
        $con->send(json_encode($data));
        self::PrintMsg($data, $type);
    }

    /**
     * 发送消息至该UID
     */
    public static function SendToUid($uid, $data)
    {
        Gateway::sendToUid($uid, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        self::PrintMsg($data, 0);
    }

    /**
     * 发送消息至该套接字
     */
    public static function SendToClient($client, $data)
    {
        Gateway::sendToClient($client, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        self::PrintMsg($data, 0);
    }

    /**
     * 大厅注册返回
     * @param $message
     */
    public static function HallRegister($message)
    {
        $status = $message['status'];

        if ($status == 1) {
            echo $message['msg']."\n";
     
        }
        else {
            //注册错误
            var_dump($message);
        }
    }

    /**
     * 转发用户
     * @param $message
     */
    public static function SendToUser($message)
    {
        self::SendToUid($message['uid'], $message);
    }

    /**
     * 转发中心服
     * @param $client_id
     * @param $message
     */
    public static function SendToCenter($client_id, $message)
    {
        self::SendData(self::$centralConnection, $message, 1);
    }

    /**
     * 用户断开连接 同步断开消息到中心服务器
     * @param $client_id
     */
    public static function UserClose($client_id)
    {
        self::SendToCenter($client_id,
            array(
                'event' => 'UserClose',
                'area' => GAME_HALL,
                'client_id' => $client_id,
                'uid' => -11111,
            )
        );
    }

    public static function Msg_Hall_BreakPlayer ($client_id, $message) {
        echo "pppppppppppppppppqqqqqqqqqqqqqqqqqqq\n";
        var_dump($client_id);
        var_dump($message);
//        $ii = $message['client_id'];
//        self::SendToClient($ii, $message);
    }

    /**
     * 玩家登陆
     */
    public static function Msg_Hall_Connect($client_id, $message)
    {
        $count = 0;
        $randId = 1000;
        if (Config::$randlogin) {
            do {
                $randId += 1;
                $count += 1;
                $user = DBInstance::randLogin($randId);

                if($count > 1000){
                    break;
                }
            } while (!isset($user['uid']));
        } else {
            var_dump($message);
            $user = DBInstance::userLogin($message['data']['openid']);
        }
        if (!isset($user['uid']) || $user['uid'] <= 0) {
            echo 'Connect Error : Msg_Hall_Connect' . "\n";
            return;
        }

        DBInstance::setUserOnline($user['uid']);
        Gateway::bindUid($client_id, $user['uid']);

        $msg = array(
            'event' => 'Msg_Hall_Connect',
            'client_id' => $client_id,
            'uid' => $user['uid'], //用户ID
            'area' => GAME_HALL,
            'status' => 1,
            'data' => [
                'uid' => $user['uid'], //用户ID
                'gold' => $user['gold'], //门票
                'sex' => $user['sex'], //性别 1男2女
                'nickname' => $user['nickname'],
                'headimgurl' => $user['headimgurl'],
                'rid' => 0, //游戏房间号 没有发0
                'state' => 1,//玩家状态 0离线 1正常
                'client_id' => $client_id,
                'ip' => $_SERVER['REMOTE_ADDR'],
            ]
        );

        self::SendToCenter($client_id, $msg);
    }

    /**
     * 创建房间
     */
    public static function Msg_Hall_CreateRoom($connection, $message)
    {
        self::SendToCenter($connection, $message);
    }

    public static function Msg_Hall_QuickTool ($connection, $message) {
         self::SendToCenter($connection, $message);
    }

    public static function Msg_Hall_GetAllUser ($connection, $message) {
        self::SendToCenter($connection, $message);
    }

    /**
     * 返回大厅
     */
    public static function Msg_Hall_GameQuit($connection, $message)
    {
        $uid = $message['uid'];

        $cur = DBInstance::getArrUser('card,gold,usdt', $uid);
        $message['data']['usdt'] = $cur['usdt'];
        $message['data']['card'] = $cur['card'];
        $message['data']['gold'] = $cur['gold'];

        self::SendToCenter(0, $message);
    }

    /**
     * 加入房间
     */
    public static function Msg_Hall_EnterRoom($connection, $message)
    {
        $uid = $message['uid'];

        $message['data']['cur'] = DBInstance::getArrUser('*', $uid);

        self::SendToCenter(0, $message);
    }


    /*
     * 获得当前所有桌子*/
    public static function Msg_Hall_GetAllTables ($connection, $message) {
        self::SendToCenter(0 , $message);
    }


    public static function Msg_GAME_SYNC($client_id, $message){
        $msg = [
            'event' => 'Msg_Hall_BreakPlayer',
            'data' => [
                'state' => 2,
                'Desc' => '登录超时'
            ],
        ];
        self::SendToClient($client_id, $msg);
    }

    /**
     * 玩家心跳
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_Heart($client_id, $message)
    {
        $message['status'] = 1;
        $message['data'] = array();
        if (!isset($message['uid'])){
            return;
        }
        if($message['uid'] == -1){
            $message['uid'] = -11111;
        }
        self::SendToClient($client_id, $message);
    }

    /*玩家获得房间号等*/
    public static function Msg_Hall_Rooms ($client_id, $message)
    {
        self::SendToCenter($client_id , $message);
    }
}