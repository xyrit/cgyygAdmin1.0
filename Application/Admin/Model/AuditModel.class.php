<?php
namespace Admin\Model;
use Think\Model;

/**
 * 审核模型
 * @author jerry
 * 2016.1.14
 */
class AuditModel extends Model{
    /**
     * 查看待审核头像记录
     */
    public function showAuditFace(){
        $data = array();//用于存放数据
        $field = 'id,uid,user_name,nickname,face,apply_time';
        $model = M('audit_face');
        $list = $model
                ->field($field)
                ->where('status=1')
                ->order('id desc')
                ->select();
        $data['list'] = $list;//每页显示的记录
        $count = $model->where('status=1')->select();
        $data['count'] = $count;//总记录数
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $data['show'] = $show;
        return $data;
    }
    
    /**
     * 头像审核通过。改变状态为已审核，修改member中的头像
     * @param id 头像审核记录编号
     */
    private function auditFaceAgree($id, $uid){
        if (is_array($id)) {//批量删除
            $where_a['id'] = array('in', $id);
        } else {
            $where_a['id'] = $id;
        }
        $data = array(
            'status' => 2,
            'result' => 1,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        $model = M('audit_face');
        $res = $model->where($where_a)->save($data);
        header("Content-type:text/html;charset=utf-8");
        if($res){
            echo '修改头像审核为已通过';
            return true;
        } else {
            echo '修改头像审核状态为已通过失败';
            return false;
        }
    }
    
    /**
     * 头像审核拒绝。改变状态为已审核，修改member中的头像为空，由前台给默认头像
     */
    private function auditFaceRefuse($id, $uid){
        if (is_array($id)) {
            $where_a['id'] = array('in', $id);
        } else {
            $where_a['id'] = $id;
        }
        
        if (is_array($uid)) {
            $where_b['id'] = array('in', $uid);
        } else {
            $where_b['id'] = $uid;
        }
        $data = array(
            'status' => 2,
            'result' => 2,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        $model = M('audit_face');
        $res = $model->where($where_a)->save($data);
        header("Content-type:text/html;charset=utf-8");
        if ($res) {
            echo '修改头像审核为拒绝';
            $res = M('member')->where($where_b)->setField('face','');
            if ($res) {
                echo '修改member表中的头像为空字符串成功';
                return true;
            } else {
                echo '修改member表中的头像为空字符串失败';
                return false;
            }
        } else {
            echo '修改头像审核为拒绝失败';
            return false;
        }
    }
    
    /**
     * 查看已审核头像记录
     */
    public function showAuditedFace(){
        //选定状态，这里是result字段
        if (isset(I('get.result')) && !empty(I('get.result'))) {
            $where['result'] = I('get.result');
        }
        
        //输入申请时间的开始时间
        if (isset(I('get.start_time')) && !empty(I('get.start_time'))) {
            $where['apply_time'] = array('egt', I('get.start_time'));
        }
        
        //输入申请时间的截止时间
        if (isset(I('get.end_time')) && !empty(I('get.end_time'))) {
            $where['apply_time'] = array('elt', I('get.apply_time'));
        }
        
        //输入审核人
        if ( isset(I('get.audit_name')) && !empty(I('get.audit_name'))) {
            $where['audit_name'] = array('like', '%'.I('get.audit_name').'%');
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
        $data = array();//用于存放数据
        $model = M('audit_face');
        $field = 'id,uid,user_name,nickname,face,apply_time,audit_name,audit_time,result';
        $list = $model
                ->field($field)
                ->where('status=2')
                ->order('d.id desc')
                ->page($_GET['p'].',10')
                ->select();
        $data['list'] = $list;
        $count=$model->where('status=2')->count();
        $data['count'] = $count;
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $data['show'] = $show;
        return $data;
    }
    
    /**
     * 晒单查看待审核记录
     */
    public function showDisplayProduct(){
       $data = array();
       $field = 'd.id,d.uid,d.description,d.pics,d.apply_time,d.title,
                m.user_name,m.nickname,
                w.lottery_id,w.title,';
       $list = M()
                ->table('display_product d')
                ->field($field)
                ->join('__MEMBER__ m ON d.uid = m.id')
                ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
                ->where('status=1')
                ->order('d.id desc')
                ->page($_GET['p'].',10')
                ->select();
       //遍历晒单图片，用逗号分隔成一个数组
       $list_length = count($list);
       if ($list_length > 0) {
           for($i=0; $i<$list_length; $i++){
               if (strpos($list[$list_length]['pics'], ',')===false) {//没有出现逗号，即只有一张图片
                   $res[$list_length]['pics'] = array($list[$list_length]['pics']);
               } else {//出现了逗号，即有多张图片
                   $res[$list_length]['pics'] = explode(',', $res[$list_length]['pics']);
               }
           }
       }
       $data['list'] = $list;
       $count = M('display_product')->where('status=1')->count();
       $page = new \Think\Page($count,10);
       $show = $page->show();
       $data['show'] = $show;
       return $data;
    }
    
    /**++++++++++++++++++++++++++++++++++++++++++++++++++++
     * 待审核晒单的操作
     * @param $id 待审核晒单的编号
     * @param $action 操作（1通过，2拒绝）
     * @param $note 拒绝原因，可选
     * @param bool
     * [debug]审核未通过时，前台用户可重新提交晒单申请没有做
     */
    private function audtiDisplayProduct($id, $action, $note=''){
        if (is_array($id)) {//批量操作
            $where['id'] = array('in', $id);
        } else {
            $where['id'] = $id;
        }
        $data = array(
            'status' => 2,
            'result' => $action,
            'note' => $note,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        $res = M('display_product')->where($where)->save($data);
        //测试用
        header("Content-type:text/html;charset=utf-8");
        if ($res) {//操作成功
            echo '操作待审核晒单成功';
            return true;
        } else {
            echo '操作待审核晒单失败';
            return false;
        }
    }
    
    /**
     * 查看已审核晒单
     */
    private function showAuditedDisplay(){
        //测试用
        header('Conent-type:text/html;charset=utf-8');
        
        //选定状态，在表中是result字段
        if( isset(I('get.result')) && !empty(I('get.result'))) {
            $where['result']= I('get.result');    
        }
        
        //选定申请起始时间
        if (isset(I('get.start_time')) && !empty(I('get.start_time'))) {
            $where['apply_time'] = array('egt',I('get.start_time'));
        }
        
        //选定截止时间
        if (isset(I('get.end_time')) && !empty(I('get.end_time'))) {
            $where['apply_time'] =array('elt',I('get.end_time'));
        }
        
        //输入审核人用户名
        if (isset(I('get.audit_name')) && !empty(I('get.audit_name'))) {
            $where['audit_name'] = array('like','%'.I('get.audit_name').'%');
        }
        
        //输入用户名或者ID
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
        
        $where['status'] = 2;
        $data = array();//用于存放数据
        $field = 'd.id,d.uid,d.description,d.pics,d.apply_time,d.title,d.status,d.audit_name,d.audit_time,
                m.user_name,m.nickname,
                w.lottery_id,w.title goods_title,';
        $list = M()
        ->table('display_product d')
        ->field($field)
        ->join('__MEMBER__ m ON d.uid = m.id')
        ->join('__WIN_PRIZE__ w ON d.win_id = w.id')
        ->where($where)
        ->order('d.id desc')
        ->page($_GET['p'].',10')
        ->select();
        //遍历晒单图片，用逗号分隔成一个数组
        $list_length = count($list);
        if ($list_length > 0) {
            for($i=0; $i<$list_length; $i++){
                if (strpos($list[$list_length]['pics'], ',') === false) {//没有出现逗号，即只有一张图片
                    $list[$list_length]['pics'] = array($list[$list_length]['pics']);
                } else {//出现了逗号，即有多张图片
                    $list[$list_length]['pics'] = explode(',', $list[$list_length]['pics']);
                }
            }
        }
        $data['list'] = $list;
        $count = M('display_product')->where('status=2')->count();
        $data['count'] = $count;
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $data['show'] = $show;
        return $data;
    }
    
    /**
     * 查看待审核评论记录
     */
    private function showAuditingComment(){
        $data = array();
        $field = 'c.id,c.content,c.uid,c.apply_time,m.user_name,m.nickname,d.lottery_id,d.title';
        $list = M()
                ->table('comment c')
                ->join('__MEMBER__ m ON c.uid = m.id')
                ->join('__DISPLAY_PRODUCT__ d ON c.did = d.id')
                ->field($field)
                ->where('status=1')
                ->order('c.id desc')
                ->page($_GET['p'].',10')
                ->select();
        $data['list'] = $list;
        $count = M('comment')->where('status=1')->count();
        $data['count'] = $count;
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $data['show'] = $show;
        return $data;
    }
    
    /**
     * 对待审核评论进行操作。修改status和result字段
     * @param $id 待审核评论记录编号
     * @param $action 1通过，2拒绝
     * @return bool
     */
    private function auditComment($id, $action){
        header('Content-type:text/html;charset=utf-8');
        if (is_array($id)) {//批量操作
            $where['id'] = array('in', $id);
        } else {
            $where['id'] =$id;
        }
        $data = array(
            'status' => 2,
            'result' => $action,
            'audit_name' => session('name'),
            'audit_time' => getCurrentTime(),
        );
        if (M('comment')->where($where)->save($data)) {
            echo '审核评论成功';
            return true;
        } else {
            echo '审核评论失败';
            return false;
        }
    }
    
    /**
     * 查看已审核评论记录
     */
    private function showAuditedComment(){
        //测试用
        header('Conent-type:text/html;charset=utf-8');
        
        //选定状态
        if( isset(I('get.result')) && !empty(I('get.result'))) {
            $where['result']= I('get.result');
        }
        
        //选定申请起始时间
        if (isset(I('get.start_time')) && !empty(I('get.start_time'))) {
            $where['apply_time'] = array('egt',I('get.start_time'));
        }
        
        //选定截止时间
        if (isset(I('get.end_time')) && !empty(I('get.end_time'))) {
            $where['apply_time'] =array('elt',I('get.end_time'));
        }
        
        //输入审核人用户名
        if (isset(I('get.audit_name')) && !empty(I('get.audit_name'))) {
            $where['audit_name'] = array('like','%'.I('get.audit_name').'%');
        }
        
        //输入用户名或者ID
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
        
        $where['status'] = 2;
        $data = array();
        $field = 'c.id,c.uid,c.content,c.apply_time,c.result,c.audit_name,c.audit_time,
                m.user_name,m.nickname,
                w.lottery_id,w.title,';
        $list = M()
        ->table('comment c')
        ->field($field)
        ->join('__MEMBER__ m ON c.uid = m.id')
        ->join('__WIN_PRIZE__ w ON c.win_id = w.id')
        ->where($where)
        ->order('id desc')
        ->page($_GET['p'].',10')
        ->select();
        $data['list'] = $list;
        $count = M('comment')->where('status=2')->count();
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $data['show'] = $show;
        return $data;
    }
}