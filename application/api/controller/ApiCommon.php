<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/23 0023
 * Time: 18:10
 */
namespace app\api\controller;

class ApiCommon extends ApiBase
{
    var $method;
    var $reduceStock;
    var $page;
    var $pageSize;

    var $signKeyList;
    var $signKey;
    var $fromSource;

    public function __construct(){
        parent::__construct();
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origin = array(
            'http://m.bi.cn',
            'http://m.bi.com',
            'http://mn.bi.com',
            'http://test.com',
        );
        if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);
            header('Access-Control-Allow-Methods:POST');
            header('Access-Control-Allow-Headers:x-requested-with,content-type');
        }
        $this->_checkPostParams();
        $this->page = isset($this->postParams['page']) && $this->postParams['page'] ? intval($this->postParams['page']) : 0;
        $this->pageSize = isset($this->postParams['page_size']) && $this->postParams['page_size'] ? intval($this->postParams['page_size']) : 0;
        if ($this->pageSize > 50) {
            $this->_returnMsg(['code' => 1, 'msg' => '单页显示数量(page_size)不能大于50']);
        }
        $this->signKey = isset($this->postParams['signkey']) && $this->postParams['signkey'] ? trim($this->postParams['signkey']) : '';
        //客户端签名密钥get_nonce_str(12)
        $this->signKeyList = array(
            'Applets'   => '8c45pve673q1',
            'H5'        => 'hsktz5jkuxcq',
            'Android'   => 'r4q1xpri0clt',
            'Ios'       => 'usn9es45fxhn',
            'TEST'      => 'ds7p7auqyjj8',
        );
//        $this->verifySignParam($this->postParams);
        //请求参数验证
        foreach($this->signKeyList as $key => $value) {
            if($this->signKey == $value) {
                $this->fromSource = trim($key);
                break;
            }else{
                continue;
            }
        }
    }
    /**
     * 请求参数处理
     */

    protected function _returnMsg($data, $echo = TRUE){
        $result = parent::_returnMsg($data);
        $responseTime = $this->_getMillisecond() - $this->visitMicroTime;//响应时间(毫秒)
        $addData = [
            'request_time'  => $this->requestTime,
            'request_source'=> $this->fromSource ? $this->fromSource : '',
            'return_time'   => time(),
            'method'        => $this->method ? $this->method : '',
            'request_params'=> $this->postParams ? json_encode($this->postParams) : '',
            'return_params' => $result,
            'response_time' => $responseTime,
            'error'         => isset($data['code']) ? intval($data['code']) : 0,
        ];
        $apiLogId = db('apilog_app')->insertGetId($addData);
        exit();
    }
}
