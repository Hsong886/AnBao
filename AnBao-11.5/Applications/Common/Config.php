<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/29 0029
 * Time: 10:42
 */
//define('msgOn', true);
//define('LoginRandom', true);
//define('SendToLua', true);

class Config
{
    //子进程数
    public static $serviceCount = 8;

    //数据库心跳间隔
    public static $dbpingInterval = 60;

    //项目名
    public static $ProjectName = 'CSgame';

    //register服务监听端口
    public static $registerPort = '2223';

    //中心服务器端口
    public static $centralPort = '2222';

    //中心匹配服务器端口
    public static $centralMatchPort = '2232';

    //网关通信协议
    public static $gatewayProtocol = 'websocket';
    //public static $gatewayProtocol = 'websocket';

    //网关监听端口
    public static $gatewayPort = '22222';

    //网关内部通讯起始端口
    public static $gatewayStartPort = 2900;

    //异步服务器端口
    public static $AsyCenteralPort = '2224';

    public static $registerAddress = '127.0.0.1'; //服务注册地址
    public static $centralAddress = '127.0.0.1'; //中心服务注册地址
    public static $localAddress = '127.0.0.1';

//    public static $localAddress = '192.168.31.6';
//    public static $registerAddress = '192.168.31.6';
//    public static $centralAddress = '192.168.31.6';

    //数据库连接参数
    public static $dbConfig = [
//        'host' => '192.168.31.6',
//        'user' => 'adminroot',
//        'password' => '123456',

        'host' => '127.0.0.1',
        'user' => 'root',
        'password' => '123456',
        'dbname' => 'csgame',
        'port' => '3306',
        'tablepre' => '',
        'charset' => 'utf8mb4'
    ];

    //Redis 服务器连接配置
    public static $RedisConfig = [
        'host' => '127.0.0.1',
        'port' => '6379',
    ];

    //MongoDB 服务器连接配置
    public static $MongoDBConfig = [
        'host' => '127.0.0.1',
        'port' => '27017',
        //数据库名字 ， 每次启动清空原来mongodb里面的数据库
        'databasename' => 'fcgame',
    ];

    //心跳设置
    public static $pingSwitch = true; //心跳开关
    public static $pingInterval = 5; //心跳间隔
    public static $pingNotResponseLimit = 1; //心跳次数
    public static $AnBaoRot = 5; // 暗堡机器人是否出来

    public static $randlogin = false; //随机登陆
    /*是否开启缓存*/
    public static $openredis = true;
    /*是否开启MongoDB 数据库*/
    public static $MongoDB = false;

        //"中心服" 接收 "附属服务器" 消息调用函数
    public static $CenterCenterCallBackList = array(
        'Msg_Hall_EnterMatch' => 'Msg_Hall_MatchData',
        'Msg_Hall_CancelMatch' => 'SendToClient',
        'Msg_Hall_MatchSwitch' => 'SendToClient',
        'Msg_Hall_GameOver' => 'Msg_Hall_GameOver',
        'Msg_Hall_Anti-addiction' => 'SendToClient',
        'Msg_Hall_DeleteFriend' => 'SendToClient',
        'Msg_Hall_SearchPlayer' => 'SendToClient',
        'Msg_Hall_AddFriend' => 'SendToClient',
        'Msg_Hall_FriendsApply' => 'SendToClient',
        'Msg_Hall_FriendsAction' => 'SendToClient',
        'Msg_Hall_ApplyRequest' => 'SendToClient',
    );

    //"中心服" 接收 "大厅" 消息调用函数
    public static $CenterHallCallBackList = array(
        'Msg_Hall_MatchSwitch' => 'SendToMatch',
        'Msg_Hall_CancelMatch' => 'SendToMatch',
        'Msg_Hall_FriendList' => 'SendToFriendSys',
        'Msg_Hall_DeleteFriend' => 'SendToFriendSys',
        'Msg_Hall_SearchPlayer' => 'SendToFriendSys',
        'Msg_Hall_AddFriend' => 'SendToFriendSys',
        'Msg_Hall_FriendsApply' => 'SendToFriendSys',
        'Msg_Hall_FriendsAction' => 'SendToFriendSys',
        'Msg_Hall_ApplyRequest' => 'SendToFriendSys',
    );

    //"中心服" 接收 "逻辑服" 消息调用函数
    public static $CenterLogicCallBackList = array(
        //'RoomOld' => 'SendToSettlement',
    );

    //"逻辑服" 接收 "客户端" 消息调用函数
    public static $LogicClientCallBackList = array(
        'Msg_Hall_Heart' => 'Msg_Hall_Heart',
        'Msg_Hall_Connect' => 'Msg_Hall_Connect',
        'Msg_Hall_Rooms' => 'Msg_Hall_Rooms',
        'Msg_Hall_GetAllTables' => 'Msg_Hall_GetAllTables',
        'Msg_Hall_QuickTool' => 'Msg_Hall_QuickTool',
        'Msg_Hall_GetAllUser' => 'Msg_Hall_GetAllUser',
    );

    //"逻辑服" 接收 "中心服" 消息调用函数
    public static $LogicCenterCallBackList = array(
        'Msg_Hall_EnterRoom' => 'SendToUser',
        'Msg_Hall_GetAllTables' => 'SendToUser',
        'Msg_Hall_QuickTool' => 'SendToUser',
        'Msg_Hall_GetAllUser' => 'SendToUser',
        'Msg_Hall_MatchSucceed' => 'SendToUser',
        'Msg_Hall_MatchData' => 'SendToUser',
        'Msg_Hall_EnterMatch' => 'SendToUser',
    );

    //"大厅" 接收 "客户端" 消息调用函数
    public static $HallClientCallBackList = array(
        'Msg_Hall_getGameType' => 'SendToCenter',
        'Msg_Hall_CancelMatch' => 'SendToCenter',
        'Msg_Hall_InviteFriend' => 'SendToCenter',
        'Msg_Hall_AnswerInvite' => 'SendToCenter',
        'Msg_Hall_Quit' => 'SendToCenter',
        'Msg_Hall_Ready' => 'SendToCenter',
        'Msg_Hall_SendAddr' => 'SendToCenter',
        'Msg_Hall_MatchSwitch' => 'SendToCenter',
        'Msg_Hall_FriendList' => 'SendToCenter',
        'Msg_Hall_DeleteFriend' => 'SendToCenter',
        'Msg_Hall_SearchPlayer' => 'SendToCenter',
        'Msg_Hall_AddFriend' => 'SendToCenter',
        'Msg_Hall_FriendsApply' => 'SendToCenter',
        'Msg_Hall_FriendsAction' => 'SendToCenter',
        'Msg_Hall_ApplyRequest' => 'SendToCenter',
        'Msg_Hall_UpdateCard' => 'SendToCenter',

    );

    //"大厅" 接收 "中心服" 消息调用函数
    public static $HallCenterCallBackList = array(

        'Msg_Hall_Anti-addiction' => 'SendToUser',
        'Msg_Hall_GameOver' => 'SendToUser',
        'Msg_Hall_Connect' => 'SendToUser',
        'Msg_Hall_Rooms' => 'SendToUser',
        'Msg_Hall_getGameType' => 'SendToUser',
        'Msg_Hall_EnterRoom' => 'SendToUser',
        'Msg_Hall_GetAllTables' => 'SendToUser',
        'Msg_Hall_QuickTool' => 'SendToUser',
        'Msg_Hall_GetAllUser' => 'SendToUser',
        'Msg_Hall_EnterMatch' => 'SendToUser',
        'Msg_Hall_CreateRoom' => 'SendToUser',
        'Msg_Hall_InviteFriend' => 'SendToUser',
        'Msg_Hall_AnswerInvite' => 'SendToUser',
        'Msg_Hall_Quit' => 'SendToUser',
        'Msg_Hall_Ready' => 'SendToUser',
        'Msg_Hall_PlayAgain' => 'SendToUser',
        'Msg_Hall_AnswerChallenge' => 'SendToUser',
        'Msg_Hall_VS' => 'SendToUser',
        'Msg_Hall_GameQuit' => 'SendToUser',
        'Msg_Hall_UpdateZuJu' => 'SendToUser',
        'Msg_Hall_MatchSucceed' => 'SendToUser',
        'Msg_Hall_MatchData' => 'SendToUser',
        'Msg_Hall_MatchSwitch' => 'SendToUser',
        'Msg_Hall_CancelMatch' => 'SendToUser',
        'Msg_Hall_FriendList' => 'SendToUser',
        'Msg_Hall_DeleteFriend' => 'SendToUser',
        'Msg_Hall_SearchPlayer' => 'SendToUser',
        'Msg_Hall_AddFriend' => 'SendToUser',
        'Msg_Hall_FriendsApply' => 'SendToUser',
        'Msg_Hall_FriendsAction' => 'SendToUser',
        'Msg_Hall_ApplyRequest' => 'SendToUser',
        'Msg_Hall_UpdateCard' => 'SendToUser',
    );


    //需要加载配置文件,包含mongodb ， redis 等
    public static $ReloadConfig = [
        '/../../vendor/autoload.php',
        '/../Common/DBInstance.php',
        '/../Common/MyTools.php',
        '/../../vendor/mLog.php',
        '/../Common/RedisServer.php',
        '/../Common/MongoDBserver.php',
        '/../Common/Log.php',

    ];

    public static function PorkStart ($worker , $name , $id) {

        $worker->name = Config::$ProjectName.'_BusinessWorker_' . "$name";
// bussinessWorker进程数量
        $worker->count = Config::$serviceCount;
// 本机ip，分布式部署时使用内网ip
        $worker->lanIp = Config::$localAddress;
// 服务注册地址
        $worker->registerAddress = Config::$registerAddress . ':' . Config::$registerPort;
// 中心服务注册地址
        $worker->centralAddress = Config::$centralAddress . ':' . Config::$centralPort;
        $worker->id = $id;

// 如果不是在根目录启动，则运行runAll方法
        return $worker;
    }

}