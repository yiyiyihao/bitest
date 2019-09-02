<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/3/25 0025
 * Time: 15:15
 */
namespace app\api\controller;

class Device extends ApiBase
{

    public function __construct(){
        parent::__construct();
    }

    public function StatusChange()
    {
        $params = $this -> postParams;

        $str = var_export($params,TRUE);
        file_put_contents('../runtime/log/bibibi',$str);
        #TODO:在设备表中添加is_online字段，修改设备列表页，根据这个字段显示实时直播
//        $data = json_decode($params,true);
        $data = $params;

        if(isset($data['type']) && $data['type'] == 'STATE'){
            if($data['data']['state'] == 'OFFLINE'){
                $update = ['status' => 0];
            }else{ //ONLINE
                $update = ['status' => 1];
                $deviceApi = new \app\common\api\DeviceApi();
                //开启实时录像
                $result = $deviceApi -> openvideo($data['mac_id']);
            }
            $info = db('device') -> where('device_code','=',$data['mac_id']) -> update($update);


        }
//        $results = @curl_post('http://bi.micyi.com/api/device/StatusChange',$this->postParams);
    }



}
