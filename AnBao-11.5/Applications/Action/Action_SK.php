//Msg_Game_Sync
// 进入房间后同步房间信息消息
// {
//     "event":"Msg_NN_RoomInfo",
//     "uid":66810063,
//     "data":{
//         "GameState":0,
//         "BankerUid":0,庄家uid
//         "PlayerNum":2,房间内总人数
//         "Ruid":66809959, 房主uid
//         "Rname":房主昵称,
//         "Circle":0, 当前圈数
//         "TablePlayers":[ 桌面所有玩家
//             {
//                 "uid":66809959,
//                 "nick":昵称,
//                 "headImg":头像,
//                 "sex":1, 性别 1男2女
//                 "score":0, 积分
//                 "cards":[0,0,0,0,0], 手牌
//                 "cardType":null, 手牌类型
//                 "isOnline":true, 是否在线
//                 "index":8, 座位号
//                 "isShow":0, 是否摊牌
//                 "isReady":0, 是否准备
//                 "isPlaying":0, 是否在游戏中
//                 "beiShu":-1 倍数
//
//             }
//         ]
//     }
// }

// 游戏状态
// state：0等待准备 1发牌  2游戏中 3小节
// time：倒计时
// {
//     "event":"Msg_NN_GameState",
//     "data":{
//         "state":0,
//         "time":0
//     },
//     "uid":66810063
// }

// 玩家操作
// Msg_NN_UserAct  发给服务器的消息
// 1坐下 2起身	3准备 4抢庄 5下注 6亮牌
// {
//     "event":"Msg_NN_UserAct",
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
//                 "score":0,
//                 "cards":[0,0,0,0,0],
//                 "cardType":null,
//                 "isOnline":true,
//                 "index":1,
//                 "isShow":0,
//                 "state":-1观战//0坐下//1准备//2游戏中,

//                 "beiShu":-1
//             },
//         ]
//     },
//     "uid":66810063
// }

// 发牌通知
// {
//     "event":"Msg_NN_FaPai",
//     "data":{
//         "curCircle":1, 当前圈数
//         "66810063":[24,6,9,4,45], 手牌
//         "66809959":[32,39,36,10,52] 手牌
//     },
//     "uid":66810063
// }


// 定座位
// {
//     "event":"Msg_SK_FixedSeat",
//     "data":{
//
//     },
//     "uid":66810063
// }

// 发送抢庄消息
// {
//     "event":"Msg_NN_UserAct",
//     "uid":66810063,
//     "data":{
//         "act":4, 4表示抢庄
//         "num":2 抢庄倍数
//     }
// }

//GameMgr.send('Msg_SK_ChuPai', { data: { daCards: cards } });

// 定庄
// {
//     "event":"Msg_NN_Banker",
//     "data":{
//         "bankerUid":66809959, 庄家uid
//         "bankers":[66810063,66809959] 参与抢庄的人且抢庄倍数最大且相同
//     },
//     "uid":66810063
// }

// 发送下注消息
// {
//     "event":"Msg_NN_UserAct",
//     "uid":66810063,
//     "data":{
//         "act":5, 5表示下注
//         "num":2 下注倍数
//     }
// }

// 摊牌消息
// {
//     "event":"Msg_NN_ShowCard",
//     "data":{ 玩家手牌，数组最后一个表示牌型
//         "66810063":[52,50,12,5,48,4]
//     },
//     "uid":66810063
// }

// 单局结算消息
//   data = {
//      event:Msg_NN_Result_Single,
//      uid:0,
//      data:[
//          {
//             uid:0,
//             nick:'',
//             headImg:'',
//             cards:[],
//             cardType:0,
//             win:0,
//          }
//      ]
//   }

// 一轮结束结算
// Msg_NN_Result_Lun
// data = {
//     curLun:0,
//     totalLun:0,
//     list: [
//         {
//             uid: 0,
//             nick: '',
//             headImg: '',
//             scoreList: [0, 0, 0, 0, 0],
//         }
//     ]
// }

// 总结算
// Msg_NN_GameOver
// data = {
//     curLun:0,
//     totalLun:0,
//     list: [
//         {
//             uid: 0,
//             nick: '',
//             headImg: '',
//             scoreList: [0, 0, 0, 0, 0],
//         }
//     ]
// }



// 更新房卡
// 发送
// {
//     "data":{
//         "uid":66809959 更新人的uid
//     },
//     "event":"Msg_Hall_UpdateCard",
//     "uid":66809959
// }
// 接收
// {
//     "event":"Msg_Hall_UpdateCard",
//     "uid":66809959,
//     "data":{
//         "card":99900,
//     }
// }




// 丢骰子阶段
// {
//     "event":"Msg_PJ_Dice",
//     "data":{
//          'uid':['shai1','shai2']
//          'uid':['shai1','shai2']
//     },
//     "uid":66810063
// }


//Msg_PJ_ShowCards
//data={
//cards:[1,2,3,4]
//}



//data = {
//    event: Msg_SK_Result_Single,
//    uid: 0,
//    data: {
//        win: {
//            user: [
//                {
//                    uid: 0,
//                    nick: '',
//                    headImg: '',
//                    rank: 0,
//                    dao: 0,
//                    // 炸弹
//                    booms: [],
//                },
//                {
//                    uid: 0,
//                    nick: '',
//                    headImg: '',
//                    rank: 0,
//                    dao: 0,
//                    booms: [],
//                }
//            ],
//            score: 0,
//            // 特殊
//            type_s: 1,
//            jia_s: 0,
//            // joker
//            type_w: 0,
//            jia_w: 0,
//        },
//        lose: {
//            user: [
//                {
//                    uid: 0,
//                    nick: '',
//                    headImg: '',
//                    rank: 0,
//                    dao: 0,
//                    // 炸弹
//                    booms: [],
//                },
//                {
//                    uid: 0,
//                    nick: '',
//                    headImg: '',
//                    rank: 0,
//                    dao: 0,
//                    booms: [],
//                }
//            ],
//            score: 0,
//            // 特殊
//            type_s: 1,
//            jia_s: 0,
//            // joker
//            type_w: 0,
//            jia_w: 0,
//        }
//    }
//
//}

