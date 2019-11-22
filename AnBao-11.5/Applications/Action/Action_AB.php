

//创建房间
//Msg_Hall_CreateRoom
// {
//     "event":"Msg_Hall_CreateRoom",
//     "uid":66810063,
//     "data":{
//         "pnum":游戏人数,
//         "banker":1抢庄 2固定庄家
//         "circle":0 总圈数
//         "low":0 最低携带金币
//         "longhuchuru":0 龙虎出入比例  1:3 给 3
//         "jiaochuan":0 角串比例  1:3 给 3
//         "tongzhan":0 同沾比例  1:3 给 3

//     }
// }


//Msg_Game_Sync
// 进入房间后同步房间信息消息
// {
//     "event":"Msg_AnBao_RoomInfo",
//     "uid":66810063,
//     "data":{
//         "GameState":0,
//         "BankerUid":0,庄家uid
//         "PlayerNum":2,房间内总人数
//         "Ruid":66809959, 房主uid
//         "Rname":房主昵称,
//         "Circle":0, 当前圈数
//         "CanDown":1, 1能坐下 0不能坐下
//         "ZouShi":[21,22,23,22], 当前走势
//         "TablePlayers":[ 桌面所有玩家
//             'own' => {
//                  "uid":66809959,
//                  "nick":昵称,
//                  "headImg":头像,
//                  "sex":1, 性别 1男2女
//                  "gold":0, 积分
//                  "isOnline":true, 是否在线
//                  "index":8, 座位号
//                  "state":8, 状态
//             }
//             'zhuang' => {
//                  "uid":66809959,
//                  "nick":昵称,
//                  "headImg":头像,
//                  "sex":1, 性别 1男2女
//                  "gold":0, 积分
//                  "isOnline":true, 是否在线
//                  "index":8, 座位号
//                  "state":8, 状态
//             }
//         ]
//     }
// }

// 游戏状态
// state：0等待开始 1等待第二句以上开始  2定庄 3下注 4开赢边 5一圈结算 6解散阶段 7总结
// time：倒计时
// {
//     "event":"Msg_AnBao_GameState",
//     "data":{
//         "state":0,
//         "time":0
//     },
//     "uid":66810063
// }

// 玩家操作
// Msg_AnBao_UserAct  发给服务器的消息
// 1坐下 2起身  4抢庄 5下注
// {
//     "event":"Msg_AnBao_UserAct",
//     "data":{
//         "uid":操作人uid,
//         "seat":座位号,
//         "act":操作,
//         "num":下注\抢庄数量,
//         "TablePlayers":[ 桌面玩家数据 内容同RoomInfo
//             {
//                 "uid":66810063,
//                 "nick":"昵称",
//                 "headImg":头像,
//                 "sex":1,
//                 "gold":0,
//                 "isOnline":true,
//                 "index":1,
//                 "state":-1观战//1坐下（可以投注）,
//             },
//         ]
//     },
//     "uid":66810063
// }

// 发送抢庄消息
// {
//     "event":"Msg_AnBao_UserAct",
//     "uid":66810063,
//     "data":{
//         "act":4, 4表示抢庄
//         "num":1  固定发一
//     }
// }

//GameMgr.send('Msg_SK_ChuPai', { data: { daCards: cards } });

// 定庄
// {
//     "event":"Msg_AnBao_Banker",
//     "data":{
//         "bankerUid":66809959, 庄家uid
//         "bankers":[66810063,66809959] 参与抢庄的人且抢庄倍数最大且相同
//     },
//     "uid":66810063
// }

// 发送下注消息
// {
//     "event":"Msg_AnBao_UserAct",
//     "uid":66810063,
//     "data":{
//         "act":5, 5表示下注
//         "num":2 下注金额
//         "type": 1-24  下注区域
//     }
// }

// 当前局出的点数
// {
//     "event":"Msg_AnBao_Open",
//     "data":{
//         'id' : 21-24 龙 出 胡 入
//         'xianshi' : //龙
//                      21 => [
//                          'win' => [
//                              4,5,9,10,12,13,14
//                          ],
//                          'anquan' => [
//                              1,11,18
//                          ],
//                      ],
//                      //出
//                      22 => [
//                          'win' => [
//                              1,2,3,5,6,7,19
//                          ],
//                          'anquan' => [
//                              4,8,15
//                          ],
//                      ],
//                      //虎
//                      23 => [
//                          'win' => [
//                              7,8,9,11,12,16,17
//                          ],
//                          'anquan' => [
//                              3,10,20
//                          ],
//                      ],
//                      //入
//                      24 => [
//                          'win' => [
//                              2,14,15,16,18,19,20
//                          ],
//                          'anquan' => [
//                              6,13,17
//                          ],
//                      ],
//     },
//     "uid":66810063
// }

// 单局结算消息
//   data = {
//      event:Msg_AnBao_Result_Single,
//      uid:0,
//      data:[
//          {
//             uid:0,
//             win:0,
//          }
//      ]
//   }

//走势更新消息
//   data = {
//      event:Msg_AnBao_ZouShi,
//      uid:0,
//      data:[
//          {
//             new:21-24,
//          }
//      ]
//   }

// 总结算
// Msg_AnBao_GameOver
// data = {
//      'event' => Msg_AnBao_GameOver,
//      'status' => 1,
//      'msg' => '解散房间成功',
// }

// 快捷语
// Msg_GAME_QuickMsg
// data = {
//         'rid' => '房间rid'
//         'data' => [],
// }

// 获得玩家列表
//Msg_AnBao_GetUserList
// data = {
//         'uid' => $k,
//         'nick' => $v['nick'],
//         'headImg' => $v['headImg'],
//         'sex' => isset($v['sex']) ? $v['sex'] : 0,
//         'gold' => isset($v['gold']) ? $v['gold'] : 0,
//         'isOnline' => $v['isOnline'],
//         'index' => $v['index'],
//         'state' => $v['state'],
//
// }



//获得桌子列表
//Msg_Hall_GetAllTables










