<?php
namespace app\common\api;
/**
 * 支付接口
 * @author xiaojun
 */
class PaymentApi
{
    var $payments;  //支付方式列表
    var $config;    //支付方式配置信息
    var $storeId;  
    var $payCode;
    var $error;
    public function __construct($payCode = '', $storeId = 1, $option = []){
        $this->payments = [
//             'wechat_js' => [
//                 'code' => 'wechat_js',
//                 'name' => '微信公众号支付',
//                 'desc' => '',
//                 'config' => [
//                     'app_id' => [
//                         'desc' => '微信支付分配的公众账号ID',
//                     ],
//                     'mch_id' => [
//                         'desc' => '微信支付分配的商户号',
//                     ],
//                     'mch_key' => [
//                         'desc' => '微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置',
//                     ],
//                 ],
//             ],
            'wechat_applet' => [
                'code' => 'wechat_applet',
                'name' => '微信小程序支付',
                'desc' => '',
                'config' => [
                    'app_id' => [
                        'desc' => '微信分配的小程序ID',
                    ],
                    'mch_id' => [
                        'desc' => '微信支付分配的商户号',
                    ],
                    'mch_key' => [
                        'desc' => '微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置',
                    ],
                ],
            ],
//             'wechat_app' => [
//                 'code' => 'wechat_app',
//                 'name' => '微信APP支付',
//                 'desc' => '',
//                 'config' => [
//                     'app_id' => [
//                         'desc' => '微信APP支付分配的APPID',
//                     ],
//                     'mch_id' => [
//                         'desc' => '微信APP支付分配的商户号',
//                     ],
//                     'mch_key' => [
//                         'desc' => '微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置',
//                     ],
//                 ],
//             ],
//             'balance' => [
//                 'code' => 'balance',
//                 'name' => '余额支付',
//                 'desc' => '账户余额支付',
//             ],
        ];
        if ($option) {
            $this->config = $option;
        }else{
            $payment = db('payment')->where(['is_del' => 0, 'status' => 1, 'store_id' => $storeId, 'pay_code' => $payCode])->find();
            $this->config = $payment && $payment['config_json'] ? json_decode($payment['config_json'], TRUE): [];
        }
        $this->payCode = strtolower($payCode);
        $this->storeId = $storeId;
    }
    /**
     * 初始化支付数据
     * @param array $order
     * @return array
     */
    public function init($order = [])
    {
        if(!$order){
            $this->error = '订单信息不能为空';
            return FALSE;
        }
        switch ($this->payCode) {
            case 'wechat_app':
                $tradeType = 'APP';
            case 'wechat_applet':
                $tradeType = 'JSAPI';
            case 'wechat_js':
                $this->config['notify_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/api/pay/wechat';//异步通知地址
                $tradeType = isset($tradeType) && $tradeType ? $tradeType : 'JSAPI';
                $result = $this->wechatUnifiedOrder($order, $tradeType);
//                 if ($order['store_ids'] == 2) {
//                     return $result;
//                 }
                if ($result) {
                    if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS' && isset($result['result_code']) && $result['result_code'] == 'SUCCESS') {
                        if ($tradeType == 'JSAPI') {
                            $jsApiData = [
                                'appId'     => trim($this->config['app_id']),
                                'timeStamp' => strval(time()),
                                'nonceStr'  => strval(get_nonce_str(32)),
                                'package'   => 'prepay_id='.$result['prepay_id'],
                                'signType'  => 'MD5',
                            ];
                            $jsApiData['paySign'] = $this->_wechatGetSign($jsApiData, trim($this->config['mch_key']));
                            return $jsApiData;
                        }elseif ($tradeType == 'APP'){
                            $jsApiData = [
                                'appid'     => trim($this->config['app_id']),
                                'partnerid' => trim($this->config['mch_id']),
                                'prepayid'  => trim($result['prepay_id']),
                                'package'   => 'Sign=WXPay',
                                'noncestr'  => strval(get_nonce_str(32, 3)),
                                'timestamp' => strval(time()),
                            ];
                            $jsApiData['sign'] = $this->_wechatGetSign($jsApiData, trim($this->config['mch_key']));
                            return $jsApiData;
                        }else{
                            return $result;
                        }
                    }else{
                        $this->error = isset($result['err_code_des']) ? $result['err_code_des'] : $result['return_msg'];
                        return FALSE;
                    }
                }else{
                    $this->error = $this->error ? $this->error :'支付请求异常';
                    return FALSE;
                }
            break;
            case 'balance':
                $userId = isset($order['user_id']) ? intval($order['user_id']) : 0;
                if (!$userId) {
                    $this->error = '订单信息异常';
                    return FALSE;
                }
                $user = db('user')->where(['user_id' => $userId])->find();
                if (!$user) {
                    $this->error = '用户不存在';
                    return FALSE;
                }
                $realAmount = $order['real_amount'];
                $balance = $user['balance'];
                if ($balance <= 0 || $balance < $realAmount) {
                    $this->_returnMsg(['errCode' => 1, 'errMsg' => '账户余额不足，请充值']);
                }
                //用户余额变动
                $userService = new \app\common\service\User();
                $result = $userService->assetChange($user['user_id'], 'balance', -$realAmount, 'order_pay', $order['order_sn'].'(订单支付)', $order);
                if ($result === FALSE) {
                    $this->error = $userService->error;
                    return FALSE;
                }
                $orderService = new \app\common\service\Order();
                //余额支付处理
                $params = [
                    'pay_method'    => $this->payCode,
                    'paid_amount'   => $order['real_amount'],
                    'remark'        => '订单完成支付,等待商家发货',
                ];
                $result = $orderService->orderPay($order['order_sn'], 0, $user['user_id'], $params);
                if ($result === FALSE) {
                    //退还支付金额
                    $result = $userService->assetChange($user['user_id'], 'balance', $realAmount, 'system_return', $order['order_sn'].'(订单支付失败,系统退款)', $order);
                    $this->error = $orderService->error;
                    return FALSE;
                }
                return TRUE;
            break;
            default:
                $this->error = '支付方式错误';
                return FALSE;
            break;
        }
    }
    /**
     * 微信统一下单功能
     * @param array $order      订单信息
     * @param string $tradeType 交易类型
     * @return array
     */
    public function wechatUnifiedOrder($order = [], $tradeType = 'JSAPI') {
        $params = [
            'appid'     => trim($this->config['app_id']),
            'body'      => isset($order['subject']) ? trim($order['subject']) : '商品购买',
            'mch_id'    => trim($this->config['mch_id']),
            'nonce_str' => get_nonce_str(32),
            'notify_url'=> trim($this->config['notify_url']),
            'out_trade_no'      => trim($order['order_sn']),
            'spbill_create_ip'  => $_SERVER['REMOTE_ADDR'],
            'trade_type'        => $tradeType,
            'total_fee'         => intval((100 * $order['real_amount'])),
        ];
        if ($tradeType == 'JSAPI') {
            $params['openid'] = isset($order['openid']) ? $order['openid']: '';
            if (!$params['openid']) {
                $this->error = 'openid缺失';
                return FALSE;
            }
        }
        //订单信息
        $params['sign'] = $this->_wechatGetSign($params);
        // 数字签名
        $paramsXml = array_to_xml($params);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//统一下单接口地址
        $returnXml = $this->_wechatPostXmlCurl($paramsXml, $url, true);
        $result = xml_to_array($returnXml);
        return $result;
    }
    
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function _wechatGetSign($data = [])
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = to_url_params($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".trim($this->config['mch_key']);
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    /*
     *与微信通讯获得二维码地址信息，必须以xml格式
     */
    private function _wechatPostXmlCurl($xml, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        
//         $code = $this->payCode == 'wechat_app' ? $this->payCode : 'wechat_js';
//         $sslcert = dirname(getcwd()).'/biapp/common/cert/'.$this->storeId.'/'.$code.'_apiclient_cert.pem';
//         $sslkey = dirname(getcwd()).'/biapp/common/cert/'.$this->storeId.'/'.$code.'_apiclient_key.pem';
        
//         curl_setopt($ch, CURLOPT_SSLCERT, $sslcert);
//         curl_setopt($ch, CURLOPT_SSLKEY, $sslkey);
        
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        $errno = curl_errno($ch);
        $info  = curl_getinfo($ch);
        curl_close($ch);
        if ($errno > 0) {
            if ($errno == 58) {
                $this->error = 'CURL证书错误';
                return FALSE;
            }else{
                $this->error = 'CURL错误'.$errno;
                return FALSE;
            }
        }
        //返回结果
        return $data;
    }
}