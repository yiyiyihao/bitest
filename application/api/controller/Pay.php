<?php
namespace app\api\controller;
use think\Facade\Request;

/**
 * 支付接口
 * @author xiaojun
 */
class Pay extends ApiBase
{
    public function __construct(){
        $this->_checkPostParams();
    }
    public function wechat()
    {
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = xml_to_array($this->postParams);
        $orderSn = isset($result['out_trade_no']) ? $result['out_trade_no'] : '';
        $openid = isset($result['openid']) ? $result['openid'] : '';
        if ($orderSn) {
            $order = db('order')->where(['order_sn' => $orderSn])->find();
            if (!$order) {
                $this->_returnMsg(['code' => 1, 'msg' => '商户订单号不存在', 'order_sn' => $orderSn]);
            }
            $payCode = $order ? $order['pay_method'] : '';
            if(!$payCode){
                $this->_returnMsg(['code' => $error, 'msg' => '订单支付方式不能为空']);
            }
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '商户订单号为空']);
        }
        //获取通知的数据
        $result = $this->_wechatResXml($payCode, $result, $order['store_ids']);
        if ($result && $order) {
            if ($order['pay_status'] > 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '订单已支付', 'order_sn' => $orderSn]);
            }
            $payCode = $order['pay_method'] ? $order['pay_method'] : $result['trade_type'];
            $orderService = new \app\common\service\Order();
            $paidAmount = isset($result['total_fee']) ? intval($result['total_fee'])/100 : 0;
            $extra = [
                'pay_sn'        => $result['transaction_id'],
                'paid_amount'   => $paidAmount,
                'pay_method'    => $payCode,
                'remark'        => '订单完成支付,等待商家发货',
            ];
            $result = $orderService->orderPay($orderSn, 0, $order['user_id'], $extra);
            if ($result === FALSE) {
                $this->_returnMsg(['code' => 1, 'msg' => '支付错误:'.$orderService->error, 'order_sn' => $orderSn]);
            }else{
                //支付成功发送通知
                $this->_sendNotify($order, $openid, $payCode, $paidAmount);
                $this->_returnMsg(['msg' => '支付成功', 'order_sn' => $orderSn]);
            }
        }
    }
    /**
     * 发送通知
     * @param array $order      订单信息
     * @param string $payCode   支付方式
     * @param number $payAmount 支付金额
     * @return boolean
     */
    private function _sendNotify($order, $openid, $payCode, $payAmount = 0)
    {
        $extra = $order['extra'] ? json_decode($order['extra'], 1) : [];
        if($extra && isset($extra['prepay_id']) && $extra['prepay_id']){
            $formId = trim($extra['prepay_id']);
        }else{
            $formId = '';
        }
        //获取通知配置信息
        $configName = 'notice';
        $info = db('config')->where(['is_del' => 0, 'config_name' => 'notice', 'store_id' => 0])->find();
        $config = $info? json_decode($info['config_value'], 1) : [];
        
        #TODO DELETE(测试通知)
        $config['wechat_applet']['pay_success'] = 1;
        if ($config && $payCode == 'wechat_applet' && $formId && isset($config['wechat_applet']['pay_success']) && $config['wechat_applet']['pay_success']) {
            //支付成功发送小程序模板通知
            $templateId = 'PfZx9FughFHUfT29_ks3OR1iljwOj6X8Kt3XGrtXJFw';
            $post = [
                'touser'        => $openid,
                'template_id'   => $templateId,
                'page'          => '/pages/order/index',
                'form_id'       => $formId, //支付场景下，为本次支付的 prepay_id,每一个oreoay_id可发送3次模板消息
                'data' => [
                    'keyword1' => [
                        'value' => $order['order_sn'],//订单号
                    ],
                    'keyword2' => [
                        'value' => $payAmount.'元',//支付金额
                    ],
                    'keyword3' => [
                        'value' => date('Y-m-d H:i:s', time()),//支付时间
                    ],
                    'keyword4' => [
                        'value' => '已支付',//订单状态
                    ],
                    'keyword5' => [
                        'value' => '如有疑问,请联系工作人员',//备注
                    ],
                ],
            ];
            $result = $this->_sendWechatAppletNotify($post, 'pay_success');
            return TRUE;
        }
        return FALSE;
    }
    
    protected function _checkPostParams()
    {
        $this->requestTime = time();
        $this->visitMicroTime = $this->_getMillisecond();//会员访问时间(精确到毫秒)
        $notify = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '';
        if (!$notify) {
            $notify = file_get_contents('php://input');
        }
        $this->postParams = $notify;
        if (!$this->postParams) {
            $this->_returnMsg(['code' => 1, 'msg' => '请求参数异常', 'params' => $this->postParams]);
        }
    }
    /**
     * 处理返回参数
     */
    protected function _returnMsg($data, $echo = FALSE){
        $result = parent::_returnMsg($data, $echo);
        $responseTime = $this->_getMillisecond() - $this->visitMicroTime;//响应时间(毫秒)
        $error = isset($data['msg'])   ? trim($data['msg']) : '';
        $method = Request::instance()->action();
        $addData = [
            'request_time'  => $this->requestTime,
            'return_time'   => time(),
            'method'        => trim($method),
            'request_params'=> is_array($this->postParams) ? json_encode($this->postParams) : trim($this->postParams),
            'return_params' => $result,
            'response_time' => $responseTime,
            'error'         => isset($data['code'])  ? intval($data['code']) : 0,
            'error_msg'     => $error,
            'order_sn'      => isset($data['order_sn']) ? trim($data['order_sn']) : '',
        ];
        $apiLogId = db('apilog_pay')->insertGetId($addData);
        if (isset($data['code']) && $data['code'] > 0) {
            echo $this->_wechatReturn("FAIL", $error);
        }else{
            echo $this->_wechatReturn("SUCCESS");
        }
        exit();
    }
    /**
     * 处理微信异步通知的xml
     * @param string $xml
     * @return array
     */
    private function _wechatResXml($payCode, $result, $storeId = 1)
    {
        if($result['return_code'] != 'SUCCESS'){
            $this->_returnMsg(['code' => 1, 'msg' => $result['return_msg']]);
        }
        $paymentService = new \app\common\api\PaymentApi($payCode, $storeId);
        $sign = $paymentService->_wechatGetSign($result);
        $error = isset($result['sign']) && ($result['sign'] == $sign) ? 0 : 1;
        if ($error) {
            $this->_returnMsg(['code' => $error, 'msg' => '签名错误: '.'[sign:'.$sign.'] [notify_sign:'.$result['sign'].']']);
        }
        return $result;
    }
    /**
     * 返回结果给微信
     * @param string $result
     * @return string
     */
    private function _wechatReturn($result = "SUCCESS", $error = ''){
        if($result == "SUCCESS"){
            $text = "OK";
        }else{
            $text = $error ? $error : "价格核对失败";
        }
        $return = "<xml>
        <return_code><![CDATA[{$result}]]></return_code>
        <return_msg><![CDATA[{$text}]]></return_msg>
        </xml>";
        return $return;
    }
}    