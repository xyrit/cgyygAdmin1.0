<?php
namespace Admin\Model;
use Think\Model;

/**
 * 后台发货模型
 * @author Jerry
 * 2016.1.14
 */
class DeliverModel extends Model{
    protected $_validate = array(
        array('create_time','checkCreateTime','时间不是null', 0,'function'),//验证创建时间是否为null
    );
    
    protected function checkCreateTime($data) {
        if ($data === null) {
            return true;
        } else {
            return false;
        }
    }
}