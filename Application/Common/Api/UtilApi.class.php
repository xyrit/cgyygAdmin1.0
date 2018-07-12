<?php

namespace Common\Api;

class UtilApi {
    /**
     * 检测用户是否登录
     * @return integer 0-未登录，大于0-当前登录用户ID
     */
    public static function timeStamp(){
         date_default_timezone_set(PRC);
         $nowtime=date("Y-m-d G:i:s");
         $dateline=strtotime($nowtime);
         return $dateline;
    }
}

