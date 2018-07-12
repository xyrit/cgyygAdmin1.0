<?php
namespace Admin\Controller;

/**
 * 后台微信
 * sjz
 * 2016.1.23
 */
class WatchController extends AdminController
{


    //获取token
    public function get_token()
    {
        $wxconfig= M('watch_config')->find();

        //$appID = 'wxc10af3f90aced41f';
        //$appsecret = '16d11e688c1b734b13ac1ff320deca70';
        $appID =$wxconfig['appid'];
        $appsecret =$wxconfig['appsecret'];

     //   $access_token = $_COOKIE['access_token'];
       // if (!$access_token) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appID&secret=$appsecret";
            $rs = $this->https_post($url);
            $arrrs = json_decode($rs, 1);
            $access_token = $arrrs['access_token'];
            setcookie("access_token", $access_token, time() + 7200);
     //   }

        return $access_token;


    }


    //用token 换取 ticket

    public function get_ticket($id){
        //临时
        $qrcode = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$id.'}}}';
        //永久
        $y_qrcode='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "123"}}}';

        $token= $this->get_token();

        $url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token;
        $result= $this->https_post($url,$qrcode);

        $jsoninfo = (json_decode($result,true));
//        echo '<pre>';
//        print_r($jsoninfo['titck']);
        $rs= $jsoninfo['ticket'];

        return $rs;
    }

    //二维码获取
    public function get_qrcode_img($id){

        $titck=$this->get_ticket($id);

        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($titck);

        $imageInfo = $this->downloadImageFromWeiXin($url);

        $filename = BASE_PATH."cg-activty_img/qrocde-".$id.'.jpg';
        $filenameulr="/cg-activty_img/qrocde-".$id.'.jpg';
        $filenameulr1="cg-activty_img/qrocde-".$id.'.jpg';
        $local_file = fopen($filename,'w');

        if(false !==$local_file)
        {
            if(false !== fwrite($local_file,$imageInfo["body"]))
            {
                fclose($local_file);
                //将图片保存到服务器

                if($this->upImages($filename,$filenameulr1))
                {
                    return $filenameulr;
                }
                else
                {
                    return false;
                }


            }

        }
        return false;
    }




    /*
     * 图片上传至阿里云服务器
     */

    public function upImages($filePath,$object) {
        $id = '08iJabGVcaucodBT';   //阿里云Access Key ID
        $key = 'RkyzXVJI7TRPlM0e8SrgyTsS2RU4P7'; //阿里云Access Key Secret
        $host = 'http://pic.cgyyg.com';

        require_once BASE_PATH . 'Application/Addons/upload/php/aaa/autoload.php';

        $bucket = "cgchengguo";

        try {
            $ossClient = new \OSS\OssClient($id, $key, $host, true);

            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch (OssException $e) {
            print $e->getMessage();
            return false;
        }

        return true;
    }


    private function downloadImageFromWeiXin($url)
    {

        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_NOBODY,0);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        return array_merge(array('body'=>$package),array('header'=>$httpinfo));
    }

    function https_post($url,$data = null){

        $curl= curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
        if(!empty($data))
        {
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    function index()
    {

      $data =  M('watch_config')->where('id=1')->find();
       $this->assign('data',$data);
        $this->display();
    }


    //微信配置保存
    function watch_config_edit()
    {
        $data=I('post.');
      $rs= M('watch_config')->where('id=1')->save($data);

        $data = array(
            'status' => $rs,
            'info' => '保存成功',
            'url' => U('Watch/index')
        );
        $this->ajaxReturn($data);

    }



}