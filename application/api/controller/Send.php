<?php
namespace app\api\controller;

use think\Controller;
use think\Request;

trait Send
{

	/**
	 * 返回成功
	 */
	public static function returnMsg($code = 200,$message = '',$data = [],$header = [])
	{	
		http_response_code($code);    //设置返回头部
        $return['code'] = (int)$code;
        $return['message'] = $message;
        $return['data'] = is_array($data) ? $data : ['info'=>$data];
        // 发送头部信息
        foreach ($header as $name => $val) {
            if (is_null($val)) {
                header($name);
            } else {
                header($name . ':' . $val);
            }
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
	}

    protected function _returnMsg($data, $echo = TRUE)
    {
        if (!isset($data['code']) || !$data['code']) {
            $tempArr = ['code' => 0, 'msg' => 'ok'];
            $data = $data ? ($tempArr + $data) : $data;
        }
        $result = json_encode($data);
        if ($echo) {
            header('Content-Type:application/json');
            echo $result;
        }
        return $result;
    }

}

