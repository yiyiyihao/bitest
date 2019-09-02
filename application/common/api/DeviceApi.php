<?php
namespace app\common\api;
use think\Session;
use \Cache;
/**
 * 设备service
 * @author xiaojun
 */
class DeviceApi
{
    var $apiUrl;
    var $error;
    var $save_day = 10 ;         //设置默认录像保存时间10天
    var $AccessKey;
    var $SecretKey;
    var $token;
    public function __construct(){
            $this -> AccessKey = "4ab74c64f6dd47bb1a4f8939c52ffb31";
            $this -> SecretKey = "29bdbc822df2e6c13dcf4afe6913525f";

//        $this->apiUrl = 'http://devt.worthcloud.net';      //测试
        $this->apiUrl = 'http://open.worthcloud.net';     //huangyihao
//         $this->apiUrl = 'http://dev.worthcloud.net';       //正式
        $this -> token = $this -> getAuthorization();
        //先注册为 注册为 值得看云 用户
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'];

        $this -> regist($userId);
          //$this -> regist('123456');  //注册用户写死，方便查看设备列表的状态；以用户或门店id注册的情况，不方便。
//        $this -> token = '763bd775c22bdeb9320c67f054d0d6a4';   //值得看给的测试token
    }

    /**
     * created by huangyihao
     * @description 对添加的设备进行鉴权
     * @param string $macId
     * @param string $macName
     * @return bool|mixed|string|void
     */
    public function getDeviceInfo($macId = '',$macName = '',$userId=null)
    {
        if(!$macId || !$macName){
            $this->error = '参数错误:mac_id,mac_name不能为空';
            return FALSE;
        }
        //获取用户id
//        $userInfo = Session::get('admin_user');
//        $userId = $userInfo['user_id'];
//        $userId = 123456;

        if(!$userId){
            $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
            $userId = cache($authorization)['admin_user']['user_id'];
        }

        $headers = ['Authorization:'.$this -> token];
        $apiUrl = $this->apiUrl.'/v3/cameras';
        $params = ['mac_id' => $macId,'user_id' => $userId,'mac_name' => $macName];
        $paramStr = http_build_query($params);
        $apiUrl = $apiUrl. "?" . $paramStr;
        $result = curl_post($apiUrl, $params,$headers);
        return $result;
    }


    /**
     * created by huangyihao
     * @description 生成token，之后每个接口都携带Authorization：token
     * @return mixed
     */
    public function getAuthorization()
    {

        $Authorization = Cache::get('authorization');
//        $Authorization = false;
//        $Authorization = '763bd775c22bdeb9320c67f054d0d6a4';
//pre($Authorization);
        if(!$Authorization) {
            $AccessKey = $this->AccessKey;
            $SecretKey = $this->SecretKey;
            $apiUrl = $this->apiUrl . "/v3/token/$AccessKey/$SecretKey";
            $result = curl_request($apiUrl);

            $result = json_decode($result, true);

            if (isset($result['code']) && $result['code'] != 0) {

                return false;
            } else {
                Cache::set('authorization', $result['data']['token'], 7000);
                return $result['data']['token'];
            }
        }
        else{
            return $Authorization;
        }

    }

    /**
     * created by huangyihao
     * @description 注册为 值得看云 用户
     */
    public function regist($userId)
    {

        $headers = ["Authorization:".$this->token];
        $apiUrl = $this->apiUrl.'/v3/users';
        $params = ['user_id' => $userId];
        $paramStr = http_build_query($params);
        $apiUrl = $apiUrl. "?" . $paramStr;
        $result = curl_post($apiUrl, $params,$headers);
//        pre($result);
        return $result;
    }

    /**
     * created by huangyihao
     * @description              获取直播的url
     * @param string $macId      设备的串码
     * @return bool|string
     */
    public function getDevicePlaceUrl($macId = '')
    {

        $headers = ["Authorization:".$this->token];
        $apiUrl = $this->apiUrl.'/v3/cloud/play_realtime';
        $params = ['mac_id' => $macId];
        $paramStr = http_build_query($params);
        $apiUrl = $apiUrl. "?" . $paramStr;
        $result = curl_request($apiUrl,'', $headers);
        return $result;
    }

    /**
     * created by huangyihao
     * @description                 开启云录像
     * @param string $macId         设备串码
     * @return bool|string
     */
    public function openvideo($macId = '')
    {

        $headers = ["Authorization:".$this->token];
        $apiUrl = $this->apiUrl.'/v3/cloud/open';
        $params = ['mac_id' => $macId,'save_day' => $this -> save_day];
        $paramStr = http_build_query($params);
        $apiUrl = $apiUrl. "?" . $paramStr;
        $result = curl_request($apiUrl,'', $headers);
        return $result;
    }

    /**
     * created by huangyihao
     * @description             关闭云录像
     * @param string $macId     设备串码
     * @return bool|string
     */
    public function stopvideo($macId = '')
    {

        $headers = ["Authorization:".$this->token];
        $apiUrl = $this->apiUrl.'/v3/cloud/close';
        $params = ['mac_id' => $macId];
        $paramStr = http_build_query($params);
        $apiUrl = $apiUrl. "?" . $paramStr;
        $result = curl_request($apiUrl,'', $headers);
        return $result;
    }

    /**
     * created by huangyihao
     * @description         设置设备翻转
     * @param $macId        设备串码
     * @param $flipStatus   设备翻转 0：默认，1：水平翻转，2：竖直翻转，3：水平竖直翻转
     * @return bool|string
     */
    public function flip($macId,$flipStatus)
    {

        $headers = ["Authorization:".$this->token];
        $apiUrl = $this->apiUrl."/v3/devices/$macId/$flipStatus";

        $result = curl_request($apiUrl,'', $headers);
        return $result;
    }

    /**
     * created by huangyihao
     * @description         删除设备，设备解绑
     * @param $macId        设备串码
     * @return bool|string
     */
    public function del($macId,$userId=null)
    {
        $headers = ["Authorization:".$this->token];

        if(!$userId){
            $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
            $userId = cache($authorization)['admin_user']['user_id'];
        }

//        $userId = 123456;

        $apiUrl = $this->apiUrl."/v3/cameras/$macId/$userId";
        $result = curl_request($apiUrl,null, $headers,null,null,1);
        return $result;
    }

    /**
     * created by huangyihao
     * @description 获取设备的详细信息
     * @param $macId
     * @return bool|mixed|string
     */
    public function allstatus($macId)
    {
        $headers = ["Authorization:".$this->token];

        $apiUrl = $this->apiUrl."/v3/cameras/all/$macId";
        $result = curl_request($apiUrl, null,$headers);
        return $result;
    }

    /**
     * created by huangyihao
     * @description 获取设备简单信息
     * @param $macId
     */
    public function simple($macId)
    {
        $headers = ["Authorization:".$this->token];

        $apiUrl = $this->apiUrl."/v3/cameras/simple/$macId";
        $result = curl_request($apiUrl,null, $headers);
        return $result;
    }


    //根据绑定的用户id获取其名下的所有设备列表及简单状态
    public function getStatusByUserId($userId=123456)
    {
        $headers = ["Authorization:".$this->token];
        $apiUrl = $this->apiUrl."/v3/cam/list_new";
        $params = ['user_id' => $userId];
        $paramStr = http_build_query($params);
        $apiUrl = $apiUrl. "?" . $paramStr;
        $result = curl_request($apiUrl,'', $headers);
        return $result;
    }

}