<?php
/**
 * Created by PhpStorm.
 * User: 龚坤
 * Date: 2018/9/11
 * Time: 11:47
 */

use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;
use \GatewayWorker\Lib\Gateway;

define('DELEROOM' , 900);
class Central
{
    /**
     * 当前平台
     */
    public static $isWin;

    /**
     * 大厅服务器列表
     * {businessid => $connection，大厅服连接对象2，...}
     * @var array
     */
    public static $HallList = array();


    /*解散房间*/
    public static $JieRoomid = [];
    /**
     * 逻辑服列表
     * $LogicList['游戏类型'] = array(
     *       '$businessid' => [
     *           'roomlen' => 0, //房间数
     *           'connection' => '$connection' //null连接断开 Connection对象
     *       ],
     *   );
     * @var array
     */
    public static $LogicList = array();

    public static $Table = array();
    /**
     * 附属中心服列表
     * $CentralList['$businessid'] = array(
     *           'connection' => '$connection' //null连接断开 Connection对象
     *   );
     * @var array
     */
    public static $CentralList = array();

    /**
     * 用户列表
     * $nUserList['uid'] = array(
     *      'uid' => 0,
     *      'client_id' => 0,
     *      'name' => '',
     *      'head' => '',
     *      'sex' => '',
     *      'ip' => '',
     *      'lat' => '',
     *      'lng' => '',
     *      ...
     * );
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
     * $RoomList['rid'] = array(
     *      'businessid' => 'businessid'
     *      'pnum' => 3, //房间内玩家人数上限 0是无限制人数
     *      'ruid' => '', //房主UID
     *      'rid' => '', //房间号 6位数字
     *      'rstage' => '', //房间状态 -1未使用 0等待中 1游戏中 2结算中
     *      'type' => 1,玩家开设的房间 ， 2机器人房间
     *      'low' => 最低入场携带金币
     * );
     * @var array
     */
    public static $RoomList = array();

    /*机器人房间列表
     * $RoomList['rid'] = array(
     *      'businessid' => 'businessid'
     *      'pnum' => 3, //房间内玩家人数上限 0是无限制人数
     *      'ruid' => '', //房主UID
     *      'rid' => '', //房间号 6位数字
     *      'rstage' => '', //房间状态 -1未使用 0等待中 1游戏中 2结算中
     *      'type' => 1,玩家开设的房间 ， 2机器人房间
     *      'low' => 最低入场携带金币
    */
    public static $RoomRotList = array();

    /*机器人用户表单*/
    public static $RotUserList = [];
    /*机器人表单*/
    public static $RoomRotListPlays = [];
    /*调度次数*/
    public static $Times = 0;

    /**
     * 用户所在房间
     * $UserRoom['uid'] = 'rid' //不可重复进房
     * $UserRoom['uid'] = array(['rid' => 0],['rid' => 1],...) //可重复进房
     * @var array
     */
    public static $UserRoom = array();



    /**
     * 进程房间数限制
     */
    public static $maxRoomNum = 50;

    /**
     * 房间号生成起始位
     * bcmod(bcpow($curRoomid, 29), 898837)
     * @var int
     */
    public static $curRoomid = 2;

    /**
     * 房间号生成参数（上限 / 指数）
     * @var int
     */
    public static $maxRoomid = 898837;
    public static $powIndex = 29;

    /**
     * 房间号回收队列 【房间号生成完之后才使用】
     * $curRoomidList = array(房间号1，房间号2，...)
     * 出队列 array_shift($curRoomidList)
     * 入队列 $curRoomidList[] = 房间号
     * @var array
     */
    public static $curRoomidList = array();



    //--------------------------中心服用--------------------------

    /**
     * 发送消息
     */
    public static function SendData($con, $data)
    {
        $con->send(json_encode($data));
        $sendstr = "uid : " . $data["uid"] . "        SendTo---" ;
        if(isset($data["event"]))
            echo $sendstr . $data["event"]. "\n";
        else
            echo $sendstr . "UnKnown \n";
    }

    /**
     * 发送消息
     */
    public static function SendToUid($data)
    {
        if (!empty(self::$HallList)) {
            foreach (self::$HallList as $key => $value) {
                self::SendData($value, $data);
                break;
            }
        }
    }



    /**
     * 转发到客户端
     */
    public static function SendToClient($connection, $message)
    {
        self::SendToUid($message);
    }

    /**
     * 发送消息到附属中心服
     */
    public static function SendToCentral($cname, $data)
    {
        if (!empty(self::$CentralList)) {
            foreach (self::$CentralList as $key => $value) {
                if ($cname == $key) {
                    self::SendData($value, $data);
                    break;
                }
            }
        }
    }



    /**
     * 生成房间号
     */
    public static function CreatedRoomID()
    {
        if (self::$curRoomid >= self::$maxRoomid) {
            return array_shift(self::$curRoomidList);
        }
        $ret = bcmod(bcpow(self::$curRoomid, self::$powIndex), self::$maxRoomid);
        self::$curRoomid += 1;
        return (int)$ret + 100000;
    }

    /**
     * 查找分配逻辑服
     * @return int|string
     */
    public static function FandRoom($game)
    {
        $businessid = '';

        if (self::$isWin) {
            foreach (self::$LogicList[$game] as $k => $val) {
                if ($val['connection'] && $val['roomlen'] < self::$maxRoomNum) {
                    $businessid = $k;
                    break;
                }
            }

            for ($i = 1; $i <= self::$maxRoomNum; $i++) {
                if (empty($businessid)) {
                    foreach (self::$LogicList[$game] as $k => $val) {
                        if ($val['connection'] && $val['roomlen'] < (self::$maxRoomNum + $i)) {
                            $businessid = $k;
                            break;
                        }
                    }
                }
                else {
                    break;
                }
            }
        }
        else {
            foreach (self::$LogicList[$game] as $k => $val) {
                if ($val['connection']  && $val['roomlen'] < self::$maxRoomNum) {
                    $businessid = $k;
                    break;
                }
            }
            if (empty($businessid)) {
                foreach (self::$LogicList[$game] as $k => $val) {
                    if ($val['connection']  && $val['roomlen'] < self::$maxRoomNum) {
                        $businessid = $k;
                        break;
                    }
                }
            }

            for ($i = 1; $i <= self::$maxRoomNum; $i++) {
                if (empty($businessid)) {
                    foreach (self::$LogicList[$game] as $k => $val) {
                        if ($val['connection']  && $val['roomlen'] < (self::$maxRoomNum + $i)) {
                            $businessid = $k;
                            break;
                        }
                    }
                }
                else {
                    break;
                }
            }
            for ($i = 1; $i <= self::$maxRoomNum; $i++) {
                if (empty($businessid)) {
                    foreach (self::$LogicList[$game] as $k => $val) {
                        if ($val['connection'] &&  $val['roomlen'] < (self::$maxRoomNum + $i)) {
                            $businessid = $k;
                            break;
                        }
                    }
                }
                else {
                    break;
                }
            }
        }

        return $businessid;
    }



    //修改状态
    public static function Upstate ($id , $message) {

        //修改房间状态
        self::$RoomList[$message['data']['rid']]['rstage'] = 2;

        return true;
    }

    /**
     * 分发 同步大厅列表到逻辑服
     */
    public static function NotifyHallList()
    {
        $msg = array(
            'event' => 'HallList',
            'area' => GAME_HALL,
            'list' => array_keys(self::$HallList),
            'uid' => -11111,
        );
        foreach (self::$LogicList as $key => $list) {
            $msg['area'] = $key;
            foreach ($list as $conn) {
                if ($conn['connection']) {
                    self::SendData($conn['connection'], $msg);
                }
            }
        }
    }

    /**
     * 大厅、逻辑服务器断开
     */
    public static function ClientClose($connection)
    {
        if (isset($connection->businessid)) {
            if (isset(self::$HallList[$connection->businessid])) {
                unset(self::$HallList[$connection->businessid]);
                self::NotifyHallList();
            } else {
                foreach (self::$LogicList as $list) {
                    if (isset($list[$connection->businessid])) {
                        $list[$connection->businessid]['connection'] = null;
                        if ($list[$connection->businessid]['roomlen'] == 0) {
                            unset($list[$connection->businessid]);
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 大厅注册
     * @param $connection
     * @param $message
     * @return mixed
     */
    public static function HallRegister($connection, $message)
    {
        $msg = array(
            'event' => 'HallRegister',
            'area' => GAME_HALL,
            'msg' => 'Hall Register Success',
            'status' => 1, //0失败 1成功
            'uid' => -11111,

        );
        if (!isset($message['businessid'])) {
            $msg['msg'] = '参数错误';
            $msg['status'] = 0;
            return $connection->close(json_encode($msg));
        }
        if (isset(self::$HallList[$message['businessid']])) {
            $data['msg'] = '已登录';
            $data['status'] = 0;
            return $connection->close(json_encode($msg));
        }

        $connection->businessid = $message['businessid'];
        self::$HallList[$message['businessid']] = $connection;
        self::SendData($connection, $msg);

        self::NotifyHallList();
    }

    /**
     * 逻辑服注册
     * @param $connection
     * @param $message
     * @return mixed
     */
    public static function LogicRegister($connection, $message)
    {

        $msg = array(
            'event' => 'LogicRegister',
            'area' => $message['area'],
            'msg' => 'Logic Register Success',
            'status' => 1, //0失败 1成功
            'uid' => -11111,
        );
        if (isset($connection->businessid)) {
            $msg['msg'] = '参数错误';
            $msg['status'] = 0;
            return $connection->close(json_encode($msg));
        }
        $game = $message['area'];
        $connection->businessid = $message['businessid'];
        if (!isset(self::$LogicList[$game][$message['businessid']])) {
            self::$LogicList[$game][$message['businessid']] = array(
                'roomlen' => 0,
                'connection' => $connection, //null连接断开 Connection对象
            );
        }
        else {
            self::$LogicList[$game][$message['businessid']]['connection'] = $connection;
        }
        self::SendData($connection, $msg);
        self::SendData($connection, array(
            'event' => 'HallList',
            'area' => $message['area'],
            'list' => array_keys(self::$HallList),
            'uid' => -11111,
        ));

        //如果有投注类游戏 需要在logic连接时开启
    }

    /**
     * 其他中心服注册
     * @param $connection
     * @param $message
     * @return mixed
     */
    public static function CentralRegister($connection, $message)
    {
        $msg = array(
            'event' => 'CentralRegister',
            'area' => $message['area'],
            'msg' => 'Central Register Success',
            'status' => 1, //0失败 1成功
            'uid' => -11111,
            'mission' => self::$dailymission,
        );
        if (!isset($message['businessid'])) {
            $msg['msg'] = '参数错误';
            $msg['status'] = 0;
            return $connection->close(json_encode($msg));
        }
        if (isset(self::$CentralList[$message['businessid']])) {
            $data['msg'] = '已登录';
            $data['status'] = 0;
            return $connection->close(json_encode($msg));
        }

        $connection->businessid = $message['businessid'];
        self::$CentralList[$message['businessid']] = $connection;
        self::SendData($connection, $msg);
    }



    //--------------------------用户消息处理--------------------------

    /**
     * 用户登录
     */
    public static function Msg_Hall_Connect($connection, $message)
    {
        $uid = $message['uid'];

        if (isset(self::$nUserList[$uid])) {
            self::SendToUid(array(
                    'event' => 'Msg_Hall_BreakPlayer',
                    'client_id' => self::$nUserList[$uid]['client_id'],
                    'uid' => $uid, //用户ID
                    'area' => GAME_HALL,
                    'status' => 1,
                    'data' => array(
                        'state' => '1',
                        'info' => '您的账号已在其他设备登陆',
                    ))
            );

            unset(self::$sUserList[self::$nUserList[$uid]['client_id']]);
            return true;
        }

        self::$nUserList[$uid] = $message['data'];
        self::$sUserList[$message['client_id']] = $uid;
        if (isset(self::$UserRoom[$uid])) {
            $message['data']['rid'] = self::$UserRoom[$uid];
        }

        //
        self::SendToUid($message);
        return true;
    }


    /*用户获得房间号*/
    public static function Msg_Hall_Rooms ($connection, $message) {
        $uid = $message['uid'];

        $msg = [
            'event' => 'Msg_Hall_Rooms',
            'uid' => $uid,
            'data' => [
                'creat' => [],
                'join' => [],
            ],
        ];
        if (isset(self::$UserRoom[$uid])) {
            foreach (self::$UserRoom[$uid] as $k => $v) {
                foreach ($v as $j => $p) {
                    if (!isset(self::$RoomList[$j]['ruid'])) {
                        continue;
                    }

                    $key = self::$RoomList[$j]['ruid'] == $uid ? 'creat' : 'join';
                    $useuid = ($key == 'creat' ? $uid : self::$RoomList[$j]['ruid']);
                    $msg['data'][$key][] = [
                        'gtype' => self::$RoomList[$j]['gtype'],
                        'rid' => $j,
                        'rule' => self::$RoomList[$j]['rule'],
                        'resTime' => self::$RoomList[$j]['resTime'],
                        'rname' => DBInstance::getUser('nickname' , $useuid),
                        'rheadImg' => DBInstance::getUser('headimgurl' , $useuid),
                    ];
                }
            }

        }

        self::SendToUid($msg);
    }



    /**
     * 用户断开
     */
    public static function UserClose($connection, $message)
    {
        //移除玩家
        if(isset(self::$sUserList[$message['client_id']])){

            DBInstance::setUserOffline(self::$sUserList[$message['client_id']]);
            /*删除桌子列表*/
            if (isset(self::$UserRoom[self::$sUserList[$message['client_id']]])) {
                if (isset(self::$RoomList[self::$UserRoom[self::$sUserList[$message['client_id']]]])) {
                    unset(self::$Table[self::$UserRoom[self::$sUserList[$message['client_id']]]]['plays'][self::$sUserList[$message['client_id']]]);
                }
            }


            if (isset($message['area']) && $message['area'] == GAME_AnBao) {

                //修改到数据库中
                // $back = MongoDBserver::select('ry_user' , array('uid' => self::$sUserList[$message['client_id']]))[0];

                // DBInstance::Uprcard(self::$sUserList[$message['client_id']] , $back['rcard'] , 3);
                // MongoDBserver::delete('ry_user' , array('uid' => self::$sUserList[$message['client_id']]));

//            unset(self::$UserRoom[self::$sUserList[$message['client_id']]]);
//            unset(self::$nUserList[self::$sUserList[$message['client_id']]]);
//            unset(self::$sUserList[$message['client_id']]);

            } else {


                unset(self::$UserRoom[self::$sUserList[$message['client_id']]]);
                unset(self::$nUserList[self::$sUserList[$message['client_id']]]);
                unset(self::$sUserList[$message['client_id']]);
            }
        }


    }



    //中心服收到解散房间的消息
    public static function Msg_GAME_DissolveNotice ($con , $message) {
        $msg = [
            'event' => 'Msg_GAME_DissolveNotice',
            'uid' => $message['uid'],
            'status' => 0,
            'msg' => '房间已结算或解散',
            'data' => array(),
        ];
        return $con->send(json_encode($msg));
    }

    //--------------------------好友房处理--------------------------

    /*创建机器人房间*/
    public static function RotAdd(){

        self::$Times++;
        Timer::add(Config::$AnBaoRot , function () {

            //一个小时,清空所有桌子，重新增加机器人
            if (is_int(self::$Times / 720)) {
                self::$RotUserList = [];
                self::$RoomRotList = [];
                self::$RoomRotListPlays = [];
            }
            if (empty(self::$RoomRotList)) {
                self::initial();
            } else {
//
//                $rand = rand(1,99);
//                if ($rand >= 60) {
//
//                    $rand1 = rand(1,count(self::$RoomRotList));
//                    for ($i = 1; $i < $rand1; $i++) {
//
//                        //随机删除第几号位的人
//                        $shanren = rand(0,self::$RoomRotList[$i]['pnum']);
//                        self::$RoomRotList[$i]['plays'][$shanren] = self::$RotUserList[0];
//                        unset(self::$RotUserList[0]);
//                        self::$RotUserList = array_values(self::$RotUserList);
//                    }
//                }

            }

            self::RotAdd();
        } , array() , false);
    }

    /*创建初始机器人房间数量*/
    public static function initial () {
        $suijinum = rand(6,9);
        $robnum = [10,13,16,18,20,26,32];
        for ($i = 1; $i < $suijinum; $i++) {
            $rid = self::CreatedRoomID();
            self::$RoomRotList[$rid] = [
                'businessid' => -1,
                'level' => rand(1,3),
                'gtype' => MyTools::$GameNameToId['GAME_AnBao'], //游戏类型
                'rstage' => 2, //1开始前 2已开始
                'rid' => $rid, //房间号 6位数字
                'ruid' => -1, //房主uid
                'pnum' => $robnum[rand(0,6)],//人数
                'rule' => [],
                'plays' => array(),//玩家数据
                'type' => 1,
                'low' => -1,
                'alllook' => 2,
                'resTime' => date('Y-m-d H:i:s' , time()+900),
            ];
        }

        self::$RotUserList = DBInstance::getRot();

        foreach (self::$RoomRotList as $k => $v) {
            for ($j = 0; $j < $v['pnum']; $j++) {
                if ($j == 0) {
                    self::$RoomRotList[$k]['ruid'] = self::$RotUserList[$j]['nickname'];
                }
                /*增加机器人桌子表单*/
                $val = self::$RotUserList[$j];

                if (count(self::$RoomRotList[$k]['plays']) < 6) {
                    self::$RoomRotList[$k]['plays'][$val['uid']] = $val;
                }

                /*增加机器人用户表单*/
                self::$RoomRotListPlays[] = self::$RotUserList[$j];
                unset(self::$RotUserList[$j]);
            }
            self::$RotUserList = array_values(self::$RotUserList);
        }
        return true;
    }

    /**
     * 创建好友房
     */
    public static function Msg_Hall_CreateRoom($connection, $message)
    {
        $uid = $message['uid'];
        if (!isset($message['data'])) {
            $msg['msg'] = '参数错误';
            self::SendToUid($msg);
            return true;
        }

        $data = $message['data'];
        $gtype = $message['data']['gtype'];


        $msg = array(
            'event' => 'Msg_Hall_CreateRoom',
            'area' => $data['gtype'],
            'msg' => '创建成功',
            'uid' => $uid,
            'status' => 0,
            'data' => array(),
        );


        // $back = !isset(self::$nUserList[$uid]);

        // if (empty($back)) {
        //     $msg['msg'] = '请先登录';
        //     self::SendToUid($msg);
        //     return true;
        // }


//        if (isset(self::$UserRoom[$message['uid']])) {
//            $msg['msg'] = '创建房间失败,已在其它游戏中';
//            self::SendToUid($msg);
//            return;
//        }
//         $can = true;
//         //判断是否在房间中
//         if (isset(self::$UserRoom[$message['uid']]) && isset(self::$UserRoom[$message['uid']])) {
// //            foreach (self::$UserRoom as $k => $v) {
// //                foreach ($v as $j => $p) {
// //                    if ($p == 1) {
// //                        if (self::$RoomList[$j]['gtype'] == $gtype) {
// //                            $can = false;
// //                            $yy = $j;
// //                            break;
// //                        }
// //
// //                    }
// //                }
// //            }
//             $can = false;

//         }

//         if (!$can) {
//             $msg['msg'] = '已在房间中';
//             $msg['data']['rid'] = self::$UserRoom[$message['uid']];
//             self::SendToUid($msg);
//             return true;
//         }

        if (empty(self::$LogicList[$gtype])) {
            $msg['msg'] = '创建房间失败,逻辑服未开启';
            self::SendToUid($msg);
            return true;
        }

        $businessid = self::FandRoom($gtype);
        if (empty($businessid)) {
            $msg['msg'] = '服务器人数已满';
            self::SendToUid($msg);
            return true;
        }
        $rid = self::CreatedRoomID();
        if (isset(self::$RoomList[$rid])) {
            $msg['msg'] = '创建房间失败,房间号获取失败';

            self::SendToUid($msg);
            return true;
        }

        if (!isset($data['pnum']) && empty($data['pnum'])) {
            $msg['msg'] = '没传pnum';
            self::SendToUid($msg);
            return true;
        }
        self::$RoomList[$rid] = array(
            'businessid' => $businessid,
            'gtype' => $gtype, //游戏类型
            'level' => $data['level'],
            'rstage' => 1, //1开始前 2已开始
            'rid' => $rid, //房间号 6位数字
            'ruid' => $uid, //房主uid
            'pnum' => $data['pnum']+50,// 游戏人数+观战人数
            'gamepnum' => $data['pnum'],
            'lookpnum' => 50,
            'rule' => $data,
            'plays' => array(),//玩家数据
            'resTime' => date('Y-m-d H:i:s' , time()+900),
            'gamestatus' => 0,
        );

        self::$Table[$rid] = self::$RoomList[$rid];

        self::$LogicList[$gtype][$businessid]['roomlen'] += 1;
//        self::RoomAddUser($uid, $rid);
        $msg['data']['rid'] = $rid;
        $msg['status'] = 1;
        self::SendToUid($msg);
        $msg['event'] = "Msg_Hall_CreateRoom";
        $msg['status'] = 1;
        $msg['data'] = [
            'rid' => $rid,
            'ruid' => $uid,
            'rule' => $data,
        ];
//        $msg['data']['plays'] = array_values($msg['data']['plays']);


        self::SendRoomBegin($rid , $data);
        return true;
    }

    public static function Game_Run ($connection, $message) {
        $rid = $message['data']['rid'];
        $status = $message['data']['status'];

        if (isset(self::$RoomList[$rid])) {
            self::$RoomList[$rid]['gamestatus'] = $status;
        }
        return true;
    }

    /**
     * 加入房间
     */
    public static function Msg_Hall_EnterRoom($connection, $message)
    {
        $uid = $message['uid'];
        $data = $message['data'];
        $msg = array(
            'event' => 'Msg_Hall_EnterRoom',
            'uid' => $uid,
            'status' => 0,
            'data' => array(),
        );

        if (isset(self::$RoomRotList[$data['rid']])) {
            $msg['msg'] = '当前房间不允许观战';
            self::SendToUid($msg);
            return true;
        }

        if (!isset(self::$RoomList[$data['rid']])) {
            $msg['msg'] = '房间已解散';
            self::SendToUid($msg);
            return true;
        }

        // $back = isset(self::$nUserList[$uid]) ? true : false;

//         if ($back) {
//             $msg['msg'] = '请先登录';
//             self::SendToUid($msg);
//             return true;
//         }


//        if (isset(self::$UserRoom[$uid])) {
//
//            if (!empty(self::$UserRoom[$uid])) {
//                $chonglian = false;
//                foreach (self::$UserRoom[$uid] as $k => $v) {
//                    foreach ($v as $j => $p) {
//                        if ($p == 1) {
//                            $rid = $j;
//                            $chonglian = true;
//                            break;
//                        }
//                    }
//                }
//
//                if ($chonglian) {
//                    //重连
////                    $rid = self::$UserRoom[$uid];
//                    $msg['event'] = 'UserOnline';
//                    $msg['area'] = self::$RoomList[$rid]['gtype'];
//                    $msg['client_id'] = self::$nUserList[$uid]['client_id'];
//                    $msg['uid'] = $uid;
//                    $msg['rid'] = $rid;
//
//
////                    self::SendEnterRoom($uid);
//                    self::SendData(self::$LogicList[self::$RoomList[$rid]['gtype']][self::$RoomList[$rid]['businessid']]['connection'] , $msg);
//                    return true;
//                }
//            }
//        }

        $rid = $data['rid'];
        if (!isset(self::$RoomList[$rid])) {
            $msg['msg'] = '房间号错误';
            self::SendToUid($msg);
            return true;
        }

        /*判断是否满足最低携带金币数量*/
        $rcard = DBInstance::getUser('gold' , $uid);
        if ($rcard < self::$RoomList[$rid]['rule']['low']) {
            $msg['msg'] = '携带金额不满足房间最小金额 , 当前房间为' . self::$RoomList[$rid]['low'];
            self::SendToUid($msg);
            return true;
        }

        if (self::$RoomList[$rid]['rule']['hight'] == 2) {
            $msg['msg'] = '当前房间不允许观战';
            self::SendToUid($msg);
            return true;
        }

        $room = self::$RoomList[$rid];
        $plays = $room['plays'];
        if (count($plays) >= $room['pnum']) {
            $msg['msg'] = '房间人数已满';
            self::SendToUid($msg);
            return true;
        }

        self::RoomAddUser($uid, $rid);

        $msg = array(
            'event' => 'Msg_Hall_EnterRoom',
            'uid' => $uid,
            'status' => '1',
            'msg' => '成功',
            'data' => array(
                'rid' => $rid,
                'client_id' => self::$nUserList[$uid]['client_id'],
            ),
        );

        self::SendData(self::$LogicList[self::$RoomList[$rid]['gtype']][self::$RoomList[$rid]['businessid']]['connection'], $msg);
        return true;
    }

    public static function TablesTool ($Tables , $level) {
        $arr = [];
        if (!empty($Tables)) {
            foreach ($Tables as $k => $v) {
                if (empty($v['plays'])) {
                    continue;
                }
                $uu = [];
                $rt = array_values($v['plays']);
                foreach ($rt as $k1 => $v1) {
                    if (count($uu) == 6) {
                        break;
                    }
                    $uu[] = $v1['headimgurl'];
                }
                if ($level == 0) {
                    $arr[] = [
                        'rid' => $v['rid'],
                        'plays' => $uu,
                    ];
                } else {
                    if ($level == $v['level']) {
                        $arr[] = [
                            'rid' => $v['rid'],
                            'plays' => $uu,
                        ];
                    }
                }
            }
        }
        return $arr;
    }

    /*获得桌子列表*/
    public static function Msg_Hall_GetAllTables ($connection, $message) {
        //整理数组
        $level = $message['data']['level'];

        $arr = [];
        var_dump(self::$Table);
        if (!empty(self::$Table)) {
            $arr = self::TablesTool(self::$Table , $level);
        }

        if (!empty(self::$RoomRotList)) {
            $arrrr = self::TablesTool(self::$RoomRotList , $level);
            foreach ($arrrr as $k => $v) {
                $arr[] = $v;
            }
        }



        $msg = array(
            'event' => 'Msg_Hall_GetAllTables',
            'uid' => $message['uid'],
            'status' => 1,
            'msg' => '查询成功',
            'data' => $arr,
        );

        self::SendToUid($msg);
        return true;
    }

    //--------------------------点击快速匹配桌子列表--------------------------
    public static function Msg_Hall_QuickTool ($connection, $message) {

        $uid = $message['uid'];
        if (!empty(self::$Table)) {
            foreach (self::$Table as $k => $v) {
                $yy[] = $k;
            }
        }

        $msg = [
            'event' => 'Msg_Hall_QuickTool',
            'uid' => $uid,
            'status' => 1,
            'data' => [],
        ];
        if (!empty($yy)) {
            $rand = rand(0,count($yy) - 1);
            $msg['data']['rid'] = $yy[$rand];
        } else {
            $msg['status'] = 0;
            $msg['msg'] = '没有合适房间';
        }
        self::SendToUid($msg);
        return true;
    }

    /*获得成员列表*/
    public static function Msg_Hall_GetAllUser ($connection, $message) {
        $uid = $message['uid'];
        $back = [];

        foreach (self::$nUserList as $key => $value) {
            $back[] = [
                'nickname' => $value['nickname'],
                'headimgurl' => $value['headimgurl'],
                'gold' => $value['gold'],
            ];
        }

        if (!empty(self::$RoomRotListPlays)) {
            foreach (self::$RoomRotListPlays as $key => $value) {
                $back[] = [
                    'nickname' => $value['nickname'],
                    'headimgurl' => $value['headimgurl'],
                    'gold' => $value['gold'],
                ];
            }
        }

        $msg = [
            'event' => 'Msg_Hall_GetAllUser',
            'status' => 1,
            'uid' => $uid,
            'data' => $back,
        ];
        self::SendToUid($msg);
        return true;
    }








    //--------------------------其他处理--------------------------

    /**
     * 房间内玩家断线
     */
    public static function UserOffline($connection, $uid)
    {
        if(!isset(self::$UserRoom[$uid]))
            return false;

        $rid = self::$UserRoom[$uid];

        if(!isset(self::$RoomList[$rid]))
            return false;

        $room = self::$RoomList[$rid];

        if($room['rstage'] == 2)
            return false;

        $msg = array(
            'event' => 'Msg_Hall_Quit',
            'area' => GAME_HALL,
            'uid' => $uid,
            'status' => 1,
            'data' => array(),
        );

        $msg['data'] = array('uid' =>  $uid);
        foreach ( $room['plays'] as $val)
        {
            $msg['uid'] = $val['uid'];
            self::SendToUid($msg);
        }

        if($room['ruid'] == $uid)
        {
            $game = $room['gtype'];
            foreach ($room['plays'] as $val){
                if(isset(self::$UserRoom[$val['uid']])){
                    unset(self::$UserRoom[$val['uid']]);
                }
            }

            if(isset(self::$LogicList[$game][$connection->businessid])){
                self::$LogicList[$game][$connection->businessid]['roomlen'] -= 1;
            }

            unset(self::$RoomList[$rid]);

            self::$curRoomidList[] = $rid;
        }
        else{
            if(isset(self::$UserRoom[$uid])){
                unset(self::$UserRoom[$uid]);
            }
            unset(self::$RoomList[$rid]['plays'][$uid]);
        }
        return true;
    }

    /**
     * 退出
     */
    public static function Msg_Hall_Quit($connection, $message)
    {
        $uid = $message['uid'];
        $rid = $message['data']['rid'];

        $msg = array(
            'event' => 'Msg_Hall_Quit',
            'area' => GAME_HALL,
            'uid' => $uid,
            'status' => 0,
            'data' => array(),
        );

        if (!isset(self::$nUserList[$uid])) {
            $msg['msg'] = '请先登录';
            self::SendToUid($msg);
            return;
        }

        if (!isset(self::$RoomList[$rid])) {
            $msg['msg'] = '房间不存在';
            self::SendToUid($msg);
            return;
        }

        $room = self::$RoomList[$rid];

        if(!isset($room['plays'][$uid])){
            $msg['msg'] = '参数错误';
            self::SendToUid($msg);
            return;
        }

        if($room['rstage'] == 2){
            $msg['msg'] = '游戏已开始';
            self::SendToUid($msg);
            return;
        }

        $msg['status'] = 1;
        $msg['data'] = array('uid' =>  $uid);
        foreach ( $room['plays'] as $val) {
            $msg['uid'] = $val['uid'];
            self::SendToUid($msg);
        }

        if ($room['ruid'] == $uid) {
            $game = $room['gtype'];
            foreach ($room['plays'] as $val) {
                if(isset(self::$UserRoom[$val['uid']])){
                    unset(self::$UserRoom[$val['uid']]);
                }
            }

            if (isset(self::$LogicList[$game][$connection->businessid])) {
                self::$LogicList[$game][$connection->businessid]['roomlen'] -= 1;
            }

            unset(self::$RoomList[$rid]);

            self::$curRoomidList[] = $rid;
        } else {
            if (isset(self::$UserRoom[$uid])) {
                unset(self::$UserRoom[$uid]);
            }
            unset(self::$RoomList[$rid]['plays'][$uid]);
        }
    }

    /**
     * 用户进入房间
     */
    private static function RoomAddUser($uid, $rid)
    {
        $back = self::$nUserList[$uid];
        self::$UserRoom[$uid] = $rid;
        self::$RoomList[$rid]['plays'][$uid] = $back;
        self::$Table[$rid]['plays'][$uid] = $back;
        return true;
    }



    /**
     * 回收房间
     */
    private static function RoomOldReal($message)
    {
        $rid = $message['data']['rid'];
        $uids = $message['data']['uids'];
        if (isset(self::$RoomList[$rid])) {
            $room = self::$RoomList[$rid];
            $game = $room['gtype'];

            foreach ($uids as $k => $v) {
                if (isset(self::$UserRoom[$v])) {
                    unset(self::$UserRoom[$v]);
                }
            }

            if(isset(self::$LogicList[$game][$room['businessid']]))
            {
                self::$LogicList[$game][$room['businessid']]['roomlen'] -= 1;
            }

            unset(self::$RoomList[$rid]);
            unset(self::$Table[$rid]);
            self::$curRoomidList[] = $rid;
        }
    }

    /**
     *房间回收
     */
    public static function RoomOld($connection, $message)
    {
        $rid = $message['data']['rid'];
        if (!isset(self::$RoomList[$rid])){
            return;
        }
        self::RoomOldReal($message);
    }



    public static function Msg_UserListDown ($connection, $message) {
        $rid = $message['data']['rid'];
        $uid = $message['data']['uid'];

        if (!isset(self::$RoomList[$rid])) {
            return true;
        }

        if (isset(self::$RoomList[$rid]['plays'][$uid])) {
            unset(self::$RoomList[$rid]['plays'][$uid]);
            unset(self::$Table[$rid]['plays'][$uid]);
        }
        if (isset(self::$UserRoom[$uid])) {
            unset(self::$UserRoom[$uid]);
        }
        return true;
    }

    /*处理玩家是否有金币在redis中，并处理掉*/
    public static function Msg_UpdateGold ($connection, $message) {
        $uid = $message['data']['uid'];
        $gold = $message['data']['gold'];
        if (Config::$openredis) {
            $list = RedisServer::GetHash('user');

            if (isset($list[$uid])) {
                $gold = $gold + $list[$uid];
                RedisServer::DelHash('user' , $uid);
            }

        }
        DBInstance::Uprcard($uid , $gold , 3);
        return true;
    }




    //--------------------------逻辑服相关--------------------------

    /**
     * 开始房间
     */
    public static function SendRoomBegin($rid , $message)
    {
        if(!isset(self::$RoomList[$rid]))
            return;

        $room = self::$RoomList[$rid];

        $roombeg['event'] = 'RoomBegin';
        $roombeg['area'] = $room['gtype'];
        $roombeg['uid'] = self::$RoomList[$rid]['ruid'];
        $roombeg['data'] = array(
            'rid' => $rid, //房间号 6位数字
            'gtype' => $room['gtype'],
            'rule' => $room['rule'],
        );

        self::SendData(self::$LogicList[$room['gtype']][$room['businessid']]['connection'], $roombeg);
    }

    /**
     * 客户端打开游戏界面
     */
    public static function SendEnterRoom($uid)
    {
        if (!isset(self::$UserRoom[$uid])) {
            return;
        }

        $rid = self::$UserRoom[$uid];
        if (!isset(self::$RoomList[$rid])) {
            return;
        }

        if (self::$RoomList[$rid]['plays'][$uid]['robot'] == 1) {
            return;
        }

        $room = self::$RoomList[$rid];
        $gtype = $room['gtype'];

        $msg['event'] = 'Msg_Hall_EnterRoom';
        $msg['area'] = GAME_HALL;
        $msg['uid'] = $uid;
        $msg['status'] = 1;
        $msg['data'] = array(
            'rid' => $rid,
            'gtype' => $gtype,
            'rtype' => $room['rtype'],
            'level' => $room['level'],
            'pnum' => $room['pnum'],
        );

        foreach ($room['plays'] as $k =>$val) {
            $arr = DBInstance::getGameUseID($k, $gtype);
            $msg['data']['uids'][] = array(
                'uid' => $k,
                'aid' => $arr,
            );
        }

        self::SendData(self::$LogicList[$gtype][$room['businessid']]['connection'], $msg);
    }


    /**
     *玩家返回
     */
    public static function UserQuit($connection, $message)
    {
        if (isset(self::$UserRoom[$message['uid']])) {
            unset(self::$UserRoom[$message['uid']]);
        }
    }


    /*刷新房卡*/
    public static function Msg_Hall_UpdateCard ($client, $message) {

        $uid = $message['uid'];
        // $val = MongoDBserver::select('ry_user' , array('uid' => $uid))[0];
        $gold = (array)DBInstance::getUser('*' , $uid);
        $msg = [
            'event' => 'Msg_Hall_UpdateCard',
            'uid' => $uid,
            'data' => [
                'gold' => $gold['gold'],
            ],
        ];
        self::SendToUid($msg);
//        $client->send(json_encode($msg));
        return true;
    }



}