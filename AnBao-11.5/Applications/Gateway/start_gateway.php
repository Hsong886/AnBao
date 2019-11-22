<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../Common/Config.php';

foreach (Config::$ReloadConfig as $k => $v) {
    require_once __DIR__ . $v;
}

// gateway 进程
//ssl证书
//$context = array(
//    // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
//    'ssl' => array(
//        // 请使用绝对路径
//        'local_cert'                 => '磁盘路径/server.pem', // 也可以是crt文件
//        'local_pk'                   => '磁盘路径/server.key',
//        'verify_peer'                => false,
//        // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
//    )
//);
$gateway = new Gateway(Config::$gatewayProtocol . '://0.0.0.0:' . Config::$gatewayPort);

// gateway名称，status方便查看
$gateway->name = Config::$ProjectName.'_Gateway_' . Config::$gatewayProtocol;
// gateway进程数
$gateway->count = Config::$serviceCount;
// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = Config::$localAddress;
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = Config::$gatewayStartPort;
// 服务注册地址
$gateway->registerAddress = Config::$registerAddress . ':' . Config::$registerPort;

// ==路由绑定==
$gateway->router = function($worker_connections, $client_connection, $cmd, $buffer)
{
    //var_dump(__FILE__ . __LINE__);
    $session = unserialize($client_connection->session);
    if ($session && isset($session['router']) && !empty($session['router'])) {
        $client_connection->businessworker_address = $session['router'];
        //var_dump('session[router]' . $client_connection->businessworker_address);
       // echo "1:$client_connection->businessworker_address\n";
    }
    // 临时给客户端连接设置一个businessworker_address属性，用来存储该连接被绑定的worker进程下标
    if (!isset($client_connection->businessworker_address) || !isset($worker_connections[$client_connection->businessworker_address])) {
        $client_connection->businessworker_address = array_rand($worker_connections);
        //var_dump('array_rand' . $client_connection->businessworker_address);
        //echo "2:\n";
    }
    return $worker_connections[$client_connection->businessworker_address];
};

if (Config::$pingSwitch) {
    //心跳间隔
    $gateway->pingInterval = Config::$pingInterval;
    //心跳次数
    $gateway->pingNotResponseLimit = Config::$pingNotResponseLimit;

}

/* 
// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$gateway->onConnect = function($connection)
{
    $connection->onWebSocketConnect = function($connection , $http_header)
    {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
        if($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net')
        {
            $connection->close();
        }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
    };
}; 
*/

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

