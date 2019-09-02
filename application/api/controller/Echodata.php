<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/3/15 0015
 * Time: 15:47
 */
namespace app\api\controller;
use app\common\controller\Base;

class Echodata extends Base
{

    public function __construct(){
        parent::__construct();
    }
    //app注册用户
    public function appregist()
    {
        header('Access-Control-Allow-Origin:*');
        $params = input();
        $username = isset($params['username']) ? trim($params['username']) : '';
        $password = isset($params['password']) ? trim($params['password']) : '';
        $userService = new \app\common\service\User();
        $params['group_id'] = 2;
        $userId = $userService->register($username, $password, $params,1);
        if (!$userId) {
            //$this->error($userService->error);
            echo json_encode(['code' => 1, 'msg' => $userService->error]);die;
        }
        $data['name'] = '初始门店_'.date('YmdHis');
        $storeId = db('store')->insertGetId($data);
        $data = [
            'store_id'  => $storeId,
            'fuser_id'  => '',
            'user_id'   => $userId,
            'add_time'  => time(),
            'update_time'=> time(),
            'status'    =>  1,
            'is_admin'  => 1,
            'group_id'  => 2,
        ];
        $result = db('store_member')->insertGetId($data);

        if($result){
            echo json_encode(['code' => 0, 'msg' => '成功','data' => ['userId' => $userId]]);die;
        }else{
            echo json_encode(['code' => 0, 'msg' => '获取失败']);die;
        }
    }


    //app添加设备
    public function adddevice()
    {
        $params = input();
        $mac_id = isset($params['mac_id']) ? trim($params['mac_id']) : '';
        $user_id = isset($params['user_id']) ? trim($params['user_id']) : '';
        $mac_name = isset($params['name']) ? trim($params['name']) : '';

        $info = db('store_member') -> where('user_id','=',$user_id) -> where('is_del','=',0) -> find();
//        $data = ['device_code'=>$mac_id,'store_id'=>$info['store_id'],'name'=>$mac_name,'add_time'=>time(),'is_online'=>'ONLINE'];
        $data = ['device_code'=>$mac_id,'store_id'=>$info['store_id'],'name'=>$mac_name,'add_time'=>time()];
        $res = db('device') -> insertGetId($data);
        if(!$res){
            echo json_encode(['code' => 1, 'msg' => 'app添加成功，bi后台添加失败']);die;
        }
        echo json_encode(['code' => 0, 'msg' => '成功','data' => ['device_id' => $res]]);die;
    }

    //app删除设备
    public function deldevice()
    {
        $params = input();
        $mac_id = isset($params['mac_id']) ? trim($params['mac_id']) : '';

        $res = db('device') -> where('device_code','=',$mac_id) -> update(['is_del'=>1]);file_put_contents( 'log',$res);
        if(!$res){
            echo json_encode(['code' => 1, 'msg' => 'app删除成功,bi后台删除失败']);die;
        }
        echo json_encode(['code' => 0, 'msg' => '成功','data' => ['macId' => $mac_id]]);die;
    }



}