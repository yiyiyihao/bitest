<?php
namespace app\common\controller;
use app\service\service\Auth;
use \Request;
//登陆后管理内容公共处理
class AdminBase extends Backend
{
    var $subMenu;
    var $storeId;
    var $storeIds;
    var $adminUser;
	//管理内容预处理方法
	public function __construct()
    {
    	parent::__construct();
    	//后台管理初始化操作
    	$this->init();
    }
    
    //后台管理预处理机制
    private function init() {
    	//检查管理员是否登陆
    	defined('ADMIN_ID') or define('ADMIN_ID', $this->isLogin());
    	$controller = Request::controller();
    	if(!ADMIN_ID && !in_array(strtolower($controller), ['apilog'])){
    		$this->redirect('/login');
    	}
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $this->adminUser = cache($authorization)['admin_user'];
//    	$this->adminUser = session('admin_user');
    	//检查用户是否拥有操作权限
    	$this->storeId = isset($this->adminUser['store_id']) && $this->adminUser['store_id'] ? $this->adminUser['store_id'] : 0;
    	$this->storeIds = isset($this->adminUser['store_ids']) && $this->adminUser['store_ids'] ? $this->adminUser['store_ids'] : [];
//     	if(!self::checkPurview($this->adminUser,$this->storeId)){
    	if(!self::checkPurview($this->adminUser,$this->storeId)){//pre([$this->adminUser,$this->storeId]);
    	    $this->error("没有操作权限");
    	}
    	//初始化页面赋值
    	$this->initAssign();
    }
    
    //检查用户是否拥有操作权限
    private function checkPurview($user = [],$storeid = FALSE){
        if(ADMIN_ID == 1 || $user['groupPurview'] == 'all'){
            return true;
        }
        $auth = new Auth();
        $checkName = [
            'module'        =>  $this->request->module(),
            'controller'    =>  $this->request->controller(),
            'action'        =>  $this->request->action(),
        ];
        $groupPurview = json_decode($user['groupPurview'],true); //huangyihao
        $groupPurview = isset($groupPurview)?$groupPurview:[];  //huangyihao
        return $auth->check($checkName,$groupPurview);
    }
    
    //页面初始化赋值
    protected function initAssign() {
        $server = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
        $self = strip_tags($server);
        $this->assign('self', $self);
//        $this->assign('title',lang(config('setting.title')).lang('home_manager'));
        $this->assign('title','ajdkfds');
        $this->assign('adminUser', $this->adminUser);
    }
    
    //渲染输出
    protected function fetch($template = '', $vars = [], $replace = [], $config = []) {
        //获取当前页二级菜单
        $subMenu = $this->subMenu;
        $this->assign('subMenu',$subMenu);
        $this->view->engine->layout(true);
        return parent::fetch($template, $vars, $replace, $config);
    }
    
}
