<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Oauth;
use think\facade\Cache;
use app\service\service\Key;

/**
 * 生成token
 */
class Token
{
	use Send;

	/**
	 * 请求时间差
	 */

	public static $timeDif = 10000;

	//public static $accessTokenPrefix = 'accessToken_';
	public static $accessTokenPrefix = '';
	public static $refreshAccessTokenPrefix = 'refreshAccessToken_';
	public static $expires = 7200;
	public static $refreshExpires = 60*60*24*30;   //刷新token过期时间

    //暂时用属性存放第三方调用接口的akey，skey。以后可以建一个表存放，并绑定相关的用户，用户组，权限。
    //public static $akey       = 'WhwTOCRu6A3sMrjrgojUnfhDx_jLOe57';  //调用API的API Key
    //public static $skey    = 'iH_sdhD12ctZQKDg6OfFUQB_bOc3IAT_';  //调用API的API Secret


	/**
	 * 测试appid，正式请数据库进行相关验证
	 */
	public static $appid = 'tp5restfultest';
	/**
	 * appsercet
	 */
	public static $appsercet = '123456';

	/**
	 * 生成token
	 */
	public function token($userInfo = false)
	{
        header('Access-Control-Allow-Origin:*');
		//参数验证
//		$validate = new \app\api\validate\Token;
//		if(!$validate->check(input(''))){
//			return self::returnMsg(401,$validate->getError());
//		}
		//self::checkParams(input(''));  //参数校验
		//数据库已经有一个用户,这里需要根据input('mobile')去数据库查找有没有这个用户
        if($userInfo){
            //登入获取
            $tag = true;
        }elseif(input('type') == 'admin'){
            //直接访问获取的情况
            //...
            $access_key = input('access_key');
            $secret_key = input('secret_key');
            $keyObj = new Key();
            $keylist = $keyObj -> getKey();
            if($keylist['admin']['akey'] != $access_key || $keylist['admin']['skey'] != $secret_key){
                $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
            }
            $userInfo = [
                "user_id" => "1",
                "username" => "admin",
                "phone" => "15812345678",
                "email" => "admin@huixiang.com",
                "add_time" => "0",
                "group_id" => "1",
                "store_id" => "1",
                "store_ids" => [],
                "last_login_time" => "1548901477",
                "groupPurview" => "",
                ];
            //虚拟一个uid返回给调用方
            $tag = false;
        }elseif(input('type') == 'developer'){
            $access_key = input('access_key');
            $secret_key = input('secret_key');
            $keyObj = new Key();
            $keylist = $keyObj -> getKey();
            if($keylist['developer']['akey'] != $access_key || $keylist['developer']['skey'] != $secret_key){
                $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
            }
//            $result = db('user_key') -> where('akey','=', $access_key) -> where('skey','=',$secret_key) -> find();
//            if(!$result){
//                $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
//            }

            $userInfo = [
                "user_id" => "1",
                "username" => "developer",
                "phone" => "18775202222",
                "email" => "admin@huixiang.com",
                "add_time" => "0",
                "group_id" => "1",
                "store_id" => "0",
                "store_ids" => [],
                "last_login_time" => "1548901477",
                "groupPurview" => "",
            ];
            //虚拟一个uid返回给调用方
            $tag = false;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

		try {
			$accessToken = self::setAccessToken(array_merge($userInfo,input('')));  //传入参数应该是根据手机号查询改用户的数据
            if($tag){
                return $accessToken;
            }else{
                //return self::returnMsg(200,'success',$accessToken['access_token']);
                $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['token' =>$accessToken['access_token']]]);die;
            }

		} catch (Exception $e) {
			//return self::returnMsg(500,'fail',$e);
            $this->_returnMsg(['code' => 500, 'msg' => $e]);die;
		}
	}

	/**
	 * 刷新token
	 */
	public function refresh($refresh_token='',$appid = '')
	{
		$cache_refresh_token = Cache::get(self::$refreshAccessTokenPrefix.$appid);  //查看刷新token是否存在
		if(!$cache_refresh_token){
			//return self::returnMsg(401,'fail','refresh_token is null');
            $this->_returnMsg(['code' => 401, 'msg' => 'refresh_token is null']);die;
		}else{
			if($cache_refresh_token !== $refresh_token){
				//return self::returnMsg(401,'fail','refresh_token is error');
                $this->_returnMsg(['code' => 401, 'msg' => 'refresh_token is error']);die;
			}else{    //重新给用户生成调用token
				$data['appid'] = $appid;
				$accessToken = self::setAccessToken($data); 
				//return self::returnMsg(200,'success',$accessToken);
                $this->_returnMsg(['code' => 0, 'msg' => 'ok','data'=>['token' =>$accessToken['access_token']]]);die;
			}
		}
	}

	/**
	 * 参数检测
	 */
	public static function checkParams($params = [])
	{	
		//时间戳校验
		if(abs($params['timestamp'] - time()) > self::$timeDif){

			return self::returnMsg(401,'请求时间戳与服务器时间戳异常','timestamp：'.time());
		}

		//appid检测，这里是在本地进行测试，正式的应该是查找数据库或者redis进行验证
		if($params['appid'] !== self::$appid){
			return self::returnMsg(401,'appid 错误');
		}

		//签名检测
		$sign = Oauth::makeSign($params,self::$appsercet);
//		if($sign !== $params['sign']){
//			return self::returnMsg(401,'sign错误','sign：'.$sign);
//		}
	}

	/**
     * 设置AccessToken
     * @param $clientInfo
     * @return int
     */
    protected function setAccessToken($clientInfo)
    {
        //生成令牌
        //$accessToken = self::buildAccessToken();
        //$refresh_token = self::getRefreshToken($clientInfo['appid']);
        $obj = new \app\service\service\Zhongtai();
        $accessToken = $obj->get_token();
        $accessTokenInfo = [
            'access_token'  => $accessToken,//访问令牌
            'expires_time'  => time() + self::$expires,      //过期时间时间戳
            //'refresh_token' => $refresh_token,//刷新的token
            'refresh_expires_time'  => time() + self::$refreshExpires,      //过期时间时间戳
            'admin_user'        => $clientInfo,//用户信息
        ];
        self::saveAccessToken($accessToken, $accessTokenInfo);  //保存本次token
        //self::saveRefreshToken($refresh_token,$clientInfo['appid']);
        return $accessTokenInfo;
    }

    /**
     * 刷新用的token检测是否还有效
     */
    public static function getRefreshToken($appid = '')
    {
    	return Cache::get(self::$refreshAccessTokenPrefix.$appid) ? Cache::get(self::$refreshAccessTokenPrefix.$appid) : self::buildAccessToken(); 
    }

    /**
     * 生成AccessToken
     * @return string
     */
    protected static function buildAccessToken($lenght = 32)
    {
        //生成AccessToken
        $str_pol = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);

    }

    /**
     * 存储token
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected static function saveAccessToken($accessToken, $accessTokenInfo)
    {
        //存储accessToken
        cache(self::$accessTokenPrefix . $accessToken, $accessTokenInfo, self::$expires);
    }

    /**
     * 刷新token存储
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected static function saveRefreshToken($refresh_token,$appid)
    {
        //存储RefreshToken
        cache(self::$refreshAccessTokenPrefix.$appid,$refresh_token,self::$refreshExpires);
    }
}