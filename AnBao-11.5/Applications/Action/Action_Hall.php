<?php
/**
 * Created by PhpStorm.
 * User: 龚坤
 * Date: 2018/8/17
 * Time: 12:12
 */

//-------------用户大厅消息---------------



$心跳 = array(
    'event' => "Msg_Hall_Heart",
    'area' => 1,
    'uid' => 1,
//cs
    'data' => [
    ],
//sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
    ]
);

$用户登录消息 = array(
    'event' => 'Msg_Hall_Connect',
    'area' => 1,
    'uid' => 1,
//cs
    'data' => [
        'openid' => '*****',
    ],
//sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '用户ID',
        'card' => '房卡',
        'rid' => '是否有重连房间 0无 xxxxxx为房号',
        'sex' => '性别',
        'name' => '昵称',
        'head' => '用户头像地址',
        'ip' => '显示IP'
    ]
);


$进入房间 = array(
    'event' => "Msg_Hall_EnterRoom",
    'area' => 1,
    'uid' => 1,
//cs
    'data' => ['rid' => '房号'],
//sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'rid' => '房号',
        'gtype' => '游戏编号',


    ]
);








// 系统提示消息
// {
//     "event":"Msg_Hall_BreakPlayer",
//     "uid":66809959,
//     "data":{
//         "state":1 退出登录 2 返回大厅
//         "Desc":描述
//     }
// }

// 接收解散房间通知
// {
//     "event":"Msg_GAME_DissolveNotice",
//     "uid":66810063,
//     "data":{
//         "time":30, 倒计时
//         "name":"申请人昵称",
//         "uid":66809959, 申请人uid
//         'agree':0,
//         'disAgree':0,
//     }
// }

// 解散房间结果
// {
//     "event":"Msg_GAME_DissolveResult",
//     "data":{
//         "state":1 1解散 2继续游戏
//     },
//     "uid":66810063
// }

// 申请解散房间
// {"event":"Msg_GAME_DissolveNotice","uid":66810063}

// 玩家同意或拒绝解散房间
// {
//     "event":"Msg_GAME_AnswerDisband",
//     "uid":66810063,
//     "data":
//     {
//         state: 1同意 2拒绝
//     }
// }


// 用户退出
// {
//     "event":"Msg_GAME_OutRoom",
//     "uid":66810063,
//     "data":
//     {
//         uid:
//     }
// }




