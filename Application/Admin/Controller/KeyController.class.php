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
class KeyController extends AdminController
{
    
    /**
     *每日数据 
     */
    public function index(){
        $this->meta_title = '数据统计管理首页';
        $title = "每日数据 ";
        $this->assign('title', $title);
        $dtime = date('Y.m.d', NOW_TIME); /* 本周一结束时间 */
        $this->assign('dtime', $dtime);
        
        $today = date('YmdH', time());
        $today = (int)$today.'00';
        
        //选择时间针对表
        //选择开始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $start_info = date('Ymd', strtotime($_GET['start_time']));
            $where['info'][] = array('egt', $start_info);
        }
        
        //选择截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $end_info = date('Ymd', strtotime($_GET['end_time']));
            $where['info'][] = array('elt', $end_info);
        } else {
            $where['info'][] = array('elt', date('Ymd'));//默认时间
        }
        
        //选定字段，针对图
        
        $where['type'] = 2;
        $_GET['p'] = isset($_GET['p'])? $_GET['p']:0;
        //获取数据表
        $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_sum/recharge_people recharge_arppu,
                 new_pay,consume_people,consume_sum, consume_sum/consume_people consume_arppu';
        $list = M('report')
        ->field($field)
        ->where($where)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->select();
        
        //用于excel导出
        $listAll = M('report')
        ->field($field)
        ->where($where)
        ->select();
//         $listAll=array(
//             'name'=>'小明',
//             'age'=>12,
//             'sex'=>'男'
//         );
        $listAll = json_encode($listAll);
        $this->assign('listAll', $listAll);
        
        //将info改为制定格式2015-12-12的日期
        $count = count($list);
        for($i=0; $i<$count; $i++){
            $list[$i]['info']=date('Y-m-d', strtotime((string)$list[$i]['info']));
        }
        $this->assign('list', $list);
        //新增用户总数
        $new_people_sum = 0;
        //充值用户总数，去重
        $recharge_people_sum = M('recharge')->count('distinct uid');
        //充值总额
        $recharge_sum = 0;
        //ARPPU
        $recharge_arppu = 0;
        //新增付费用户数量
        $new_pay_sum = 0;
        //消费用户，去重
        $consume_people_sum = M('lottery_attend')->count('distinct uid');
        //消费总额
        $consume_sum = 0;
        //消费ARPPU
        $consume_arppu = 0;
        foreach($list as $key=>$value){
            $new_people_sum += $value['new_people'];
            $recharge_sum += $value['recharge_sum'];
            $new_pay_sum += $value['new_pay_sum'];
            $consume_sum += $value['consume_sum'];
        }
        $recharge_arppu = $recharge_sum / $recharge_people_sum;
        $consume_arppu = $consume_sum / $consume_people_sum;
        $this->assign('new_people_sum', $new_people_sum);
        $this->assign('recharge_people_sum', $recharge_people_sum);
        $this->assign('recharge_sum', $recharge_sum);
        $this->assign('recharge_arppu', $recharge_arppu);
        $this->assign('new_pay_sum', $new_pay_sum);
        $this->assign('consume_people_sum', $consume_people_sum);
        $this->assign('consume_sum', $consume_sum);
        $this->assign('consume_arppu', $consume_arppu);
        //分页
        $count = M('report')
        ->where($where)
        ->count();
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 实时数据
     */
    public function realtime(){
        //统计当日每个整点的总数据
        $this->meta_title = '实时数据';
        $title = "实时数据 ";
        $this->assign('title', $title);
        $dtime = date('Y.m.d', NOW_TIME); /* 本周一结束时间 */
        $this->assign('dtime', $dtime);
        
        $today = date('YmdH', time());
        $today = (int)$today.'00';
        
        $field_1 = '';
        //获取数据图
        //         if (isset($_GET['type']) && !empty($_GET['type'])) {
        switch ($_GET['type']) {
            case 1://新增用户
                $field_1='new_people';
                break;
            case 2://访问次数
                $field_1 = 'visit';
                break;
            case 3://活跃用户
                $field_1 = 'one_login';
                break;
            case 4://充值用户
                $field_1 = 'recharge_people';
                break;
            case 5://充值总额
                $field_1 = 'recharge_sum';
                break;
            case 6://充值ARPPU
                $field_1 = 'recharge_sum/recharge_people recharge_arppu';
                break;
            case 7://新增付费用户
                $field_1 = 'new_pay';
                break;
            case 8://消费用户
                $field_1 = 'consume_people';
                break;
            case 9://消费总额
                $field_1 = 'consume_sum';
                break;
            case 10://消费ARPPU
                $field_1 = 'consume_sum/consume_people  consume_arppu';
                break;
            default:
                $field_1 = 'new_people';
                break;
        }
        //需求理解错误，应该是在一个图里同时显示今天，昨天，7天前，30天前的数据在一起，4条线
        //选择日期
        if(isset($_GET['day']) && !empty($_GET['day'])){
            $today = $_GET['day'];    
        } else {
            $today = date('Ymd', time());
        }
        
        //获取今天点的图数据
        $t_start = (int)($today.'00');
        $t_end = $t_start+23;
        $where_1 = array(
            'info' => array('between', array($t_start, $t_end)),
            'type' => 1,
        );
        $t_info = M('report')
        ->field($field_1)
        ->where($where_1)
        ->order('id desc')
        ->select();
        $this->assign('t_info', $t_info);
        
        //获取昨天的点图数据
        $y_start = date('Ymd', strtotime('-1 day '.$today)).'00';
        $y_end = $y_start + 23;
        $where_2 = array(
            'type' => 1,
            'info' => array('between', array($y_start, $y_end)),
        );
        $y_info = M('report')
        ->field($field_1)
        ->where($where_2)
        ->order('id desc')
        ->select();
        $this->assign('y_info', $y_info);
        
        //获取7天前点的图数据
        $s_start = date('Ymd', strtotime('-7 days '.$today)).'00';
        $s_end = $s_start + 23;
        $where_3 = array(
            'type' => 1,
            'info' => array('between', array($s_start, $s_end)),
        );
        $s_info = M('report')
        ->field($field_1)
        ->where($where_3)
        ->order('id desc')
        ->select();
        $this->assign('s_info', $s_info);
        
        //获取30天前点的图数据
        $t_start = date('Ymd', strtotime('-30 days '.$today)).'00';
        $start = (int)$start;
        $end = $start + 23;
        $where_4 = array(
            'type' => 1,
            'info' => array('between', array($start, $end)),
        );
        $thirty_info = M('report')
        ->field($field_1)
        ->where($where_4)
        ->order('id desc')
        ->select();
        $this->assign('thirty_info', $thirty_info);
        
        //获取今天点数据表
        $_GET['p'] = isset($_GET['p'])? $_GET['p'] : 0;
        $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_sum/recharge_people recharge_arppu,
                 new_pay,consume_people,consume_sum, consume_sum/consume_people consume_arppu';
        $t_start = (int)($today.'00');
        $t_end = $t_start+23;
        $where_1 = array(
            'info' => array('between', array($t_start, $t_end)),
            'type' => 1,
        );
        $list = M('report')
        ->field($field)
        ->where($where_1)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->select();
        
        //用于excel导出
        $listAll = M('report')
        ->field($field)
        ->where($where_1)
        ->select();
        $listAll = json_encode($listAll);
        $this->assign('listAll', $listAll);
        
        $count = count($list);
        for($i=0; $i<$count; $i++){
            $list[$i]['info']=substr($list[$i]['info'], -2).':00';
        }
        $this->assign('list', $list);
        $count = M('report')
        ->where($where_1)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->count();
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    //excel导出，每日数据
    public function dayExcel(){
        header('Content-type:text/html;charset=utf-8');
        if(isset($_POST['listAll']) && !empty($_POST['listAll'])){
            $listAll = I('listAll');
            $listAll = json_decode($listAll,true);
            $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_arppu,new_pay,consume_people,consume_sum,consume_arppu';
            $expCellName = array('日期','新增用户','访问次数','充值用户','充值总额','ARPPU','新增付费用户','消费用户','消费总额','消费ARPPU');
            $title = '每日数据';
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data = array(
                'status'=>1,
                'info'=>'导出每日数据excel表格成功',
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
    
    //excel导出，实时数据
    public function realTimeExcel(){
        if(isset($_POST['listAll']) && !empty($_POST['listAll'])){
            $listAll = I('listAll');
            $listAll = json_decode($listAll, true);
            $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_arppu,new_pay,consume_people,consume_sum,consume_arppu';
            $expCellName = array('日期','新增用户','访问次数','充值用户','充值总额','ARPPU','新增付费用户','消费用户','消费总额','消费ARPPU');
            $title = '实时数据';
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data = array(
                'status'=>1,
                'info'=>'导出实时数据excel成功',
            );
            $this->ajaxReturn($data);
        }else{
//             echo '没有post过来表数据';
            $data = array(
                'status'=>2,
                'info'=>'没有post过来表数据',
            );
            $this->ajaxReturn($data);
        }
    }
    
    //excel导出成功的源代码
//     public function excel01(){
//         //获取数据
//         $where['type'] = 2;
//         $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_sum/recharge_people recharge_arppu,
//                  new_pay,consume_people,consume_sum, consume_sum/consume_people consume_arppu';
//         $list = M('report')
//         ->field($field)
//         ->where($where)
//         ->order('id desc')
//         ->select();
//         //将info改为制定格式2015-12-12的日期
//         $count = count($list);
//         for($i=0; $i<$count; $i++){
//             $list[$i]['info']=date('Y-m-d', strtotime((string)$list[$i]['info']));
//         }
//         vendor("PHPExcel");
//         $objPHPExcel = new \PHPExcel();
//         $objPHPExcel->setActiveSheetIndex(0);//把新创建的sheet设定为当前活动sheet，设置活动sheet，如果有3个sheet的话，是0，1，2
//         $objSheet=$objPHPExcel->getActiveSheet();//获取当前活动sheet
//         $title = '每日数据';
//         $objSheet->setTitle($title);//给当前活动sheet取一个名字
// //         $objSheet->setTitle($i."年级");//给当前活动sheet取一个名字
// //         $data=$db->getDataByGrade($i);//查询每个年级的学生数据
//         $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
//         $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_arppu,new_pay,consume_people,consume_sum,consume_arppu';
//         $field = explode(',',$field);
//         $cellNum = count($field);
//         $expCellName = array('日期','新增用户','访问次数','充值用户','充值总额','ARPPU','新增付费用户','消费用户','消费总额','消费ARPPU');
//         for($i=0;$i<$cellNum;$i++){
//             $objSheet->setCellValue($cellName[$i].'1', $expCellName[$i]);
//         }
// //         $objSheet->setCellValue('A1','日期')->setCellValue('B1','新增用户')->setCellValue('C1','访问次数')
// //                  ->setCellValue('D1','充值用户')->setCellValue('E1','充值总额')->setCellValue('F1','ARPPU')
// //                  ->setCellValue('G1','新增付费用户')->setCellValue('H1','消费用户')->setCellValue('I1','消费总额')
// //                  ->setCellValue('J1','消费ARPPU');//设置第一行，填充数据
//         $j=2;//要从第二行开始
//         //foreach=>for+$j
// //         foreach($list as $key=>$val){
// //             $objSheet->setCellValue("A".$j,$val['info'])->setCellValue('B'.$j,$val['new_people'])->setCellValue('C'.$j,$val['visit'])
// //                      ->setCellValue("D".$j,$val['recharge_people'])->setCellValue('E'.$j,$val['recharge_sum'])->setCellValue('F'.$j,$val['recharge_arppu'])
// //                      ->setCellValue("G".$j,$val['new_pay'])->setCellValue('H'.$j,$val['consume_people'])->setCellValue('I'.$j,$val['consume_sum'])
// //                      ->setCellValue("J".$j,$val['consume_arppu']);
// //             $j++;
// //         }
//         foreach($list as $key=>$val){
//             for($i=0;$i<$cellNum;$i++){
//                 $objSheet->setCellValue($cellName[$i].$j,$val[$field[$i]]);
//             }
//             $j++;
//         }
// //         $fileName = $_SESSION['loginAccount'].date('_YmdHis');
// //         $xlsTitle = iconv('utf-8', 'gb2312', '测试');//文件名称
// //         header('pragma:public');
// //         header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
// //         header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
// //         $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//         $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
// //         $objWriter->save('php://output');
// //         $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');//生成excel文件
//         $fielName = $title.date('_YmdHis').'xls';
//         $dir = __ROOT__."/Public/Excel/";
//         $objWriter->save($dir.'/'.$fielName);//保存文件    
//     }
}