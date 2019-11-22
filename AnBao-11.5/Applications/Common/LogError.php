<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/25
 * Time: 9:55
 */

//设置错误处理器
error_reporting(E_ALL);
set_error_handler(array('LogError' , 'errorHandler'));
//在脚本结束时运行的函数
register_shutdown_function(array('LogError' ,'fatalErrorHandler'));

/**
 * 错误处理
 * @param int    $err_no      错误代码
 * @param string $err_msg  错误信息
 * @param string $err_file    错误文件
 * @param int    $err_line     错误行号
 * @return string
 */
class LogError{
    public static function errorHandler($err_no = 0, $err_msg = '', $err_file = '', $err_line = 0 , $error_context = [])
    {

        echo "--------------Have a Error----------------$err_line\n";
        $log = [

            'err_time' => date('Y-m-d h-i-s') ,
            'err_no' => $err_no ,
            'err_msg' => $err_msg,
            'err_file' => $err_file,
            'err_line' => $err_line,
        ];


//    $msg = is_array($msg) ? json_encode($msg) : $msg;
        $fp = fopen(ROOT . '/' . 'msg' . '/' . date('Ymd') . ".log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, date('Y-m-d H:i:s') . "\t" . json_encode($log) . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
//    error_log(implode(' ',$log)."\r\n",3, $log_path);
        //echo implode(' ',$log)."";
    }

    /**
     * 捕捉致命错误
     * @return string
     */
    public static function fatalErrorHandler() {
        $e = error_get_last();
        switch ($e['type']) {
            case 1:
                self::errorHandler($e['type'], $e['message'], $e['file'], $e['line']);
                break;
        }
    }

}


?>