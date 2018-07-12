<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014 1010422715@qq.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 烟消云散 <1010422715@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use User\Api\UserApi as UserApi;

/**
 * 后台数据统计控制器
 * Author: 烟消云散 <1010422715@qq.com>
 *
 * php获取今日开始时间戳和结束时间戳
 *
 * $a=date('Ymd',$qtime);/*格式时间戳为 20141024
 *
 * $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
 *
 * $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
 *
 * php获取本月起始时间戳和结束时间戳
 *
 * $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
 * $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
 * PHP mktime() 函数用于返回一个日期的 Unix 时间戳。
 */
class StatisticsController extends AdminController
{

    public function index01()
    {
        $this->meta_title = '管理首页';
        $title = "今日数据统计 ";
        $this->assign('title', $title);
        $dtime = date('Y.m.d', NOW_TIME); /* 本周一结束时间 */
        $this->assign('dtime', $dtime);
        /* 销售统计 */
        $order = M("order")->where("total!=''")->select();
        $qtime = NOW_TIME;
        foreach ($order as $n => $val) {
            $time = $val['create_time'];
            $a = date('Ymd', $qtime); /* 格式时间戳为 20141024 */
            $b = date('Ymd', $time);
            if ($a == $b) { // 当天
                /* 商品销售额 */
                $salesmoney += $val['total'];
                /* 运费 */
                $trans += $val['shipprice'];
                /* 总销售额 */
                $total += $val['pricetotal'];
                $id = $val['id'];
                $list = M("shoplist")->where("orderid='$id'")->select();
                foreach ($list as $k => $vo) {
                    /* 退货中的商品id */
                    $goodid = $vo['goodid'];
                    $price = get_good_price($goodid);
                    
                    /* 销售的商品件数 */
                    $salenum += $vo['num'];
                    /* 销售的商品种类数 */
                    $salecount += 1;
                }
            }
        }
        $this->assign('salecount', $salecount);
        
        $this->assign('salenum', $salenum);
        $this->assign('trans', $trans);
        $this->assign('salesmoney', $salesmoney);
        $this->assign('total', $total);
        /**
         * 当天退货金额、种类、数量计算
         */
        $data = M("backlist")->where("status='1'")->select();
        $cqtime = NOW_TIME;
        foreach ($data as $k => $val) {
            /* 格式时间戳为 20141024 */
            $c = date('Ymd', $cqtime);
            $time = $val['create_time'];
            $d = date('Ymd', $time);
            if ($c == $d) { // 当天
                /* 退货中的商品id */
                $goodid = $val['goodid'];
                
                /* 退货中的商品总额 */
                $back += $val['total'];
                /* 退货中的商品件数 */
                $backnum += $val['num'];
                /* 退货中的商品种类数 */
                $backcount += 1;
            }
        }
        
        $this->assign('back', $back);
        $this->assign('backcount', $backcount);
        $this->assign('backnum', $backnum);
        /**
         * 当天换货金额、种类、数量计算
         */
        $data = M("exchange")->where("status='1'")->select();
        $eqtime = NOW_TIME;
        foreach ($data as $k => $val) {
            $e = date('Ymd', $eqtime); /* 格式时间戳为 20141024 */
            $time = $val['create_time'];
            $f = date('Ymd', $time);
            if ($e == $f) { // 当天
                /* 换货中的商品总额 */
                $goodid = $val['goodid'];
                $price = get_good_price($goodid);
                $change += $val['num'] * $price;
                /* 换货中的商品件数 */
                $changenum += $val['num'];
                /* 换货中的商品种类数 */
                $changecount += 1;
            }
        }
        $this->assign('change', $change);
        $this->assign('changecount', $changecount);
        $this->assign('changenum', $changenum);
        /**
         * 当天申请取消订单金额、种类、数量计算
         */
        $cdata = M("cancel")->where("status='1'")->select();
        $cqtime = NOW_TIME;
        foreach ($cdata as $k => $val) {
            /* 格式时间戳为 20141024 */
            $q = date('Ymd', $cqtime);
            $time = $val['create_time'];
            $s = date('Ymd', $time);
            if ($q == $s) {
                // 当天
                /* 取消订单的商品总额 */
                $cancel += $val['cash'];
                /* 取消订单的商品件数 */
                $cancelnum += $val['num'];
                /* 取消订单的商品种类数 */
                $cancelcount += $val['count'];
            }
        }
        $this->assign('cancel', $cancel);
        $this->assign('cancelcount', $cancelcount);
        $this->assign('cancelnum', $cancelnum);
        
        /* 比率统计 */
        $percent_a = $total / ($total + $back + $change + $cancel);
        $percent_b = $back / ($total + $back + $change + $cancel);
        $percent_c = $change / ($total + $back + $change + $cancel);
        $percent_d = $cancel / ($total + $back + $change + $cancel);
        
        $this->assign('a', $percent_a);
        $this->assign('b', $percent_b);
        $this->assign('c', $percent_c);
        $this->assign('d', $percent_d);
        /* 利润 */
        $profits = $total - $back - $change - $cancel;
        $this->assign('profits', $profits);
        /* 保存数据到数据库 */
        $turnover = M("turnover");
        $data["sales"] = $total;
        $data["back"] = $back;
        $data["change"] = $change;
        $data["cancel"] = $cancel;
        $data["profits"] = $profits;
        $user = session('user_auth');
        $data["uid"] = $user["uid"];
        $data["status"] = '1';
        $q = date('Ymd', $qtime); /* 格式时间戳为 20141024 */
        $data["info"] = date('Ymd', $qtime);
        $t_time = $turnover->where("status='1'")
            ->order("id desc")
            ->limit(1)
            ->getField("create_time");
        /* 格式时间戳为 20141024 */
        $r = date('Ymd', $t_time); /* 格式时间戳为 20141024 */
        if ($q == $r) {
            /* 当天已统计过数据，保存，创建时间不变，更新时间变化 */
            $data["update_time"] = NOW_TIME;
            $turnover->where("create_time='$t_time' and status='1'")->save($data);
        } else {
            /* 未统计过数据，新增 */
            $data["create_time"] = NOW_TIME;
            $data["update_time"] = NOW_TIME;
            $turnover->add($data);
        }
        
        $this->display();
    }

    public function week()
    {
        // date('w',$time); 取到的是星期几 0-6
        // date('W',$time); 取到的是本年度的第几周 1-53
        $time = time();
        // 判断当天是星期几，0表星期天，1表星期一，6表星期六
        $w_day = date("w", $time);
        // php处理当前星期时间点上，根据当天是否为星期一区别对待
        if ($w_day == '1') {
            $cflag = '+0';
            $lflag = '-1';
        } else {
            $cflag = '-1';
            $lflag = '-2';
        }
        // 本周一零点的时间戳
        $start_time = strtotime(date('Y-m-d', strtotime("$cflag week Monday", $time)));
        // 本周末零点的时间戳
        $stop_time = strtotime(date('Y-m-d', strtotime("$cflag week Monday", $time))) + 7 * 24 * 3600;
        
        $this->meta_title = '管理首页';
        $title = "本周数据统计 ";
        $q = date('YmdHis', $start_time); /* 本周一零点格式时间戳为20141020000000 */
        $b = date('YmdHis', $stop_time); /* 本周末格式时间戳为201410270000000 */
        $stime = date('Y.m.d H:i:s', $start_time); /* 本周一开始时间 */
        $etime = date('Y.m.d H:i:s', $stop_time); /* 本周一结束时间 */
        $this->assign('stime', $stime);
        $this->assign('etime', $etime);
        
        /* 本周销量统计 */
        $order = M("order")->where("total!=''")->select();
        foreach ($order as $n => $val) {
            $time = $val['create_time'];
            $a = date('YmdHis', $time); /* 格式时间戳为 20141020000 */
            
            if ($b > $a && $a > $q) { // 本周
                /* 商品销售额 */
                $salesmoney += $val['total'];
                /* 运费 */
                $trans += $val['shipprice'];
                /* 总销售额 */
                $total += $val['pricetotal'];
                $id = $val['id'];
                $list = M("shoplist")->where("orderid='$id'")->select();
                foreach ($list as $k => $vo) {
                    /* 退货中的商品id */
                    $goodid = $vo['goodid'];
                    $price = get_good_price($goodid);
                    
                    /* 销售的商品件数 */
                    $salenum += $vo['num'];
                    /* 销售的商品种类数 */
                    $salecount += 1;
                }
            }
        }
        
        $this->assign('salecount', $salecount);
        $this->assign('salenum', $salenum);
        $this->assign('total', $total);
        
        /**
         * 本周退货金额、种类、数量计算
         */
        $data = M("backlist")->where("status='1'")->select();
        
        foreach ($data as $n => $val) {
            /* 格式时间戳为 201410240000 */
            $dtime = $val['create_time'];
            $d = date('YmdHis', $dtime);
            if ($b > $d && $d > $q) { // 当天
                /* 退货中的商品id */
                $goodid = $val['goodid'];
                $price = get_good_price($goodid);
                /* 退货中的商品总额 */
                $back += $val['num'] * $price;
                /* 退货中的商品件数 */
                $backnum += $val['num'];
                /* 退货中的商品种类数 */
                $backcount += 1;
            }
        }
        $this->assign('back', $back);
        $this->assign('backcount', $backcount);
        $this->assign('backnum', $backnum);
        /**
         * 本周换货金额、种类、数量计算
         */
        $data = M("exchange")->where("status='1'")->select();
        
        foreach ($data as $k => $vo) {
            $qtime = $vo['create_time'];
            $f = date('YmdHis', $qtime);
            if ($b > $f && $f > $q) {
                /* 换货中的商品总额 */
                $goodid = $vo['goodid'];
                $price = get_good_price($goodid);
                $change += $vo['num'] * $price;
                /* 换货中的商品件数 */
                $changenum += $vo['num'];
                /* 换货中的商品种类数 */
                $changecount += 1;
            }
        }
        $this->assign('change', $change);
        $this->assign('changecount', $changecount);
        $this->assign('changenum', $changenum);
        /**
         * 本周申请取消订单金额、种类、数量计算
         */
        $cdata = M("cancel")->where("status='1'")->select();
        foreach ($cdata as $k => $val) {
            /* 格式时间戳为 20141024 */
            $cantime = $val['create_time'];
            $s = date('YmdHis', $cantime);
            if ($b > $s && $s > $q) {
                // 当天
                /* 取消订单的商品总额 */
                $cancel += $val['cash'];
                /* 取消订单的商品件数 */
                $cancelnum += $val['num'];
                /* 取消订单的商品种类数 */
                $cancelcount += $val['count'];
            }
        }
        $this->assign('cancel', $cancel);
        $this->assign('cancelcount', $cancelcount);
        $this->assign('cancelnum', $cancelnum);
        /* 比率统计 */
        $percent_a = $total / ($total + $back + $change + $cancel);
        $percent_b = $back / ($total + $back + $change + $cancel);
        $percent_c = $change / ($total + $back + $change + $cancel);
        $percent_d = $cancel / ($total + $back + $change + $cancel);
        $this->assign('a', $percent_a);
        $this->assign('b', $percent_b);
        $this->assign('c', $percent_c);
        $this->assign('d', $percent_d);
        /* 利润 */
        $profits = $total - $back - $change - $cancel;
        $this->assign('profits', $profits);
        /* 保存数据到数据库 */
        $turnover = M("turnover");
        $data["sales"] = $total;
        $data["back"] = $back;
        $data["change"] = $change;
        $data["cancel"] = $cancel;
        $data["profits"] = $profits;
        $user = session('user_auth');
        $data["uid"] = $user["uid"];
        $y = date('W', NOW_TIME); /* 本年度的第几周 */
        $data["info"] = $y;
        $t_time = $turnover->where("status='2'")
            ->order("id desc")
            ->limit(1)
            ->getField("create_time");
        /* 格式时间戳为 20141024 */
        $r = date('W', $t_time); /* 本年度的第几周 */
        if ($y == $r) {
            /* 本周已统计过数据，保存，创建时间不变，更新时间变化 */
            $data["update_time"] = NOW_TIME;
            $turnover->where("create_time='$t_time' and status='2'")->save($data);
        } else {
            /* 未统计过数据，新增 */
            $data["status"] = '2';
            $data["create_time"] = NOW_TIME;
            $data["update_time"] = NOW_TIME;
            $turnover->add($data);
        }
        $this->assign('title', $title);
        $this->display();
    }

    public function month()
    {
        $month = date('Y年m月', NOW_TIME); /* 格式时间戳为 201410 */
        $this->assign('month', $month);
        // date('w',$time); 取到的是星期几 0-6
        // date('W',$time); 取到的是本年度的第几周 1-53
        
        // php获取本月起始时间戳和结束时间戳
        
        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        
        $this->meta_title = '管理首页';
        $title = "本月数据统计 ";
        $q = date('YmdHis', $beginThismonth); /* 本月一零点格式时间戳为20141020000000 */
        $b = date('YmdHis', $endThismonth); /* 本月末格式时间戳为201410270000000 */
        
        /* 本月销量统计 */
        $order = M("order")->where("total!=''")->select();
        foreach ($order as $n => $val) {
            $time = $val['create_time'];
            $a = date('YmdHis', $time); /* 格式时间戳为 20141020000 */
            
            if ($b > $a && $a > $q) { // 本月
                /* 商品销售额 */
                $salesmoney += $val['total'];
                /* 运费 */
                $trans += $val['shipprice'];
                /* 总销售额 */
                $total += $val['pricetotal'];
                $id = $val['id'];
                $list = M("shoplist")->where("orderid='$id'")->select();
                foreach ($list as $k => $vo) {
                    /* 退货中的商品id */
                    $goodid = $vo['goodid'];
                    $price = get_good_price($goodid);
                    
                    /* 销售的商品件数 */
                    $salenum += $vo['num'];
                    /* 销售的商品种类数 */
                    $salecount += 1;
                }
            }
        }
        
        $this->assign('salecount', $salecount);
        $this->assign('salenum', $salenum);
        $this->assign('total', $total);
        
        /**
         * 本月退货金额、种类、数量计算
         */
        $data = M("backlist")->where("status='1'")->select();
        
        foreach ($data as $n => $val) {
            /* 格式时间戳为 201410240000 */
            $dtime = $val['create_time'];
            $d = date('YmdHis', $dtime);
            if ($b > $d && $d > $q) { // 当天
                /* 退货中的商品id */
                $goodid = $val['goodid'];
                $price = get_good_price($goodid);
                /* 退货中的商品总额 */
                $back += $val['num'] * $price;
                /* 退货中的商品件数 */
                $backnum += $val['num'];
                /* 退货中的商品种类数 */
                $backcount += 1;
            }
        }
        $this->assign('back', $back);
        $this->assign('backcount', $backcount);
        $this->assign('backnum', $backnum);
        /**
         * 本月换货金额、种类、数量计算
         */
        $data = M("exchange")->where("status='1'")->select();
        
        foreach ($data as $k => $vo) {
            $qtime = $vo['create_time'];
            $f = date('YmdHis', $qtime);
            if ($b > $f && $f > $q) {
                /* 换货中的商品总额 */
                $goodid = $vo['goodid'];
                $price = get_good_price($goodid);
                $change += $vo['num'] * $price;
                /* 换货中的商品件数 */
                $changenum += $vo['num'];
                /* 换货中的商品种类数 */
                $changecount += 1;
            }
        }
        $this->assign('change', $change);
        $this->assign('changecount', $changecount);
        $this->assign('changenum', $changenum);
        /**
         * 本月申请取消订单金额、种类、数量计算
         */
        $cdata = M("cancel")->where("status='1'")->select();
        foreach ($cdata as $k => $val) {
            /* 格式时间戳为 20141024 */
            $cantime = $val['create_time'];
            $s = date('YmdHis', $cantime);
            if ($b > $s && $s > $q) {
                // 当天
                /* 取消订单的商品总额 */
                $cancel += $val['cash'];
                /* 取消订单的商品件数 */
                $cancelnum += $val['num'];
                /* 取消订单的商品种类数 */
                $cancelcount += $val['count'];
            }
        }
        $this->assign('cancel', $cancel);
        $this->assign('cancelcount', $cancelcount);
        $this->assign('cancelnum', $cancelnum);
        /* 比率统计 */
        $percent_a = $total / ($total + $back + $change + $cancel);
        $percent_b = $back / ($total + $back + $change + $cancel);
        $percent_c = $change / ($total + $back + $change + $cancel);
        $percent_d = $cancel / ($total + $back + $change + $cancel);
        $this->assign('a', $percent_a);
        $this->assign('b', $percent_b);
        $this->assign('c', $percent_c);
        $this->assign('d', $percent_d);
        /* 利润 */
        $profits = $total - $back - $change - $cancel;
        $this->assign('profits', $profits);
        /* 保存数据到数据库 */
        $turnover = M("turnover");
        $data["sales"] = $total;
        $data["back"] = $back;
        $data["change"] = $change;
        $data["cancel"] = $cancel;
        $data["profits"] = $profits;
        $user = session('user_auth');
        $data["uid"] = $user["uid"];
        $t = date('Ym', NOW_TIME); /* 格式时间戳为 201410 */
        $data["info"] = $t;
        $sqtime = $turnover->where("status='3'")
            ->order("id desc")
            ->limit(1)
            ->getField("create_time");
        /* 格式时间戳为 20141024 */
        $r = date('Ym', $sqtime); /* 格式时间戳为 20141024 */
        
        if ($t == $r) {
            /* 当天已统计过数据，保存，创建时间不变，更新时间变化 */
            $data["update_time"] = NOW_TIME;
            $turnover->where("create_time='$sqtime' and status='3'")->save($data);
        } else {
            /* 未统计过数据，新增 */
            $data["status"] = '3';
            $data["create_time"] = NOW_TIME;
            $data["update_time"] = NOW_TIME;
            $turnover->add($data);
        }
        $this->assign('title', $title);
        $this->display();
    }
    
    /**
     * 每日数据
     * 图数据和表数据时对应的
     */
    public function index(){
        $this->meta_title = '数据统计管理首页';
        $title = "每日数据 ";
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
        //         }
//         //获取图数据
//         $info = M('report')
//         ->field($field_1)
//         ->where($where)
//         ->order('id desc')
//         ->page($_GET['p'].',10')
//         ->select();
//         $this->assign('info', $info);
        
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
     * 实时数据统计
     */
    public function realtime(){
        //统计当日每个整点的总数据
        $this->meta_title = '数据统计管理首页';
        $title = "实时数据 ";
        $this->assign('title', $title);
        
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
//         }
        /*
         * 选择时间：1=》今天，2=》昨天，3=》7天前，4=》30天前，5=》某天（由控件选择的某天）
         */        
//         if (isset($_GET['time']) && !empty($_GET['time'])) {
            switch (I('get.time')) {
                case 1://今天，是从零点到前一个整点的数据。
                    $start = date('Ymd', time()).'00';
                    $start = (int)$start;
                    $end = date('YmdH', time());
                    $where['info'] = array('between', array($start, $end));
                    break;
                case 2://昨天
                    $start = date('Ymd', strtotime('-1 day')).'00';
                    $start = (int)$start;
                    $end = $start+23;
                    $where['info'] = array('between', array($start, $end));                   
                    break;
                case 3://7天前
                    $start = date('Ymd', strtotime('-7 days')).'00';
                    $start = (int)$start;
                    $end = $start+23;
                    $where['info'] = array('between', array($start, $end));
                    break;
                case 4://30天前
                    $start = date('Ymd', strtotime('-30 days')).'00';
                    $start = (int)$start;
                    $end = $start + 23;
                    $where['info'] = array('between', array($start, $end));
                    break;
                case 5://某天，高级搜索条件
                    $start = date('Ymd', time(I('get.time')));
                    $start = (int)$start;
                    $end = $start + 23;
                    $where['info'] = array('between', array($start, $end));
                    break;
                default:
                    $start = date('Ymd', time()).'00';
                    $start = (int)$start;
                    $end = date('YmdH', time());
                    $where['info'] = array('between', array($start, $end));
                    break;
            }
//         }
        
        $info = M('report')
                ->field($field_1)
                ->where($where)
                ->order('id desc')
                ->page($_GET['p'].',10')
                ->select();
        $this->assign('info', $info);          
        
        //获取数据表
        $field = 'info,new_people,visit,recharge_people,recharge_sum,recharge_sum/recharge_people recharge_arppu, 
                 new_pay,consume_people,consume_sum, consume_sum/consume_people consume_arppu';
        $list = M('report')
        ->field($field)
        ->where($where)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->select();
        $this->assign('list', $list);
        $count = M('report')
        ->where($where)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->count();
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }

    /**
     * 新增数据统计[debug]由于在新增数据中出现了整点数据与一天数据的合并，需要修改left_rate的info为日期小时，但小时为00
     */
    public function increase(){
        //图数据
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            switch (I('get.type')) {
                case 1://新增用户
                    $field_1 = 'sum(new_people) new_people';
                    $model = M('report');
                    break;
                case 2://累积用户
                    $field_1 = 'sum(register_sum) register_sum';
                    $model = M('report');
                    break;
                case 3://新用户付费人数
                    $field_1 = 'sum(register_consume) register_consume';
                    $model = M('report');
                    break;
                case 4://新用户付费总额
                    $field_1 = 'sum(register_consume_sum) register_consume_sum';
                    $model = M('report');
                    break;
                case 5://新用户ARPPU
                    $field_1 ='sum(register_consume_sum)/sum(register_consume) register_arppu';
                    $model = M('report');
                    break;
                case 6://次日留存率
                    $field_1 = 'one_left';
                    $model = M('left_rate');
                    break;
                case 7://7日留存率
                    $field_1 = 'seven_left';
                    $model = M('left_rate');
                    break;
                case 8://30日留存率
                    $field_1 = 'thirty_left';
                    $model = M('left_rate');
                    break;
            }
        }
        $info = $model->field($field_1)->select();
        $this->assign('info', $info);
        
        //表数据
        //获取report表中的数据
        $field = 'day_info,sum(new_people) new_people,sum(register_sum) register_sum,
                  sum(register_consume) register_consume,sum(register_consume_sum) register_consume_sum,
                  sum(register_consume)/sum(register_consume_sum) register_arppu,';
        $list = M('report')
                ->field($field)
                ->order('id desc')
                ->group('day_info')//还是在report表中加入day_info字段，否则不好求和
                ->page($_GET['p'].', 10')
                ->select();
        //获取left_rate表中的数据
        $field_1 = 'one_left,seven_left,thirty_left';
        $left_list = M('left_rate')
                    ->field($field_1)
                    ->order('id desc')
                    ->page($_GET['p'].', 10')
                    ->select();
        //拼接两个数据
        $count = count($list);
        for($i=0; $i<$count; $i++){
            //将day_info改成2013-12-12的模式
            $year = substr($list[$i]['day_info'], 0,4);//截取年
            $month = substr($list[$i]['day_info'],4,2);//截取月
            $day = substr($list[$i]['day_info'],6,2);//截取日
            $list[$i]['day_info'] = $year.'-'.$month.'-'.$day;
            
            foreach ($left_list as $key => $value) {
                 $list[$i] =  $left_list;//[debug]这个可能有问题，关于for和foreahc的区别              
            }
        }
        
        $this->assign('list', $list);
        $meta_title = '新增数据';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('left_rate')->where()->count();
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 用户数据-活跃数据
     */
    public function active(){
        //图数据
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            switch(I('get.type')) {
                case 1://日活跃用户
                    $field_1 = 'sum(one_login) one_login';
                    $model = M('report');
                    break;
                case 2://老用户
                    $field_1 = 'sum(register_sum) register_sum';//[debug]这个字段应该是获取出错
                    $model = M('report');
                    break;
                case 3://周活跃用户
                    $field_1 = 'sum(week_login) week_login';
                    $model = M('report');
                    break;
                case 4://月活跃用户
                    $field_1 = 'sum(month_login) month_login';
                    $model = M('report');
                    break;
                case 5://次日留存率
                    $field_1 = 'one_left';
                    $model = M('left_rate');
                    break;
                case 6://7日留存率
                    $field_1 = 'seven_left';
                    $model = M('left_rate');
                    break;
                case 7 ://30日留存率
                    $field_1 = 'thirty_left';
                    $model = M('left_rate');
                    break;
            }
        }
        
        $info = $model
                ->field($field_1)
                ->order('id desc')
                ->group('day_info')
                ->select();
        $this->assign('info', $info);
        
        //表数据
        //获取report中的数据[debug]day_info取得有问题
        $field = 'day_info,sum(one_login) one_login,sum(register_sum) register_sum,sum(week_login) week_login,sum(month_login) month_login';
        $com_list = M('report')
                ->field($field)
                ->order('id desc')
                ->group('day_info')
                ->page($_GET['p'].', 10')
                ->select();
        //获取left中的数据
        $field = 'one_left,seven_left,month_left';
        $left_list = M('left_rate')
                     ->field($field)
                     ->order('id desc')
                     ->page($_GET['p'].',10')
                     ->select();
        //拼接数据
        $list = array();
        $count = count($com_list);
        for($i=0; $i<$count; $i++) {
            $list[] = array_merge($com_list[$i], $left_list[$i]);
        }
        $this->assign('list', $list);
        $meta_title = '活跃数据';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('left_rate')->count();
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 收支数据
     */
    public function recharge(){
        //图数据
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            switch(I('get.type')) {
                case 1://活跃用户
                    $field = 'sum(one_login) one_login';
                    break;
                case 2://充值用户
                    $field = 'sum(recharge_people) recharge_people';
                    break;
                case 3://充值总额
                    $field = 'sum(recharge_sum） recharge_sum';
                    break;
                case 4://充值ARPU
                    $field = 'sum(recharge_sum)/sum(recharge_people) recharge_arppu';
                    break;
                case 5://首充用户
                    $field = 'sum(first_recharge) first_recharge';
                    break;
                case 6://首充总额
                    $field ='sum(first_recharge_sum) first_recharge_sum';
                    break;
            }
        }
        $info = M('report')
                ->field($field)
                ->order('id desc')
                ->group('day_info')
                ->select();
        $this->assign('info', $info);
        
        //表数据
        $field = 'day_info,sum(recharge_people) recharge_people,sum(recharge_sum) recharge_sum,
                  sum(recharge_sum)/sum(recharge_peopel) recharge_arppu, count(first_recharge) first_recharge,
                  sum(first_recharge_sum) first_recharge_sum,sum(first_recharge_sum)/sum(first_recharge) first_recharge_arppu';
        $list = M('report')
                ->field($field)
                ->order('id desc')
                ->group('day_info')
                ->select();
        //修改day_info为页面指定显示格式
        $count = count($list);
        for($i=0; $i<$count; $i++){
            //将day_info改成2013-12-12的模式
            $year = substr($list[$i]['day_info'], 0,4);//截取年
            $month = substr($list[$i]['day_info'],4,2);//截取月
            $day = substr($list[$i]['day_info'],6,2);//截取日
            $list[$i]['day_info'] = $year.'-'.$month.'-'.$day;
        }
        $this->assign('list', $list);
    }
    
    /**
     * 消费数据
     */
    public function consume(){
        //图数据
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            switch(I('get.type')) {
                case 1://活跃用户
                    $field = 'sum(one_login) one_login';
                    break;
                case 2://消费用户
                    $field = 'sum(consume_people) consume_people';
                    break;
                case 3://消费总额
                    $field = 'sum(consume_sum） consume_sum';
                    break;
                case 4://消费ARPPU
                    $field = 'sum(consume_sum)/sum(consume_people) consume_arppu';
                    break;
                case 5://首次消费用户
                    $field = 'sum(first_consume) first_consume';
                    break;
                case 6://首次消费总额
                    $field ='sum(first_recharge_sum) first_recharge_sum';
                    break;
                case 7://首次消费ARPPU
                    $field = 'sum(first_recharge_sum) /sum(first_recharge) first_recharge_arppu';
                    break;
            }
        }
        $info = M('report')
        ->field($field)
        ->order('id desc')
        ->group('day_info')
        ->select();
        $this->assign('info', $info);
        
        //表数据
        $field='day_info,sum(one_login),consume_people,consume_sum,consume_sum/consume_people,first_consume,first_consume_sum,first_consume_sum/first_consume_sum';
        $list = M('report')
        ->field($field)
        ->order('id desc')
        ->group('day_info')
        ->select();
        //修改day_info为页面指定显示格式
        $count = count($list);
        for($i=0; $i<$count; $i++){
            //将day_info改成2013-12-12的模式
            $year = substr($list[$i]['day_info'], 0,4);//截取年
            $month = substr($list[$i]['day_info'],4,2);//截取月
            $day = substr($list[$i]['day_info'],6,2);//截取日
            $list[$i]['day_info'] = $year.'-'.$month.'-'.$day;
        }
        $this->assign('list', $list);
    }
}
