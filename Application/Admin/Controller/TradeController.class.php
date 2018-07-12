<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 烟消云散 <1010422715@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

/**
 * 收支数据控制器
 * Author: 烟消云散 <1010422715@qq.com>
 */
class TradeController extends AdminController
{

    /**
     *充值数据
     */
    public function index(){
        $this->meta_title = '数据统计管理首页';
        $title = "充值数据总览 ";
        $this->assign('title', $title);


        $data=M('tj_recharge_data')->order('time desc')->select();

        $this->assign('list', $data);
        $this->display();
    }

    /**
     *充值数据图表
     */
    public function graph(){

        $type=I('type','onelevel_count');

        switch($type)
        {
            case '1':
                $w='active_users';
                $name='活跃用户数目';
                break;
            case '2':
                $w='recharge_user_count';
                $name='充值人数';
                break;
            case '3':
                $w='recharge_user_money_count';
                $name='充值总额';
                break;
            case '4':
                $w='arpu';
                $name='ARPU';
                break;
            case '5':
                $w='arppu';
                $name='arppu';
                break;
            case '6':
                $w='new_paying_customers_money';
                $name='新用户总充值金额';
                break;
            case '7':
                $w='new_paying_customers';
                $name='新用户充值人数';
                break;
            case '8':
                $w='new_paying_arpu';
                $name='首冲ARPU ';
                break;

        }

        $data=M('tj_recharge_data')->field('atime,'.$w)->order('time desc')->select();

        foreach($data as $row)
        {
            $onelevel_count[]=$row[$w];
            $atime[]=$row['atime'];
        }

        $name1[0] = $name;
        $arr1= array(
            'aname'=>$name1,
            'onelevel_count'=>$onelevel_count,
            'atime'=>$atime
        );


        //  $this->assign('onelevel_count', $onelevel_count);
        //$this->assign('atime', $atime);
        echo json_encode($arr1);

    }



    
    /**
     * 充值数据excel导出
     */
    public function rechargeExcel(){
        if(isset($_POST['listAll']) && !empty($_POST['listAll'])){
            $listAll = I('listAll');
            $listAll=json_encode($listAll,true);
            $field = 'info,one_login,recharge_people,recharge_sum,recharge_arppu,recharge_arpu,first_recharge,first_recharge_sum,first_recharge_arppu ';
            $title = '活跃用户数据';
            $expCellName = array('日期','活跃用户','充值用户','充值总额','ARPU','ARPPU','首充用户','首充总额','首充ARPPU');
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data=array(
                'status'=>1,
                'info'=>'充值数据excel导出成功'
            );
            $this->ajaxReturn($data);
        }else{
//             echo '没有post过来数据';
            $data=array(
                'status'=>2,
                'info'=>'没有post过来数据'
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 消费数据
     */
    public function consume(){
        $this->meta_title = '数据统计管理首页';
        $title = "分销数据总览 ";
        $this->assign('title', $title);


        $data=M('tj_consumption_data')->order('time desc')->select();

        $this->assign('list', $data);
        $this->display();
    }
    
    /**
     * 消费数据excel导出
     */
    public function consumeExcel(){
        if(isset($_POST['listAll']) && !empty($_POST['listAll'])){
            $listAll = I('listAll');
            $listAll=json_decode($listAll,true);
            $field = 'info,one_login,consume_people,consume_sum,consume_arppu,first_consume,first_consume_sum,first_consume_arppu ';
            $title = '消费数据';
            $expCellName = array('日期','活跃用户','消费用户','消费总额','消费ARPU','消费ARPPU','首充消费用户','首充消费总额','首次消费ARPPU');
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data=array(
                'status'=>1,
                'info'=>'消费数据excel导出成功',
            );
            $this->ajaxReturn($data);
        }else{
//             echo '没有post过来数据';
            $data=array(
                'status'=>2,
                'info'=>'没有post过来数据',
            );
            $this->ajaxReturn($data);
        }
    }
}