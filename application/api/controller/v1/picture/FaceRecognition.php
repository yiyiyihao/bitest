<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/2/23 0023
 * Time: 11:32
 */
namespace app\api\controller\v1\picture;

use app\api\controller\Api;
use think\Request;
use app\common\api\BaiduAipFace;


class FaceRecognition extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


    //人脸关键点检测
    public function getKeyPoint()
    {
        $params = $this -> postParams;
        $img_url = isset($params['img_url']) ? trim($params['img_url']) : '';
        $options["face_field"] = 'landmark150';
        if(empty($img_url)){
            $this->_returnMsg(['code' => 1, 'msg' => '图片地址缺失']);die;
        }
        $obj = new BaiduAipFace();
        $result = $obj -> detect($img_url, 'URL', $options);
        if($result['error_code'] <> 0){
            $this->_returnMsg(['code' => 1, 'msg' => '检测失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => 'ok','data' => $result['result']]);die;

    }

    //人脸属性识别
    public function getAttributes ()
    {
        $params = $this -> postParams;
        $img_url = isset($params['img_url']) ? trim($params['img_url']) : '';
        $options["face_field"] = 'race,age,gender,emotion';
        if(empty($img_url)){
            $this->_returnMsg(['code' => 1, 'msg' => '图片地址缺失']);die;
        }
        $obj = new BaiduAipFace();
        $result = $obj -> detect($img_url, 'URL', $options);
        if($result['error_code'] <> 0){
            $this->_returnMsg(['code' => 1, 'msg' => '检测失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => 'ok','data' => $result['result']]);die;

    }

    //人脸收索






}