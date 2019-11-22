<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/23 0023
 * Time: 17:10
 */
namespace app\api\controller\v1;

use app\api\controller\Api;
use app\api\controller\Oauth;


class Login extends Api
{

    protected $noAuth = ['login','token'];
    //登录入口
    public function login($name='',$pwd='')
    {
        //登入的时候验证有没有这个用户，然后请求token，返回给他
        //所以需要参数用户名或者手机号
        if($name && $pwd){
            $userName = $name;
            $passWord = $pwd;
        }else{
            $userName = isset($this -> postParams['username']) ? trim($this -> postParams['username']) : '';
            $passWord = isset($this-> postParams['password']) ? trim($this -> postParams['password']) : '';
        }

        if(empty($userName) || empty($passWord))
        {
            $this->_returnMsg(['code' => 1, 'msg' => '用户名或密码为空']);die;
        }



        //查询用户
        $map['username'] = $userName;
        $userInfo = db('user') -> where($map) ->where('is_del',0) -> find();
        if(empty($userInfo)){
            $this->_returnMsg(['code' => 1, 'msg' => '用户名不存在']);die;
        }

        if(!$userInfo['status']){
            $this->_returnMsg(['code' => 1, 'msg' => '您的账户已被禁用']);die;
        }
        if($userInfo['password']<> md5($passWord)){
            $this->_returnMsg(['code' => 1, 'msg' => '密码错误']);die;
        }
        //UPDATE `cloud_user`  SET `user_id` = NULL , `last_login_time` = 1548400091  WHERE  `username` = 'admin'  AND `user_id` = 0
//        $result = db('user')->update(['last_login_time' => time(),'user_id' => $userInfo['user_id']]);
//        if($result['error']){
//            $this->_returnMsg(['code' => 1, 'msg' => $result['msg']]);die;
//        }
        $userInfo = $this -> setLogin($userInfo);
        $userInfo['password'] = $passWord;
        if($userInfo['group_id'] == 1){
            $obj = new \app\service\service\Purview();
            $menu = $obj->menu();
            $userInfo['menu'] = $menu;
        }

        $obj = new Token();
        $token = $obj -> token($userInfo);unset($userInfo['last_login_time'],$userInfo['groupPurview']);
        if($name && $pwd){
            return $token['access_token'];
        }
        unset($userInfo['password']);
        $store_type = db('store') -> where('store_id','=',$userInfo['store_id'])-> column('store_type');
        $userInfo['store_type'] = $store_type[0];
        $this->_returnMsg(['code' => 0, 'msg' => '登录成功','data' => ['token'=>$token['access_token'],'user_info'=>$userInfo]]);die;

    }

    public function refresh()
    {
        $token = isset($this -> postParams['token']) ? $this -> postParams['token'] : '';
        $userInfo = !empty(cache($token)) ? cache($token) : '';
        if(!$userInfo){
            $this->_returnMsg(['code' => 1, 'msg' => '登入过期，请重新登入']);die;
        }
        $new_token = $this-> login($userInfo['admin_user']['username'],$userInfo['admin_user']['password']);
        $this->_returnMsg(['code' => 0, 'msg' => '刷新成功','data' => ['old_token'=>$token,'new_token'=>$new_token]]);die;

    }

    public function token()
    {
        $access_key = input('access_key');
        $secret_key = input('secret_key');

        $result = db('user_key') -> where('akey','=', $access_key) -> where('skey','=',$secret_key) -> find();
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $res = db('user')->update(['last_login_time' => time(),'user_id' => $result['user_id']]);
        if($res['error']){
            $this->_returnMsg(['code' => 1, 'msg' => $result['msg']]);die;
        }
        $userInfo = db('user') -> where('user_id','=',$result['user_id']) -> find();
        $userInfo = $this -> setLogin($userInfo,null,1);
        $obj = new Token();
        $token = $obj -> token($userInfo);
        $this->_returnMsg(['code' => 0, 'msg' => '成功','token' => $token['access_token']]);die;

    }

    //页面登出
    public function logout(){
        //设置token过期无效
        $result = Oauth::logout();
        $this->_returnMsg(['code' => 0, 'msg' => $result]);die;
    }

    public function setLogin($userInfo = FALSE,$user_id = null,$tag = 0)
    {
        if(!$userInfo && !$user_id){
            $this->error = '参数错误';
            return false;
        }
        if($userInfo){
            $user_id = $userInfo['user_id'];
            // 更新登录信息
            $data = array(
                'user_id' => $user_id,
                'last_login_time' => NOW_TIME,
            );
            $result = db('user') -> where('user_id','=',$user_id) -> update($data);
            if ($result === FALSE) {
                $this->error = '系统异常';
                //return FALSE;
                $this->_returnMsg(['code' => 1, 'msg' => '系统异常']);die;
            }
        }else {
            //重新取得用户信息
            $map = ['user_id' => $user_id];
            $userInfo = User::where($map)->find()->toArray();
            if (!$userInfo) {
                $this->error = '用户不存在或已删除';
                //return FALSE;
                $this->_returnMsg(['code' => 1, 'msg' => '用户不存在或已删除']);die;
            }
        }
        if ($userInfo['group_id'] == USER) {
            $this->_returnMsg(['code' => 1, 'msg' => '没有登录权限']);die;
        }
        $groupInfo = db("user_group")->where(['ugroup_id' => $userInfo['group_id']])->find();
        if (!$groupInfo) {
            $this->_returnMsg(['code' => 1, 'msg' => '没有登录权限']);die;
        }
        $userInfo['groupPurview'] = $groupInfo['menu_json'];
        $stores = $this->getUserStores($userInfo);
        if ($stores === FALSE) {
            //return FALSE;
            $this->_returnMsg(['code' => 1, 'msg' => '失败']);die;
        }
        $storeId = isset($stores['store_id']) ? $stores['store_id']: 0;
        $shopCode = isset($stores['shop_code']) ? $stores['shop_code']: '';
        $storeIds = isset($stores['store_ids']) ? $stores['store_ids'] : [];
        //写入日志记录#TODO
//      api('Admin','AdminLog','addLog','登录系统');
        //设置session
        if($tag){
            $groupPurview = 'all';
        }else{
            $groupPurview = $userInfo['groupPurview'];
        }
        $menu = $groupInfo['menu'];
        if(!empty($menu)){
            $menu = json_decode($menu,true);
            foreach($menu as $k => $v){
                $menu[$k]['title'] = lang($v['title']);
                if(!empty($v['menuItemList'])){
                    foreach($v['menuItemList'] as $key => $value){
                        $menu[$k]['menuItemList'][$key]['name'] = lang($value['name']);
                    }
                }
            }
        }

        $adminUser = [
            'user_id'     => $userInfo['user_id'],
            'openid'     => $userInfo['openid'],
            'username'    => $userInfo['username'],
            'phone'       => $userInfo['phone'],
            'email'       => $userInfo['email'],
            'add_time'    => $userInfo['add_time'],
            'group_id'    => $userInfo['group_id'],
            'store_id'    => $storeId,
            'shop_code'    => $shopCode,
            'store_ids'   => $storeIds,
            'last_login_time' => $userInfo['last_login_time'],
            'groupPurview'    => $groupPurview,
            'user_photo'      => $userInfo['avatar'],
            'menu'    => $menu,
        ];
        return $adminUser;
    }

    public function getUserStores($userInfo = [])
    {
        $storeIds = [];
        if ($userInfo['group_id'] != SYSTEM_SUPER_ADMIN) {
            $join = [['store S', 'S.store_id = SM.store_id', 'INNER']];
            $where = [
                'SM.user_id' => $userInfo['user_id'],
                'SM.group_id' => $userInfo['group_id'],
                'SM.is_del' => 0,
                'SM.status' => 1,
                'S.is_del' => 0,
                'S.status' => 1,
            ];
            $errMsg = '账号未授权/已禁用';
            if ($userInfo['group_id'] == STORE_SUPER_ADMIN) {
                //获取当前用户管理的门店/门店列表
                $storeids = db('store_member')->alias('SM')->join($join)->where($where)->order('SM.add_time DESC')->field('S.shop_code,SM.store_id')->find();
                if ($storeids) {
                    $sub_storeIds = db('store') -> where(['parent_id' => $storeids['store_id'],'is_del' => 0, 'status' => 1]) -> column('store_id');
                    $storeIds = $sub_storeIds ? array_merge([$storeids['store_id']],$sub_storeIds) : [$storeids['store_id']];
                }
                if (!$storeIds) {
                    //$this->error = '账号未授权/已禁用';
                    //return FALSE;
                    $this->_returnMsg(['code' => 1, 'msg' => $errMsg]);die;
                }
                $storeId = $storeIds[0];
                $shopCode = $storeids['shop_code'];
            }else{
                //获取当前用户管理的门店
                $manage = db('store_member')->alias('SM')->join($join)->where($where)->order('SM.add_time DESC')->find();
                if (!$manage) {
                    //$this->error = '账号未授权/已禁用';
                    //return FALSE;
                    $this->_returnMsg(['code' => 1, 'msg' => $errMsg]);die;
                }
                $storeId = $manage['store_id'];
                $shopCode = $manage['shop_code'];
                $storeIds = [$storeId];
            }
        }else{
            $storeId = 1;
            $storeIds = db('store')->where([['is_del','=',0],['status','=',1]])->field('store_id,shop_code')->select();
            $storeIds = array_column($storeIds,'store_id');
            $shopCode = $storeIds['0']['shop_code'];
        }
        return [
            'store_id' => $storeId,
            'shop_code' => $shopCode,
            'store_ids' => $storeIds,
        ];
    }
}