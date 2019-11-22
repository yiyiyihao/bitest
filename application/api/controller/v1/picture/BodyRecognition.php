<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/2/23 0023
 * Time: 11:32
 */
namespace app\api\controller\v1\picture;

use app\api\controller\Api;
use think\Exception;
use think\Request;
use app\common\api\AipBodyAnalysis;

//后台数据接口页
class BodyRecognition extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //人体关键点
    public function getKeyPoint()
    {
        $params = $this -> postParams;
        $img = isset($params['img']) ? trim($params['img']) : '';
        if(empty($img)){
            $this->_returnMsg(['code' => 1, 'msg' => '图片缺失']);die;
        }
        try{$image = file_get_contents($img);}catch(Exception $e){$this->_returnMsg(['code' => 1, 'msg' => '图片地址解析不了']);die;}

        $obj = new AipBodyAnalysis();
        $result = $obj -> bodyAnalysis($image);
        if(isset($result['code'])){
            $this->_returnMsg(['code' => 1, 'msg' => '检测失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => 'ok','data' => $result]);die;

    }

    //人体属性识别
    public function getAttributes ()
    {
        $params = $this -> postParams;
        $img = isset($params['img']) ? trim($params['img']) : '';
        if(empty($img)){
            $this->_returnMsg(['code' => 1, 'msg' => '图片缺失']);die;
        }
        try{$image = file_get_contents($img);}catch(Exception $e){$this->_returnMsg(['code' => 1, 'msg' => '图片地址解析不了']);die;}
        $options["type"] = 'race,age,gender';
        $obj = new AipBodyAnalysis();
        $result = $obj -> bodyAttr('', $options);
        if(isset($result['error_code'])){
            $this->_returnMsg(['code' => 1, 'msg' => '识别失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => 'ok','data' => $result]);die;
    }

    //人体收索

















}