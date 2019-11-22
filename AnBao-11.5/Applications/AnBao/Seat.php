<?php
/**
 * Created by PhpStorm.
 * User: 龚坤
 * Date: 2018/9/19
 * Time: 15:26
 */

require_once __DIR__ . '/Seat.php';

class Seat
{
    //座位
    public $seat = array();


    //总牌堆
    public $tCards = array();



    public $PlayerNum = 5;

    public function __construct()
    {

    }

    //获得总牌堆
    public function getallpai () {
        for ($j = 1; $j < 14; $j++) {
            for ($i = 1; $i < 5; $i++) {
                $this->tCards[] = ($j*100)+$i;
            }
        }

        shuffle($this->tCards);
    }

    //发牌
    public function FaCard () {
        //清空总牌堆
        $this->tCards = array();
        $this->seat = array();
        $this->getallpai();
        for ($i = 0; $i < $this->PlayerNum; $i++) {
            $this->seat[$i] = array_slice($this->tCards , 0 , 5);
            for ($k = 0; $k < 5; $k++) {
                unset($this->tCards[$k]);
            }
            $this->tCards = array_values($this->tCards);
            arsort($this->seat[$i]);
            $this->seat[$i] = array_values($this->seat[$i]);
        }

        return $this->seat;
    }

    public function Unsetpai ($k , $pai) {
        $list = $this->seat[$k];

        foreach ($pai as $k => $v) {
            if (in_array($v , $list)) {
                unset($list[array_flip($list[$v])]);
            }
        }
        $this->seat[$k] = asort(array_values($list));
        return $this->seat[$k];

    }






}