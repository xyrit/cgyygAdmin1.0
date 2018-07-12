<?php
namespace Admin\Controller;

/**
 * 后台发货控制器
 * @author Jerry
 * 2016.1.14
 */
class DeliverController extends AdminController{
    /**
     * 查看待发货记录
     */
    public function index()
    {
        
        $data = array(); // 用于存储返回的数据
        //以win_prize为主表进行连接查询，去查询中奖相关的信息；os_delivering表删除。
        $field='w.id,w.uid,w.lottery_id,w.title,w.thumbnail,w.apply_time,
                m.mobile,m.nickname,
                a.cellphone,a.take_address,a.realname';
        $list = M()
                ->table('__WIN_PRIZE__ w')
                ->join('LEFT JOIN __ADDRESS__ a ON w.address_id = a.id')
                ->join('LEFT JOIN __MEMBER__ m ON w.uid = m.uid')  
                ->field($field)
                ->where('w.status=1')
                ->order('w.id asc')
                ->page($_GET['p'].',10')
                ->select();
        $this->assign('list', $list);
        $meta_title = '待发货订单';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('win_prize')->where('status=1')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 进行发货操作
     */
    public function deliver()
    {
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
        if (! empty($_POST)) {
                //测试
                //防止重复提交，先检测delivering表中此id是否存在
                $res = M('win_prize')->getById(I('post.id'));
                if (!$res) {// 重复提交或者伪造的请求，delivering中id其实不存在
                    $data = array(
                        'status' => 2,
                        'info' => '操作失败',
                        'url' =>U('Deliver/index')
                    );
                    $this->ajaxReturn($data);
                }
                // 测试用的session
                $model = M('delivered');
                $model->create();
                $model->operate_name = $user_info['username']; // 操作人姓名，即发货人
                $model->operate_time = getCurrentTime(); // 操作时间，即发货时间
                if ($model->add()) {
                    //修改win_prize表中的status为2（已发货，待客户签收）
                     $res = M('win_prize')->where('id='.I('id'))->setField('status',2);
                     if ($res) {
                         $data = array(
                             'status' => 1,
                             'info' => '操作成功',
                             'url' => U('Deliver/showDelivered')
                         );
                         $this->ajaxReturn($data);
                    }
                } else {
                    $data = array(
                        'status' => 2,
                        'info' => '在已发货表中插入记录失败',
                        'url' => U('Deliver/index')
                    );
                    $this->ajaxReturn($data);
                }
        } else {
            // 待发货地址编号，用于获取用户商品信息
            if (isset($_GET['id']) && ! empty($_GET['id'])) { // isset()和empty()是用来检测变量的，不能检测函数返回的值。否则发生错误
                $where_a['w.id'] = I('get.id');
                $field='w.id,w.uid,w.lottery_id,w.title,w.thumbnail,w.apply_time,
                        m.mobile,m.nickname,
                        a.cellphone,a.take_address,a.realname';
                $info = M()
                ->table('__WIN_PRIZE__ w')
                ->join('LEFT JOIN __MEMBER__ m ON w.uid = m.uid')
                ->join('LEFT JOIN __ADDRESS__ a ON w.address_id = a.id')
                ->field($field)
                ->where($where_a)
               ->find();
                $this->assign('info', $info);
                $this->display();
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '没有传入id或者参数格式错误',
                    'url' => U('Deliver/index')
                );
                $this->ajaxReturn($data);
            }
        }
    }
    
    /**
     * 查询已发货记录。可以根据状态、申请时间的起止时间、审核人用户名搜索
     * 由于用win_prize表，status现在需要在win_prize表中取。
     */
    public function showDelivered()
    {
        header('Content-type:text/html;charset=utf-8');
        // 选定状态
        if (isset($_GET['status']) && ! empty($_GET['status'])) {
                $where['w.status'] = (int)I('get.status');
        }
    
        // 选定申请时间的起止时间
        if (isset($_GET['start_time']) && ! empty($_GET['start_time'])) {
            $where['w.apply_time'][] = array(
                'egt',
                I('get.start_time')
            );
        }
    
        // 选定申请时间结束时间
        if (isset($_GET['end_time']) && ! empty($_GET['end_time'])) {
            $where['w.apply_time'][] = array(
                'elt',
                I('get.end_time')
            );
        }
    
        // 输入用户名或者用户ID
        if (isset($_GET['input']) && ! empty($_GET['input'])) {
            $where['m.moile|d.uid'] = array(
                'like',
                '%' . I('get.input') . '%'
            );
        }
    
        // 输入操作人
        if (isset($_GET['operate_name']) && ! empty($_GET['operate_name'])) {
            $where['d.operate_name'] = array(
                'like',
                '%' . I('get.operate_name') . '%'
            );
        }
    
        $_GET['p'] = $_GET['p']? $_GET['p'] : 1;//如果是跳转到这里的
    
        $data = array(); // 用于存放数据
        $field = 'd.id,d.uid,d.realname,d.cellphone,d.take_address,d.express_name,
                    d.express_number,d.deliver_name,d.deliver_address,d.operate_name,d.operate_time,
                    w.lottery_id,w.title,w.thumbnail,w.status,w.apply_time,
                    m.mobile,m.nickname';
        $list = M()//要使用os_delivered作为主表，因为可以避免去os_win_prize表中取排除数据
                ->table('__DELIVERED__ d')
                ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                ->join('LEFT JOIN __MEMBER__ m ON d.uid = m.uid')
                ->field($field)
                ->where($where)
                ->order('d.operate_time desc')
                ->page($_GET['p'].',10')
                ->select();
        $this->assign('list', $list);
        $meta_title = '已发货订单';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M()
                ->table('__DELIVERED__ d')
                ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                ->join('LEFT JOIN __MEMBER__ m ON d.uid = m.uid')
                ->where($where)
                ->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 编辑已发货信息
     * @param $id 已发货记录编号
     */
    public function updateDelivered(){
        if (IS_POST && isset($_POST['id']) && !empty($_POST['id'])) {//进行确认操作
            $model = M('delivered');
            $model->create();
            if ($model->save()) {//编辑成功
                $data = array(
                    'status' => 1,
                    'info' => '编辑成功',
                    'url' => U('Deliver/showDelivered')
                );
                $this->ajaxReturn($data);
            } else{//编辑失败
                $data = array(
                    'status' => 1,
                    'info' => '编辑失败',
                    'url' => U('Deliver/showDelivered')
                );
                $this->ajaxReturn($data);
            }
        } elseif (isset($_GET['id']) && !empty($_GET['id'])) {//显示选定待修改的发货信息
            $field = 'd.id,d.uid,d.realname,d.cellphone,d.take_address,d.express_name,
                    d.express_number,d.deliver_name,
                    w.lottery_id,w.title,w.thumbnail,w.apply_time,
                    m.mobile,m.nickname';
            $info = M()//要使用os_delivered作为主表，因为可以避免去os_win_prize表中取排除数据
            ->table('__DELIVERED__ d')
            ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
            ->join('LEFT JOIN __MEMBER__ m ON d.uid = m.uid')
            ->field($field)
            ->where('d.id='.I('get.id'))
            ->find();
            $this->assign('info', $info);
            $this->display();
        } else {
            $data = array(
                'status' => 2,
                'info' => '没有传入参数id',
                'url' =>U('Deliver/showDelivered')
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 已发货申请换货
     * @param $delivered_id 已发货记录编号
     */
    public function applyReplace(){
        header("content-type:text/html;charset=utf-8");
        $user_info = session('user_auth');
        if (!empty($_POST)) {//确定申请换货
            $model = M('apply_replace');
            $model->create();
            $model->apply_name = $user_info['username'];
            $model->apply_time = getCurrentTime();
            if ($model->add()) {//在申请换货表中加入一条记录
                $info = $model->_sql();
                    //修改win_prize表中对应记录status为5，已申请换货
                    //$data_a = array('status'=>5);
                    $res = M('win_prize')->where('lottery_id='.I('lottery_id'))->setField('status', 5);
                    if ($res) {
                        $data = array(
                            'status' => 1,
                            'info' => '填写申请成功',
                            'url' => U('Replace/index')
                        );
                        //=================[debug]要在申请换货审核表中插入一条记录
                        $this->ajaxReturn($data);
                    } else {
                        $data = array(
                            'status' => 2,
                            'info' => '修改win_prize表中的status失败',
                            'url' => U('Deliver/showDelivered')
                        );
                        $this->ajaxReturn($data);
                    }
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '填写确定申请换货失败',
                    'url' => U('Deliver/showDelivered')
                );
                $this->ajaxReturn($data);
            }
        } elseif(isset($_GET['id']) && !empty($_GET['id'])) {//get请求
             $field = 'd.id,d.uid,d.realname,d.cellphone,d.take_address,d.express_name,
                    d.express_number,d.deliver_name,d.deliver_address,d.deliver_phone,d.deliver_address,d.operate_time,
                    w.lottery_id,w.title,w.thumbnail,w.apply_time,
                    m.mobile,m.nickname';
            $info = M()//要使用os_delivered作为主表，因为可以避免去os_win_prize表中取排除数据
            ->table('__DELIVERED__ d')
            ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
            ->join('LEFT JOIN __MEMBER__ m ON d.uid = m.uid')
            ->field($field)
            ->where('d.id='.I('get.id'))
            ->find();
            $this->assign('info', $info);
            $this->display();
        } else {//没有传入参数id
            $data = array(
                'status' => 2,
                'info' => '没有传入参数id',
                'url' => U('Deliver/showDelivered')
            );            
            $this->ajaxReturn($data);
        }
    }
}