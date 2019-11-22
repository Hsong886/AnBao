<?php
/**
 * Created by PhpStorm.
 * User: 龚坤
 * Date: 2018/9/19
 * Time: 15:26
 */

//require_once __DIR__ . '/Seat.php';

class Rule
{

    /*
     * 0 无牛
     * 1 牛1
     * 2 牛2
     * 3 牛3
     * 4 牛4
     * 5 牛5
     * 6 牛6
     * 7 牛7
     * 8 牛8
     * 9 牛9
     * 10 牛牛
     * 11 顺子牛
     * 12 同花牛
     * 13 金牌牛
     * 14 五花牛
     * 15 炸弹
     * 16 五小牛
     * 17 同花顺
     * */

    private $pai = array();
    public function __construct()
    {

    }


    public function getpaixin ($pai) {
        $this->pai = $pai;
        $dian = $this->getdian($pai);
        $hua = $this->gethua($pai);

        if (count(array_count_values($hua)) == 1)
        {

            $shun = $this->islianxu($dian['use']);

            if ($shun)
            {
                //同花顺
                return 17;
            }

            $teshu = $this->teshu($dian['use'] , $dian['main']);

            if ($teshu) {
                return $teshu;
            };
            //同花牛
            return 12;
        };



        if ($this->teshu($dian['use'] , $dian['main'])) {
            return $this->teshu($dian['use'] , $dian['main']);
        }

        $shun = $this->islianxu($dian['use']);
        if ($shun) {
            //顺子牛
            return 11;
        }

        $nn = $this->niuniu($dian['use'] , array_sum($dian['use']) % 10);

        if ($nn === null) {
            return 0;
        } else {
            if ($nn === 0) {
                return 10;
            } else {
                return $nn;
            }
        }

    }

    public function niuniu ($pai , $num) {
        $wu = false;
        foreach ($pai as $k => $v) {
            if ($wu) {
                return $num;
                break;
            }
            for ($i = $k; $i < 4; $i++) {

                if (($v + $pai[$i+1]) % 10 == $num) {
                    $wu = true;
                    break;
                }
            }
        }
    }

    /*
         * 0 无牛
         * 1 牛1
         * 2 牛2
         * 3 牛3
         * 4 牛4
         * 5 牛5
         * 6 牛6
         * 7 牛7
         * 8 牛8
         * 9 牛9
         * 10 牛牛
         * 11 顺子牛
         * 12 同花牛
         * 13 金牌牛
         * 14 五花牛
         * 15 炸弹
         * 16 五小牛
         * 17 同花顺
         * */
    //获得对应倍数
    public function beishu ($id) {
        $num = 2;
        switch ($id) {
            case 17:
                $num = 15;
                break;
            case 16:
                $num = 10;
                break;
            case 15:
                $num = 9;
                break;
            case 14:
                $num = 8;
                break;
            case 13:
                $num = 7;
                break;
            case 12:
                $num = 6;
                break;
            case 11:
                $num = 6;
                break;
            case 10:
                $num = 5;
                break;
            case 0:
                $num = 1;
                break;
        }

        $arr = [1,2,3,4,5,6];
        $num = in_array($id , $arr) ? 1 : $num;
        return $num;
    }

    public function teshu ($dian , $main)
    {
        $dian['use'] = $dian;
        $dian['main'] = $main;
        $wuxiao = $this->iswuxiaoniu($dian['use']);
        if ($wuxiao)
        {
            //五小牛
            return 16;
        }

        if (max(array_count_values($this->getputong($this->pai))) == 4)
        {
            //炸弹
            return 15;
        }

        if ($dian['main'])
        {
            //五花牛
            return 14;
        }
        if (max(array_count_values($this->getputong($this->pai))) == 3) {
            //金牌牛
            return 13;
        }
    }

    public function getputong ($pai) {
        $arr = [];

        foreach ($pai as $k => $v) {
            $num = intval($v / 100);

            $arr[] = $num ;
        }
        return $arr;
    }

    //判断是否是五小牛
    public function iswuxiaoniu ($dian)
    {
        $wuxiao = false;
        $num = array_sum($dian);
        if ($num <= 10) {
            $wuxiao = true;
        }
        return $wuxiao;
    }

    //判断是否是连续的
    public function islianxu ($dian) {

        $max = true;
        $temp = 0;
        for ($i = 0; $i < 5; $i++) {
            if ($i == 0) {
                $temp = $dian[$i];
            } else {
                if (($temp + 1) != $dian[$i]) {
                    $max = false;
                    break;
                } else {
                    $temp++;
                }

            }

        }

        return $max;


    }

    //获得点
    public function getdian ($pai)
    {
        $arr = [];
        $num1 = 0;
        foreach ($pai as $k => $v) {
            $num = intval($v / 100);
            $num > 10 ? $num1++ : $num1--;
            $arr['use'][] = $num > 10 ? 10 : $num ;
        }
        $num1 == 5 ? $arr['main'] = true : $arr['main'] = false;
        return $arr;
    }


    //获得花
    public function gethua ($pai)
    {
        $arr = [];
        foreach ($pai as $k => $v) {
            $arr[] = $v % 100;
        }
        return $arr;
    }


    public function getdian1 ($pai) {
        $arr = [];

        foreach ($pai as $k => $v) {
            $num = intval($v / 100);

            $arr[] = $num ;
        }

        return $arr;
    }

    //比较牌点数花色大小
    public function Comper ($xian , $zhuang) {

        $ll = max($xian);
        $lll = max($zhuang);
        $num = $ll > $lll ? 1 : 2;
        return $num;
    }


}



