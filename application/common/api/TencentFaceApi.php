<?php
namespace app\common\api;

use think\Facade\Request;

/**
 * 腾讯人脸识别接口
 * @author xiaojun
 */
class TencentFaceApi extends BaseFaceApi
{
    var $config;    //Api接口配置
    var $error;
    public function __construct(){
        parent::__construct();
        $server = Request::server();
        if ($server['HTTP_HOST'] == 'bi.api.worthcloud.net') {
//            $this->config = [
//                'appid' => '1253964067',                                //接入项目的唯一标识
//                'secretid' => 'AKIDc2D9pHqUEj02pNIkdKmzyiDTVIqAO8lA',   //SECRET_ID
//                'secretkey' => 'JOGsFQM4XmmX4LMCpEOOAh04tUt65pLd',      //SECRET_KEY
//                'bucket' => 'tencentyun',
//            ];
        }else{
//            $this->config = [
//                'appid' => '1253543182',                                //接入项目的唯一标识-local
//                'secretid' => 'AKID4EE82o2q8NTOBopoIesvmW6bwALb2zym',   //SECRET_ID
//                'secretkey' => 'Op7GVecVCm7PZhgZKvQq9IOQK4azIUsI',      //SECRET_KEY
//                'bucket' => 'tencentyun',
//            ];
        }
    }
    /**
     * 人脸检测：检测给定图片中的所有人脸( Face )的位置和相应的面部属性，位置包括(x, y, w, h)，面部属性包括性别( gender ), 年龄( age ), 表情( expression ), 魅力( beauty ), 眼镜( glass )和姿态 (pitch，roll，yaw )。
     * @param string $imageUrl
     * @param int $mode  检测模式：0-所有人脸，1-最大的人脸
     * @param string $returnAttributes
     * @return array
     */
    public function detectApi($imageUrl = '', $mode = 0)
    {
        if(!$imageUrl){
            $this->error = 'url:图片地址不能为空';
            return FALSE;
        }
        if ($mode !== 0 && $mode !== 1) {
            $this->error = 'mode:错误';
            return FALSE;
        }
        $params = [
            'appid' => $this->config['appid'],
            'mode' => $mode,   //检测模式：0-所有人脸，1-最大的人脸
            'url' => $imageUrl,                 //图片的 url地址
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/detect';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 人脸检索：用于对一张待识别的人脸图片，在一个或多个 group 中识别出最相似的 Top5 person 作为其身份返回，返回的 Top5 中按照相似度从大到小排列。
     * @param string $groupId
     * @param array $groupIds 
     * @param string $url
     * @return boolean|array
     */
    public function searchApi($groupId = '', $groupIds = [], $url = '')
    {
        if(!$groupId && !$groupIds){
            $this->error = '候选人组 ID和候选人组 ID 列表不能同时为空';
            return FALSE;
        }
        if(!$url){
            $this->error = '搜索图片地址不能为空';
            return FALSE;
        }
        $params = [
            'appid' => $this->config['appid'],
//             'group_id' => $groupId,
            'url' => $url,
        ];
        if ($groupId) {
            $params['group_id'] = $groupId;
        }elseif ($groupIds){
            $params['group_ids'] = $groupIds;
        }
        $apiUrl = 'https://recognition.image.myqcloud.com/face/identify';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    
    /**
     * 人脸对比:本接口用于计算两个 Face 的相似性以及五官相似度。
     * @param string $urlA
     * @param string $urlB
     * @return boolean|array
     */
    public function compareApi($urlA = '', $urlB = '')
    {
        if(!$urlA || !$urlA){
            $this->error = '参数错误';
            return FALSE;
        }
        $params = [
            'appid' => $this->config['appid'],
            'urlA' => $urlA,
            'urlB' => $urlB,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/compare';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 获取组列表：获取一个 appid 下所有 group 列表。
     * @return array
     */
    public function getFaceGroupList()
    {
        $params = [
            'appid' => $this->config['appid'],
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/getgroupids';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 获取人列表:获取一个组 group 中所有 person 列表。
     * @param string $groupId
     * @return array
     */
    public function getFacePersonList($groupId = '')
    {
        $params = [
            'appid' => $this->config['appid'],
            'group_id' => $groupId,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/getpersonids';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 获取person信息：获取一个 person 的信息, 包括 name , id , tag , 相关的 face , 以及 groups 等信息。
     * @param string $personId
     * @return array
     */
    public function getFacePersonInfo($personId = '')
    {
        $params = [
            'appid' => $this->config['appid'],
            'person_id' => $personId,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/getinfo';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 获取人脸列表：获取一个组 person 中所有 face 列表。
     * @param string $personId
     * @return array
     */
    public function getFaceTokenList($personId = '')
    {
        $params = [
            'appid' => $this->config['appid'],
            'person_id' => $personId,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/getfaceids';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 获取人脸信息：获取一个 face 的相关特征信息。
     * @param string $faceToken
     * @return array
     */
    public function getFaceTokenInfo($faceToken = '')
    {
        $params = [
            'appid' => $this->config['appid'],
            'face_id' => $faceToken,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/getfaceinfo';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 增加人脸：将一组 face 加入到一个 person 中。一个 person 最多允许包含 20 个 face 。
     * @param string $url
     * @param string $personId
     * @return boolean|array
     */
    public function facePersonAddFace($url = '', $personId = '', $tag = '')
    {
        if(!$url || !$personId){
            $this->error = '参数错误';
            return FALSE;
        }
        $urls = is_array($url) ? $url : [$url];
        $params = [
            'appid' => $this->config['appid'],
            'person_id' => $personId,
            'urls' => $urls,
            'tag' => $tag,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/addface';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 删除人脸：删除一个 person 下的 face ，包括特征、属性和 face id。
     * @param array $faceIds
     * @param string $personId
     * @return boolean|array
     */
    public function facePersonDelFace($faceIds = [], $personId = '')
    {
        if(!$faceIds || !$personId){
            $this->error = '参数错误';
            return FALSE;
        }
        $params = [
            'appid' => $this->config['appid'],
            'person_id' => $personId,
            'face_ids' => $faceIds,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/delface';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 个体创建：创建一个 person，并将 person 放置到 group_ids 指定的组当中，不存在的 group_id 会自动创建。
     * @param string $url
     * @param string $groupIds
     * @param string $personId
     * @param string $tag
     * @return boolean|array
     */
    public function facePersonCreate($url = '', $groupIds = '', $personId = '', $tag = '')
    {
        if(!$url || !$groupIds || !$personId){
            $this->error = '参数错误';
            return FALSE;
        }
        $params = [
            'appid' => $this->config['appid'],
            'group_ids' => $groupIds,
            'person_id' => $personId,
            'url' => $url,
            'person_name' => 'name_'.$personId,
            'tag' => $tag,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/newperson';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    /**
     * 删除个体：删除一个 Person。
     * @param string $personId
     * @return boolean|array
     */
    public function facePersonDelete($personId = '')
    {
        if(!$personId){
            $this->error = '参数错误';
            return FALSE;
        }
        $params = [
            'appid' => $this->config['appid'],
            'person_id' => $personId,
        ];
        $apiUrl = 'https://recognition.image.myqcloud.com/face/delperson';
        $result = $this->_sendRequest($apiUrl, 'POST', $params);
        return $result;
    }
    
    public function _getTencentGenderData($gender = 0, $code = 'id')
    {
        $genders = [
            1 => [
                'code' => 'male',
                'name' => '男士',
                'count' => 0,
            ],
            2 => [
                'code' => 'female',
                'name' => '女士',
                'count' => 0,
            ],
        ];
        //性别 [0(female)~100(male)]
        if ($gender <50){
            $id = 2;
        }else{
            $id = 1;
        }
        if ($code == 'id') {
            return $id;
        }else{
            return isset($genders[$id][$code]) ? $genders[$id][$code] : FALSE;
        }
    }
    public function _getTencentEmotionData($emotion = 0)
    {
        //微笑[0(normal)~50(smile)~100(laugh)]
        if ($emotion <= 0) {
            $id = 5;//平静
        }elseif ($emotion <= 50) {
            $id = 4;//高兴
        }else{
            $id = 1;//愤怒
        }
        return $id;
    }
    public function _getErrMsg($code = '')
    {
        if (!$code) {
            return FALSE;
        }
        switch ($code) {
            case '14':
                $msg = '签名校验失败';
            break;
            case '15':
                $msg = '操作太频繁，触发频控';
            break;
            case '107':
            case '108':
                $msg = '鉴权服务不可用';
                break;
            case '212':
                $msg = '内部错误';
                break;
            case '-1101':
                $msg = '人脸检测失败';
                break;
            case '-1102':
                $msg = '图片解码失败';
                break;
            case '-1103':
                $msg = '特征处理失败';
                break;
            case '-1104':
                $msg = '提取轮廓错误';
                break;
            case '-1105':
                $msg = '提取性别错误';
                break;
            case '-1106':
                $msg = '提取表情错误';
                break;
            case '-1107':
                $msg = '提取年龄错误';
                break;
            case '-1108':
                $msg = '提取姿态错误';
                break;
            case '-1109':
                $msg = '提取眼镜错误';
                break;
            case '-1200':
                $msg = '特征存储错误';
                break;
            case '-1300':
                $msg = '图片为空';
                break;
            case '-1301':
                $msg = '参数为空';
                break;
            case '-1302':
                $msg = '个体已存在';
                break;
            case '-1303':
                $msg = '个体不存在';
                break;
            case '-1304':
                $msg = '参数过长';
                break;
            case '-1305':
                $msg = '人脸不存在';
                break;
            case '-1306':
                $msg = '组不存在';
                break;
            case '-1307':
                $msg = '组列表不存在';
                break;
            case '-1308':
                $msg = 'url 图片下载失败';
                break;
            case '-1309':
                $msg = '人脸个数超过限制';
                break;
            case '-1310':
                $msg = '个体个数超过限制';
                break;
            case '-1311':
                $msg = '组个数超过限制';
                break;
            case '-1312':
                $msg = '对个体添加了相似度为99%及以上的人脸';
                break;
            case '-1313':
                $msg = '参数不合法（特殊字符比如空格、斜线、tab、换行符）';
                break;
            case '-1400':
                $msg = '非法的图片格式';
                break;
            case '-1403':
                $msg = '图片下载失败';
                break;
            default:
                $msg = '接口异常';
            break;
        }
        return $msg;
    }
    
    
    /**
     * 获取请求头 header
     * @return array()
     */
    private function _baseHeaders() {
        $agent = 'CIPhpSDK/1.0.0 ('.php_uname().') User('.$this->config["appid"].')';
        return array (
            'Host:service.image.myqcloud.com',
            'Authorization:'.$this->_getAuthSign(),
            'User-Agent:'.$agent,
        );
    }
    /**
     * 
     * @param string $url       请求的url地址
     * @param string $method    请求方法，'get', 'post', 'put', 'delete', 'head'
     * @param array $params     请求数据，如有设置，则method为post
     * @param array $header     需要设置的http头部
     * @param number $timeout   请求超时时间
     * @return array            http请求响应
     */
    private function _sendRequest($url = '', $method = 'POST', $params = array(), $header = array(), $timeout = 60) {
        if (!$url) {
            return FALSE;
        }
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        $method = $method ? strtoupper($method) : '';
        if (!$method || !in_array($method, array('GET', 'POST', 'PUT', 'DELETE', 'HEAD'))) {
            if (isset($params)) {
                $method = 'POST';
            }else{
                $method = 'GET';
            }
        } 
        $header = $header ? $header : $this->_baseHeaders();
        $header[] = 'Content-Type:application/json';
        
        
        $header[] = 'Method:'.$method;
        $header[] = 'Connection: keep-alive';
        if ('POST' == $method) {
            $header[] = 'Expect: ';
        }
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($params && in_array($method, array('POST', 'PUT'))) {
            $params = json_encode($params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }
        $ssl = substr($url, 0, 8) == "https://" ? true : false;
        if ($ssl){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   //true any ca
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);       //check only host
            curl_setopt($curl, CURLOPT_SSLVERSION, 4);
        }
        
        //执行命令
        $data = curl_exec($curl);
        $error = '';
        if($data === false){
            $error = curl_error($curl);
            echo 'Curl error: ' . curl_error($curl);
        }
        //关闭URL请求
        curl_close($curl);
        $json = json_decode($data,true);
        if (empty($json)){
            return $data = $data ? $data : $url.':'.$error;
        }
        //显示获得的数据
        return $json;
    }
    /**
     * 获取鉴权签名
     * @param number $howlong 签名的有效期,单位为秒
     * @return boolean|string 
     */
    private function _getAuthSign($howlong = 60) {
        if ($howlong <= 0) {
            return false;
        }
        $now = time();
        $expiration = $now + $howlong;
        $random = rand();
        
        $plainText = "a=".$this->config['appid']."&b=".$this->config['bucket']."&k=".$this->config['secretid']."&e=$expiration&t=$now&r=$random&f=";
        $bin = hash_hmac('SHA1', $plainText, $this->config['secretkey'], true);
        return base64_encode($bin.$plainText);
    }
}