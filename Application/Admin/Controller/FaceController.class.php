<?php
namespace Admin\Controller;

/**
 * 头像审核
 * jerry
 * 2016.1.23
 */
class FaceController extends AdminController{
    /**
     * 查看头像待审核记录
     */
    public function index(){
        $data = array();//用于存放数据
        $_GET['p'] = $_GET['p'] ? $_GET['p'] : 0 ;
        $model = M('audit_face');

        $field = 'a.id,a.uid,a.face,a.apply_time,
                  m.mobile,m.nickname';
        $list = M()
            ->table('__AUDIT_FACE__ a')
            ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
            ->field($field)
            ->where('a.type=1')
            ->order('a.id asc')
            ->page($_GET['p'].',10')
            ->select();
        $this->assign('list', $list);
        $meta_title = '头像审核';
        $this->assign('meta_title',  $meta_title);

        $count = M()
            ->table('__AUDIT_FACE__ a')
            ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
            ->where('a.type=1')
            ->count('a.face');
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }

    /**
     * 头像审核。‘通过’只修改待审核记录表中的type=2和status=1；‘拒绝‘修改待审核记录表中的type=2，status=2，并且修改member表中的头像为’‘
     * @param $_GET['id']  待审核头像记录编号
     * @param $_GET['uid'] 待审核头像记录对应用户编号
     * @param $_GET['action']  操作：1通过，2拒绝
     */
    /**
     * 批量通过
     */
    public function auditFaceAgree(){

        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
        if(!empty($_REQUEST['id'])){//选择了记录

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
        }else{//没有选择记录
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
        $model = M('audit_face');
        $res = $model->where($where)->save($data);
        if ($res == true) {//修改审核头像记录成功，页面发生跳转
            $data = array(
                'status' => 1,
                'info' => '头像审核成功',
                'url' => U('Face/showAuditedFace')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '头像审核失败',
                'url' => U('Face/index')
            );
            $data = array(
                'status'=>2,
                'info'=>$model->_sql(),
                'url'=>U('Face/index')
            );
            $this->ajaxReturn($data);
        }
    }

    /*
   * 批量拒绝
   */
    public function auditFaceReject(){
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
        $where_b = array();
        $model = M('audit_face');

        if(!empty($_REQUEST['id'])){//选择了记录

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
        }else{//没有选择记录
            $data = array(
                'status' => 2,
                'info' => '没有选择记录，请选择记录'
            );
            $this->ajaxReturn($data);
        }

        $data = array(
            'type' => 2,
            'status' => 2,
            'audit_name' => $user_info['username'],
            'audit_time' => getCurrentTime(),
        );
        $res = $model->where($where)->save($data);
        if ($res !== false) {//修改待审核头像记录成功
            $data_b = array(
                'face' => '',
                'update_time' => time()
            );
            $res = M('member')->where($where_b)->save($data_b);
            if ($res) {
                $data = array(
                    'status' => 1,
                    'info' => '头像审核拒绝成功',
                    'url' => U('Face/showAuditedFace')
                );
                $this->ajaxReturn($data);
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '修改member表中头像为空字符串失败',
                    'url' => U('Face/index')
                );
                $this->ajaxReturn($data);
            }
        } else {
            $data = array(
                'status' => 2,
                'info' => '头像审核拒绝失败',
                'url' => U('Face/index')
            );
            $this->ajaxReturn($data);
        }
    }


    /**
     * 查看已审核头像记录
     */
    public function showAuditedFace(){
        //选定状态，这里是status字段
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where['a.status'] = I('get.status');
        }

        //输入申请时间的开始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $where['a.apply_time'][] = array('egt', I('get.start_time'));
        }

        //输入申请时间的截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $where['a.apply_time'][] = array('elt', I('get.apply_time'));
        }

        //输入审核人
        if ( isset($_GET['audit_name']) && !empty($_GET['audit_name'])) {
            $where['a.audit_name'] = array('like', '%'.I('get.audit_name').'%');
        }

        // 输入用户名或者用户ID
        if (isset($_GET['input']) && !empty($_GET['input'])) {
            $where['m.mobile|a.uid'] = array('like','%'.I('get.input').'%');
        }

        $where['a.type'] = 2;
        $data = array();//用于存放数据
//         $model = M('audit_face');
//         $field = 'id,uid,user_name,nickname,face,apply_time,audit_name,audit_time,status';
//         $list = $model
//         ->field($field)
//         ->where($where)
//         ->order('id desc')
//         ->page($_GET['p'].',10')
//         ->select();
        $field = 'a.id,a.face,a.apply_time,a.audit_name,a.audit_time,a.status,
                  m.uid,m.nickname,m.mobile';
        $list = M()
            ->table('__AUDIT_FACE__ a')
            ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
            ->field($field)
            ->where($where)
            ->order('a.id desc')
            ->page($_GET['p'].',10')
            ->select();
        $this->assign('list', $list);
        $meta_title = '头像审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
//         $count = $model->where('status=2')->count();//总记录数
        $count = M()
            ->table('__AUDIT_FACE__ a')
            ->join('LEFT JOIN __MEMBER__ m ON a.uid = m.uid')
            ->where($where)
            ->count();
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
}