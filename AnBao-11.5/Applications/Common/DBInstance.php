<?php
date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\DbModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/6 0006
 * Time: 16:38
 */

class DBInstance
{
    /**
     * 数据库连接
     * @var object
     */
    public static $db;

    /**
     * 数据库连接初始化
     */
    public static function Init($dbConfig)
    {
        self::$db = new DbModel(
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['user'],
            $dbConfig['password'],
            $dbConfig['dbname'],
            $dbConfig['tablepre'],
            $dbConfig['charset']
        );
    }

    /**
     * 获取房卡数
     * @param $uid
     * @return int
     */
    public static function getncard($uid)
    {
        $sql = '';
        self::$db->query($sql);
        return self::$db->table('ry_user')->select('ncard')->where(array('uid' => $uid))->one(true);
    }

    /*获得最低房间*/
    public static function GetLow ($tt) {
        $val = self::$db->table('ry_sysconfig')->select('*')->where(array('key' => 'SEAT'))->one();

        $val = json_decode($val['val'] , true);
        return $val[$tt];
    }

    /**
     * 上线
     * @param $uid
     * @param $rcard
     */
    public static function setUserOnline($uid = 0)
    {
        if($uid == 0)

            self::$db->table('ry_user')->where('1')->update(array('online' => 1));
        else
            self::$db->table('ry_user')->where(array('uid' => $uid))->update(array('online' => 1));
    }

    /**
     * 下线
     * @param $uid
     * @param $rcard
     */
    public static function setUserOffline($uid = 0)
    {

        if($uid == 0){
            self::$db->table('ry_user')->where('1')->update(array('online' => 0));
        }
        else{
            self::$db->table('ry_user')->where(array('uid' => $uid))->update(array('online' => 0));
        }
    }


    //麻将换牌查询
    public static function GetHuan ()
    {
        $val = self::$db->table('ry_huan')->where()->asArray()->all();
        return $val;
    }
    public static function delhuan ($uid) {
        self::$db->table('ry_huan')->where(['uid' => $uid])->delete();

    }

    /*
     *
     * 扣除房卡*/
    public static function Uprcard ($uid , $num , $type = 1) {
        if ($type == 1) {
            self::$db->table('ry_user')->where(array('uid' => $uid))->decrement(array('gold' => $num));
        } elseif ($type == 3) {
            self::$db->table('ry_user')->where(array('uid' => $uid))->update(array('gold' => $num));
        } else {
            self::$db->table('ry_user')->where(array('uid' => $uid))->increment(array('gold' => $num));
        }
        echo("ssssssssssssssss111111\n");
        var_dump(self::$db->getQuerySql());
    }



    public static function Upstatus ($id) {
        return self::$db->table('ry_history')->where(array('id' => $id))->update(array('status' => 1));
    }

    public static function GetHistory($id)
    {
        return self::$db->table('ry_history')->where(array('id' => $id))->asArray()->one();
    }

    /**
     * 新增history战绩列表
     */
    public static function InsertHistory($data)
    {
        return self::$db->table('ry_history')->insert($data);
    }

    /**
     * 修改history战绩列表
     */
    public static function UpdateHistory($data , $id)
    {
        return self::$db->table('ry_history')->where(array('id' => $id))->update($data);
    }


    //新增hitdeail
    public static function InsertHitdeail($data)
    {
        return self::$db->table('ry_hitdetail')->insert($data);
    }



    /**
     * 获取user表数据
     * @param $uid
     * @return int
     */
    public static function getUser($key, $uid, $isStr = false)
    {
        $val = self::$db->table('ry_user')->select($key)->where(array('uid' => $uid))->one(true);

        if ($val) {
            return $val;
        } else {
            return $isStr ? ' ' : 0;
        }
    }

    /*获得机器人所有表单，用完在取*/
    public static function getRot () {
        $val = self::$db->table('ry_user')->where(array('type' => 0))->asArray()->all();
        shuffle($val);
        return $val;
    }

    /**
     * 获取很多user表数据
     * @param $uid
     * @return int
     */
    public static function getArrUser($key, $uid)
    {
        $val = self::$db->table('ry_user')->select($key)->where(array('uid' => $uid))->asArray()->one();
        shuffle($val);
        return $val;
    }

    /**
     * 获取sysconfig表数据
     */
    public static function getSysconfig($key)
    {
        $val = self::$db->table('ry_sysconfig')->select('val')->where(array('key' => $key))->one(true);

        if ($val) {
            return $val;
        } else {
            return 0;
        }
    }

    /**
     * 更改user表数据
     */
    public static function updateUser($key, $uid, $num)
    {
        $ret = self::$db->table('ry_user')->where(array('uid' => $uid))->increment(array($key => $num));
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 随机登录 - 获取用户信息
     * @param $openid
     * @return array
     */
    public static function randLogin($id)
    {
        $user = self::$db->table('ry_user')->select('*')->where(array('id' => $id, 'online' => 0))->asArray()->one();
        return $user;
    }

    /**
     * 随机登录 - 获取用户信息
     */
    public static function userLogin($openid)
    {
        if (strpos($openid, 'ry_register') === 0)
        {
            $uid = substr($openid, 11);
        }
        elseif (strpos($openid, 'ry_email') === 0)
        {
            $uid = substr($openid, 8);
        }
//        else {
//
//        }
        $uid = self::$db->table('ry_weixin')->select('uid')->where(array('openid' => $openid))->asArray()->one(true);
        $user = self::$db->table('ry_user')->select('*')->where(array('uid' => $uid))->asArray()->one();
        return $user;

    }




    /**
     * 跑马灯
     */
    public static function getMarquee()
    {
        return self::$db->table('ry_marquee')->select('content')->where(array('status' => 1))->asArray()->all();
    }


    /*获得层级关系表*/
    public static function GetNext($uid) {


        $sys[1] = self::$db->table('ry_sysconfig')->where(array('key' => 'one'))->asArray()->one();
        $sys[2] = self::$db->table('ry_sysconfig')->where(array('key' => 'two'))->asArray()->one();
        $sys[3] = self::$db->table('ry_sysconfig')->where(array('key' => 'three'))->asArray()->one();

        $list = [];
        $num = 0;
        do {
            $ts = self::$db->table('ry_nexus')->where(array('uid' => $uid))->asArray()->one();
            if (!empty($ts)) {
                $uid = $ts['fuid'];
                $list[$ts['fuid']] = $sys[$num+1]['val'] / 100;
            }
            $num++;
        }while(!empty($ts) && $num < 3);
        return $list;
    }

    /*新增返利表*/
    public static function AddRebate ($uid , $fuid , $num) {
        self::$db->table('ry_rebate')->insert(array('fuid' => $fuid , 'uid' => $uid , 'rebate' => $num , 'created' => date('Y-m-d H:i:s')));

        self::$db->table('ry_user')->where(array('uid' => $fuid))->increment(array('rebate_num' => 1));
        self::$db->table('ry_user')->where(array('uid' => $fuid))->increment(array('w_rebate' => $num));
        self::$db->table('ry_user')->where(array('uid' => $fuid))->increment(array('rebate' => $num));
        return true;
    }

    /*新增战绩*/
    public  static function insertHit ($val) {
        self::$db->table('ry_hitdetail')->insert($val);
        var_dump(self::$db->getQuerySql());
        return true;
    }
}