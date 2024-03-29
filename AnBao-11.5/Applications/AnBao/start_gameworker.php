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

// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称


$worker->name = Config::$ProjectName.'_BusinessWorker_' . "AB";
// bussinessWorker进程数量
$worker->count = Config::$serviceCount;
// 本机ip，分布式部署时使用内网ip
$worker->lanIp = Config::$localAddress;
// 服务注册地址
$worker->registerAddress = Config::$registerAddress . ':' . Config::$registerPort;
// 中心服务注册地址
$worker->centralAddress = Config::$centralAddress . ':' . Config::$centralPort;
$worker->id = GAME_AnBao;
// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

