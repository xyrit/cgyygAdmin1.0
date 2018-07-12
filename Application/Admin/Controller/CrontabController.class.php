<?php
namespace Admin\Controller;
use Think\Controller;
use OT\TagLib\Think;

/**
 * 定时器控制器，用于获取统计数据
 * 不加入权限，因为加了权限不能通过linux的定时器访问此路径了
 * @author Administrator
 *
 */
class CrontabController extends Controller {
    //测试
    public function test(){
        echo 'hello';
        $data = array(
            'nickname' => 'orion pax',
        );
        M('member')->add($data);
    }
    
    /**
     * 生成缓存的定时器，每天执行一次，生成24小时的各个数据缓存key，用于存取数据
     * 统计访问次数的缓存key
     * 前一天23点50执行一次，生成生命周期为1天零10分钟的缓存
     */
    public function getCacheKey(){
         $day = date('Ymd',strtotime('+1 day'));
         $day.='00';
         $day = (int)$day;
         $arr = array();
         for($j=0; $j<23; $j++){
             $day++;
            S('visit_'.$day, $arr ,86410);
         }
    }
    
    public function testCacheKey(){
        var_dump(S('visit_2016021701'));   
    }
    
    /**
     * 数据统计的定时操作，每个小时执行一次
     */
    public function hourCron(){
        header('Content-type:text/html;charset=utf-8');
        $data = array();//用于存储数据
        $data['info'] = date('YmdH', time());
        $data['type'] = 1;
        $model = M('report');
        $hour = (int)date('YmdH', time()); 
        $today = (int)date('Ymd00', time());
        $today_e = $today+23;//这里是float类型的
        
        //使用缓存统计访问次数,IP地址去重
        $visit = S('visit_'.$hour);
        $visit_arr = array();//用于存储过滤后的访问记录数组
        $visit_sum = 0;
        $count = count($visit);            
        for($i = 0; $i < $count; $i++) {
           if ($i == 0) {//第一条记录没有前一条记录
               $visit_arr[] = $visit[$i];
                $visit_sum++;
           } else {
               $res = deep_in_array($visit[$i]['ip'], $visit_arr);               
               if ( $res === false ) {//ip地址没有重复
                   $visit_arr[] = $visit[$i];
                   $visit_sum++;
               }
           }
        }
        $data['visit'] = $visit_sum;
        
        //统计活跃用户,UID去重
        $data['one_login'] = M('member')->where('log_info='.$hour)->count('distinct uid');
        
        //统计新增用户数量
        $data['new_people'] = M('member')->where('reg_info='.$hour)->count();
        
        //累积注册用户数量
        $data['register_sum'] = M('member')->where('reg_info<='.$hour)->count();
        
        //统计充值用户数量,UID去重
        $data['recharge_people'] = M('recharge')->where('info='.$hour)->count('distinct uid');
        
        //统计充值金额
        $data['recharge_sum'] = M('recharge')->where('info='.$hour)->sum('money');
        
        //统计新增付费用户
        $consume = M('recharge')->group('uid')->field('uid,info')->select();
        $data['new_pay'] = 0;
        foreach($consume as $key => $value) {
            if ($value['info'] == $hour) {
                $data['new_pay']++;
            }
        }
        
        //统计消费用户,uid区重
        $consume_people = M('lottery_attend')->group('uid')->field('uid,info')->select();
        $data['consume_people'] = 0;
        foreach($consume_people as $key => $value) {
            if ($value['info'] == $hour) {
                $data['consume_people']++;
            }
        }
        
        //消费总额
        $data['consume_sum'] = M('lottery_attend')->where('info='.$hour)->sum('attend_count');
        
        //新用户付费人数
        $where_2['m.reg_info'] = array('between', array($today, $today_e));
        $register_consume = M()
                            ->table('__MEMBER__ m')
                            ->join('__LOTTERY_ATTEND__ l ON m.uid = l.uid')
                            ->field('l.info')//开发的时候，可以多返回id等字段，方便调试
                            ->group('m.uid')
                            ->where($where_2)
                            ->select();
        $data['register_consume'] = 0;
        foreach($register_consume as $key => $value) {
            if ($value['info'] == $hour) 
                $data['register_consume']++;
        }
        
        //新用户付费总额
        $where_2 = array(
            'm.reg_info'=> array('between', array($today, $today_e)),
            'l.info' => $hour,
        );
        $data['register_consume_sum'] = M()
                            ->table('__MEMBER__ m')
                            ->join('__LOTTERY_ATTEND__ l ON m.uid = l.uid')
                            ->where($where_2)
                            ->field('l.attend_count')
                            ->sum('l.attend_count');
        
        //首充用户数量和总额
        $data['first_recharge'] = 0;
        $data['first_recharge_sum'] = 0;
        $first_charge = M('recharge')->field('info, money')->group('uid')->select();
        foreach($first_charge as $key => $value){
            if($value['info'] == $hour) {
                $data['first_recharge']++;
                $data['first_recharge_sum']+=$value['money'];
            }
        }
        
        //首次消费用户数量和总额
        $data['first_consume'] = 0;
        $data['first_consume_sum'] = 0;
        $first_consume = M('lottery_attend')->distinct(true)->field('uid')->select();
        foreach($first_consume as $key => $value) {
            if($value['info'] == $hour){
                $data['first_consume']++;
                $data['first_consume_sum']+=$first_consume['attend_count'];
            }
        }
        
        //插入数据
        $res = M('report')->add($data);
        if ($res) {
            echo '插入统计数据成功';
        } else {
            echo '插入统计数据失败';
        }
    }
    
    /**
     * 访问记录入库操作,每小时进行一次
     */
    public function visit(){
        header("Content-type:text/html;charset=utf-8");
        $hour = date('YmdH', time());
        $visits = S('visit_'.$hour);//访问记录
//         $visits = array(
//             array('ip'=>1,'uid'=>2,'visit_time'=>3,'info'=>2016021913),
//             array('ip'=>2,'uid'=>2,'visit_time'=>3,'info'=>2016021913),
//             array('ip'=>3,'uid'=>2,'visit_time'=>3,'info'=>2016021913),
//             array('ip'=>4,'uid'=>2,'visit_time'=>3,'info'=>2016021913),
//         );
        //拼sql语句
        $sql_arr = array();
        $count = count($visits);
        foreach($visits as $key => $value){
               $sql_arr[] ='('.$value['ip'].','.$value['uid'].",'".$value['visit_time']."',".$value['info'].')';
        }
        $sql = implode(',', $sql_arr);
        $query = "INSERT INTO __PREFIX__visit(`ip`,`uid`,`visit_time`,`info`) VALUES $sql";
        $model= new \Think\Model();
        $res = $model->execute($query);
        if($res){
            echo '访问记录入库成功';
        }else{
            echo '访问记录入库失败';
        }
    }
    
    /**
     * 定时任务，统计留存率，每日数据，每日整点执行一次
     */
    public function leftRate(){
        header("Content-type:text/html;charset=utf-8");
        /*
         * 留存率有活跃用户留存率和注册用户留存率两种
         * log_info和reg_info都是以小时为单位的
         */
        $today = date('Ymd', time());
        //次日留存率
        $yesterday = date('Ymd', strtotime('-1 day'));
        //昨天小时段
        $yesterday_log = array((int)($yesterday.'00'), (int)($yesterday.'23'));
        //今日小时时间段
        $today_log = array((int)($today.'00'), (int)($today.'23'));
        //对昨天的数据进行统计
        $where['info'] = array('between', $yesterday_log);
        $data = array(
            'info' => $yesterday,
            'type' => 2,
        );
        $data['one_login'] = M('report')->where($where)->sum('one_login');
        $data['visit'] = M('report')->where($where)->sum('visit');
        $data['new_people'] = M('report')->where($where)->sum('new_people');
        $data['recharge_people'] = M('report')->where($where)->sum('recharge_people');
        $data['recharge_sum'] = M('report')->where($where)->sum('recharge_sum');
        $data['new_pay'] = M('report')->where($where)->sum('new_pay');
        $data['consume_people'] = M('report')->where($where)->sum('consume_people');
        $data['consume_sum'] = M('report')->where($where)->sum('consume_sum');
        $data['register_consume'] = M('report')->where($where)->sum('register_consume');
        $data['register_consume_sum'] = M('report')->where($where)->sum('register_consume_sum');
        $data['first_recharge'] = M('report')->where($where)->sum('first_recharge');
        $data['first_recharge_sum'] = M('report')->where($where)->sum('first_recharge_sum');
        $data['first_consume'] = M('report')->where($where)->sum('first_consume');
        $data['first_consume_sum'] = M('report')->where($where)->sum('first_consume_sum');
        M('report')->add($data);
        //统计昨日数据结束
        
        //统计登录用户的留存率
        //昨天登录的人数  
        $where_1 = array(
            'm.info' => array('between', $today_log),
            'o.info' => array('between', $yesterday_log),
        );
        //获取昨天登录，且今天登录了的用户
        $yes_login = M()
        ->table('__LOGIN__ m')//今天
        ->join('__LOGIN__ o ON m.uid = o.uid')//同一用户
        ->where($where_1)
        ->count('distinct m.uid');
        //获取昨天登录的人数
        $where_2['info'] = array('between', $yesterday_log);
        $tod_login = M('login')->where($where_2)->count('distinct uid');
        //获得昨天的次日留存率
        $one_left = $yes_login/$tod_login;
        $one = array(
            'one_left' => $one_left,
            'info' => $yesterday,
            'type' => 2,
        );
        M('left_rate')->add($one);//写入次日留存率
        
        //7日留存率
        $seven_day = date('Ymd', strtotime('-7 day'));
        $seven_log = array((int)($seven_day.'00'), (int)($seven_day.'23'));
        
        //获取7天前登录，且今天登录了的用户
        $where_3 = array(
            'm.info' => array('between', $today_log),//今天登陆
            'o.info' => array('between', $seven_log),//7天前登录
        );
        $tod_login = M()
        ->table('__LOGIN__ m')//今天
        ->join('__LOGIN__ o ON m.uid = o.uid')//7天前
        ->where($where_3)
        ->count('distinct m.uid');
        //获取7天前登录的用户数
        $where_4['info'] = array('between', $seven_log);
        $seven_login = M('login')->where($where_4)->count('distinct uid');
        //获得7天前登录的7日留存率
        $seven_left = $tod_login/$seven_login;
        //写入7日留存率
        M('left_rate')->where("info=$seven_day and type=2")->setField('seven_left', $seven_left);
        
        //30日留存率
        $thirty_day = date('Ymd', strtotime('-30 day'));
        $thirty_log = array((int)($thirty_day.'00'), (int)($thirty_day.'23'));
        //获取30天前登录，且今天登录了的用户
        $where_5 = array(
            'm.info' => array('between', $today_log),
            'o.info' => array('between', $thirty_log),
        );
        $tod_login = M()
        ->table('__LOGIN__ m')//今天
        ->join('__LOGIN__ o  ON m.uid = o.uid')//30天前
        ->where($where_5)
        ->count('distinct m.uid');
        //获取30天前登录的用户数
        $where_6['info'] = array('between', $thirty_log);
        $thirty_login = M('login')->where($where_6)->count('distinct uid');
        $thirty_left = $tod_login/$thirty_login;
        //写入30天前的活跃用户留存率
        M('left_rate')->where("info=$thirty_day and type=2")->setField('thirty_left', $thirty_left);
        //活跃用户留存率统计完毕
        
        //开始注册用户留存率
        //获取昨天注册，且今天登录了的用户
        $where_7 = array(
            'm.info' => array('between', $today_log),
            'o.reg_info' => array('between', $yesterday_log),
        );
        $tod_login = M()
        ->table('__LOGIN__ m')//今天登录
        ->join('__MEMBER__ o ON m.uid = o.uid')//昨天注册
        ->where($where_7)
        ->count('distinct m.uid');
        //获取昨天注册的人数
        $where_8['reg_info'] = array('between', $yesterday_log);
        $yes_reg = M('member')->where($where_8)->count();
        //获得昨天的次日留存率
        $one_left = $tod_login/$yes_reg;
        $one = array(
            'one_left' => $one_left,
            'info' => $yesterday,
            'type' => 1,
        );
        //写入昨天注册用户的次日留存率成功
        M('left_rate')->add($one);
        
        //7日留存率
        //获取7天前注册，且今天登录了的用户
        $where_9 = array(
            'm.info' => array('between', $today_log),//今天登陆
            'o.reg_info' => array('between', $seven_log),//7天前注册
        );
        $tod_login = M()
        ->table('__LOGIN__ m')//今天
        ->join('__MEMBER__ o ON m.uid = o.uid')//7天前
        ->where($where_9)
        ->count('distinct m.uid');
        //获取7天前注册的用户数
        $where_10['reg_info'] = array('between', $seven_log);
        $seven_reg = M('member')->where($where_10)->count();
        
        //写入7天前登录的7日留存率
        $seven_left = $tod_login/$seven_reg;
        M('left_rate')->where("info=$seven_day and type=1")->setField('seven_left', $seven_left);
        
        //30日留存率
        //获取30天前登录，且今天登录了的用户
        $where_11 = array(
            'm.info' => array('between', $today_log),
            'o.reg_info' => array('between', $thirty_log),
        );
        $tod_login = M()
        ->table('__LOGIN__ m')//今天
        ->join('__MEMBER__ o  ON m.uid = o.uid')//30天前
        ->where($where_11)
        ->count('distinct m.uid');
        
        //获取30天前注册的用户数
        $where_12['reg_info'] = array('between', $thirty_log);
        $thirty_reg = M('member')->where($where_12)->count();
        $thirty_left = $tod_login/$thirty_reg;
        //写入30天前注册用户的留存率
        M('left_rate')->where("info=$thirty_day and type=1")->setField('thirty_left', $thirty_left);
    }
}