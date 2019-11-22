<?php
/**

 */
date_default_timezone_set("Asia/Shanghai");
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_WAIT', 0); //等待开始
define('STAGE_WAITGO', 1); //等待开始
define('STAGE_BANKER', 2); //定庄
define('STAGE_Bets', 3); //下注
define('STAGE_Open', 4); //开赢边
define('STAGE_check', 5); //一圈结算

define('STAGE_JS', 6); //解散阶段
define('STAGE_Check_END', 7); //总结

define('USERONLINE' , true);
define('USEROFF' , false);

define('Roomold' , 600);
define('waitzhuang' , 10);
define('XiaZhuTime' , 20);
define('JS' , 30);
define('WaitJs' , 180);
define('Wait_check' , 6);

class Room
{
    //房间解散定时器
    private $Time_id;
    //座位号对应uid
    private $m_Seat = array();
    //房间规则
    private $m_GR ;
    //游戏状态 0等待开始 1对战 2总结算 3结束
    private $m_GameStatus = STAGE_WAIT;
    //房主uid
    private $roomuid;
    //房间id
    private $roomid;
    //用户表单
    private $m_UserList = array();
    //庄家uid
    private $Banker_uid = -1;

    //抢庄表单
    private $m_RushV = array();

    //下注表单
    private $m_Bets = array();
    private $Table = [];
    private $m_CurCircle = 0;       //当前圈数
    private $m_banker;

    /*上次阶段*/
    public $LastgameStatus;
    /*本局出的大小id*/
    public $BenNum = -1;
    /*出现列表*/
    public $BenList = [];

    /*本局血池*/
    public $XueChi = 0;

    /*当前下注总额*/
    public $TableList = 0;

    /*解散id*/
    public $JsId;
    /*对用赢的点数*/
    public $DuiYingList = [
        //龍
        21 => [
            'win' => [
                4,5,9,10,12,13,14,21
            ],
            'anquan' => [
                1,11,18
            ],
        ],
        //出
        22 => [
            'win' => [
                1,2,3,5,6,7,19,22
            ],
            'anquan' => [
                4,8,15
            ],
        ],
        //虎
        23 => [
            'win' => [
                7,8,9,11,12,16,17,23
            ],
            'anquan' => [
                3,10,20
            ],
        ],
        //入
        24 => [
            'win' => [
                2,14,15,16,18,19,20,24
            ],
            'anquan' => [
                6,13,17
            ],
        ],

    ];
    /**
     * 构造
     */
    public function __construct($msg)
    {

        $this->m_banker = $msg['data']['rule']['banker'];
        $msg['data']['rule']['rid'] = $msg['data']['rid'];
        $this->m_rule = $msg['data']['rule'];
//        $msg['data']['rule']['rid'] = $msg['data']['rid'];
        $this->m_GameStatus = STAGE_WAIT;
        $this->roomuid = $msg['uid'];
        $this->roomid = $msg['data']['rid'];
        $this->beginTime = time();

        for ($i=0; $i < $this->m_rule['pnum']; $i++) {
            $this->m_Seat[$i] = 0;
        }
        $this->JsId = Timer::add(WaitJs , function () {
            if ($this->m_CurCircle == 0) {
                $this->OldRoom();
            }
        } , array() , false);

    }

    /**
     * 阶段跳转
     */
    private function Game_RUN($gamestage , $time = -1)
    {
        $this->m_GameStatus = $gamestage;
        $this->GameState($time);

        switch ($this->m_GameStatus) {
            case STAGE_WAIT:
                break;
            case STAGE_WAITGO:
                $this->BANKER();
                break;
            case STAGE_BANKER:
//                $this->BANKER();
                break;
            case STAGE_Bets:
//                $this->Bets();
                break;
            case STAGE_check:
                $this->Check();
                break;
            case STAGE_Open:
                $this->Open();
                break;
            case STAGE_JS:
                $this->OldRoom();
                break;
            default:
                break;
        }

        //上报中心服务器当前房间阶段
        Logic::SendToCentral(0 , array(
            'event' => 'Game_Run',
            'area' => GAME_AnBao,
            'uid' => -11111,
            'data' => array(
                'rid' => $this->roomid,
                'status' => $this->m_GameStatus,
            ),
        ));
        return true;
    }

    /**
     * 所有消息回调
     */
    public function All_RECV($client_id, $message)
    {
        $event = $message['event'];
        switch ($event) {
            case 'Msg_AnBao_UserAct':
                $this->UserAct($message);
                break;
            case 'Msg_GAME_SYNC':
                $this->RoomInfo($message);
                break;
            case 'Msg_Hall_UpdateCard':
                $this->UpdateCard($message);
                break;
            case 'Msg_GAME_OutRoom':
                $this->OutRoom($message);
                break;
            case 'Msg_AnBao_GetUserList':
                $this->GetUserList($message);
                break;
            default:
                {
                    $this->sendError($message['uid'],$message['event'],'');
                    echo 'uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'] . "\n";
                    break;
                }
        }
    }

    //玩家操作
    public function UserAct ($message) {
        $uid = $message['uid'];
        $num = $message['data']['num'];
        $act = $message['data']['act'];

        if ($act <= 2) {
            $this->StandDown($act , $num , $uid);
            return true;
        }

        $msg = [
            'event' => 'Msg_AnBao_UserAct',
            'data' => [
                'act' => $act,
                'num' => $num,
                'uid' => $uid,
            ],
        ];
        if ($act == 4) {
            self::SendAll($msg);
            $this->Rush($act , $num , $uid);
            return true;
        }
        //下注
        if ($act == 5) {
            $type = $message['data']['type'];
            $posi = $message['data']['posi'];
            $this->Bets($act , $num , $uid , $type , $posi);
            return true;
        }
    }

    public function StandDown ($act , $num , $uid) {

        $num = -1;
        foreach ($this->m_Seat as $k => $v) {
            if ($v == 0) {
                $num = $k;
                break;
            }
        }
        if ($num == -1) {
            self::sendError($uid , 'Msg_AnBao_UserAct' , '当前游戏座位已满');
            return true;
        }

        $msg = [
            'event' => 'Msg_AnBao_UserAct',
            'data' => [
                'uid' => $uid,
                'act' => $act,
                'num' => $num,
                'seat' => $num,
            ],
        ];
        if ($act == 1) {


            if ($this->m_UserList[$uid]['gold'] < $this->m_rule['low']) {
                self::sendError($uid , 'Msg_AnBao_UserAct' , '携带金币不能坐下');
                return true;
            }

            if (isset($this->m_Seat[$num]) && $this->m_Seat[$num] != 0) {
                self::sendError($uid , 'Msg_AnBao_UserAct' , '座位已经有人了');
                return true;
            }

            // if ($this->m_GameStatus > STAGE_WAITGO and $this->m_GameStatus < STAGE_check) {
            //     self::sendError($uid , 'Msg_AnBao_UserAct' , '游戏中不能坐下');
            //     return true;
            // }

        } else {
            if ((!empty($this->m_Bets) && isset($this->m_Bets[$uid])) || isset($this->m_RushV[$uid])) {
                $this->sendError($uid , 'Msg_AnBao_UserAct' , '参与游戏中不能退出');
                return true;
            }

        }

        $act == 1 ? $this->m_UserList[$uid]['state'] = 1 : $this->m_UserList[$uid]['state'] = -1;
        $act == 1 ? $this->m_UserList[$uid]['index'] = $num : $this->m_UserList[$uid]['index'] = -1;
        $act == 1 ? $this->m_Seat[$num] = $uid : $this->m_Seat[$num] = 0;

        $info = $this->Roominfo($act);
        $msg['data']['TablePlayers'] = $info;

        $this->SendAll($msg);


        $rennum = 0;
        foreach ($this->m_Seat as $k => $v) {
            if ($v != 0) {
                $rennum++;
            }
        }

        if ($rennum >= 2 && $this->m_GameStatus < 2) {

            $this->Game_RUN(STAGE_WAITGO);
        }


        return true;
    }

//    public function Ready($act , $num , $uid) {
//
//
//        $this->m_UserList[$uid]['state'] = 1;
//
//        $can = true;
//        $readyperson = 0;
//        foreach ($this->m_UserList as $k => $v) {
//            if ($v['state'] >= 0) {
//                if ($v['state'] == 1) {
//                    $readyperson++;
//                } else {
//                    $can = false;
//                    break;
//                }
//            }
//        }
//
//        if (!$this->canready()) {
//            self::sendError($uid , 'Msg_AnBao_UserAct','请等待本局结束');
//            return true;
//        };
//
//        $msg = [
//            'event' => 'Msg_AnBao_UserAct',
//            'status' => 1,
//            'msg' => '成功',
//            'data' => [
//                'uid' => $uid,
//                'seat' => $this->m_UserList[$uid]['index'],
//                'act' => $act,
//                'num' => $num,
//            ],
//        ];
//
//
//        $info = $this->RoomInfo($act);
//        $msg['data']['TablePlayers'] = $info;
//        self::SendAll($msg);
//
//
//        //告诉大厅修改游戏状态
//
//        if ($readyperson >= 2 && $can) {
//            $this->m_RushV = array();
//            $this->m_Bets = array();
//            $this->m_CurCircle++;
//
//            foreach ($this->m_UserList as $k => $v) {
//
//                if ($v['state'] == 1) {
//                    $this->m_UserList[$k]['state'] = 2;
//                }
//                $this->m_UserList[$k]['beiShu'] = -1;
//            }
//            $this->Game_RUN(STAGE_FAPAI);
//
//        }
//        return true;
//    }
//
//    //是否能准备
//    public function canready () {
//        $go = true;
//        if ($this->m_GameStatus > 1) {
//            $go = false;
//        }
//        return $go;
//    }



    public function wait () {

        $zoushi = [
            'event' => 'Msg_AnBao_ZouShi',
            'data' => [
                'new' => $this->BenNum,
            ],
        ];
        $this->SendAll($zoushi);

        $this->BenNum = -1;
        $this->m_Bets = [];
        $this->m_RushV = [];
        $this->TableList = 0;
        $this->Table = [];
        $this->XueChi = 0;
        if ($this->m_banker == 1 || !isset($this->m_UserList[$this->Banker_uid])) {
            $this->Banker_uid = -1;
        }

        $this->BANKERTOOL();
        return true;
    }

    public function BANKERTOOL () {
        foreach ($this->m_UserList as $k => $v) {
            if ($v['isOnline'] == USEROFF) {
                if (isset($this->m_UserList[$k])) {
                    $this->m_Seat[$this->m_UserList[$k]['index']] = 0;
                    $this->UpdateGold($k);
                    $this->UserListDown($k);
                    unset($this->m_UserList[$k]);
                }
            }
        }
    }

    //定庄
    public function BANKER () {

        $this->BANKERTOOL();

        //1抢庄 2固定坐庄
        if ($this->m_banker == 1) {
            $this->ZhuangTool();
        } else {

            if ($this->m_CurCircle != 0) {
                if (!isset($this->m_UserList[$this->Banker_uid])) {
                    $this->ZhuangTool();
                    return true;
                }
                if ($this->m_UserList[$this->Banker_uid] < $this->m_rule['low']) {
                    $this->ZhuangTool();
                } else {
                    $this->m_RushV[$this->Banker_uid] = 1;
                    $this->zhuang();
                    //清空上一盘下注的人
                    $this->XiaZhu();
                }
            } else {
                $this->ZhuangTool();
            }
        }

        return true;
    }

    public function ZhuangTool () {
        $uids = [];
        foreach ($this->m_UserList as $k => $v) {
            if (in_array($k , $this->m_Seat) && $v['gold'] >= $this->m_rule['low']) {
                $uids[] = $k;
            }
        }
        $num = 0;
        foreach ($this->m_Seat as $key => $value) {
            if ($value != 0) {
                $num++;
            }
        }
        Timer::del($this->Time_id);
        if (empty($uids) || $num < 2) {
            $this->Time_id = Timer::add(JS , function (){
                $this->Game_RUN(STAGE_JS);
            } , array() , false);
            //30秒后准备解散房间
            return true;
        } else {

            Timer::del($this->JsId);
            if ($this->m_CurCircle == 0) {
                $this->sendCenter();
            }
            $this->m_CurCircle++;

            $this->Game_RUN(STAGE_BANKER,waitzhuang);
            $msg = [
                'event' => 'Msg_AnBao_NotifyBanker',
                'status' => 1,
                'data' => [],
            ];
            foreach ($uids as $k => $v) {
                $this->m_RushV[$v] = -1;
            }
            foreach ($uids as $k => $v) {
                $this->SendToUid($v , $msg);
            }
            $this->Time_id = Timer::add(waitzhuang , function () {
                $this->Rush();
            } , array() , false);
            return true;
        }
    }

    public function XiaZhu () {
        $this->m_Bets = array();
        foreach ($this->m_UserList as $k => $v) {
            if (in_array($k , $this->m_Seat) && $k != $this->Banker_uid) {
                $this->m_Bets[$k] = [];
            }
        }

        $this->Game_RUN(STAGE_Bets , XiaZhuTime);
        $this->Time_id = Timer::add(XiaZhuTime , function () {
            $this->Game_RUN(STAGE_Open);
        } , array() , false);
    }
    //抢庄
    public function Rush ($act = 0 , $num = 0 , $uid = 0) {

        if ($uid) {
            if (isset($this->m_RushV[$uid])) {
                $this->m_RushV[$uid] = $num;
            }

            $can = in_array(-1 , $this->m_RushV) ? false : true;

            if (!$can) {
                return true;
            }
            Timer::del($this->Time_id);
        } else {
            if (!in_array(1 , $this->m_UserList)) {
                foreach ($this->m_RushV as $k => $v) {
                    if ($v == -1) {
                        $this->m_RushV[$k] = 1;
                    }
                }
            }

            $can = true;
        }

        if ($can) {
            $this->zhuang();
            //清空上一盘下注的人
            $this->XiaZhu();
        }
        return true;
    }


    //下注
    public function Bets ($act , $num , $uid , $type , $posi) {

        if (!in_array($uid , $this->m_Seat)) {
            $val = [
                'event' => 'Msg_AnBao_UserAct',
                'status' => 0,
                'msg' => '无效操作',
                'data' => [
                    'act' => $act,
                    'num' => $num,
                    'type' => $type,
                    'uid' => $uid,
                ],
            ];
            $this->SendAll($val);
            return true;
        }
        if ($this->m_UserList[$uid]['teshu'] == 1 && $this->BenNum == -1) {
            if (in_array($type , [21,22,23,24])) {
                $this->BenNum = $type;
            }
        }
        $msg = [
            'event' => 'Msg_AnBao_UserAct',
            'status' => 1,
            'data' => [
                'act' => $act,
                'num' => $num,
                'type' => $type,
                'uid' => $uid,
                'posi' => $posi,
            ],
        ];

        if ($this->m_UserList[$uid]['gold'] - $num >= 0) {

            $xuechi = $this->CheckTool($type , $num);

            if (($xuechi + $this->XueChi) > $this->m_UserList[$this->Banker_uid]['gold']) {
                $msg['status'] = 0;
                $msg['msg'] = '血池不足';
                $this->SendToUid($uid , $msg);
                return true;
            }

            $this->XueChi += $xuechi;
            $this->m_UserList[$uid]['gold'] -= $num;
        } else {
            $msg['status'] = 0;
            $msg['msg'] = '金币不足';
            $this->SendToUid($uid , $msg);
            return true;
        }
        if (isset($this->m_Bets[$uid][$type])) {
            $this->m_Bets[$uid][$type] += $num;
        } else {
            $this->m_Bets[$uid][$type] = $num;
        }
        $this->TableList += $num;

        $this->Table[$type][] = $num;

        $this->SendAll($msg);
        return true;
    }

    //当局出的点数
    public function Open() {

        if ($this->BenNum == -1) {
            $win = rand(21,24);
            $this->BenNum = $win;
        } else {
            $win = $this->BenNum;
        }

        $msg = [
            'event' => 'Msg_AnBao_Open',
            'status' => 1,
            'data' => [
                'id' => $win,
                'xianshi' => $this->DuiYingList[$win],
            ],
        ];
        $this->BenList[] = $win;
        $this->SendAll($msg);
        Timer::add(Wait_check , function () {
            $this->Game_RUN(STAGE_check);
        } , array() , false);

    }

    public function CheckTool ($type , $num) {

        $longhuchuru = [21,22,23,24];
        $jiaochuan = [2,5,7,9,12,14,16,19];
        $tongzhan = [1,3,4,6,8,10,11,13,15,17,18,20];

        if (in_array($type , $longhuchuru)) {
            $xuechi = $num * $this->m_rule['longhuchuru'];
        } elseif (in_array($type , $jiaochuan)) {
            $xuechi = $num * $this->m_rule['jiaochuan'];
        } elseif (in_array($type , $tongzhan)) {
            $xuechi = $num * $this->m_rule['tongzhan'];
        }
//        $xuechi = (number_format($xuechi,2));
        var_dump($xuechi);
        return $xuechi;
    }

    /*增加战绩*/
    public function addHistory ($value) {
        if (empty($value)) {
            return true;
        }

        $hit = [
            'rid' => $this->roomid,
            'uid' => '',
            'win' => [],
            'openid' => $this->BenNum,
            'rule' => json_encode($this->m_rule),
            'created' => date('Y-m-d H:i:s'),
        ];

        foreach ($value as $k => $v) {
            $num = 0;
            if (!empty($this->m_Bets[$k])) {
                foreach ($this->m_Bets[$k] as $k1 => $v1) {
                    $num += $v1;
                }
            } else {
                continue;
            }

            $xialist = !empty($this->m_Bets[$k]) ? $this->m_Bets[$k] : [];

            $hit['uid'] = $k;
            $hit['win'] = json_encode([
                'xia' => json_encode($num),
                'win' => json_encode($v['win']),
                'xialist' => json_encode($xialist),
            ]);
            if ($num == 0 && $v['win'] == 0) {
                if ($k != $this->Banker_uid) {
                    continue;
                }
            }
            DBInstance::insertHit($hit);
        }
    }

    //小节
    public function Check () {

        $suan = $this->DuiYingList[$this->BenNum];
        $win = $suan['win'];
        $anquan = $suan['anquan'];

        //庄家用户的gold和闲家下注的相加
        $Pond = $this->m_UserList[$this->Banker_uid]['gold'] + $this->TableList;
        //计算
        $list = [];
        foreach ($this->m_Bets as $k => $v) {
            $list[$k] = 0;
//            if (!isset($this->m_UserList[$k]) || $this->m_UserList[$k]['isOnline'] == USEROFF) {
//                continue;
//            }
            foreach ($v as $k1 => $v1) {
                if (in_array($k1 , $win)) {
                    $num = $this->CheckTool($k1 , $v1);

                    //玩家赢钱
                    $list[$k] += $num;
                    $Pond -= $num;
                } elseif (in_array($k1 , $anquan)) {
                    //玩家不扣钱，也不亏钱
                    $Pond -= $v1;
                    $list[$k] += $v1;
                } 
            }
        }

        $list[$this->Banker_uid] = $Pond - $this->m_UserList[$this->Banker_uid]['gold'];


        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if ($v > 0) {
                    //赢的钱，去做返利
                    $this->Rebate($k , $v);
                } else {
                    if ($k == $this->Banker_uid) {
                        $this->m_UserList[$this->Banker_uid]['gold'] = $Pond;
                    }
                }
            }
        }

        foreach ($list as $key => $value) {
            if ($key != $this->Banker_uid) {
                $num = array_sum($this->m_Bets[$key]);
                $list[$key] -= $num;
            }
        }

        $KeDuan = [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $KeDuan[$k] = [
                    'win' => $v,
                    'gold' => $this->m_UserList[$k]['gold'],
                ];
            }
        }

        $this->addHistory($KeDuan);

        $msg = [
            'event' => 'Msg_AnBao_Result_Single',
            'status' => 1,
            'msg' => '结算成功',
            'data' => $KeDuan,
        ];

        $this->SendAll($msg);

        $this->wait();

        Timer::add(6 , function () {
            $this->Game_RUN(STAGE_WAITGO);
        } , array() , false);
        return true;
    }

    /*做返利新增*/
    public function Rebate ($uid , $win) {
        if (empty($this->m_UserList[$uid]['fanli'])) {
            $this->m_UserList[$uid]['gold'] += $win;
            return $win;
        }
        $money = 0;
        foreach ($this->m_UserList[$uid]['fanli'] as $k => $v) {
            $rebate = $v * $win;
            DBInstance::AddRebate($uid , $k , $rebate);
            $money += $rebate;
        }
        $num = $win - $money;
        $this->m_UserList[$uid]['gold'] += $num;

        return true;
    }

    //抢庄通用
    public function zhuang() {
        $ren = [];
        foreach ($this->m_RushV as $k => $v) {
            if ($v == 1 && isset($this->m_UserList[$k]) && $this->m_UserList[$k]['isOnline'] == USERONLINE) {
                $ren[] = $k;
            }
        }

        if (empty($ren)) {
            $this->Game_RUN(STAGE_WAITGO);
            return true;
        }

        $this->Banker_uid = $ren[array_rand($ren)];

        $msg = [
            'event' => 'Msg_AnBao_Banker',
            'data' => [
                'uid' => $this->Banker_uid,
                "nick" => $this->m_UserList[$this->Banker_uid]['nick'],
                "headImg" => $this->m_UserList[$this->Banker_uid]['headImg'],
                "sex"=>$this->m_UserList[$this->Banker_uid]['sex'],
                "gold"=>$this->m_UserList[$this->Banker_uid]['gold'],
                "isOnline"=>$this->m_UserList[$this->Banker_uid]['isOnline'],
                "index"=>$this->m_UserList[$this->Banker_uid]['index'],
                "state"=>$this->m_UserList[$this->Banker_uid]['state'],
            ],
        ];
        self::SendAll($msg);
        return true;
    }




    //游戏状态
    public function GameState ($time = '' , $uid = 0) {

        if ($this->m_banker == 2 && $this->m_GameStatus == 2) {
            return true;
        } else {

            $msg = [
                'event' => 'Msg_AnBao_GameState',
                'data' => [
                    'state' => $this->m_GameStatus == STAGE_JS ? $this->LastgameStatus : $this->m_GameStatus,
                    'time' => $time,
                ],
            ];
            if ($uid != 0){
                $this->SendToUid($uid , $msg);
            } else {
                self::SendAll($msg);
            }
        }
        return true;

    }

    public function RoomInfo ($message , $true = true) {
        $info['own'] = [
            'uid' => $message['uid'],
            'nick' => isset($this->m_UserList[$message['uid']]['nick']) ? $this->m_UserList[$message['uid']]['nick'] : 0,
            'headImg' => isset($this->m_UserList[$message['uid']]['headImg']) ? $this->m_UserList[$message['uid']]['headImg'] : 0,
            'sex' => isset($this->m_UserList[$message['uid']]['sex']) ? $this->m_UserList[$message['uid']]['sex'] : 0,
            'gold' => isset($this->m_UserList[$message['uid']]['gold']) ? $this->m_UserList[$message['uid']]['gold'] : 0,
            'isOnline' => isset($this->m_UserList[$message['uid']]['isOnline']) ? $this->m_UserList[$message['uid']]['isOnline'] : 0,
            'index' => isset($this->m_UserList[$message['uid']]['index']) ? $this->m_UserList[$message['uid']]['index'] : 0,
            'state' => isset($this->m_UserList[$message['uid']]['state']) ? $this->m_UserList[$message['uid']]['state'] : 0,
        ];

        if ($this->Banker_uid == -1) {
            $info['zhuang'] = null;
        } else {
            $info['zhuang'] = [
                'uid' => $this->Banker_uid,
                'nick' => $this->m_UserList[$this->Banker_uid]['nick'],
                'headImg' => $this->m_UserList[$this->Banker_uid]['headImg'],
                'sex' => isset($this->m_UserList[$this->Banker_uid]['sex']) ? $this->m_UserList[$this->Banker_uid]['sex'] : 0,
                'gold' => isset($this->m_UserList[$this->Banker_uid]['gold']) ? $this->m_UserList[$this->Banker_uid]['gold'] : 0,
                'isOnline' => $this->m_UserList[$this->Banker_uid]['isOnline'],
                'index' => $this->m_UserList[$this->Banker_uid]['index'],
                'state' => $this->m_UserList[$this->Banker_uid]['state'],
            ];
        }


        if (isset($message['event'])) {

            $msg['event'] = 'Msg_AnBao_RoomInfo';
            $msg['data'] = [
                'GameState' => $this->m_GameStatus == STAGE_JS ? $this->LastgameStatus : $this->m_GameStatus,
                'BankerUid' => $this->Banker_uid,
                'PlayerNum' => count($this->m_UserList),
                'Ruid' => $this->roomuid,
                'Rname' => $this->m_UserList[$this->roomuid]['nick'],
                'Circle' => $this->m_CurCircle,
                'ZouShi' => $this->BenNum,
                'Rid' => $this->roomid,
                'Level' => $this->m_rule['level'],
            ];

            $msg['data']['TablePlayers'] = $info;
            if ($true == false) {
                return $msg;
            }
            $this->SendToUid($message['uid'] , $msg);
            $this->GameState(0 , $message['uid']);

            //告诉当前所有走势和下注情况

            $vvg = [
                'event' => 'Msg_AnBao_TableShow',
                'data' => [
                    'ZouShi' => $this->BenList,
                    'XiaZhu' => $this->Table,
                ]
            ];

            $this->SendToUid($message['uid'] , $vvg);
            return true;
        } else {
            return $info;
        }
    }

    //通知中心服务器，游戏开始了
    public function sendCenter () {
        //发送到中心服务器处理
        Logic::SendToCentral(0 , array(
            'event' => 'Upstate',
            'area' => GAME_AnBao,
            'uid' => -11111,
            'data' => array(
                'rid' => $this->roomid,
            ),
        ));
        return true;
    }

    /**
     * 玩家进入
     */
    public function UserEnter($con , $msg)
    {
        $uid = $msg['uid'];
        $data = $msg['data'];
        $msg = array(
            'event' => 'Msg_Hall_EnterRoom',
            'status' => 1,
            'msg' => '进入成功',
            'uid' => $uid,
        );


        Gateway::updateSession($data['client_id'], array('router' => Logic::$businessWorkerID));

        $vall = (array)DBInstance::getUser('*' , $uid);

        if (isset($this->m_UserList[$uid])) {

            $this->m_UserList[$uid]['isOnline'] = USERONLINE;
//            $this->m_UserList[$uid]['gold'] = $vall['gold'];
        } else {

            //查找返利关系
            $val = DBInstance::GetNext($uid);
            $info = [
                'client_id' => $data['client_id'],
                'uid' => $uid,
                'nick' => $vall['nickname'],
                'gold' => $vall['gold'],
                'headImg' => $vall['headimgurl'],
                'sex' => $vall['sex'],
                'index' => -1,
                'isOnline' => USERONLINE,
                'state' => -1,
                'teshu' => $vall['teshu'],
                'fanli' => $val,
            ];
            $this->m_UserList[$uid] = $info;

        }

        $msg['data'] = [
            'rid' => $this->roomid,
            'gtype' => 1,
            // 'circle' => $this->m_Circle,
            'banker' => $this->m_banker,
            'rname' => DBInstance::getUser('nickname' , $this->roomuid),
        ];
        Logic::SendToUid($uid , $msg);

        $GroupList = Gateway::getClientIdListByGroup($this->roomid);
        if (empty($GroupList) || !isset($GroupList[Gateway::getClientIdByUid($uid)[0]])) {
            Gateway::joinGroup(Gateway::getClientIdByUid($uid)[0] , $this->roomid);
        }

        $rr = [
            'event' => 'Msg_GAME_NewPlayerCome',
            'data' => [
                'uid' => $uid,
                'nick' => $vall['nickname'],
                'headImg' => $vall['headimgurl'],
                'sex' => $vall['sex'],
            ],
        ];

        self::SendAll($rr , $uid);
        return true;
    }


    /*更新房卡*/
    public function UpdateCard ($message) {
        $uid = $message['uid'];
        $msg = [
            'event' => 'Msg_Hall_UpdateCard',

        ];

        $msg['data']['card'] = DBInstance::getUser('rcard' , $uid);

        Logic::SendToUid($uid , $msg);
    }


    /**
     * 玩家重连
     */
    public function UserOnline($client_id, $uid)
    {
        //1进入 2退出 3断线 4观战 5重连
        $this->m_UserList[$uid]['isOnline'] = USERONLINE;
        $this->m_UserList[$uid]['client_id'] = $client_id;


        //绑定路由
        Gateway::updateSession($this->m_UserList[$uid]['client_id'], array('router' => Logic::$businessWorkerID));
        $msg = array(
            'event' => 'Msg_Hall_EnterRoom',
            'status' => 1,
            'msg' => '进入成功',
            'uid' => $uid,
        );
        $msg['data'] = [
            'rid' => $this->roomid,
            'gtype' => 1,
            // 'circle' => $this->m_Circle,
            'banker' => $this->m_banker,
            'rname' => DBInstance::getUser('nickname' , $this->roomuid),
        ];
        Logic::SendToUid($uid , $msg);
//        //更新状态


        //刷新桌面
        $this->RoomInfo($uid);

        return true;
    }


    /*获得用户观战，游戏中表单*/
    public function GetUserList ($message) {
        $info = [];
        $type = $message['data']['type'];

        foreach ($this->m_UserList as $k => $v) {
            if (($type == 1 ? $v['index'] == -1 : $v['index'] != -1)) {
                continue;
            }
            $info[] = [
                'uid' => $k,
                'nick' => $v['nick'],
                'headImg' => $v['headImg'],
                'sex' => isset($v['sex']) ? $v['sex'] : 0,
                'gold' => isset($v['gold']) ? $v['gold'] : 0,
                'isOnline' => $v['isOnline'],
                'index' => $v['index'],
                'state' => $v['state'],
            ];
        }
        $msg = [
            'event' => 'Msg_AnBao_GetUserList',
            'uid' => $message['uid'],
            'data' => $info,
        ];
        Logic::SendToUid($message['uid'] , $msg);
        return true;
    }

    /**
     * 玩家离线
     */
    public function Userclose ($uid) {
        $msg = [
            'event' => 'Msg_GAME_PlayerState',
            'data' => [
                'uid' => $uid,
            ],
        ];
        self::SendAll($msg);
        $this->m_UserList[$uid]['isOnline'] = USEROFF;

        if ($this->Banker_uid == $uid || (isset($this->m_Bets[$uid]) && !empty($this->m_Bets[$uid]))) {

        } else {
            $this->UpdateGold($uid);
            $this->UserListDown($uid);
        }
        // isset($this->m_Seat[$this->m_UserList[$uid]['index']]) ? $this->m_Seat[$this->m_UserList[$uid]['index']] = 0 : '';
        //     unset($this->m_UserList[$uid]);
        return true;
    }


    public function UserListDown ($uid) {

        //让用户不重连
        Logic::SendToCentral(0, array(
            'event' => 'Msg_UserListDown',
            'area' => GAME_AnBao,
            'uid' => -11111,
            'data' => array(
                'rid' => $this->roomid,
                'uid' => $uid,
            ),
        ));
    }

    /*玩家金币丢到大厅，大厅处理金币金额放进数据库*/
    public function UpdateGold ($uid = 0) {
        if ($uid) {
            if (!isset($this->m_UserList[$uid])) {
                $this->UpdateGold($uid);
            }
            Logic::SendToCentral(0, array(
                'event' => 'Msg_UpdateGold',
                'area' => GAME_AnBao,
                'uid' => -11111,
                'data' => array(
                    'rid' => $this->roomid,
                    'uid' => $uid,
                    'gold' => $this->m_UserList[$uid]['gold']
                ),
            ));
        } else {
            foreach ($this->m_UserList as $k => $v) {
                Logic::SendToCentral(0, array(
                    'event' => 'Msg_UpdateGold',
                    'area' => GAME_AnBao,
                    'uid' => -11111,
                    'data' => array(
                        'rid' => $this->roomid,
                        'uid' => $k,
                        'gold' => $v['gold']
                    ),
                ));
            }
        }

        return true;
    }
    /*
     * 玩家正常返回大厅*/
    public function OutRoom($message)
    {

        $uid = $message['uid'];

        $msg = [
            'event' => 'Msg_GAME_OutRoom',
            'status' => 1,
            'data' => [
                'uid' => $uid,
                'nick' => $this->m_UserList[$uid]['nick'],
            ],
        ];

        if (isset($this->m_Bets[$uid])) {
            $this->sendError($uid , 'Msg_GAME_OutRoom' , '参与游戏中不能退出');
            return true;
        }

        if ($this->Banker_uid == $uid) {
            $this->sendError($uid , 'Msg_GAME_OutRoom' , '本局当庄不能退出');
            return true;
        }

        $this->UpdateGold($uid);

        if (isset($this->m_UserList[$uid])) {
            $this->m_UserList[$uid]['isOnline'] = USEROFF;
        }

        $this->m_Seat[$this->m_UserList[$uid]['index']] = 0;

        $this->UserListDown($uid);
        self::SendAll($msg);

        Gateway::updateSession($this->m_UserList[$uid]['client_id'], array('router' => array_rand(Logic::$HallList)));
        Gateway::leaveGroup(Gateway::getClientIdByUid($uid)['0'] , $this->roomid);
        unset($this->m_UserList[$uid]);
        return true;
    }


    public function lun () {
        $msg = [
            'event' => 'Msg_AnBao_GameOver',
            'status' => 1,
            'msg' => '解散房间成功',
        ];
        $this->SendAll($msg);
        return true;
    }

    /**
     * 解散房间
     */
    public function OldRoom()
    {
        $this->UpdateGold();


        $this->lun();
        $uids = [];
        foreach ($this->m_UserList as $key => $val) {
            $uids[] = $key;

            Gateway::updateSession($val['client_id'], array('router' => array_rand(Logic::$HallList)));
        }

        Logic::SendToCentral(0, array(
            'event' => 'RoomOld',
            'area' => GAME_AnBao,
            'uid' => -11111,
            'data' => array(
                'rid' => $this->roomid,
                'uids' => $uids,
            ),
        ));

        return true;
    }

    /**
     * 发送给所有人正确消息
     * @throws Exception
     */
    public function SendAll($msg , $uid = 0)
    {

        if ($uid != 0) {
            Gateway::sendToGroup($this->roomid , json_encode($msg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) , Gateway::getClientIdByUid($uid)[0]);
        } else {
            Gateway::sendToGroup($this->roomid , json_encode($msg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }
        return true;
    }

    /*发送给个人*/
    public function SendToUid ($uid , $msg) {
        Gateway::sendToUid($uid , json_encode($msg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }



    /**
     * 发送错误消息
     */
    public function sendError($uid, $msgname, $msg)
    {


        Logic::SendToUid($uid, array(
            'event' => $msgname,
            'uid' => $uid,
            'status' => 0,
            'msg' => $msg,
            'data' => array(),
        ));
    }

}