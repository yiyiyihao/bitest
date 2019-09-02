<?php
namespace app\common\controller;
use app\common\model\User;
//后端公共处理
class Backend extends Base
{
	//后端预处理方法
	public function __construct()
    {
    	parent::__construct();
    }    

    /**
     * 检测用户是否登录
     * @return int 用户ID
     */
    protected function isLogin(){
    	if(session('admin_user')){
    		$user = session('admin_user');
    		return $user['user_id'];
    	}else{
    		return false;
    	}
    }
    /**
     * 更新用户信息
     */
    protected function updateLogin($userInfo = FALSE){
    	defined('ADMIN_ID') or define('ADMIN_ID', $this->isLogin());
    	$userMod = new User();
    	$result = $userMod->setLogin($userInfo,ADMIN_ID);
    	if ($result === false) {
    	    return [
    	        'error' => 1,
    	        'msg' => $userMod->error,
    	    ];
    	}
    	return [
    	    'error' => 0,
    	];
    }
}
