<?php
namespace app\common\controller;

//登陆验证操作公共处理
class LoginBase extends Backend
{
	//登陆预处理方法
	public function __construct()
    {
    	parent::__construct();
    	//登陆初始化操作
    	$this->init();
    }
    //登陆预处理机制
    protected function init() {
    	//检查管理员是否登陆
    	defined('ADMIN_ID') or define('ADMIN_ID', $this->isLogin());
    	if( ADMIN_ID && ($this->request->action()!='logout')){
    		$this->redirect('/admin');
    	}
    	
    }


}
