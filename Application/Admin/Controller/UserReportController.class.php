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
 * 后台数据统计控制器
 * Author: 烟消云散 <1010422715@qq.com>
 */
class UserReportController extends AdminController
{

    /**
     *新增用户 
     */
    public function index(){
        $this->meta_title = '新增数据';
        $title = "新增数据";
        $this->assign('title', $title);
        $dtime = date('Y.m.d', NOW_TIME); /* 本周一结束时间 */
        $this->assign('dtime', $dtime);
        $today = (int)$today.'00';
        
        //获取数据表
//         $where['r.type'] = 2;
        $where=array(
            'r.type'=>2,
            'l.type'=>2,
        );
        //选择日期
        //选择开始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $start_info = date('Ymd', strtotime($_GET['start_time']));
            $where['r.info'][] = array('egt', $start_info);
        }
        
        //选择截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $end_info = date('Ymd', strtotime($_GET['end_time']));
            $where['r.info'][] = array('elt', $end_info);
        } else {
            $where['r.info'][] = array('elt', date('Ymd'));
        }
//         if(isset($_GET['day']) && !empty($_GET['day'])){
//             $today = $_GET['day'];
//         } else {
//             $today = date('Ymd', time());
//         }
//         $t_start = (int)($today.'00');
//         $t_end = $t_start+23;
//         $where = array(
//             'r.info' => array('between', array($t_start, $t_end)),
//             'r.type' => 2,
//         );
        
        $_GET['p'] = isset($_GET['p'])? $_GET['p']:0;
        $field = 'r.info,r.new_people,r.register_sum,r.register_consume,r.register_consume_sum,
                  r.register_consume_sum / r.register_consume register_consume_arppu,
                  l.one_left,l.seven_left,l.thirty_left';
        $list = M()
                ->table('__REPORT__ r')
                ->join('__LEFT_RATE__ l ON r.info = l.info')
                ->where($where)
                ->field($field)
                ->order('r.id desc')
                ->group('r.id')
                ->page($_GET['p'].',10')
                ->select();
        //用于excel导出
        $listAll = M()
        ->table('__REPORT__ r')
        ->join('__LEFT_RATE__ l ON r.info = l.info')
        ->where($where)
        ->field($field)
        ->select();
        $listAll = json_encode($listAll);
        $this->assign('listAll', $listAll);
        
        //拼接日期
        $count = count($list);
        for($i=0; $i<$count; $i++){
            $list[$i]['info'] = date('Y-m-d', strtotime((string)$list[$i]['info']));
        }
        $this->assign('list', $list);
        //分页
        $count = M()
        ->table('__REPORT__ r')
        ->join('__LEFT_RATE__ l ON r.info = l.info')
        ->where($where)
        ->count();
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        //新增用户总数
        $new_people_sum = 0;
        //新用户付费总人数
        $register_consume = 0;
        //新用户付费总额数
        $register_consume_sum = 0;
        //新用户ARPPU
        $register_consume_arppu = 0;
        foreach($list  as $key => $value){
            $new_people_sum += $value['new_people'];
            $register_consume += $value['register_consume'];
            $register_consume_sum += $value['register_consume_sum'];
        }
        $register_consume_arppu = $register_consume_sum / $register_consume;
        $this->assign('new_people_sum', $new_people_sum);
        $this->assign('register_consume', $register_consume);
        $this->assign('register_consume_sum', $register_consume_sum);
        $this->assign('register_consume_arppu', $register_consume_arppu);
        $this->display();
    }
    /**
     * 新增数据excel导出
     */
    public function addExcel(){
        if(isset($_POST['listAll']) && !empty($_POST['listAll'])){
            $listAll = I('listAll');
            $listAll = json_decode($listAll, true);
            $title = '新增数据';
            $field = 'info,r.new_people,register_sum,register_consume,register_consume_sum,register_consume_arppu,one_left,seven_left,thirty_left';
            $expCellName = array('日期','新增用户','累积用户','新用户付费人数','新用户付费总额','新用户ARPPU','次日留存率','7日留存率','30日留存率');
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data = array(
                'status'=>1,
                'info'=>'导出新增用书数据excel成功'
            );
            $this->ajaxReturn($data);
        }else{
//             echo '没有post过来表数据';
            $data=array(
                'status'=>1,
                'info'=>'没有post过来表数据'
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 活跃用户
     */
    public function activeUser(){
        //统计当日每个整点的总数据
        $this->meta_title = '活跃数据';
        $title = "活跃数据 ";
        $this->assign('title', $title);
        $dtime = date('Y.m.d', NOW_TIME); /* 本周一结束时间 */
        $this->assign('dtime', $dtime);
        
        //获取表数据
        $today = (int)$today.'00';
//         $where['r.type'] = 2;
        $where = array(
            'r.type'=>2,
            'l.type'=>2,
        );
        //选择时间针对表
        //选择开始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $start_info = date('Ymd', strtotime($_GET['start_time']));
            $where['r.info'][] = array('egt', $start_info);
        } 
        
        //选择截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $end_info = date('Ymd', strtotime($_GET['end_time']));
            $where['r.info'][] = array('elt', $end_info);
        } else {
            $where['r.info'][] = array('elt', date('Ymd'));//默认值是今天
        }
//         $where['r.type'] = 2;
//         $t_start = (int)($today.'00');
//         $t_end = $t_start+23;
//         $where = array(
//             'r.info' => array('between', array($t_start, $t_end)),
//             'r.type' => 2,
//         );
        $_GET['p'] = isset($_GET['p'])? $_GET['p']:0;
        $field = 'r.info,r.one_login,r.register_sum,r.week_login,r.month_login,
                  l.one_left,l.seven_left,l.thirty_left';
        $list = M()
                ->table('__REPORT__ r')
                ->join('LEFT JOIN __LEFT_RATE__ l ON r.info ')
                ->field($field)
                ->where($where)
                ->order('r.id desc')
                ->group('r.id')
                ->page($_GET['p'].',10')
                ->select();
        //用于excel导出
        $listAll = M()
        ->table('__REPORT__ r')
        ->join('__LEFT_RATE__ l ON r.info ')
        ->field($field)
        ->where($where)
        ->select();
        $listAll = json_encode($listAll);
        $this->assign('listAll',$listAll);
        
        $count = count($list);
        for($i=0; $i<$count; $i++){
            $list[$i]['info'] = date('Y-m-d', strtotime($list[$i]['info']));
        }
        $this->assign('list', $list);
        $list = M()
        ->table('__REPORT__ r')
        ->join('__LEFT_RATE__ l ON r.info ')
        ->where($where)
        ->count();
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    //活跃用户excel导出
    public function activeExcel(){
        if(isset($_POST['listAll']) && !empty($_POST['listAll'])){
            $listAll = I('listAll');
            $listAll = json_decode($listAll, true);
            $field = 'info,one_login,register_sum,week_login,month_login,one_left,seven_left,thirty_left';
            $title = '活跃用户数据';
            $expCellName = array('日期','日活跃用户','老用户','周活跃用户','月活跃用户','次日留存率','7日留存率','30日留存率');
            jerryExportExcel($title, $listAll, $field, $expCellName);
            $data=array(
                'status'=>1,
                'info'=>'活跃用户数据excel导出成功'
            );
            $this->ajaxReturn($data);
        }else{
//             echo '没有post过来表数据';
            $data=array(
                'status'=>1,
                'info'=>'没有post过来表数据',
            );
            $this->ajaxReturn($data);
        }
    }
}