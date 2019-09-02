<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/26 0026
 * Time: 14:59
 */
namespace app\api\controller\v1\store;

use app\api\controller\Api;


//后台数据接口页
class Qrcode extends Api
{
    public function getQrcode()
    {
        $params = $this -> postParams;
        $storeId = isset($params['storeId']) ? $params['storeId'] : 1;
        //远程判断图片是否存在
        $qiniuApi = new \app\common\api\QiniuApi();
        $config = $qiniuApi->config;
        $domain = $config ? 'http://'.$config['domain'].'/': '';
        $filename = 'cloud_store_wxa_qrcode_'.$storeId.'.png';
        $result = curl_post($domain.$filename, []);
        if (isset($result['error'])) {
            $wechatApi = new \app\common\api\WechatApi('wechat_applet');
            $data = $wechatApi->getWXACodeUnlimit($storeId);
            if ($wechatApi->error) {
//                $this->error($wechatApi->error);
            }else{
                $result = $qiniuApi->uploadFileData($data, $filename);
                if (isset($result['error']) && $result['error'] > 0) {
//                    $this->error($result['msg']);
                }
            }
        }
        return $domain.$filename;
    }
}