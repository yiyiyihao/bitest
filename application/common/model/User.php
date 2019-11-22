<?php

namespace app\common\model;
use think\Model;

class User extends Model
{
	protected $fields;
	public $error;

	//自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
    }
    
    /**
     * 登录用户
     * @param array $userInfo
     * @return bool 登录状态
     */
    public function setLogin($userInfo = FALSE,$user_id = null)
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
            $result = $this->save($data,['user_id' => $user_id]);
            if ($result === FALSE) {
                $this->error = '系统异常';
                return FALSE;
            }
        }else {
            //重新取得用户信息
            $map = ['user_id' => $user_id];
            $userInfo = User::where($map)->find()->toArray();
    		if (!$userInfo) {
    		    $this->error = '用户不存在或已删除';
    		    return FALSE;
    		}
        }
        if ($userInfo['group_id'] == USER) {
            $this->error = '没有登录权限';
            return FALSE;
        }
		$groupInfo = db("user_group")->where(['ugroup_id' => $userInfo['group_id']])->find();
		if (!$groupInfo) {
		    $this->error = '没有登录权限';
		    return FALSE;
		}
		$userInfo['groupPurview'] = $groupInfo['menu_json'];
		$stores = $this->getUserStores($userInfo);
		if ($stores === FALSE) {
		    return FALSE;
		}
		$storeId = isset($stores['store_id']) ? $stores['store_id']: 0;
		$storeIds = isset($stores['store_ids']) ? $stores['store_ids'] : [];
        //写入日志记录#TODO
//      api('Admin','AdminLog','addLog','登录系统');
        //设置session
		$adminUser = [
		    'user_id'     => $userInfo['user_id'],
		    'username'    => $userInfo['username'],
		    'phone'       => $userInfo['phone'],
		    'email'       => $userInfo['email'],
		    'add_time'    => $userInfo['add_time'],
		    'group_id'    => $userInfo['group_id'],
		    'store_id'    => $storeId,
		    'store_ids'   => $storeIds,
		    'last_login_time' => $userInfo['last_login_time'],
		    'groupPurview'    => $userInfo['groupPurview'],
		];
    	session('admin_user',$adminUser);
        return true;        
    }
    
    public function getUserStores($userInfo = [])
    {
        $storeIds = [];
        if ($userInfo['group_id'] != SYSTEM_SUPER_ADMIN) {
            if ($userInfo['group_id'] == STORE_MANAGER) {
                //获取当前用户管理的门店/门店列表
                $storeIds = db('store_member')->where(['user_id' => $userInfo['user_id'], 'group_id' => $userInfo['group_id'], 'is_del' => 0, 'status' => 1])->order('add_time DESC')->column('store_id');
                if (!$storeIds) {
                    $this->error = '账号未授权/已禁用';
                    return FALSE;
                }
                $storeId = $storeIds[0];
            }else{
                //获取当前用户管理的门店
                $manage = db('store_member')->where(['user_id' => $userInfo['user_id'], 'group_id' => $userInfo['group_id'], 'is_del' => 0, 'status' => 1])->order('add_time DESC')->find();
                if (!$manage) {
                    $this->error = '账号未授权/已禁用';
                    return FALSE;
                }
                $storeId = $manage['store_id'];
            }
        }else{
            $storeId = 1;
        }
        return [
            'store_id' => $storeId,
            'store_ids' => $storeIds,
        ];
    }
    
    
    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        #TODO 记录相应登出操作
        session('admin_user', null);
    }    
}