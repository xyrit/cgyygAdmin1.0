<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | author 烟消云散 <1010422715@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Log;
/**
 * 后台订单控制器
 * 
 * @author 烟消云散 <1010422715@qq.com>
 */
class OrderController extends AdminController
{

    /**
     * 订单管理
     * author 烟消云散 <1010422715@qq.com>
     */
    public function index()
    {
        /* 查询条件初始化 */
        $status = $_GET['status'];
        if (isset($_GET['status'])) {
            switch ($status) {
                case '0':
                    
                    $meta_title = "所有订单";
                    break;
                case '1':
                    $map['status'] = $status;
                    $meta_title = "已提交订单";
                    break;
                case '2':
                    $map['status'] = $status;
                    $meta_title = "已发货订单";
                    break;
                
                case '3':
                    $map['status'] = $status;
                    $meta_title = "已签收订单";
                    
                    break;
            }
        } 

        else {
            $status = '';
            
            $meta_title = "所有订单";
        }
        if (isset($_GET['title'])) {
            $map['orderid'] = array(
                'like',
                '%' . (string) I('title') . '%'
            );
        }
        if (isset($_GET['time-start'])) {
            $map['update_time'][] = array(
                'egt',
                strtotime(I('time-start'))
            );
        }
        if (isset($_GET['time-end'])) {
            $map['update_time'][] = array(
                'elt',
                24 * 60 * 60 + strtotime(I('time-end'))
            );
        }
        if (isset($_GET['nickname'])) {
            $map['uid'] = M('Member')->where(array(
                'nickname' => I('nickname')
            ))->getField('uid');
        }
        $this->assign('status', $status);
        $this->meta_title = $meta_title;
        $list = $this->lists('Order', $map, 'id desc');
        $this->assign('list', $list);
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        
        $this->display();
    }
    // 导出excel
    public function out()
    {
        $xlsName = CONTROLLER_NAME;
        $Field = 'id,total_money,tag,orderid,ship_price,score,coupon_money,uid';
        if (isset($_GET['status'])) {
            $map['status'] = array(
                'like',
                '%' . (int) I('status') . '%'
            );
        } else {
            $map['status'] = array(
                'gt',
                0
            ); // 有效订单
        }
        $xlsCell = array(
            array(
                'id',
                '编号'
            ),
            array(
                'orderid',
                '订单号'
            ),
            array(
                'tag',
                '支付订单号'
            ),
            array(
                'total_money',
                '实际金额'
            ),
            array(
                'ship_price',
                '运费'
            ),
            array(
                'score',
                '消耗积分'
            ),
            array(
                'coupon_money',
                '消耗优惠券金额'
            ),
            array(
                'uid',
                '用户uid'
            )
        );
        $xlsModel = M($xlsName);
        $xlsData = $xlsModel->where($map)
            ->Field($Field)
            ->select();
        if (! $xlsData) {
            $this->error('无数据');
        }
        exportExcel($xlsName, $xlsCell, $xlsData);
    }

    public function out2()
    {
        $Field = 'id,pricetotal,orderid,shipprice,score,codeid';
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="user.csv"');
        header('Cache-Control: max-age=0');
        
        // 从数据库中获取数据，为了节省内存，不要把数据一次性读到内存，从句柄中一行一行读即可
        $sql = 'select * from tbl where ……';
        $stmt = M('order')->Field($Field)
            ->limit(10)
            ->select();
        ;
        
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        
        // 输出Excel列名信息
        $head = array(
            '姓名',
            '性别',
            '年龄',
            'Email',
            '电话',
            '……'
        );
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = iconv('utf-8', 'gbk', $v);
        }
        
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);
        
        // 计数器
        $cnt = 0;
        // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;
        
        // 逐行取出数据，不浪费内存
        while ($row = $stmt->fetch(Zend_Db::FETCH_NUM)) {
            
            $cnt ++;
            if ($limit == $cnt) { // 刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $cnt = 0;
            }
            
            foreach ($row as $i => $v) {
                $row[$i] = iconv('utf-8', 'gbk', $v);
            }
            fputcsv($fp, $row);
        }
    }

    /**
     * 新增订单
     * 
     * @author 烟消云散 <1010422715@qq.com>
     */
    public function add()
    {
        if (IS_POST) {
            $order = D('order');
            
            if (false !== $order->update()) {
                
                $this->success('新增成功', U('index'));
            } else {
                $this->error('新增失败');
            }
        } else {
            $this->meta_title = '新增配置';
            $this->assign('info', null);
            $this->display();
        }
    }

    /**
     * 编辑订单
     * 
     * @author 烟消云散 <1010422715@qq.com>
     */
    public function edit($id = 0)
    {
        if (IS_POST) {
            $order = D('order');
            
            if (false !== $order->update()) {
                
                $this->success('更新成功', Cookie('__forward__'));
            } else {
                $this->error('更新失败55' . $id);
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('order')->find($id);
            $detail = M('order')->where("id='$id'")->select();
            $list = M('shoplist')->where("orderid='$id'")->select();
            $addressid = M('order')->where("id='$id'")->getField("addressid");
            
            $trans = M("address")->where("id='$addressid'")->select();
            $this->assign('trans', $trans);
            $this->assign('alist', $address);
            if (false === $info) {
                $this->error('获取订单信息错误');
            }
            $this->assign('list', $list);
            $this->assign('detail', $detail);
            $this->assign('info', $info);
            $this->assign('a', $orderid);
            $this->meta_title = '编辑订单';
            $this->display();
        }
    }

    /**
     * 订单发货
     * 
     * @author 烟消云散 <1010422715@qq.com>
     */
    public function send($id = 0)
    {
        if (IS_POST) {
            $order = D('order');
            
            if (false !== $order->update()) {
                // 记录行为
                action_log('update_order', 'order', $data['id'], UID);
                $this->success('更新成功', Cookie('__forward__'));
            } else {
                $this->error('更新失败' . $id);
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('order')->find($id);
            $detail = M('order')->where("id='$id'")->select();
            $list = M('shoplist')->where("orderid='$id'")->select();
            $addressid = M('order')->where("id='$id'")->getField("addressid");
            $trans = M("address")->where("id='$addressid'")->select();
            $this->assign('trans', $trans);
            $this->assign('list', $list);
            $this->assign('detail', $detail);
            $this->assign('info', $info);
            
            $this->meta_title = '订单发货';
            $this->display();
        }
    }

    public function complete($id = 0)
    {
        if (IS_POST) {
            $order = D('order');
            if (false !== $order->update()) {
                // 记录行为
                action_log('update_order', 'order', $data['id'], UID);
                $this->success('更新成功', Cookie('__forward__'));
            } else {
                $this->error('更新失败' . $id);
            }
        } 

        else {
            $info = array();
            /* 获取数据 */
            $info = M('order')->find($id);
            $detail = M('order')->where("id='$id'")->select();
            $list = M('shoplist')->where("orderid='$id'")->select();
            $addressid = M('order')->where("id='$id'")->getField("addressid");
            
            $trans = M("address")->where("id='$addressid'")->select();
            $this->assign('trans', $trans);
            if (false === $info) {
                $this->error('获取订单信息错误');
            }
            $this->assign('list', $list);
            $this->assign('detail', $detail);
            $this->assign('info', $info);
            
            $this->meta_title = '订单发货';
            $this->display();
        }
    }

    /**
     * 删除订单
     * 
     * @author yangweijie <yangweijiester@gmail.com>
     */
    public function del()
    {
        if (IS_POST) {
            $ids = I('post.id');
            $order = M("order");
            
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    
                    $order->where("id='$id'")->delete();
                    $shop = M("shoplist");
                    $shop->where("orderid='$id'")->delete();
                }
            }
            $this->success("删除成功！");
        } else {
            $id = I('get.id');
            $db = M("order");
            $status = $db->where("id='$id'")->delete();
            if ($status) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }

    /**
     * 查看待发货记录
     */
    public function showDelivering()
    {
        $data = array(); // 用于存储返回的数据
        $field = 'd.uid,d.user_name,d.nickname,d.address_id,d.win_id,
                  w.lottery_id,w.apply_time,w.thumbnail,w.title,
                  a.cellphone,a.take_address,a.realname';
        $list = M() // 每页显示记录
            ->table('__DELIVERING__ d')
            ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
            ->join('__ADDRESS__ a ON d.address_id = a.id')
            ->field($field)
            ->order('d.id desc')
            ->page($_GET['p'] . ',10')
            ->select();
        $this->assign('list', $list);
        $meta_title = '待发货订单';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('delivering')->count();//总记录数
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
        // 测试单元测试是否成功
        if (! empty($_POST)) {
            if (isset($_POST['id']) && ! empty($_POST['id'])) {
                //防止重复提交，先检测delivering表中此id是否存在
                $res = M('delivering')->getById(I('post.id'));
                if (!$res) {// 重复提交或者伪造的请求，delivering中id其实不存在
                    $data = array(
                        'status' => 2,
                        'info' => '操作失败',
                        'url' =>U('Order/showDelivering')
                    );
                    $this->ajaxReturn($data);
                    exit;
                }
                
                // 测试用的session
                // session('name','lindaman');
                $model = M('delivered');
                $model->create();
                $model->operate_name = session('name'); // 操作人姓名，即发货人
                $model->operate_time = getCurrentTime(); // 操作时间，即发货时间
//                 $model->create_time = null;//对于null或者为空的，对象是不会创建数据的。更改了验证规则也不行
                if ($model->add()) {
//                     echo "在已发货表中插入记录成功<br/>";
                    $res = M('delivering')->where('id=' . I('post.id'))->delete();
                    if ($res) {
                        //修改win_prize表中的状态
                        $res = M('win_prize')->where('id='.I('win_id'))->setField('status',2);
                        if ($res) {
//                         $this->success(U('Order/showDelivered'));
                            $data = array(
                                'status' => 1,
                                'info' => '操作成功',
                                'url' => U('Order/showDelivered')
                            );
                            $this->ajaxReturn($data);
                            exit;
                        }
                    } else {
                        echo "在待发货表中删除记录失败<br />";
//                         $info = M('delivering')->getDbError();
//                         die(M('delivering')->getDbError());
                    }
                } else {
                    echo '在已发货表中插入记录失败';
                    exit();
                }
            } else {
                echo '没有传递待发货记录的编号';
                exit();
            }
        } else {
            // 待发货地址编号，用于获取用户商品信息
            if (isset($_GET['id']) && ! empty($_GET['id'])) { // isset()和empty()是用来检测变量的，不能检测函数返回的值。否则发生错误
                $where_a['id'] = I('get.id');
            }
            
            $field = 'd.id,d.uid,d.user_name,d.nickname,d.win_id,
                      w.lottery_id,w.apply_time,w.thumbnail,w.title,
                      a.cellphone,a.take_address,a.realname';
            $info = M() 
                ->table('__DELIVERING__ d')
                ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
                ->join('__ADDRESS__ a ON d.address_id = a.id')
                ->field($field)
                ->where($where_a)
                ->find();
            $this->assign('info', $info);
//             var_dump($list);
            $this->display();
        }
    }

    /**
     * 查询已发货记录。可以根据状态、申请时间的起止时间、审核人用户名搜索
     */
    public function showDelivered()
    {
        header('Content-type:text/html;charset=utf-8');
        // 选定状态
        if (isset($_GET['status']) && ! empty($_GET['status'])) {
            if ($_GET['status'] != 5){
                $where['status'] = I('get.status');
            }
        }
        
        // 选定申请时间的起止时间
        if (isset($_GET['start_time']) && ! empty($_GET['start_time'])) {
            $where['apply_time'][] = array(
                'egt',
                I('get.start_time')
            );
        }
        
        // 选定申请时间结束时间
        if (isset($_GET['end_time']) && ! empty($_GET['end_time'])) {
            $where['apply_time'][] = array(
                'elt',
                I('get.end_time')
            );
        }
        
        // 输入用户名或者用户ID
        if (isset($_GET['input']) && ! empty($_GET['input'])) {
            $where['user_name|uid'] = array(
                'like',
                '%' . I('get.input') . '%'
            );
        }
        
        // 输入操作人
        if (isset($_GET['operate_name']) && ! empty($_GET['operate_name'])) {
            $where['operate_name'] = array(
                'like',
                '%' . I('get.operate_name') . '%'
            );
        }
        
        $_GET['p'] = $_GET['p']? $_GET['p'] : 1;//如果是跳转到这里的
        
        $data = array(); // 用于存放数据
        $field = 'id,uid,user_name,nickname,lottery_id,title,thumbnail,realname,cellphone,take_address,
                    apply_time,express_name,express_number,deliver_name,deliver_address,operate_name,
                    operate_time,status';
        $order = 'id desc';
        $model = M('delivered');
        
        // 每页记录数
        $list = $model->field($field)
            ->where($where)
            ->order($order)
            ->page($_GET['p'] . ',10')
            ->select();
        $this->assign('list', $list);
        $meta_title = '已发货订单';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('delivered')->count();//总记录数
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
        if (!empty($_POST)) {//进行确认操作
            $model = M('delivered');
            $model->create();
            if ($model->save()) {//编辑成功
                $data = array(
                    'status' => 1,
                    'info' => '编辑成功',
                    'url' => U('Order/showDelivered')
                );
                $this->ajaxReturn($data);
                exit;
            } else{//编辑失败
                $data = array(
                    'status' => 1,
                    'info' => '编辑失败',
                    'url' => U('Order/showDelivered')
                );
                $this->ajaxReturn($data);
                exit;
            }
        } else {//显示选定待修改的发货信息
            $model = M('delivered');
            $field = 'id,uid,user_name,nickname,lottery_id,title,thumbnail,realname,cellphone,take_address,apply_time,
                      express_name,express_number,deliver_name,deliver_phone,deliver_address';
            $info = $model->field($field)->where('id='.I('get.id'))->find();
            $this->assign('info', $info);
            $this->display();
        }
    }
    
    /**
     * 点击申请换货操作
     * @param $id 已发货记录编号
     */
    public function applyReplace(){
        if (!empty($_POST)) {//确定申请换货
            
            $model = M('apply_replace');
            $model->create();
            $model->apply_name = session('name');
            $model->apply_time = getCurrentTime();
            if ($model->add()) {//在申请换货表中加入一条记录
                //修改delivered中的对应记录status为4（已申请换货）
                $res = M('delivered')->where('id='.I('delivered_id'))->setField('status',4);
                if ($res) {
                    //修改win_prize表中对应记录status为5，已申请换货
                    $res = M('win_prize')->where('lottery='.I('lottery_id'))->setField('status', 5);
                    if ($res) {
                        $data = array(
                            'status' => 1,
                            'info' => '填写申请成功',
                            'url' => U('Order/showAuditingReplace')
                        );
                        //=================[debug]要在申请换货审核表中插入一条记录
                        $this->ajaxReturn($data);
                        exit;
                    }
                } else {
                    echo '修改delivered表中的status失败';
                    exit;
                }
            } else {
                echo '填写确定申请换货失败';
                exit();
            }
        } else {
            $field ='id,uid,user_name,nickname,lottery_id,title,thumbnail,realname,cellphone,take_address,apply_time,
                      express_name,express_number,deliver_name,deliver_phone,deliver_address';
            $info = M('delivered')->field($field)->where('id='.I('id'))->find();
            $this->assign('info', $info);
            $this->display();
        }
    }
    

    /**
     * 查询待审核申请换货
     */
    public function showAuditingReplace()
    {
        header('Content-type:text/html;charset=utf-8');
        $data = array(); // 用于存放数据
        $field = 'd.id,d.uid,d.user_name,d.nickname,d.lottery_id,d.title,d.thumbnail,d.realname,d.cellphone,d.take_address,
                  a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,a.deliver_address,a.apply_name,a.apply_time';
        $list = M()->table('__APPLY_REPLACE__ a')
            ->join('__DELIVERED__ d ON a.delivered_id = d.id')
            ->field($field)
            ->where('a.type=1')
            ->order('a.id desc')
            ->page($_GET['p'] . ',10')
            ->select();
        $this->assign('list', $list);
        $meta_title = '待审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('applyReplace')->where('status=1')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }

    /**
     * 编辑待审核换货信息
     * [debug]要改成type，因为后面的编辑已审核也用到
     * 
     * @param $_GET['id'] 待审核申请换货记录编号            
     */
    public function updateAuditingReplace()
    {
        header('Content-type:text/html;charset=utf-8');
        if (!empty($_POST)) {
            $model = M('apply_replace');
            $model->create();
            $model->update_time = getCurrentTime();
            $res = $model->save();
            if ($res) {
                $data = array(
                    'status' => 1,
                    'info' => '修改申请换货信息成功',
                    'url' => U('Order/showAuditingReplace')
                );
                $this->ajaxReturn($data);
                exit();
            } else {
                $info = $model->getDbError();
                $data = array(
                    'status' => 2,
                    'info' => '修改失败'.$info,
                    'url' => U('Order/showAuditingReplace')
                );
                $this->ajaxReturn($data);
                echo "修改申请换货信息失败<br />";
                exit();
            }
        } else {
            $model = M('apply_replace');
            $where['a.id'] = I('id');
            $data = array(); // 用于存放数据
            $field = 'd.id,d.uid,d.user_name,d.nickname,d.lottery_id,d.title,d.thumbnail,d.realname,d.cellphone,d.take_address,
                      a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,a.deliver_address,a.apply_name,a.apply_time';
            $info = M()->table('__APPLY_REPLACE__ a')
            ->join('__DELIVERED__ d ON a.delivered_id = d.id')
            ->field($field)
            ->where($where)
            ->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     * 对待审核申请换货记录进行“通过”或“拒绝操作
     * ================加入批量通过和拒绝
     * 
     * @param $_GET['id'] 待审核申请换货记录编号            
     * @param $_GET['action'] 操作（1通过，3拒绝）=====aciont的数值不同            
     */
    public function auditReplace()
    {
        header('Content-type:text/html;charset=utf-8');
        if (isset($_GET['id']) && ! empty($_GET['id'])) {
            $id = I('get.id');
            if (is_array($id)) { // 批量审核
                $where['id'] = array(
                    'in',
                    $id
                );
            } else {
                $where['id'] = $id;
            }
            if (isset($_GET['action']) && ! empty($_GET['action'])) {
                $data = array(
                    'type' => 2, // 1待审核，2已审核
                    'status' => I('get.action'), // 审核结果
                    'audit_name' => session('name'), // 审核人员
                    'audit_time' => getCurrentTime()//审核时间
                ) ;
                $model = M('apply_replace');
                $res = $model->where($where)->save($data);
                if ($res) { // 审核成功
                    /*
                     *修改win_prize表中的status为2待发货或者6已拒绝申请换货 
                     */
                    if ($_GET['action'] == 1) {//通过
                        $status = 2;
                    } else {
                        $status = 6;  
                    }
                    M('win_prize')->where('lottery_id='.I('lottery_id'))->setField('status', $status);
//                     echo '审核申请换货成功，即将跳转到已审核页面';
                    $href = U('Order/showAuditedReplace');
                    header("Location:$href");
                } else {
                    echo "失败，审核申请换货。<br />";
                    echo $model->getDbError();
                    exit();
                }
            } else {
                echo '没有传入action值';
                exit();
            }
        } else {
            echo '没有传入$_GET[id]或者传参格式有问题';
            exit();
        }
    }

    /**
     * 查看已审核换货记录
     */
    public function showAuditedReplace()
    {
        header('Content-type:text/html;charset=utf-8');
        // 选定记录状态，对应'status'
        if (isset($_GET['status']) && ! empty($_GET['status'])) {
            $where['d.status'] = I('get.status');
        }
        
        // 输入用户名或ID，不区分出是用户名或ID，而是把它们两个同时当作条件
        if (isset($_GET['input']) && ! empty($_GET['input'])) {
            $where['d.user_name|d.uid'] = array(
                'like',
                '%' . I('get.input') . '%'
            );
        }
        
        // 输入申请时间的起始时间
        if (isset($_GET['start_time']) && ! empty($_GET['start_time'])) {
            $where['d.apply_time'] = array(
                'egt',
                I('get.start_time')
            );
        }
        
        // 输入申请时间的截止时间
        if (isset($_GET['end_time']) && ! empty($_GET['end_time'])) {
            $where['d.apply_time'] = array(
                'elt',
                I('get.end_time')
            );
        }
        
        // 输入审核人
        if (isset($_GET['audit_name']) && ! empty($_GET['audit_name'])) {
            $where['d.audit_name'] = array(
                'like',
                '%' . I('get.audit_name') . '%'
            );
        }
        
        $data = array(); // 用于存储数据
        $field = 'd.uid,d.user_name,d.nickname,d.lottery_id,d.title,d.thumbnail,d.realname,d.cellphone,d.take_address,
                  a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,a.deliver_address,a.apply_name,
                  a.apply_time,a.audit_name,a.audit_time,a.status';
        $list = M()->table('__APPLY_REPLACE__ a')
            ->join('__DELIVERED__ d ON a.delivered_id = d.id')
            ->field($field)
            ->where('a.type=2')
            ->order('a.id desc')
            ->page($_GET['p'] . ',10')
            ->select();
        $this->assign('list', $list);
        $meta_title = '已审核换货';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('applyReplace')->where('type=2')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
}