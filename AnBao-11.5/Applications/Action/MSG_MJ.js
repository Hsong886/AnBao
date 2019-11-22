// // 麻将
// data = {
//     gtype: 1,            // 游戏类型
//     mode: 1,             // 1冲刺 2平搓
//     pnum: 3,             // 人数
//     taiNum: 4,           // 台数
//     circle: 1,           // 圈数 （平搓才回有圈数）
//     jinchi: 1 / 0,         // 禁吃 （2/3人才出现）
// }

// //Msg_GAME_SYNC
// // 进入房间后同步房间信息消息
// data = {
//     event: "Msg_MJ_RoomInfo",
//     uid: 66810063,
//     data: {
//         GameState: 0,
//         BankerUid: 0,//庄家uid
//         Ruid: 66809959, //房主uid
//         Rname: '',//房主昵称,
//         Circle: 0, //当前圈数
//         EndTime: 0, //结束秒数
//         CurNum: 0, //桌面剩余牌数量
//         LaiZi: 0,  //癞子牌id
//         LastUid:
//         CurUid:当前操作玩家uid
//         TablePlayers: [ //桌面所有玩家
//             {
//                 uid: 66809959,
//                 nick: 昵称,
//                 headImg: 头像,
//                 sex: 1, //性别 1男2女
//                 score: 0, //积分
//                 cards: [0, 0, 0, 0, 0], //手牌
//                 daCards: [],//出过的牌
//                 pgCards: [[
//                     1, // 类型 1吃 2碰 3明杠 4暗杠 5点杠  6花牌杠 7胡
//                     2, 202, 2 // 牌  大于100的表示吃碰杠的哪张牌， /100=打出这张牌的玩家座位号
//                 ]],
//                 huaCards: [],//花牌
//                 isOnline: true, //是否在线
//                 index: 8, //座位号
//                 touchCard: 0, //摸的牌 0没有
//                 state: 0, //-1观战//0坐下//1准备//2游戏中,
//             }
//         ]
//     }
// }

// // 游戏状态
// // state：0等待开始 1抢庄 2发牌 3游戏中 4结算 5结束
// // time：倒计时
// data = {
//     event: "Msg_MJ_GameState",
//     data: {
//         state: 0,
//         time: 0,
//         points: {              //抢庄状态发送每个玩家的骰子点数
//             uid1: [1, 2],
//             uid2: [1, 2],
//             uid3: [1, 2],
//             uid4: [1, 2],
//         },
//         point: [1, 2],          //发牌发送起始摸牌位置的点数
//     },
//     uid: 66810063
// }

// // 玩家操作
// // 1坐下 2起身	3准备
// data = {
//     event: "Msg_MJ_UserAct",
//     data: {
//         uid: 操作人uid,
//         act: 操作,
//         TablePlayers:
//         {
//             uid: 66809959,
//             nick: 昵称,
//             headImg: 头像,
//             sex: 1, //性别 1男2女
//             score: 0, //积分
//             cards: [0, 0, 0, 0, 0], //手牌
//             daCards: [], //出过的牌
//             pgCards: [[
//                 1, // 类型 1吃 2碰 3点杠(直杠：手上3张，别人打出第四张) 4暗杠（手上三张，自己摸到第四张） 5明杠（风险杠：之前碰了3张，自己摸到第四张）  6花牌杠 7胡
//                 2, 202, 2 // 牌  大于100的表示吃碰杠的哪张牌， /100=打出这张牌的玩家座位号
//             ]],
//             huaCards: [],// 花牌
//             isOnline: true, //是否在线
//             index: 8, //座位号
//             touchCard: 0, //摸的牌 0没有
//             state: 0, //- 1观战//0坐下//1准备//2游戏中,
//         },
//     },
//     uid: 66810063
// }

// //吃碰杠胡过提示
// data = {
//     event: "Msg_MJ_Call",
//     uid: 66810063,
//     data: {
//         pid: 牌id,
//         hu: 1 / 0,
//         peng: 1 / 0,
//         gang: [[type, pid]],//pid为3这种格式如果是暗杠或是明杠则为3  type:3明杠(碰杠) 4暗杠 5点杠 6花牌杠
//         chi: [[2, 3, 4], [1, 2, 3], [3, 4, 5]],
//     }
// }

// // 摸牌通知
// data = {
//     event: "Msg_MJ_MoCard",
//     uid: 0,
//     data: {
//         uid: 摸牌玩家uid,
//         pid: 牌id,
//         huas:[],//花牌
//         curNum: 牌堆剩余张数
//     }
// }

// // 通知玩家打牌
// data = {
//     event: "Msg_MJ_DaNotify",
//     uid: 0,
//     data: {
//         uid: 打牌玩家uid,
//     }
// }

// // 打牌 （发送给服务器消息data里没有uid）
// data = {
//     event: "Msg_MJ_DaCard",
//     uid: 0,
//     // 发送
//     data: {
//         pid: 打的牌id
//     },
//     // 接收
//     data: {
//         uid: 打牌玩家uid,
//         pid: 打的牌id
//     }
// }

// // 过
// data = {
//     event: "Msg_MJ_Pass",
//     uid: 0,
// }

// //吃  (发送给服务器消息data里没有uid)
// data = {
//     event: "Msg_MJ_Chi",
//     uid: 0,
//     //发送
//     data: {
//         type:1
//         pid: [4, 5, 6],
//     },
//     //接收
//     data: {
//         uid: 吃牌玩家,
//         pid: [4, 5, 6],
//         duid:谁打的牌
//         dpid:6,
//     }
// }

// //碰  (发送给服务器消息data里没有uid)
// data = {
//     event: "Msg_MJ_Peng",
//     uid: 0,
//     // 发送
//     data: {
//         pid: 4,
//     },
//     // 接收
//     data: {
//         duid:
//         uid: 碰牌玩家,
//         dpid: 4,
//     }
// }

// //杠  (发送给服务器消息data里没有uid)
// data = {
//     event: "Msg_MJ_Gang",
//     uid: 0,
//     // 发送
//     data: {
//         pid: 4,
//     },
//     // 接收
//     data: {
//         uid:杠牌玩家,
//         duid:
//         type: 3 / 4 / 5 / 6,  //3明杠 4暗杠 5点杠 6花牌杠
//         dpid: 4,
//         pid : 4
//     }
// }

// //胡  (发送给服务器消息data里没有uid)
// data = {
//     event: "Msg_MJ_Hu",
//     uid: 0,
//     // 发送
//     data: {
//         pid: 4,
//     },
//     // 接收
//     data: {
//         uid: 胡牌玩家,
//         pid: 4,
//     }
// }

// // 发牌
// data = {
//     event: "Msg_MJ_FaPai",
//     uid: 0,
//     data: {
//         laiZi: 0,
//         zhuang:
//         uid1: {
//             cards: [],//手牌
//             huaCards: [],//花牌
//
//         }
//     }
// }

// // 小结
// data = {
//     event: "Msg_MJ_Result",
//     uid: 0,
//     data: [
//         {
//             uid: 0,                        // uid
//             nick: '',                      // 昵称
//             headImg: '',                   // 头像
//             win: 0,                        // 单局结算积分
//             score: 0,                      // 积分
//             pgCards: [[
//                 1, // 类型 1吃 2碰 3明杠 4暗杠 5点杠  6花牌杠 7胡
//                 2, 202, 2 // 牌  大于100的表示吃碰杠的哪张牌， /100=打出这张牌的玩家座位号
//             ]],
//             cards: [0, 0, 0, 0, 0], //手牌
//             huCard: 0,		//胡牌
//             huaCards:[],    //花牌
//             info: '',//描述
//             dianPao: 0 / 1,       // 0无 1 点炮
//             huType: 0 / 1 / 2,   // 0无-没有胡牌 1 点炮 2 自摸
//         }
//     ]
// }

// //总结算
// // Msg_MJ_GameOver
// data = [
//     {
//         uid: 0,
//         nick: '',
//         headImg: '',
//         scoreList: [0, 0, 0, 0, 0],
//         score:0,   //总积分
//     }
// ]
