<?php
namespace Admin\Controller;

/**
 * 晒单审核
 * jerry
 * 2016.1.23
 */
class DisplayController extends AdminController{
    /**
     * 晒单查看待审核记录。[debug]注意，一个中奖只能晒一次单，需要添加外检唯一约束。
     */
    public function index(){
        header('Content-type:text/html;charset=utf-8');
        $data = array();
        $field = 'd.id,d.uid,d.description,d.pics,d.apply_time,d.title,
                m.nickname,m.mobile,
                w.lottery_id,w.title goods_title';
        $list = M()
        ->table('__DISPLAY_PRODUCT__ d')
        ->field($field)
        ->join('LEFT JOIN __MEMBER__ m ON d.uid = m.uid')
        ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
        ->where('d.type=1')
        ->order('d.id asc ')
        ->page($_GET['p'].',10')
        ->select();
        //遍历晒单图片，用逗号分隔成一个数组
        $list_length = count($list);
        if ($list_length > 0) {
            for($i=0; $i<$list_length; $i++){
                if (strpos($list[$i]['pics'], ',')===false) {//没有出现逗号，即只有一张图片
                    $list[$i]['pics'] = array($list[$i]['pics']);
                } else {//出现了逗号，即有多张图片
                    $list[$i]['pics'] = explode(',', $list[$i]['pics']);
                }
            }
        }
        $this->assign('list', $list);
        $meta_title = '晒单审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('display_product')->where('type=1')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 晒单通过
     */
    public function auditDisplayAgree(){
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
//         if (IS_POST) {
//             if (!empty($_POST['id'])) {
//                 $where['id'] = array('in', I('id'));
//             }
//         } elseif (!empty($_GET['id'])) {
//             $where['id'] = I('id');
//         } else {
//             $data = array(
//                 'status' => 2,
//                 'info' => '没有传入参数id',
//                 'url' => U('Display/index')
//             );
//             $this->ajaxReturn($data);
//         }
        if(!empty($_REQUEST['id']))
        {//选择了记录

            //判断是否是数组
            if(is_array($_REQUEST['id']))
            {
            if(count($_REQUEST['id']) > 1)
            {//选择了多条记录
                $where['id'] = array('in', I('id'));
            }
            else
            {//选择了一条记录
                $tid= I('id');
                $where['id'] = $tid[0];
            }
            }
            else
            {
                $where['id'] = I('id');
            }
        }
        else
        {//没有选择记录
            $data = array(
                'status' => 2,
                'info' => '没有选择记录，请选择记录'
            );
            $this->ajaxReturn($data);
        }
        $data = array(
            'type' => 2,
            'status' => 1,
            'audit_name' => $user_info['username'],
            'audit_time' => getCurrentTime(),
        );
        $res = M('display_product')->where($where)->save($data);
        if ($res) {
            $data = array(
                'status' => 1,
                'info' => '晒单全部通过成功',
                'url' => U('Display/showAuditedDisplay')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '晒单全部通过失败',
                'url' => U('Display/index')
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     *晒单拒绝，只能做单个操作，不能批量操作。需求发生改变，不再由后台人员输入
     */
    public function auditDisplayReject(){
        $user_info = session('user_auth');
        if (!empty($_GET['id'])) {
            $where['id'] = I('id');
            $data = array(
                'type' => 2,
                'status' => 2,
                'audit_name' => $user_info['username'],
                'audit_time' => getCurrentTime(),
            );
            $res = M('display_product')->where($where)->save($data);
            if ($res !== false ) {
                $data = array(
                    'status' => 1,
                    'info' => '拒绝晒单成功',
                    'url' => U('Display/showAuditedDisplay')
                );
                $this->ajaxReturn($data);
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '拒绝晒单失败',
                    'url' => U('Display/index')
                );
                $this->ajaxReturn($data);
            }
        } else {
            $data = array(
                'status' => 2,
                'info' => '没有传入参数id',
                'url' => 'Display/index'
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 查看已审核晒单
     */
    public function showAuditedDisplay(){
        //测试用
        header('Content-type:text/html;charset=utf-8');
    
        //选定状态，在表中是status字段
        if( isset($_GET['status']) && !empty($_GET['status'])) {
            $where['d.status']= I('get.status');
        }
    
        //选定申请起始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $where['d.apply_time'][] = array('egt',I('get.start_time'));
        }
    
        //选定截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $where['d.apply_time'][] =array('elt',I('get.end_time'));
        }
    
        //输入审核人用户名
        if (isset($_GET['audit_name']) && !empty($_GET['audit_name'])) {
            $where['d.audit_name'] = array('like','%'.I('get.audit_name').'%');
        }
    
        // 输入用户名或者用户ID
        if (isset($_GET['input']) && !empty($_GET['input'])) {
            $where['m.mobile|d.uid'] = array('like','%'.I('get.input').'%');
        }
    
        //对$_GET['p']参数进行判断，防止在使用page方法时出现错误。
        $_GET['p'] = $_GET['p']? $_GET['p'] : 0;
    
        $where['d.type'] = 2;
        $data = array();//用于存放数据
        $field = 'd.id,d.uid,d.description,d.pics,d.apply_time,d.title,d.type,d.audit_name,d.audit_time,d.status,
                m.nickname,m.mobile,
                w.lottery_id,w.title goods_title';
        $list = M()
        ->table('__DISPLAY_PRODUCT__ d')
        ->field($field)
        ->join('LEFT JOIN __MEMBER__ m ON d.uid = m.uid')
        ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
        ->where($where)
        ->order('d.id desc')
        ->page($_GET['p'] . ',10')
        ->select();
        //遍历晒单图片，用逗号分隔成一个数组
        $list_length = count($list);
        if ($list_length > 0) {
            for($i=0; $i<$list_length; $i++){
                if (strpos($list[$i]['pics'], ',')===false) {//没有出现逗号，即只有一张图片
                    $list[$i]['pics'] = array($list[$i]['pics']);
                } else {//出现了逗号，即有多张图片
                    $list[$i]['pics'] = explode(',', $list[$i]['pics']);
                }
            }
        }
        $this->assign('list', $list);
        $meta_title = '晒单审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('display_product')->where('type=2')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
}