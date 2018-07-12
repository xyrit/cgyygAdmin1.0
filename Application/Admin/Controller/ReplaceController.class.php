<?php

namespace Admin\Controller;

/**
 * 换货管理控制器
 * jerry
 * 2016.1.23
 */
class ReplaceController extends AdminController {

    /**
     * 查询待审核申请换货
     */
    public function index() {
        header('Content-type:text/html;charset=utf-8');
        $data = array(); // 用于存放数据
        $_GET['p'] = isset($_GET['p']) ? $_GET['p'] : 0;
        $field = 'd.uid,d.realname,d.cellphone,d.take_address,
                w.lottery_id,w.title,w.thumbnail,
                a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,
                a.deliver_address,a.apply_name,a.apply_time,
                m.mobile,m.nickname';
        $list = M()
                ->table('__APPLY_REPLACE__ a')
                ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
                ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                ->field($field)
                ->where('a.type=1')
                ->order('a.apply_time asc')
                ->page($_GET['p'] . ',10')
                ->select();


        $this->assign('list', $list);
        $meta_title = '换货管理';
        $this->assign('meta_title', $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
//         $count = M('applyReplace')->where('status=1')->count();//总记录数
        $count = M()
                ->table('__APPLY_REPLACE__ a')
                ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
                ->where('a.type=1')
                ->count();
        $this->assign('count', $count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p = $page->show();
        $this->assign('page', $p ? $p : '');
        $this->display();
    }

    /**
     * 编辑待审核换货信息
     * @param $_GET['id'] 待审核申请换货记录编号
     */
    public function updateAuditingReplace() {
        header('Content-type:text/html;charset=utf-8');
//         if (!empty($_POST)) {
        if (IS_POST && isset($_POST['id']) && !empty($_POST['id'])) {
            $model = M('apply_replace');
            $model->create();
            $model->update_time = getCurrentTime();
            $res = $model->save();
            if ($res) {
                $data = array(
                    'status' => 1,
                    'info' => '修改申请换货信息成功',
                    'url' => U('Replace/index')
                );
                $this->ajaxReturn($data);
            } else {
                $info = $model->getDbError();
                $data = array(
                    'status' => 2,
                    'info' => '修改申请换货信息失败' . $info,
                    'url' => U('Replace/index')
                );
                $this->ajaxReturn($data);
            }
        } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
            $model = M('apply_replace');
            $where['a.id'] = I('id');
            $data = array(); // 用于存放数据
            $field = 'd.uid,d.realname,d.cellphone,d.take_address,
                w.lottery_id,w.title,w.thumbnail,
                a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,
                a.deliver_address,a.apply_name,a.apply_time,a.operate_time,
                m.mobile,m.nickname';
            $info = M()
                    ->table('__APPLY_REPLACE__ a')
                    ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                    ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                    ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
                    ->field($field)
                    ->where($where)
                    ->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     * 对待审核申请换货记录进行“通过”或“拒绝操作
     * @param $_GET['id'] 待审核申请换货记录编号
     * @param $_GET['action'] 操作（1通过，3拒绝）=====aciont的数值不同
     * @param $_GET['lottery_id'] 
     */
    public function auditReplace() {
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $where['id'] = I('get.id');
            if (isset($_GET['action']) && !empty($_GET['action'])) {
                $data = array(
                    'type' => 2, // 1待审核，2已审核
                    'status' => I('get.action'), // 审核结果
                    'audit_name' => $user_info['username'], // 审核人员
                    'audit_time' => getCurrentTime()//审核时间
                );
                $model = M('apply_replace');
                $res = $model->where($where)->save($data);
                if ($res) { // 审核成功
                    /*
                     * 修改win_prize表中的status为2待发货或者6已拒绝申请换货
                     */
                    if ($_GET['action'] == 1) {//通过
                        $status = 2;
                        $lottery_id = I('lottery_id');
                        $field = "express_name,express_number,deliver_name,deliver_phone,deliver_address";
                        $deliver_info = $model->field($field)->where($where)->find();
                        $Dao = M();
                        $sql = "UPDATE os_delivered d left join os_win_prize w on d.win_id=w.id SET d.express_name ='" . $deliver_info["express_name"] . "',d.express_number='" . $deliver_info["express_number"] . "',d.deliver_name='" . $deliver_info["deliver_name"] . "',d.deliver_phone='" . $deliver_info["deliver_phone"] . "',d.deliver_address='" . $deliver_info["deliver_address"] . "' WHERE (w.lottery_id='" . $lottery_id . "')";
//                        ECHO $sql;
//                        EXIT;
                        $deliver_id = $Dao->execute($sql);
                    } else {
                        $status = 6;
                    }
                    $res = M('win_prize')->where('lottery_id=' . I('lottery_id'))->setField('status', $status);
                    if ($res) {
                        $data = array(
                            'status' => 1,
                            'info' => '审核成功',
                            'url' => U('Replace/showAuditedReplace')
                        );
                        $this->ajaxReturn($data);
                    } else {
                        $info = M('win_prize')->getDbError();
                        $data = array(
                            'status' => 2,
//                             'info' => $info,
                            'info' => '修改win_prize表中的status出现错误',
                            'url' => U('Replace/index')
                        );
                        $this->ajaxReturn($data);
                    }
                } else {
                    $data = array(
                        'status' => 2,
                        'info' => '审核申请换货失败',
                        'url' => U('Replace/index')
                    );
                    $this->ajaxReturn($data);
                }
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '没有传入action值',
                    'url' => U('Replace/index')
                );
                $this->ajaxReturn($data);
            }
        } else {
            $data = array(
                'status' => 2,
                'info' => '没有传入id或者参数格式有问题',
                'url' => U('Replace/index')
            );
            $this->ajaxReturn($data);
        }
    }

    /**
     * 查看已审核换货记录
     */
    public function showAuditedReplace() {
        header('Content-type:text/html;charset=utf-8');
        // 选定记录状态，对应'status'
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            if ($_GET['status'] != -1) {
                $where['w.status'] = I('get.status');
            }
        }

        // 输入用户名或ID，不区分出是用户名或ID，而是把它们两个同时当作条件
        if (isset($_GET['input']) && !empty($_GET['input'])) {
            $where['m.mobile|d.uid'] = array(
                'like',
                '%' . I('get.input') . '%'
            );
        }

        // 输入申请时间的起始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $where['a.apply_time'][] = array(
                'egt',
                I('get.start_time')
            );
        }

        // 输入申请时间的截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $where['a.apply_time'][] = array(
                'elt',
                I('get.end_time')
            );
        }

        // 输入审核人
        if (isset($_GET['audit_name']) && !empty($_GET['audit_name'])) {
            $where['a.audit_name'] = array(
                'like',
                '%' . I('get.audit_name') . '%'
            );
        }

        $where['a.type'] = 2;
        $_GET['p'] = isset($_GET['p']) ? $_GET['p'] : 0;
        $data = array(); // 用于存储数据
        $field = 'd.uid,d.realname,d.cellphone,d.take_address,
                w.lottery_id,w.title,w.thumbnail,w.status,
                a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,
                a.deliver_address,a.apply_name,a.apply_time,a.audit_name,a.audit_time,
                m.mobile,m.nickname';
        $list = M()
                ->table('__APPLY_REPLACE__ a')
                ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
                ->field($field)
                ->where($where)
                ->order('a.id desc')
                ->page($_GET['p'] . ',10')
                ->select();
        $this->assign('list', $list);
        $meta_title = '已审核换货';
        $this->assign('meta_title', $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M()
                ->table('__APPLY_REPLACE__ a')
                ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
                ->where($where)
                ->count(); //总记录数
        $this->assign('count', $count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p = $page->show();
        $this->assign('page', $p ? $p : '');
        $this->display();
    }

    /**
     * 对已审核换货进行编辑操作
     */
    public function updateAuditedReplace() {
        header('Content-type:text/html;charset=utf-8');
        if (IS_POST && isset($_POST['id']) && !empty($_POST['id'])) {
            $model = M('apply_replace');
            $model->create();
            $model->update_time = getCurrentTime();
            $res = $model->save();
            if ($res) {
                $data = array(
                    'status' => 1,
                    'info' => '修改申请换货信息成功',
                    'url' => U('Replace/showAuditedReplace')
                );
                $this->ajaxReturn($data);
            } else {
                $info = $model->getDbError();
                $data = array(
                    'status' => 2,
                    'info' => '修改申请换货信息失败' . $info,
                    'url' => U('Replace/showAuditedReplace')
                );
                $this->ajaxReturn($data);
            }
        } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
            $model = M('apply_replace');
            $where['a.id'] = I('id');
            $data = array(); // 用于存放数据
            $field = 'd.uid,d.realname,d.cellphone,d.take_address,
                w.lottery_id,w.title,w.thumbnail,
                a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,
                a.deliver_address,a.apply_name,a.apply_time,a.operate_time,
                m.mobile,m.nickname';
            $info = M()
                    ->table('__APPLY_REPLACE__ a')
                    ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                    ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                    ->join('LEFT JOIN __MEMBER__ m  ON a.uid = m.uid')
                    ->field($field)
                    ->where($where)
                    ->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     * 对已审核换货进行申请换货操作
     */
    public function applyReplace() {
        $user_info = session('user_auth');
        if (!empty($_POST)) {//确定申请换货
            $model = M('apply_replace');
            $model->create();
            $model->apply_name = $user_info['username'];
            $model->apply_time = getCurrentTime();
            if ($model->add()) {
                //在申请换货表中加入一条记录
                //修改win_prize表中对应记录status为5，已申请换货
                //在delver表中插入一条记录
                $delivered_info = M('delivered')->where('id=' . I('delivered_id'))->find();
                $data_1 = array(
                    'uid' => $delivered_info['uid'],
                    'nickname' => $delivered_info['nickname'],
                    'realname' => $delivered_info['realname'],
                    'cellphone' => $delivered_info['cellphone'],
                    'take_address' => $delivered_info['take_address'],
                    'express_name' => I('express_name'),
                    'express_number' => I('express_number'),
                    'delivered_address' => I('delivered_address'),
                    'delivered_name' => I('delivered_name'),
                    'delivered_phone' => I('delivered_phone'),
                    'operate_name' => $model->apply_name,
                    'operate_time' => $model->apply_time,
                    'win_id' => $delivered_info['win_id']
                );
                M('delivered')->add($data_1);
                /*
                 *  $field = 'd.uid,d.realname,d.cellphone,d.take_address,
                  w.lottery_id,w.title,w.thumbnail,
                  a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,
                  a.deliver_address,a.apply_name,a.apply_time,a.operate_time,a.delivered_id,
                  m.mobile,m.nickname';
                 */
                $res = M('win_prize')->where('lottery_id=' . I('lottery_id'))->setField('status', 5);
                if ($res) {
                    $data = array(
                        'status' => 1,
                        'info' => '填写申请成功',
                        'url' => U('Replace/index')
                    );
                    $this->ajaxReturn($data);
                } else {
                    $data = array(
                        'status' => 2,
                        'info' => '填写确定申请换货失败~修改win_prize表中的status为5失败',
                        'url' => U('Replace/showAuditedReplace')
                    );
                    $this->ajaxReturn($data);
                }
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '填写确定申请换货失败~修改delivered中status为4失败',
                    'url' => U('Replace/showAuditedReplace')
                );
                $this->ajaxReturn($data);
            }
        } else {
            $field = 'd.id delivered_id,d.uid,d.realname,d.cellphone,d.take_address,
                w.lottery_id,w.title,w.thumbnail,
                a.id,a.express_name,a.express_number,a.deliver_name,a.deliver_phone,
                a.deliver_address,a.apply_name,a.apply_time,a.operate_time,a.delivered_id,
                m.mobile,m.nickname';
            $info = M()
                    ->table('__APPLY_REPLACE__ a')
                    ->join('LEFT JOIN __DELIVERED__ d ON a.delivered_id = d.id')
                    ->join('LEFT JOIN __WIN_PRIZE__ w ON d.win_id = w.id')
                    ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
                    ->field($field)
                    ->where('a.id=' . I('id'))
                    ->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

}
