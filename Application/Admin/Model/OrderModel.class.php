<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com>
// +----------------------------------------------------------------------
namespace Admin\Model;

use Think\Model;

/**
 * 配置模型
 * 
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class OrderModel extends Model
{

    protected $_validate = array(
        array(
            'orderid',
            'require',
            '订单号必须填写'
        ),
        array(
            'assistant',
            'require',
            '操作人必须填写'
        )
    );

    /* 自动完成规则 */
    protected $_auto = array(
        array(
            'orderid',
            'htmlspecialchars',
            self::MODEL_BOTH,
            'function'
        )
    );

    public function info($id, $field = true)
    {
        /* 获取信息 */
        $map = array();
        if (is_numeric($id)) { // 通过ID查询
            $map['id'] = $id;
        } else { // 通过标识查询
            $map['name'] = $id;
        }
        return $this->field($field)
            ->where($map)
            ->find();
    }

    /**
     * 获取优惠券树，指定优惠券则返回指定优惠券极其子优惠券，不指定则返回所有优惠券树
     * 
     * @param integer $id
     *            优惠券ID
     * @param boolean $field
     *            查询字段
     * @return array 优惠券树
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    
    /**
     * 更新优惠券信息
     * 
     * @return boolean 更新状态
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function update()
    {
        $data = $this->create();
        if (! $data) { // 数据对象创建错误
            return false;
        }
        
        /* 添加或更新数据 */
        if (empty($data['id'])) {
            $res = $this->add();
        } else {
            $res = $this->save();
        }
        
        $id = safe_replace($_POST["id"]);
        $orderid = M('order')->where("id='$id'")->getField("orderid");
        $status = I('status');
        /* 根据状态判断操作 */
        if ($status) {
            switch ($status) {
                case '1':
                    M('order')->where("orderid='$orderid'")->setField('status', '1');
                    
                    break;
                case '2':
                    M('order')->where("id='$id'")->setField('status', '2');
                    
                    // 根据订单id获取购物清单
                    $list = M("shoplist")->where("orderid='$id'")->select();
                    
                    foreach ($list as $k => $val) {
                        // 获取购物清单数据表产品id，字段id
                        $byid = $val["id"];
                        $goodid = $val["goodid"];
                        // 销量加1 库存减1
                        $sales = M('document')->where("id='$goodid'")->setInc('sale');
                        $stock = M('document')->where("id='$goodid'")->setDec('stock');
                        $data['status'] = 2;
                        M("shoplist")->where("id='$byid'")->save($data);
                    }
                    break;
                
                case '3':
                    M('order')->where("id='$id'")->setField('status', '3');
                    
                    // 根据订单id获取购物清单,设置商品状态为已完成.，status=3
                    $del = M("shoplist")->where("orderid='$id'")->select();
                    
                    foreach ($del as $k => $val) {
                        // 获取购物清单数据表产品id，字段id
                        $byid = $val["id"];
                        $data['iscomment'] = 1;
                        $data['status'] = 3;
                        $shop = M("shoplist");
                        $shop->where("id='$byid'")->save($data);
                    }
                    
                    break;
            }
        }
        
        return $res;
    }

    /**
     * 查看待发货记录，返回待发货记录。
     */
    public function showDeliveringList()
    {
        return 2;
        $data = array(); // 用于存储返回的数据
        $field = 'd.uid,d.user_name,d.nickname,,
                  w.lottery_id,w.apply_time,w.thumbnail,
                  a.cellphone,a.take_address,a.realname';
        $list = M() // 每页显示记录
            ->table('delivering d')
            ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
            ->join('__ADDRESS__ a ON d.address_id = a.id')
            ->field($field)
            ->order('d.id desc')
            ->page($_GET['p'] . ',10')
            ->select();
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $data['list'] = $list; // 赋值每页显示的记录
                               // 总记录数
        $count = M('delivering')->count();
        $data['count'] = $count;
        $page = new \Think\Page($count, 10);
        $show = $page->show();
        $data['show'] = $show; // 赋值底部显示
        return $data;
    }

    /**
     * 发货方法之显示待发货信息。传入待发货记录编号和快递信息，在已发货表中插入一条记录，在待发货表中删除对应记录
     * 
     * @param
     *            id 待发货记录编号
     * @param address_id 收货地址编号
     * @param
     *            快递信息
     */
    public function showDeliveringInfo(){
           $data = array();//用于存放信息
           $model = M('delivered');
           $where_address['id'] = I('get.address_id');
           $field = 'realname,cellphone,take_address';
           //收货地址信息
           $take = M('address') 
               ->where($where_address)
               ->field($field)
               ->find();
           
           //用户商品信息
           $delivering_info = M('delivering')
                                ->field('uid,user_name,nickname,lottery_id,title,thumbnail')
                                ->where('id='.I('get.id'))
                                ->find();
           $data = array_merge($delivering_info, $take);            
           return $data;
    }

    /**
     * 发货。填写快递、发货人等信息，在已发货表中插入一条记录，删除待发货表中的记录
     */
    public function deliver($model, $delivering_id){
            header('Content-type:text/html;charset=utf-8');
            $model->operate_name = session('name');//操作人姓名，即发货人
            $model->operate_time = getCurrentTime();//操作时间，即发货时间
            if ($model->add()) {
                echo "在已发货表中插入记录成功<br/";
                 $res = M('delivering')->where('id='.$delivering_id)->delete();
                 if ($res) {
                     echo '在待发货表中插入记录成功';
                     return true;
                 }
            } else {
                echo '在已发货表中插入记录失败';
                return false;
            }
    }
    
    /**
     * 查询已发货记录。可以根据状态、申请时间的起止时间、审核人用户名搜索
     */
    public function showDelivered()
    {
        
        // 选定状态
        if (isset(I('get.status')) && ! empty(I('get.status'))) {
            $where['status'] = I('get.status');
        }
        
        // 选定申请时间的起止时间
        if (isset(I('get.start_time')) && ! empty(I('get.start_time'))) {
            $where['apply_time'] = array(
                'egt',
                I('get.start_time')
            );
        }
        
        // 选定申请时间结束时间
        if (isset(I('get.end_time')) && ! empty(I('get.end_time'))) {
            $where['apply  _time'] = array(
                'elt',
                I('get.end_time')
            );
        }
        
        // 输入用户名或者用户ID
        if (isset(I('get.input')) && !empty(I('get.input'))) {
            //如果匹配的是8位数字，就是用户ID，否则是用户名
            $pattern = '/[1-9]{8}/';
            if (preg_match($pattern, I('get.input'))) {//用户编号是固定的8位数
                echo '匹配用户编号成功';
                $where['uid'] = I('get.input');
            } else {
                echo '输入的不是用户编号。是用户名';
                $where['user_name'] = array('like', '%'.I('get.user_name').'%');
            }
        }
        
        //输入审核人
        if (isset(I('get.audit_name')) && !empty(I('get.audit_name'))) {
            $where['audit_name'] =  array(
                'like',
                '%'.I('get.audit_name').'%'
            );
        }
        $data = array(); // 用于存放数据
        $field = 'uid,user_name,nickname,lottery_id,title,thumbnail,realname,cellphone,take_address,
                    apply_time,express_name,express_number,deliver_address,operate_name,
                    operate_time,status';
        $order = 'id desc';
        $model = M('delivered');
        // 每页记录数
        $list = $model->field($field)
            ->where($where)
            ->order($order)
            ->page($_GET['p'] . ',10')
            ->select();
        $data['list'] = $list;
        // 总记录数
        $count = $model->where($where)->count();
        $data['count'] = $count;
        $page = new \Think\Page($count, 10);
        $show = $page->show();
        $data['show'] = $show;
        return $data;
    }
    
    /**
     * 对已发货记录的发货信息进行修改或者申请换货或者编辑待审核申请换货
     * 返回已发货的记录信息
     */
    public function showDeliveredDetail($id){
        $field = 'id,uid,user_name,nickname,lottery_id,title,thumbnail,realname,cellphone,
                    take_address,apply_time,express_name,express_number,deliver_name,deliver_phone,deliver_address';
        $data = M('delivered')
                ->field($field)
                ->where('id='.$id)
                ->find();
        return $data;
    }
    
    /**
     * 对已发货的记录信息进行编辑
     * @parma $model 对象中包含有已发货记录的编号
     */
    public function updateDelivered($model){
        $model->operate_name = session('name');
        $model->operate_time = getCurrentTime();
        if ($model->save()) {
            echo '修改已发货信息成功';
            return;
        } else {
            echo '修改已发货信息失败';
            return false;
        }
    }
        
    /**
     * 填写申请换货的发货信息
     * @param  $model  由post请求创建的数据对象，排除已发货记录的编号
     * @param $id 已发货记录的编号
     * @param bool
     */
    public function addReplace($model, $id){
        $model->apply_name = session('name');
        $model->apply_time = getCurrentTime();
        header('Content-type:text/html;charset=utf-8');
        if ($model->add()) { //在换货表中插入一条记录
            echo '填写申请换货信息成功';
            //修改已发货表中的记录状态为已申请换货
            if (M('delivered')->where('id='.$id)->setField('status',4)) {
                echo '修改已发货表中的记录状态为已申请换货成功';
                return true;
            } else {
                echo '修改已发货表中的记录状态为已申请换货失败';
                return false;
            }
            return true;
        } else {
            echo '填写申请换货信息失败';
            return false;
        }
    }
    
    /**
     * 查看待审核换货
     */
    public function auditApplyReplace(){
        $data = array();//用于存放数据
        $field = 'd.uid,d.user_name,d.nickname,d.lottery_id,d.title,d.thumbnail,d.realname,d.cellphone,d.take_address,
                  a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,a.deliver_address,a.apply_name,a.apply_time';
        $list = M()
                ->table('__APPLY_REPLACE__ a')
                ->join('__DELIVERED__ d ON a.delivered_id = d.id')
                ->field($field)
                ->where('status=1')
                ->order('a.id desc')
                ->page($_GET['p'].',10')
                ->select();
        $data['list'] = $list;//每页记录
        var_dump($data);
        exit;  
        //总记录数
        $count = M('apply_replace')->where('status=1')->count();
        $data['count'] = $count;
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $data['show'] = $show;
    }

    /**
     * 编辑待审核换货信息
     */
    public function updateReplace($model){
         $model->apply_name = session('name');
         $model->apply_time = getCurrentTime();
         header('Content-type:text/html;charset=utf-8');
         if ($model->save()) { //修改换货表中的发货信息
             echo '修改换货信息成功';
             return true;
         } else {
             echo '修改换货信息失败';
             return false;
         }
     }
    
    /**
     * 审核待审核申请换货记录
     * 状态改变：通过---》待客户确认；拒绝----》已拒绝
     * @param $id   申请换货记录编号
     * @param $action 操作（2通过，3拒绝）
     */
    public function auditApplyRepalce($id, $action){
        $data = array(
            'result' => $action,
            'status' => 2,
            'audit_name' => session('name'),
            'audit_name' => getCurrentTime(),
        );
        $res = M('apply_replace')->where('id='.$id)->setField('status',$action);
        if ($res) {
            echo '审核操作完成';
            return true;
        } else {
            echo '审核操作失败';
            return false;
        }
    }
}
