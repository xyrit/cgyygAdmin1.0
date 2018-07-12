<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 审核控制器
 */
class AuditController extends Controller{
    /**
     * 查看头像待审核记录
     */
    public function showAuditFace(){
        $data = array();//用于存放数据
        $field = 'id,uid,user_name,nickname,face,apply_time';
        $_GET['p'] = $_GET['p'] ? $_GET['p'] : 0 ;
        $model = M('audit_face');
        $list = $model
        ->field($field)
        ->where('type=1')
        ->order('id desc')
        ->page($_GET['p']. ',10')
        ->select();
        $this->assign('list', $list);
        $meta_title = '待审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = $model->where('type=1')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 头像审核。‘通过’只修改待审核记录表中的status=2和result=1；‘拒绝‘修改待审核记录表中的status=2，result=2，并且修改member表中的头像为’‘
     * @param $_GET['id']  待审核头像记录编号
     * @param $_GET['uid'] 待审核头像记录对应用户编号
     * @param $_GET['action']  操作：1通过，2拒绝
     */
    /**
     * 批量通过
     */
    public function auditFaceAgree(){
        header('Content-type:text/html;charset=utf-8');
        if (!empty($_POST['id'])) {
            $where['id'] = array('in', I('id'));
           
        } else {//单个通过头像审核
            $where['id'] = I('id');
        }
        $data = array(
            'type' => 2,
            'status' => 1,
            'audit_name' => session('name'),
            'audit_name' => getCurrentTime(),
        );
        $model = M('audit_face');
        $res = $model->where($where)->save($data);
        if ($res !== false) {//修改审核头像记录成功，页面发生跳转
            $data = array(
                'status' => 1,
                'info' => '头像审核全部通过',
                'url' => U('Audit/showAuditedFace')
            );
            $this->ajaxReturn($data);
        } else {
           $data = array(
                'status' => 2,
                'info' => '头像审核全部通过失败',
                'url' => U('Audit/showAuditingFace')
            );
           $this->ajaxReturn($data);
        }
    }
    
    /*
     * 批量拒绝
     */
    public function aduitFaceReject(){
        header('Content-type:text/html;charset=utf-8');
        $where_b = array();
        $model = M('audit_face');
        if (IS_POST) {
            if (!empty($_POST['id'])){
                $where['id'] = array('in', $_POST['id']);
                //修改member表中的头像
                $uid = $model->where($where)->getField('uid',true);
                $where_b['uid'] = array('in', $uid);
            }
        } else {
            $where['id'] = I('id');
            $where_b['uid'] = I('uid');
        }
        $data = array(
            'type' => 2,
            'status' => 2,
            'audit_name' => session('name'),
            'audit_name' => getCurrentTime(),
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
                    'info' => '全部拒绝成功',
                    'url' => U('Audit/showAuditedFace')
                );
                $this->ajaxReturn($data);
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '拒绝失败',
                    'url' => U('Audit/showAuditFace')
                );
                $this->ajaxReturn($data);
            }
        } else {
            $data = array(
                'status' => 2,
                'info' => '修改待审核头像记录失败',
                'url' => U('Audit/showAuditFace')
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
            $where['status'] = I('get.status');
        }
        
        //输入申请时间的开始时间
        if (isset($_GET['start_time']) && !empty($_GET['start_time'])) {
            $where['apply_time'][] = array('egt', I('get.start_time'));
        }
    
        //输入申请时间的截止时间
        if (isset($_GET['end_time']) && !empty($_GET['end_time'])) {
            $where['apply_time'][] = array('elt', I('get.apply_time'));
        }
    
        //输入审核人
        if ( isset($_GET['audit_name']) && !empty($_GET['audit_name'])) {
            $where['audit_name'] = array('like', '%'.I('get.audit_name').'%');
        }
    
        // 输入用户名或者用户ID
        if (isset($_GET['input']) && !empty($_GET['input'])) {
            $where['user_name|uid'] = array('like','%'.I('get.input').'%');
        }
        
        $where['type'] = 2;
        $data = array();//用于存放数据
        $model = M('audit_face');
        $field = 'id,uid,user_name,nickname,face,apply_time,audit_name,audit_time,status';
        $list = $model
        ->field($field)
        ->where($where)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->select();
        $this->assign('list', $list);
        $meta_title = '头像审核';
        $this->assign('meta_title',  $meta_title);
        // 对收货地址用空格，进行拼接，因为在delivered表中地址的字段已经发生变化
        $count = $model->where('status=2')->count();//总记录数
        $this->assign('count',$count);
        $page = new \Think\Page($count, 10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();
    }
    
    /**
     * 晒单查看待审核记录。[debug]注意，一个中奖只能晒一次单，需要添加外检唯一约束。
     */
    public function showDisplayProduct(){
        header('Content-type:text/html;charset=utf-8');
        $data = array();
        $field = 'd.id,d.uid,d.user_name,d.description,d.pics,d.apply_time,d.title,
                m.nickname,
                w.lottery_id,w.title goods_title';
        $list = M()
        ->table('__DISPLAY_PRODUCT__ d')
        ->field($field)
        ->join('__MEMBER__ m ON d.uid = m.uid')
        ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
        ->where('d.type=1')
        ->order('d.id desc')
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
        if (IS_POST) {
            if (!empty($_POST['id'])) {
                $where['id'] = array('in', I('id'));
            }
        } elseif (!empty($_GET['id'])) {
            $where['id'] = I('id');
        }
        $data = array(
            'type' => 2,
            'status' => 1,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        $res = M('display_product')->where($where)->save($data);
        if ($res) {
            $data = array(
                'status' => 1,
                'info' => '晒单全部通过',
                'url' => U('Audit/showAuditedDisplay')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '晒单全部通过失败',
                'url' => U('Audt/showDisplayProduct')
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     *晒单拒绝，只能做单个操作，不能批量操作。需求发生改变，不再由后台人员输入 
     */
    public function auditDisplayReject(){
        if (!empty($_GET['id'])) {
            $where['id'] = I('id');
            $data = array(
                'type' => 2,
                'status' => 2,
                'audit_name' => session('name'),
                'audit_time' => getCurrentTime(),
            );
            $res = M('display_product')->where($where)->save($data);
            if ($res !== false ) {
                $data = array(
                    'status' => 1,
                    'info' => '拒绝晒单成功',
                    'url' => U('Audit/showAuditedDisplay')
                );
                $this->ajaxReturn($data);
            } else {
                $data = array(
                    'status' => 2,
                    'info' => '拒绝晒单失败',
                    'url' => U('Audit/showDisplayProduct')
                );
                $this->ajaxReturn($data);
            }
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
            $where['m.user_name|d.uid'] = array('like','%'.I('get.input').'%');
        }
        
        //对$_GET['p']参数进行判断，防止在使用page方法时出现错误。
        $_GET['p'] = $_GET['p']? $_GET['p'] : 0;
        
        $where['d.type'] = 2;
        $data = array();//用于存放数据
        $field = 'd.id,d.uid,d.description,d.pics,d.apply_time,d.title,d.type,d.audit_name,d.audit_time,d.status,
                m.nickname,m.user_name,
                w.lottery_id,w.title goods_title';
        $list = M()
        ->table('__DISPLAY_PRODUCT__ d')
        ->field($field)
        ->join('__MEMBER__ m ON d.uid = m.uid')
        ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
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
    
    /**
     * 查看待审核评论记录
     */
    public function showAuditingComment(){
        header('Content-type:text/html;charset=utf-8');
        $data = array();
        $field = 'c.id,c.content,c.uid,c.apply_time,m.user_name,m.nickname,d.lottery_id,d.goods_title';
        $_GET['p'] = $_GET['p'] ? $_GET['p'] : 0;
        $list = M()
        ->table('__COMMENT__ c')
        ->join('__MEMBER__ m ON c.uid = m.uid')
        ->join('__DISPLAY_PRODUCT__ d ON c.did = d.id')
        ->field($field)
        ->where('c.type=1')
        ->order('c.id desc')
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
     */
    public function auditCommentAgree(){
        header('Content-type:text/html;charset=utf-8');
        if (IS_POST && !empty($_POST['id'])) {
            $where['id'] = array('in', I('id'));
        } elseif (!empty($_GET['id'])) {
            $where['id'] = $_GET['id'];
        } else {
            die('没有传入参数id');            
        }
        $data = array(
            'type' => 2,
            'status' => 1,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        $res = M('comment')->where($where)->save($data);
        if ($res !== false) {
            $data = array(
                'status' => 1,
                'info' => '审核评论成功',
                'url' => U('Audit/showAuditedComment')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '审核评论失败',
                'url' => U('Audit/showAuditingComment')
            );
            $this->ajaxReturn($data);
        }
    }
    
    /**
     * 待审核评论拒绝
     */
    public function auditCommentReject(){
        if (IS_POST && !empty($_POST['id'])) {
            $where['id'] = array('in', I('id'));
        } elseif (!empty($_GET['id'])) {
            $where['id'] = I('id');
        } else {
            die('没有传入id');
        }
        $data = array(
            'type' => 2,
            'status' => 2,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        $res = M('comment')->where($where)->save($data);
        if ($res !== false) {
            $data = array(
                'status' => 1,
                'info' => '审核评论成功',
                'url' => U('Audit/showAuditedComment')
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'status' => 2,
                'info' => '审核评论失败',
                'url' => U('Audit/showAuditingComment')
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
            $where['m.user_name|c.uid'] = array('like','%'.I('get.input').'%');
        }
        
        $where['c.type'] = 2;
        $data = array();
         $field = 'c.id,c.content,c.uid,c.apply_time,c.audit_name,c.audit_time,c.status,
                   m.user_name,m.nickname,
                   d.lottery_id,d.goods_title';
        $list = M()
        ->table('__COMMENT__ c')
        ->join('__MEMBER__ m ON c.uid = m.uid')
        ->join('__DISPLAY_PRODUCT__ d ON c.did = d.id')
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