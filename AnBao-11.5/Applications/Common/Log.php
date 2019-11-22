<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/25
 * Time: 9:55
 */

class Log {

    protected static $loginfo = [];

    public static function set($msg = '', $error = false)
    {
        self::$loginfo[] = $msg;
        $fp = fopen(ROOT . '/' . 'log' . '/' . date('Ymd') . ".log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, date('Y-m-d H:i:s') . "\t" . $msg . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($error) {
            die($msg);
        }
    }

    public static function msg($msg = '')
    {
        $msg = is_array($msg) ? json_encode($msg) : $msg;
        $fp = fopen(ROOT . '/' . 'msg' . '/' . date('Ymd') . ".log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, date('Y-m-d H:i:s') . "\t" . $msg . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public static function show()
    {
        return self::$loginfo;
    }
}



?>