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
 * 关键数据控制器
 * jerry,2016.2.17
 */
class DistributionController extends AdminController
{

    /**
     *分销数据总表
     */
    public function index()
    {
        $this->meta_title = '数据统计管理首页';
        $title = "分销数据总览 ";
        $this->assign('title', $title);


        $data=M('distribution_statistics')->order('time desc')->select();

        $this->assign('list', $data);
        $this->display();
    }

    /**
     *分销数据日表
     */
    public function daylist()
    {
        $this->meta_title = '前一天分销数据';
        $title = "前一天分销数据 ";
        $this->assign('title', $title);


        $data=M('distribution_statistics')->order('time desc')->limit(1)->find();

        $dataInfo=M('distribution_statistics')->order('time desc')->select();

        $str="[".$data['onelevel_count'].",".$data['twolevel_count'].",".$data['one_consumption'].",".$data['two_consumption'].",".$data['sum_consumption'].",".$data['sum_commission'].",".$data['withdrawals'].",".$data['accounts'].",".$data['sum_cp'].",".$data['surplus_commission']."]";

        $data=json_encode($data);
        $this->assign('data', $str);
        $this->assign('list', $dataInfo);
        $this->display();
    }



    public function graph(){

        $type=I('type','onelevel_count');

        switch($type)
        {
            case '1':
                $w='onelevel_count';
                $name='一级好友数量';
                break;
            case '2':
                $w='twolevel_count';
                $name='二级好友数量';
                break;
            case '3':
                $w='one_consumption';
                $name='一级好友消费';
                break;
            case '4':
                $w='two_consumption';
                $name='二级好友消费';
                break;
            case '5':
                $w='sum_consumption';
                $name='好友消费总额';
                break;
            case '6':
                $w='withdrawals';
                $name='提现到银行卡';
                break;
            case '7':
                $w='accounts';
                $name='转账到帐号';
                break;
            case '8':
                $w='sum_cp';
                $name='消费佣金总额';
                break;
            case '9':
                $w='surplus_commission';
                $name='获得佣金';
                break;
            case '10':
                $w='sum_commission';
                $name='剩余可用佣金';
                break;

        }

        $data=M('distribution_statistics')->field('atime,'.$w)->order('time desc')->select();

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


    //excel导出，实时数据
    public function realTimeExcel()
    {
        if (isset($_POST['listAll']) && !empty($_POST['listAll'])) {
            $listAll = I('listAll');
            $listAll = json_decode($listAll, true);
            $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_arppu,new_pay,consume_people,consume_sum,consume_arppu';
            $expCellName = array('日期', '新增用户', '访问次数', '充值用户', '充值总额', 'ARPPU', '新增付费用户', '消费用户', '消费总额', '消费ARPPU');
            $title = '实时数据';
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data = array(
                'status' => 1,
                'info' => '导出实时数据excel成功',
            );
            $this->ajaxReturn($data);
        } else {
//             echo '没有post过来表数据';
            $data = array(
                'status' => 2,
                'info' => '没有post过来表数据',
            );
            $this->ajaxReturn($data);
        }
    }


    public function find_createtime($day,$nday=0,$oday=0){
//查询当天数据
        if($day==1){
            $today=strtotime(date('Y-m-d 00:00:00'));
           
            $data['addtime'] = array('egt',$today);
            return $data;
//查询本周数据
        }else if($day==2){
            $arr=array();
            $arr=getdate();
            $num=$arr['wday'];
            $start=time()-($num-1)*24*60*60;
            $end=time()+(7-$num)*24*60*60;
           
            $data['addtime'] = array('between',array($start,$end));
            return $data;
//查询本月数据
        }else if($day==3){
            $start=strtotime(date('Y-m-01 00:00:00'));
            $end = strtotime(date('Y-m-d H:i:s'));
           
            $data['addtime'] = array('between',array($start,$end));
            return $data;
//查询本季度数据
        }else if($day==4){
            $month=date('m');
            if($month==1 || $month==2 ||$month==3){
                $start=strtotime(date('Y-01-01 00:00:00'));
                $end=strtotime(date("Y-03-31 23:59:59"));
            }elseif($month==4 || $month==5 ||$month==6){
                $start=strtotime(date('Y-04-01 00:00:00'));
                $end=strtotime(date("Y-06-30 23:59:59"));
            }elseif($month==7 || $month==8 ||$month==9){
                $start=strtotime(date('Y-07-01 00:00:00'));
                $end=strtotime(date("Y-09-30 23:59:59"));
            }else{
                $start=strtotime(date('Y-10-01 00:00:00'));
                $end=strtotime(date("Y-12-31 23:59:59"));
            }
           
            $data['addtime'] = array('between',array($start,$end));
            return $data;
//查询本年度数据
        }else if($day==5){
            $year=strtotime(date('Y-01-01 00:00:00'));
           
            $data['addtime'] = array('egt',$year);
            return $data;
//查询规定时间
        }else if($day==6){
            if($oday && $nday)
            {
                return false;
            }
            $start=$oday;
            $end=$nday;

            $data['addtime'] = array('between',array($start,$end));
            return $data;
//全部数据
        }else{
           
            return $data;
        }
    }

}