<?php
namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\controller\Send;
use app\api\controller\Oauth;

/**
 * api 入口文件基类，需要控制权限的控制器都应该继承该类
 */
class Api
{	
	use Send;
	/**
     * @var \think\Request Request实例
     */
    protected $request;

    protected $clientInfo;

    protected $postParams;

    /**
     * 不需要鉴权方法
     */
    protected $noAuth = [];
    protected $userInfo;

	/**
	 * 构造方法
	 * @param Request $request Request对象
	 */
	public function __construct(Request $request)
	{
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Credentials:true');
        $this->request = $request;
		$this->init();
		//$this->uid = $this->clientInfo['uid'];
//cache('admin_user',Array
//(
//    "user_id" => "1",
//    "username" => "admin",
//    "phone" => "15812345678",
//    "email" => "admin@huixiang.com",
//    "add_time" => "0",
//    "group_id" => "1",
//    "store_id" => "20",
//    "store_ids" => Array
//    (
//    ),
//
//    "last_login_time" => "1548638799",
//    "groupPurview" => ''
//));
		$authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
		$this->userInfo = $authorization ? cache($authorization)['admin_user'] : [];
	}

	/**
	 * 初始化
	 * 检查请求类型，数据格式等
	 */
	public function init()
	{
        defined('USER')              or define('USER', 0);              //前台会员
        defined('SYSTEM_SUPER_ADMIN')or define('SYSTEM_SUPER_ADMIN', 1);//平台超级管理员
        defined('STORE_SUPER_ADMIN') or define('STORE_SUPER_ADMIN', 2); //连锁店/门店超级管理员
        defined('STORE_MANAGER')     or define('STORE_MANAGER', 3);     //店长
        defined('STORE_CLERK')       or define('STORE_CLERK', 4);       //店员
        defined('EXPERIENCER')       or define('EXPERIENCER', 5);       //店员


        defined('NOW_TIME')or define('NOW_TIME', $_SERVER['REQUEST_TIME']);
        defined('IS_POST') or define('IS_POST', $this->request->isPost());
        defined('IS_AJAX') or define('IS_AJAX', $this->request->isAjax());
        defined('IS_GET')  or define('IS_GET', $this->request->isGet());
        defined('IS_MOBILE')or define('IS_MOBILE', $this->request->isMobile());

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'];
        defined('ADMIN_ID')or define('ADMIN_ID', $userId);
		//所有ajax请求的options预请求都会直接返回200，如果需要单独针对某个类中的方法，可以在路由规则中进行配置
		if($this->request->isOptions()){

			return self::returnMsg(200,'success');
		}
		$this -> _checkPostParams();
		if(!Oauth::match($this->noAuth)){
			$oauth = app('app\api\controller\Oauth');   //tp5.1容器，直接绑定类到容器进行实例化
    		return $this->clientInfo = $oauth->authenticate();
		}


	}



	/**
	 * 空方法
	 */
	public function _empty()
    {
        return self::returnMsg(404, 'empty method!');
    }

    protected function _checkPostParams()
    {
        $this->requestTime = time();
        $this->visitMicroTime = $this->_getMillisecond();//会员访问时间(精确到毫秒)
        $data = file_get_contents('php://input');
        if ($data) {
            $tempData = json_decode($data, true);
        }else{
            $tempData = [];
        }
        if (!$data || !$tempData ) {
            $data = input();
            if(!empty($data['version'])){
                unset($data['version']);
            }
        }
        if (!$tempData) {
            $tempData = $data ? $data : (isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '');
        }
        if(!is_array($tempData)) {
            $this->postParams = json_decode($tempData, true);
        }else{
            $this->postParams = $tempData;
        }
//        if (!$this->postParams) {
//            $this->_returnMsg(['code' => 1, 'msg' => '请求参数异常']);die;
//        }
    }

    protected function _getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    function _checkStoreVisit($sid = 0, $storeSuperVisit = TRUE, $clerkVisit = TRUE)
    {
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $storeIds = cache($authorization)['admin_user']['store_ids'];
        $groupId = cache($authorization)['admin_user']['group_id'];

        $storeId = $sid ? $sid : $storeId;
        if ($storeId) {
            if ($storeId && in_array($groupId, [STORE_SUPER_ADMIN, STORE_MANAGER])) {
                if ($groupId == STORE_SUPER_ADMIN) {
                    $childs = db('store')->where(['is_del' => 0, 'status' => 1, 'parent_id' => $storeId])->column('store_id');
                    if ($storeSuperVisit && $storeId == $sid) {
                        $childs[] = $storeId;
                    }
                    if ($sid && !in_array($sid, $childs)) {
                        $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                        //$this->error(lang('NO ACCESS'));
                    }
                    if (!in_array($storeId, $childs)) {
                        $childs[] = $storeId;
                    }
                    return $childs;
                }else{
                    if ($storeIds && !in_array($storeId, $storeIds)) {
                        $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                        //$this->error(lang('NO ACCESS'));
                    }
                    return $storeIds;
                }
            }elseif ($groupId == SYSTEM_SUPER_ADMIN || $groupId == EXPERIENCER){
                return TRUE;
            }elseif ($groupId == STORE_CLERK){
                if ($sid && $sid != $storeId) {
                    $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                    //$this->error(lang('NO ACCESS'));
                }
                if (!$clerkVisit) {
                    $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                    //$this->error(lang('NO ACCESS'));
                }
                return $storeId;
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                //$this->error(lang('NO ACCESS'));
            }
        }else{
            return FALSE;
        }
    }
}