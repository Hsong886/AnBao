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
use Workerman\Lib\Timer;

class Logic
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
     * 大厅服列表
     * @var array
     */
    public static $HallList = array();

    /**
     * 用户列表
     * $nUserList['uid'] = 'rid'
     * @var array
     */
    public static $nUserList = array();

    /**
     * 用户列表
     * $sUserList['client_id'] = 'uid'
     * @var array
     */
    public static $sUserList = array();

    /**
     * 房间列表
     * $RoomList['rid'] = array(房间对象1，2，...);
     * @var array
     */
    public static $RoomList = array();

    /**
     * 中心服消息处理
     */

    /**
     * 发送消息
     */
    public static function PrintMsg($data, $type)
    {
        if($data['event'] == 'Msg_Hall_Heart' || $data['event'] == 'Msg_FSZS_PetSync' || $data['event'] == 'Msg_FSZS_setPlayer'){
            return;
        }

        $sendstr = 'uid : ' . $data['uid'] . '        SendToClient    : ';
        if($type == 1)
            $sendstr = 'uid : ' . $data['uid'] . '        SendToCentral   : ';
        elseif($type == 2){
            $sendstr = 'uid : ' . $data['uid'] . '        SendToHall      : ';
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
     * 发送消息
     */
    public static function SendToUid($uid, $data)
    {
        Gateway::sendToUid($uid, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

    }

    /**
     * 发送消息
     */
    public static function SendToClient($client, $data)
    {
        Gateway::sendToClient($client, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        self::PrintMsg($data, 0);
    }

    /**
     * 发送消息
     */
    public static function SendToCentral($client_id, $data)
    {

        if ($data['event'] == 'RoomOld') {
            if (isset(self::$RoomList[$data['data']['rid']])) {
                unset(self::$RoomList[$data['data']['rid']]);
            }
        }
        self::$centralConnection->send(json_encode($data));

        self::PrintMsg($data, 1);
    }



    /**
     * 大厅注册返回
     * @param $message
     */
    public static function LogicRegister($client_id, $message)
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
     * 大厅列表
     * @param $message
     */
    public static function HallList($client_id, $message)
    {
        self::$HallList = array_flip($message['list']);
    }

    /**
     * 转发用户
     * @param $message
     */
    public static function SendToUser($client_id, $message)
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
     * 创建房间 房间开始
     * @param $message
     */
    public static function RoomBegin($client_id, $message)
    {

        //$val = $message['data'];
        self::$RoomList[$message['data']['rid']] = new Room($message);
//        self::$sUserList[$val['client_id']] = $message['uid'];
//        self::$nUserList[$val['uid']] = $message['data']['rid'];
    }

    /**
     * 回收房间
     * @param $message
     */
    public static function RoomOld($client_id, $message)
    {
        $rid = $message['data']['rid'];
        if (isset(self::$RoomList[$rid])) {
            foreach (self::$nUserList as $k => $val) {
                if ($val == $rid) {
                    unset(self::$sUserList[self::$nUserList[$k]['client_id']]);
                    unset(self::$nUserList[$k]);
                }
            }

            unset(self::$RoomList[$rid]);
        }
    }


    /**
     * 玩家进入
     * @param $connection
     * @param $message
     */
    public static function Msg_Hall_EnterRoom($connection, $message)
    {


        if (isset(self::$RoomList[$message['data']['rid']])) {
            //通知游戏开始

            if(self::$RoomList[$message['data']['rid']]->UserEnter(Logic::$businessWorkerID, $message))
            {
                self::$sUserList[Gateway::getClientIdByUid($message['uid'])['0']] = $message['uid'];
                self::$nUserList[$message['uid']] = $message['data']['rid'];
            }
        }
        else {
            //房间已解散 未创建成功？
            $msg = array(
                'event' => 'Msg_Hall_EnterRoom',
                'uid' => $message['uid'],
            );
            $msg['data']['state'] = 0;  //0失败 1成功
            $msg['data']['msg'] = "房间号不存在";
            self::SendToUid($message['uid'], $msg);
        }
    }

    /**
     * 玩家重连
     * @param $message
     */
    public static function UserOnline($client_id, $message)
    {
        if (isset(self::$RoomList[$message['rid']])) {
            self::$sUserList[$message['client_id']] = $message['uid'];
            self::$nUserList[$message['uid']] = $message['rid'];
            self::$RoomList[$message['rid']]->UserOnline($message['client_id'], $message['uid']);
        }
    }

    /**
     * 用户断开连接 同步断开消息到中心服务器
     * @param $client_id
     */
    public static function UserClose($client_id)
    {
        self::SendToCentral($client_id, array(
            'event' => 'UserClose',
            'area' => GAME_AnBao,
            'client_id' => $client_id,
            'uid' => isset(self::$sUserList[$client_id]) ? self::$sUserList[$client_id] : -11111,
        ));

        if (isset(self::$sUserList[$client_id])) {
            $uid = self::$sUserList[$client_id];
            //通知游戏玩家离线
            if (isset(self::$nUserList[$uid])) {
                $rid = self::$nUserList[$uid];
                self::$RoomList[$rid]->Userclose($uid);
            }
            unset(self::$sUserList[$client_id]);
        }
    }

    /**
     * 玩家登陆
     */
    public static function Msg_Hall_Connect($client_id, $message)
    {
        $count = 0;
        $randId = 668097;
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
                'rcard' => $user['rcard'], //门票
                'card' => $user['rcard'], //门票
                'sex' => $user['sex'], //性别 1男2女
                'name' => $user['nickname'],
                'nickname' => $user['nickname'],
                'head' => $user['headimgurl'],
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
     * 玩家返回
     */
    public static function Msg_Hall_GameQuit($client_id, $message)
    {
        if (isset(self::$nUserList[$message['uid']])) {
            unset(self::$sUserList[self::$nUserList[$message['uid']]['client_id']]);
            unset(self::$nUserList[$message['uid']]);
        }

        self::SendToUser($client_id, $message);
    }

    /**
     * 断开该玩家
     */
    public static function Msg_Hall_BreakPlayer($client_id, $message)
    {
        self::SendToUid($message['uid'], $message);
        if ($message['data']['state'] == 1) {
            Gateway::unbindUid($message['client_id'], $message['uid']);
        }
    }

    /**
     * 玩家心跳
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_Heart($client_id, $message)
    {
        $message['status'] = 1;
        $message['uid'] = isset(self::$sUserList[$client_id]) ? self::$sUserList[$client_id] : -11111;
        self::SendToClient($client_id, $message);
        return;
    }
}