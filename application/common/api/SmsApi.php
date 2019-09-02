<?php
namespace app\common\api;

/**
 * 阿里云短信接口
 * @author xiaojun
 */
class SmsApi
{
    var $config;
    var $error;
    var $templateCodes;
    var $signName;
    public function __construct($config = []){
        $this->config = [
            'accessKeyId'     => 'LTAIIjUwy0ZCVFvl',
            'accessKeySecret' => 'TPOAbpRXzxh07xCtto86mtCONb8owW',
        ];
        $this->templateCodes = [
            'send_code'         => 'SMS_147437513',//发送验证码
            'winning_notice'    => 'SMS_147417695',//中奖结果通知
            'resetpwd'          => 'SMS_152700174',
        ];
        $this->signName = '回响科技';
    }
    public function getSmsCode()
    {
        $code = get_nonce_str(6, 2);
        $exist = db('log_code')->where(['code' => $code])->find();
        if ($exist){
            return $this->getSmsCode();
        }else{
            return $code;
        }
    }
    
    /**
     * 微信接口:获取access_token(接口调用凭证)
     * @return string
     */
    public function send($phone, $type, $param)
    {
        $templateCode = isset($this->templateCodes[$type]) ? trim($this->templateCodes[$type]) : '';
        if (!$templateCode) {
            $this->error = 'TemplateCode不能为空';
            return FALSE;
        }
        $params = [
            'PhoneNumbers'  => trim($phone),
            'SignName'      => $this->signName,
            'TemplateCode'  => $templateCode,
            'TemplateParam' => $param,
        ];
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        $security = FALSE;
        $params = array_merge($params, array("RegionId" => "cn-hangzhou", "Action" => "SendSms", "Version" => "2017-05-25"));
        // 此处可能会抛出异常，注意catch
        $content = $this->sendRequest($this->config['accessKeyId'], $this->config['accessKeySecret'], "dysmsapi.aliyuncs.com", $params, $security);
        return $content;
    }
    /**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @param $security boolean 使用https
     * @param $method boolean 使用GET或POST方法请求，VPC仅支持POST
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    protected function sendRequest($accessKeyId, $accessKeySecret, $domain, $params, $security=false, $method='POST') {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);
        
        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }
        
        $stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));
        
        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));
        
        $signature = $this->encode($sign);
        
        $url = ($security ? 'https' : 'http')."://{$domain}/";
        
        try {
            $content = $this->fetchContent($url, $method, "Signature={$signature}{$sortedQueryStringTmp}");
            return json_decode($content, TRUE);
        } catch( \Exception $e) {
            return false;
        }
    }
    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }
    
    private function fetchContent($url, $method, $body) {
        $ch = curl_init();
        
        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            $url .= '?'.$body;
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));
        
        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $rtn = curl_exec($ch);
        
        if($rtn === false) {
            // 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
            // 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }
        curl_close($ch);
        
        return $rtn;
    }
}