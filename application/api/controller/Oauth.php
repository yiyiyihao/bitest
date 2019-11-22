<?php
namespace app\api\controller;

use app\api\controller\Send;
use think\Exception;
use think\facade\Request;
use think\facade\Cache;

/**
 * API鉴权验证
 */
class Oauth
{
    use Send;
    
    /**
     * accessToken存储前缀
     *
     * @var string
     */
    //public static $accessTokenPrefix = 'accessToken_';
    public static $accessTokenPrefix = '';

    /**
     * 过期时间秒数
     *
     * @var int
     */
    public static $expires = 7200;

    /**
     * 认证授权 通过用户信息和路由
     * @param Request $request
     * @return \Exception|UnauthorizedException|mixed|Exception
     * @throws UnauthorizedException
     */
    final function authenticate()
    {      
        return self::certification(self::getClient());
    }

    /**
     * 获取用户信息
     * @param Request $request
     * @return $this
     * @throws UnauthorizedException
     */
    public static function getClient()
    {   
        //获取头部信息
        try {
            if(!empty(Request::header('authentication'))){
                $authorization = Request::header('authentication');
            }else{
                $authorization = input('token');
            }
             //获取请求中的authentication字段，值形式为USERID asdsajh..这种形式
//            $authorization = explode(" ", $authorization);        //explode分割，获取后面一窜base64加密数据
//            $authorizationInfo  = explode(":", base64_decode($authorization[1]));  //对base_64解密，获取到用:拼接的自字符串，然后分割，可获取appid、accesstoken、uid这三个参数
//            $clientInfo['uid'] = $authorizationInfo[2];
//            $clientInfo['appid'] = $authorizationInfo[0];
//            $clientInfo['access_token'] = $authorizationInfo[1];
            return $authorization;
        } catch (Exception $e) {
            return self::returnMsg(401,'Invalid authorization credentials',Request::header(''));
        }
    }

    /**
        * 获取用户信息后 验证权限
        * @return mixed
            */
    public function certification($data = []){

        $getCacheAccessToken = Cache::get(self::$accessTokenPrefix . $data);  //获取缓存access_token
        if(!$getCacheAccessToken){
            //return self::returnMsg(401,'fail',"非法的access_token");
            $this->_returnMsg(['code' => 401, 'msg' => '非法的access_token']);die;
        }
//         pre(json_decode($getCacheAccessToken['admin_user']['groupPurview'], 1), 1);

        //验证权限
        if(!self::checkPurview($getCacheAccessToken['admin_user']))
        {pre('没有操作权限');
            //return self::returnMsg(403,'fail',"没有操作权限");
            $this->_returnMsg(['code' => 403, 'msg' => '没有操作权限']);die;
        };

//        if($getCacheAccessToken['client']['appid'] !== $data['appid']){
//
//            return self::returnMsg(401,'fail',"appid错误");  //appid与缓存中的appid不匹配
//        }
        return $data;
    }

    //检查用户是否拥有操作权限
    private static function checkPurview($user = [],$storeid = FALSE){
        if($user['user_id'] == 1 || $user['groupPurview'] == 'all'){
            return true;
        }
        $auth = new \app\service\service\Auth();
        $checkName = [
            'module'        =>  Request::module(),
            'controller'    =>  Request::controller(),
            'action'        =>  Request::action(),
        ];
        $url = strtolower(Request::url());
        //不需要验证权限
        $purviews = [
            '/v1/refresh', '/v1/device/gettypelist', '/v1/store/storetype', '/v1/device/gettypelist', '/v1/store/realstore',
            '/v1/store/allstore','/v1/user/getstores','/v1/staffer/getgroups','/v1/staffer/grouplist','/v1/data/get_menu?lang=en-us','/v1/data/get_menu',
        ];
        if (in_array($url, $purviews)) {
            return TRUE;
        }
        $groupPurview = json_decode($user['groupPurview'],true); //huangyihao
        $groupPurview = isset($groupPurview)?$groupPurview:[];  //huangyihao
        //pre([$checkName,$groupPurview]);
        return $auth->check($checkName,$groupPurview);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public static function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr)
        {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr) || strtolower(request()->url()) =='/api/app/index')
        {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 生成签名
     * _字符开头的变量不参与签名
     */
    public static function makeSign ($data = [],$app_secret = '')
    {   
        unset($data['version']);
        unset($data['sign']);
        return self::_getOrderMd5($data,$app_secret);
    }

    /**
     * 计算ORDER的MD5签名
     */
    private static function _getOrderMd5($params = [] , $app_secret = '') {
        ksort($params);
        $params['key'] = $app_secret;
        return strtolower(md5(urldecode(http_build_query($params))));
    }

    public static function logout()
    {
        $data = self::getClient();
        return Cache::set(self::$accessTokenPrefix . $data,null);

    }

}