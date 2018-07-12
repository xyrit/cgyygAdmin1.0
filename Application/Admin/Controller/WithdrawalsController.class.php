<?php
namespace Admin\Controller;

/**
 * 晒单审核
 * jerry
 * 2016.1.23
 */
class WithdrawalsController extends AdminController{
    /**
     * 晒单查看待审核记录。[debug]注意，一个中奖只能晒一次单，需要添加外检唯一约束。
     */
    public function index(){
        header('Content-type:text/html;charset=utf-8');
        $data = array('consumption_status'=>'0');
        $field = 'c.id as cid,c.addtime as ctime,c.*,m.uid,m.nickname,mb.* ';
        $list = M()
            ->table('__CONSUMPTIONS__ c')
            ->field($field)
            ->join('LEFT JOIN __MEMBER__ m ON c.uid = m.uid')
            ->join('LEFT JOIN __MEMBER_BANKS__ mb ON mb.uid = c.uid')
            ->where($data)
            ->order('c.addtime asc')
            ->page($_GET['p'].',10')
            ->select();

        //遍历晒单图片，用逗号分隔成一个数组
        $this->assign('list', $list);
        $meta_title = '提现审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('consumptions')->where($data)->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }

    /**
     * 提现通过
     */
    public function auditDisplayAgree(){
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
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
            'seriall_number'=>$_REQUEST['seriall_number'],
            'consumption_status' => 1,
            'audit' => $user_info['username'],
            'audittime' => time(),
        );

        $res = M('consumptions')->where($where)->save($data);
        if ($res) {
            $data = array(
                'status' => 1,
                'info' => '晒单全部通过成功',
                'url' => U('Withdrawals/showAuditedDisplay')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '晒单全部通过失败',
                'url' => U('Withdrawals/index')
            );
            $this->ajaxReturn($data);
        }
    }

    /**
     *晒单拒绝，只能做单个操作，不能批量操作。需求发生改变，不再由后台人员输入
     */
    public function auditDisplayReject(){
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
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
            'seriall_number'=>$_REQUEST['seriall_number'],
            'consumption_status' => 2,
            'audit' => $user_info['username'],
            'audittime' => time(),
        );

        $res = M('consumptions')->where($where)->save($data);
        if ($res) {
            $data = array(
                'status' => 1,
                'info' => '操作成功',
                'url' => U('Withdrawals/showAuditedDisplay')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '操作失败',
                'url' => U('Withdrawals/index')
            );
            $this->ajaxReturn($data);
        }
    }

    /**
     * 查看已审核晒单
     */
    public function showAuditedDisplay(){
        header('Content-type:text/html;charset=utf-8');
        $data['consumption_status'] = array('in','1,2');
        $field = 'c.consumption_money,c.id as cid,c.addtime as ctime,c.audit as audit_name,c.audittime as audittime,c.consumption_status,m.uid,m.nickname,mb.* ';
        $list = M()
            ->table('__CONSUMPTIONS__ c')
            ->field($field)
            ->join('LEFT JOIN __MEMBER__ m ON c.uid = m.uid')
            ->join('LEFT JOIN __MEMBER_BANKS__ mb ON mb.uid = c.uid')
            ->where($data)
            ->order('c.addtime desc')
            ->page($_GET['p'].',10')
            ->select();
//        echo '<pre>';
//    print_r($list);exit;
        //遍历晒单图片，用逗号分隔成一个数组
        $this->assign('list', $list);
        $meta_title = '提现审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('consumptions')->where($data)->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
}