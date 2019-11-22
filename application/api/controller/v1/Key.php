<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/3/15 0015
 * Time: 15:47
 */
namespace app\api\controller\v1;

class Key
{
    /**
     * created by huangyihao
     * @description [] 只需要传手机号码过来就可以生成对应的key；2
     * @return false|string
     */
    public function add()
    {
        header('Access-Control-Allow-Origin:*');
        $phone = input('phone');
        $akey = $this -> makeKey_1();
        $skey = $this -> makeKey_2();
        $userService = new \app\common\service\User();
        $params['group_id'] = 2;
        $userId = $userService->register($phone, 'bi_huixiang_2018', $params);
        if (!$userId) {
            //$this->error($userService->error);
            return json_encode(['code' => 1, 'msg' => $userService->error]);die;
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

        $res = db('user_key') -> insert(['user_id'=>$userId , 'akey'=>$akey , 'skey'=>$skey ,'add_time'=>time()]);
        if($res){
            return json_encode(['code' => 0, 'msg' => '成功','data' => ['akey' =>$akey,'skey'=>$skey]]);die;
        }else{
            return json_encode(['code' => 0, 'msg' => '获取失败']);die;
        }
    }

    public function makeKey_1()
    {
        $str = uniqid(mt_rand(),1);
        return md5($str);
    }

    public function makeKey_2($length = 32)
    {
        $str_pol = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz_";
        return substr(str_shuffle($str_pol), 0, $length);
    }

    //app注册用户
    public function appRegist()
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
            return json_encode(['code' => 1, 'msg' => $userService->error]);die;
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
            return json_encode(['code' => 0, 'msg' => '成功','data' => ['userId' => $userId]]);die;
        }else{
            return json_encode(['code' => 0, 'msg' => '获取失败']);die;
        }
    }




}