<?php
namespace app\common\api;
use think\facade\Cache;

/**
 * 微信接口
 * @author xiaojun
 */
class WechatApi
{
    var $config;
    var $error;
    var $templateCodes;
    public function __construct($type = 'wechat', $config = []){
        if (!$config) {
            if ($type == 'wechat') {//微信公众账号配置
                $this->config = [
                    'appid'     => 'wxa57c32c95d2999e5',
                    'appsecret' => '3e93fa277e80451301fd34d5cc9a37bc',
                ];
            }elseif ($type == 'wechat_applet'){//微信小程序配置
                $this->config = [
                    'appid'     => 'wx0451129aa1cd6fa9',
                    'appsecret' => 'f594de719adf4d6dc3bc42c541c65d3e',
                ];
            }
        }else{
            $this->config = $config;
        }
        $this->templateCodes = [
            'applet_winning_notice' => 'vz21TTIZUi5_dmOi0dc66S0IrnJ-Z2u6Yf0ZivvAt-Y',//小程序中奖结果通知
        ];
    }
    /**
     * 微信接口:获取access_token(接口调用凭证)
     * @return string
     */
    public function getWechatAccessToken()
    {
        $appid = isset($this->config['appid']) ? trim($this->config['appid']) : '';
        if (!$appid) {
            $this->error = 'APPID不能为空';
            return FALSE;
        }
        $tokenName = 'asscess_token_'.$appid;
        $accessToken = Cache::get($tokenName);
        if (!$accessToken) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.trim($this->config['appsecret']);
            $result = curl_post_https($url, []);
            if (isset($result['access_token'])) {
                $accessToken = $result['access_token'];
                $expiresIn = isset($result['expires_in']) ? $result['expires_in']-1 : 7100;
                Cache::set($tokenName, $accessToken, $expiresIn);
                return $accessToken;
            }else{
                $this->error = 'errcode:'.$result['errcode'].'; errmsg:'.$result['errmsg'];
                return FALSE;
            }
        }else{
            return $accessToken;
        }
    }
    
    public function getWXACodeUnlimit($scene, $page = 'pages/index/index')
    {
        $token = $this->getWechatAccessToken();
        if (!$token) {
            return FALSE;
        }
        $post = [
            'scene' => $scene,
            'page'  => $page
        ];
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.trim($token);
        $result = curl_post_https($url, json_encode($post));
        if (isset($result['errcode']) && $result['errcode']) {
            $this->error = 'errcode:'.$result['errcode'].'; errmsg:'.$result['errmsg'];
            return FALSE;
        }
        return $result;
    }
    
    /**
     * 微信小程序接口:小程序发送模板消息
     * @param array $post 模板消息发送内容
     * @return array
     */
    public function sendAppletTemplateMessage($post)
    {
        $token = $this->getWechatAccessToken();
        if (!$token) {
            return FALSE;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.trim($token);
        $result = curl_post_https($url, json_encode($post));
        if (isset($result['errcode']) && $result['errcode']) {
            $this->error = 'errcode:'.$result['errcode'].'; errmsg:'.$result['errmsg'];
            return FALSE;
        }
        return $result;
    }
    /**
     * 微信公众平台接口：发送模板消息
     * @param array $post
     * @return array
     */
    public function sendTemplateMessage($post)
    {
        $token = $this->getWechatAccessToken();
        if (!$token) {
            return FALSE;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.trim($token);
        $result = curl_post_https($url, json_encode($post));
        if (isset($result['errcode']) && $result['errcode']) {
            $this->error = 'errcode:'.$result['errcode'].'; errmsg:'.$result['errmsg'];
            return FALSE;
        }
        return $result;
    }
    
}