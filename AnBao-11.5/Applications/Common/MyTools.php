<?php
date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\DbModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/6 0006
 * Time: 16:38
 */


define("CENTARL_MATCH", -2);//匹配服
define("CENTARL_SETTLEMENT", -1);//结算服
define("CENTARL_HTTP", -3);//结算服

define("GAME_HALL", 0);//大厅
define("GAME_AnBao", 1);//暗堡
define("GAME_SIZE", 4);//数量

class MyTools
{
    public static $ConcectName = [
        -4 => 'CENTARL_FRIENDSYS  : ',
        -3 => 'CENTARL_BACKSTAGE  : ',
        -2 => 'CENTARL_MATCH      : ',
        -1 => 'CENTARL_SETTLEMENT : ',
        0 => 'GAME_HALL          : ',
        1 => 'GAME_AnBao         : ',
    ];

    public static $GameNameToId = [
        'GAME_AnBao' => 1,
    ];

    public static $GetUserdata = 'uid,name,head,vip,';

    /**
     * 得到当前毫秒级时间戳
     */
    public static function GET_MS()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * 得到当前时分秒
     */
    public static function GET_NOW()
    {
        return date('Y-m-d H:i:s',time());
    }

    /**
     * 得到今天日期
     */
    public static function GET_TODAY()
    {
        return date('Y-m-d',time());
    }

    /**
     * 得到本周一
     */
    public static function GET_MONDAY()
    {
        return date('Y-m-d', strtotime('monday', time()));
        //return date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
    }

    /**
     * 得到本周日
     */
    public static function GET_SUNDAY()
    {
        return date('Y-m-d', strtotime('sunday', time()));
    }

    /**
     * 得到上周一
     */
    public static function GET_LAST_MONDAY()
    {
        return date('Y-m-d', strtotime('-1 monday', time()));
    }

    /**
     * 得到上周日
     */
    public static function GET_LAST_SUNDAY()
    {
        return date('Y-m-d', strtotime('-1 sunday', time()));
    }

    /**
     * 得到本月一日
     */
    public static function GET_1ST_MONTH()
    {
        return date('Y-m-d', strtotime(date('Y-m', time()) . '-01 00:00:00'));
    }

    /**
     * 得到本月最后一日
     */
    public static function GET_1LST_MONTH()
    {
        return date('Y-m-d', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
    }

    /**
     * 得到上月一日
     */
    public static function GET_LAST_1ST_MONTH()
    {
        return date('Y-m-d', strtotime('-1 month', strtotime(date('Y-m', time()) . '-01 00:00:00')));
    }

    /**
     * 得到上月最后一日
     */
    public static function GET_LAST_1LST_MONTH()
    {
        return date('Y-m-d', strtotime(date('Y-m', time()) . '-01 00:00:00') - 86400);
    }

    //得到某年，几月份的天数
    public static function Get_Year_Month ($month , $year) {
        return cal_days_in_month(CAL_GREGORIAN , $month , $year);
    }

    //-------------------------------检测类
    //做纯数字检测
    public static function Check_num ($string) {
        $string = !is_string($string) ? (string)($string) : $string;

        return ctype_digit($string);
    }

}