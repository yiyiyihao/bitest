<?php
namespace app\api\controller;
use app\common\api\TencentFaceApi;
use app\common\api\FaceApi;
use think\Facade\Request;
class Test
{
    public function test(){
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/api/face/index';
        $post_data = array(
             'face_img' => 'http://face.worthcloud.net/2018-09-25-19-44-12_00_00_00_132_132.jpeg',
             //                 'face_img' => 'http://face.worthcloud.net/2018-09-25-17-31-54_00_23_15_132_132.jpeg',
             'timestamp' => time(),
             'mac_id' => '2000594740005863',
             'face_x' => 0,
             'face_y' => 0,
             'img_pixel' => 'TEST',
         );
        $results = curl_post($url, $post_data);
        pre($results);
    }
    function device()
    {
        $deviceApi = new \app\common\api\DeviceApi();
        $result = $deviceApi->getDeviceInfo('1201391150001508');
        pre($result);
    }
    public function app()
    {
        $request = Request::instance()->param();
        header("Content-type: text/html; charset=utf-8");
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/index';
//        $url = 'http://bi.api.worthcloud.net/api/app/index';
        $params['method'] = $request['method'];
        $params['timestamp'] = time();
        $params['signkey'] = 'ds7p7auqyjj8';

        if ($request) {
            $params = array_merge($params, $request);
            unset($params['/api/test/app']);
        }
//        $params['timestamp'] = time();
//         $params ['sign'] = $this->generateSign($params, $params['signkey']);
//        echo '<pre>';
//        print_r($params);
        $params['sign'] = $this->getSign($params, $params['signkey']);
        if ($params['method'] == 'faceDetect') {
            $filename = $_SERVER['DOCUMENT_ROOT'].'/123.jpeg';
//             $filename = $_SERVER['DOCUMENT_ROOT'].'\test\1539586469(1).jpg';
            if (file_exists($filename)) {
                echo 'exist';
            }else{
                echo 'no';
            }
//             $path = 'test\1201468330001509_201712191722.jpg';
//             $data = realpath($path);
//             pre($data);
            $params['face_img'] = new \CURLFile($filename);
        }else{
            $params = json_encode($params);
//            pre($params, 1);
//            echo "<hr>";
        }
        $result = $this->curl_post($url, $params);
        pre(json_encode($result));
    }
    protected function getSign($params, $signkey)
    {
        //除去待签名参数数组中的空值和签名参数(去掉空值与签名参数后的新签名参数组)
        $para = array();
        while (list ($key, $val) = each ($params)) {
            if($key == 'sign' || $key == 'signkey' || $val === "")continue;
            else	$para [$key] = $params[$key];
        }
        //对待签名参数数组排序
        ksort($para);
        reset($para);
        
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr  = "";
        
        while (list ($key, $val) = each ($para)) {
            $prestr.= $key."=".$val."&";
        }
        //去掉最后一个&字符
        $prestr = substr($prestr,0,count($prestr)-2);
        
        //字符串末端补充signkey签名密钥
        $prestr = $prestr . $signkey;
        //生成MD5为最终的数据签名
        $mySgin = md5($prestr);
        return $mySgin;
    }
    public function facefull()
    {
        $database = $this->_getDatabase();
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/api/FaceFull';
        $imgs = $database->group('img_url')->limit(10, 5)->select();
        foreach ($imgs as $key => $value) {
            $post_data = array(
                'img_url' => $value['img_url'],
                'timestamp' => $value['add_time'],
//                'mac_id' => $value['device_code'],
                'mac_id' => '2000594740005863',
            );
            $results = curl_post($url, $post_data);
            pre($results, 1);
        }
    }
    public function face() 
    {
        ini_set('max_execution_time','0');
        $params = Request::instance()->param();
        $database = $this->_getDatabase();
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/api/face';
//        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/add';

//        $i = isset($params['p']) && intval($params['p']) ? intval($params['p']) : 0;
//        $length = isset($params['length']) && intval($params['length']) ? intval($params['length']) : 1;
//        $id = isset($params['start_id']) && intval($params['start_id']) ? intval($params['start_id']) : 3485;
//        $startTime = isset($params['start_time']) && trim($params['start_time']) ? trim($params['start_time']) : '';
//        $endTime = isset($params['end_time']) && trim($params['end_time']) ? trim($params['end_time']) : '';
//        $where = [];
//        if ($startTime || $endTime) {
//            if ($startTime) {
//                $where['create_time'] = ['>=', $startTime];
//            }
//            if ($endTime) {
//                $where['create_time'] = ['<', $endTime];
//            }
//        }else{
//            $where['id'] = ['>=', $id];
//        }
//        $nextp = $i+1;
//        $offset = $i * $length;
//        $imgs = $database->group('img_url')->where($where)->limit($offset, $length)->order('id ASC')->select();
//        if(count($imgs) < $length){#TODO 计算是否有下一页 无下一页 填写0
//            $nextp = 0;
//        }
//        $return = [
//            'code'  => 0,
//            'msg'   => 'success',
//            'html'  => [],
//            'nextp' => $nextp
//        ];
//         $imgs = [1];
        $imgs = $database -> field('img_url') -> limit(4000,1) -> select();
        foreach ($imgs as $key => $value) {
//            $post_data = array(
//                'face_img' => $value['img_url'],
//                'timestamp' => strtotime($value['create_time']),
//                'mac_id' => $value['mac_id'],
//                'face_x' => 0,
//                'face_y' => 0,
//            );
            $post_data = array(
//                'face_img' => $value['img_url'],
                 'face_img' => 'https://ss0.bdstatic.com/70cFuHSh_Q1YnxGkpoWK1HF6hhy/it/u=1585703471,1321779509&fm=26&gp=0.jpg',
//                'timestamp' => strtotime('2018-09-25 10:10:25'),
                'timestamp' => time(),
//                'mac_id' => '2000158820023599',
                'mac_id' => '2000554040005965',
//                'mac_id' => '2000554040005938',
                'face_x' => rand(100,1800),
                'face_y' => rand(100,1800),
                'img_pixel' => '132*132',
            );
            $daaa = [   "access_token"=>"24.6d11d1efe73d42c16afcb190e720ee6b.2592000.1549510317.282335-15369697",
                        "user_id"=>rand(1,1800),
                        "group_id"=>"123456",
                        "image"=>$value['img_url'],
                        "image_type"=>"URL"
                    ];

            $results = curl_post($url, $post_data);pre($results);
//            $results = curl_post_https($url, $post_data); static $i = 1; echo $i++;
//            $return['html'][] = $results;
//            if (!$params) {
//                pre($results, 1);
//            }
        }
//        if ($params) {
//            header('Content-Type:application/json; charset=utf-8');
//            ;die;
//        }
    }
    public function clear()
    {
        echo 'Face++:<br>';
        $faceSetModel = db('faceset');
        $timestamp = time();
//         $faceApi = new \app\common\api\FaceApi();
//         $listResult = $faceApi->faceSetList();
//         if ($listResult && isset($listResult['error_message'])) {
//             echo $listResult['error_message'];
//             die();
//         }
//         $facesets = $listResult['facesets'];
//         if(!$facesets){
//             echo '无faceSet数据';
//         }else{
//             foreach ($facesets as $key => $value) {
//                 $removeResult = $faceApi->faceSetRemoveFace($value['faceset_token'], 0, false, true);
//                 //获取详情
//                 $detailResult = $faceApi->faceSetDetail($value['faceset_token']);
//                 if ($detailResult && isset($detailResult['error_message'])) {
//                     echo '获取faceSet:'.$value['faceset_token'].'失败//'.$detailResult['error_message'];
//                     die();
//                 }
//                 $deleteResult = $faceApi->faceSetDelete($value['faceset_token']);
//                 if ($deleteResult && isset($deleteResult['error_message'])) {
//                     echo '删除faceSet:'.$value['faceset_token'].'失败//'.$deleteResult['error_message'];
//                     die();
//                 }
//             }
//             pre($facesets, 1);
//         }
        
        echo '<hr>腾讯云:<br>';
        
        $tencentApi = new TencentFaceApi();
        $groupResult = $tencentApi->getFaceGroupList();
        if (isset($groupResult['data']['group_ids']) && $groupResult['data']['group_ids']) {
            foreach ($groupResult['data']['group_ids'] as $key => $value) {
                $personResult = $tencentApi->getFacePersonList($value);
                if ($personResult['data']['person_ids']) {
                    foreach ($personResult['data']['person_ids'] as $k => $v) {
                        echo 'person_id:'.$v.'<br>';
                        $tokenList = $tencentApi->getFaceTokenList($v);
                        pre($tokenList, 1);
//                         $result = $tencentApi->facePersonDelete($v);
//                         pre($result, 1);
                    }
                }
                pre($personResult, 1);
            }
            pre($groupResult, 1);
        }else{
            echo '无分组数据';
        }
    }
    private function _getDatabase()
    {
        $config = array(
            // 数据库类型
            'type'            => 'mysql',
            // 服务器地址
            'hostname'        => '127.0.0.1',
            // 数据库名
            'database'        => 'bi',
            // 用户名
            'username'        => 'root',
            // 密码
            'password'        => '101142',
            // 端口
            'hostport'        => '3306',
            // 连接dsn
            'dsn'             => '',
            // 数据库连接参数
            // 数据库编码默认采用utf8
            'charset'         => 'utf8',
            // 数据集返回类型
            'resultset_type'  => 'array',
        );
        return $database = db('cloud_face_token', $config);
    }
    /**
     * curl函数
     * @url :请求的url
     * @post_data : 请求数组
     **/
    function curl_post($url, $post_data){
        if (empty($url)){
            return false;
        }
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        $error = '';
        if($data === false){
            $error = curl_error($curl);
            echo 'Curl error: ' . $error;
        }
        //关闭URL请求
        curl_close($curl);
        $json = json_decode($data,true);
        if (empty($json)){
            return $data;
        }
        //显示获得的数据
        return $json;
    }
}