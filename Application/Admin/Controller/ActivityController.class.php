<?php
namespace Admin\Controller;

/**
 * 后台活动微信
 * jerry
 * 2016.1.23
 */
class ActivityController extends AdminController{


            //活动列表
        public function activityList()
        {

            $this->assign('meta_title','活动列表');

            $list = M()
                ->table('os_activity a')
                ->order('a.add_time desc')
                ->page($_GET['p'].',10')
                ->select();

            $count = M()
                ->table('os_activity a')
                ->order('a.add_time desc')
                ->page($_GET['p'].',10')
                ->count();
            $this->assign('list',$list);
            $this->assign('count',$count);
            $page = new \Think\Page($count,10);
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $p =$page->show();
            $this->assign('page', $p? $p: '');
            $this->display();
        }


    //修改删除页面
    public function activityEdit()
    {
        $id=I('id');

        if($id)
        {
            $this->assign('meta_title','修改活动');
            $this->assign('id',$id);
        }
        else
        {
            $this->assign('meta_title','新增活动');

        }

        $this->display();

    }


            //添加,修改活动操作
    public function activityAdd()
    {
        $data=I('post.');
        if($data['id'])
        {
            //执行修改
            $rs= M('activity')->where('id='.$data['id'])->save($data);
        }
        else
        {
            //执行新增
            $aid= M('activity')->add($data);
            if($aid)
            {
                $qrcode= A('Watch');
               $url=$qrcode->get_qrcode_img($aid);

             $arr=array(
                 'add_time'=>time(),
                 'qrcode_img'=>$url,
             );
                $rs= M('activity')->where('id='.$aid)->save($arr);
            }
            else
            {
                $data = array(
                    'status' => 0,
                    'info' => '保存失败',
                );
                $this->ajaxReturn($data);
            }

        }

        if($rs)
        {
            $data = array(
                'status' => $rs,
                'info' => '保存成功',
                'url' => U('Activity/activityList')
            );
        }
        else
        {
            $data = array(
                'status' => $rs,
                'info' => '保存失败',
            );
        }
        $this->ajaxReturn($data);

    }

    public function activity_count(){

        $activity_id=$_GET['id'];
      //  $list=M('activity_statistics')select();
        $this->assign('meta_title','活动详情');
      //  $this->assign('list',$list);


        $list = M()
            ->table('os_activity_statistics a')
            ->where('a.activity_id='.$activity_id)
            ->order('a.time desc')
            ->page($_GET['p'].',10')
            ->select();

        $count = M()
            ->table('os_activity_statistics a')
            ->where('activity_id='.$activity_id)
            ->count();
        $this->assign('list',$list);
        $this->assign('count',$count);
        $page = new \Think\Page($count,10);
        $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p =$page->show();
        $this->assign('page', $p? $p: '');
        $this->display();

    }
}