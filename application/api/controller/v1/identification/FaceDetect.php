<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/5/8 0008
 * Time: 18:39
 */

namespace app\api\controller\v1\identification;

use app\api\controller\Api;
use think\Request;

class FaceDetect extends Api
{
    protected $noAuth = ['index','face'];

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function index()
    {
        $faceImg = isset($this -> postParams['faceImg']) ? $this -> postParams['faceImg'] : 0;
        $faceApi = new \app\common\api\BaseFaceApi();
        $result = $faceApi->faceRecognition($faceImg, '', '1');
        pre($result);
    }

    public function face()
    {
        ini_set('max_execution_time','0');
        $params = $this -> postParams;
        $img_url = isset($params['img_url']) ? $params['img_url'] : '';
        $store_id = isset($params['store_id']) ? $params['store_id'] : 0;
        if(empty($img_url)){
            $this->_returnMsg(['code' => 1, 'msg' => '图片地址缺失']);die;
        }
//        $url = 'http://bi.micyi.com/api/face/index';
        $url = 'http://bi.api.worthcloud.net/api/face/index';
//        file_put_contents('/../runtime/log/faceDetect.log',date('Ymd H:i:s').PHP_EOL,FILE_APPEND);
        trace('yyyyyyyy');
        $post_data = array(
//            'face_img' => 'https://ss1.bdstatic.com/70cFvXSh_Q1YnxGkpoWK1HF6hhy/it/u=3268053374,1675796445&fm=26&gp=0.jpg',
            'face_img' => $img_url . '?imageView2/1/w/500/h/600',
            'timestamp' => time(),
//            'mac_id' => '2000615150005453',
            'store_id' => !empty($store_id) ? $store_id : '88888888',
            'face_x' => rand(100,1800),
            'face_y' => rand(100,1800),
            'img_pixel' => '132*132',
        );


        $results = curl_post($url,$post_data);
        return json_encode($results);

    }




}