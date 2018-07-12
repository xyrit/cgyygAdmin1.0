<?php
namespace Admin\Controller;
  
/*
 * 
 */
/**
 * 评论审核
 * jerry
 * 2016.1.23
 */
class CommentController extends AdminController{
    /**
     * 查看待审核评论记录
     */
    public function index(){
        header('Content-type:text/html;charset=utf-8');
        $data = array();
        $field = 'c.id,c.content,c.uid,c.apply_time,m.mobile,m.nickname,d.lottery_id,d.goods_title';
        $_GET['p'] = $_GET['p'] ? $_GET['p'] : 0;
        $list = M()
        ->table('__COMMENT__ c')
        ->join('LEFT JOIN __MEMBER__ m ON c.uid = m.uid')
        ->join('LEFT JOIN __DISPLAY_PRODUCT__ d ON c.did = d.id')
        ->field($field)
        ->where('c.type=1')
        ->order('c.id asc')
        ->page($_GET['p'].',10')
        ->select();
        $this->assign('list', $list);
        $meta_title = '评论审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('comment')->where('type=1')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 待审核评论通过
     * 审核评论中批量和单个是同一个方法
     */
    public function auditCommentAgree(){
        header('Content-type:text/html;charset=utf-8');
        $user_info = session('user_auth');
//         if (IS_POST && !empty($_POST['id'])) {
//             $where['id'] = array('in', I('id'));
//         } elseif (!empty($_GET['id'])) {
//             $where['id'] = $_GET['id'];
//         } else {
//             $data = array(
//                 'status' => 2,
//                 'info' => '没有传入参数id',
//                 'url' => 'Comment/index'
//             );
//             $this->ajaxReturn($data);
//         }
        if(!empty($_REQUEST['id'])){//有记录
            if(count($_REQUEST['id']) > 1){//选择了多条记录
                $where['id'] = array('in', I('id'));
            }elseif(count($_REQUEST['id'])=== 1){//选择了一条记录
                $where['id'] = I('id');
            }
        }else{//没有记录
            $data = array(
                'status'=>2,
                'info'=>'没有选择记录，请选择记录',
            );
            $this->ajaxReturn($data  );
        }
        $data = array(
            'type' => 2,
            'status' => 1,
            'audit_name' => $user_info['username'],
            'audit_time' => getCurrentTime(),
        );
        $res = M('comment')->where($where)->save($data);
        if ($res !== false) {
            $data = array(
                'status' => 1,
                'info' => '审核评论成功',
                'url' => U('Comment/showAuditedComment')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '审核评论失败',
                'url' => U('Comment/index')
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 待审核评论拒绝
     */
    public function auditCommentReject(){
        $user_info = session('user_auth');
        if (IS_POST && !empty($_POST['id'])) {
            $where['id'] = array('in', I('id'));
        } elseif (!empty($_GET['id'])) {
            $where['id'] = I('id');
        } else {
            $data = array(
                'status' => 2,
                'info' => '没有传入参数id',
                'url' => 'Comment/index'
            );
            $this->ajaxReturn($data);
        }
        $data = array(
            'type' => 2,
            'status' => 2,
            'audit_name' => $user_info['username'],
            'audit_time' => getCurrentTime(),
        );
        $res = M('comment')->where($where)->save($data);
        if ($res !== false) {
            $data = array(
                'status' => 1,
                'info' => '审核评论成功',
                'url' => U('Comment/showAuditedComment')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '审核评论失败',
                'url' => U('Comment/index')
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 查看已审核评论记录
     */
    public function showAuditedComment(){
        //测试用
        header('Content-type:text/html;charset=utf-8');
    
        //选定状态
        if( isset($_GET['status']) && !empty($_GET['status'])) {
            $where['c.status']= I('get.status');
        }
    
        //选定申请起始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $where['c.apply_time'][] = array('egt',I('get.start_time'));
        }
    
        //选定截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $where['c.apply_time'][] =array('elt',I('get.end_time'));
        }
    
        //输入审核人用户名
        if (isset($_GET['audit_name']) && !empty($_GET['audit_name'])) {
            $where['c.audit_name'] = array('like','%'.I('get.audit_name').'%');
        }
    
        // 输入用户名或者用户ID
        if (isset($_GET['input']) && !empty($_GET['input'])) {
            $where['m.mobile|c.uid'] = array('like','%'.I('get.input').'%');
        }
    
        $where['c.type'] = 2;
        $data = array();
        $field = 'c.id,c.content,c.uid,c.apply_time,c.audit_name,c.audit_time,c.status,
                   m.mobile,m.nickname,
                   d.lottery_id,d.goods_title';
        $list = M()
        ->table('__COMMENT__ c')
        ->join('LEFT JOIN __MEMBER__ m ON c.uid = m.uid')
        ->join('LEFT JOIN __DISPLAY_PRODUCT__ d ON c.did = d.id')
        ->field($field)
        ->where($where)
        ->order('c.id desc')
        ->page($_GET['p'].',10')
        ->select();
        $this->assign('list', $list);
        $meta_title = '评论审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = M('comment')->where('type=2')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
}