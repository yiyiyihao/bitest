<?php
namespace app\api\controller;

use think\Facade\Request;

/**
 * Wechat接口
 * @author chany
 */
class Wechat extends ApiBase
{
	var $wxproAppId;
	var $wxproAppSecret;
    public function __construct(){
        parent::__construct(FALSE);
        $this->wxproAppId       = 'wx0451129aa1cd6fa9';
        $this->wxproAppSecret   = 'f594de719adf4d6dc3bc42c541c65d3e';
    }
    public function index()
    {
    }
    //获取微信小程序openid
    public function getopenid(){
    	$params = Request::instance()->param();
    	$code = isset($params['code']) ? trim($params['code']) : '';
    	$url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.trim($this->wxproAppId).'&secret='.trim($this->wxproAppSecret).'&js_code='.$code.'&grant_type=authorization_code';
    	$result = curl_post_https($url, []);
    	if (isset($result['code']) && $result['code']) {
    	    $this->_returnMsg($result);
    	}else{
    	    $openid = $result && isset($result['openid']) ? trim($result['openid']) : '';
    	    $this->_returnMsg(['openid' => $openid]);
    	}
    }
    protected function _returnMsg($data, $echo = TRUE){
        parent::_returnMsg($data);
        die();
    }
}    