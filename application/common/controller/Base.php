<?php
namespace app\common\controller;
use think\Controller;
//公共处理
class Base extends Controller
{
	//系统预处理方法
	public function __construct()
    {
    	parent::__construct();
    	$this->initBase();
    }
    public function getVisitData($storeId = 0, $num = 0,$color = false,$storeVisit = "", $setflag = false)
    {
        $storeId = $storeId ? $storeId : 1;
        //异步实时获取投屏数据
//        if($storeId === 1){
//            $storeId = db('store')->where([['is_del','=',0],['status','=',1]])->column('store_id');
//
//        }
        $dataService = new \app\service\service\Dataset();
        $dataService->initialize($storeId);

        $count = 1;

        //获取基础数据
        $normalDataset = $dataService->getNormalDataset($count);
        $totalModel = db('day_total');
        $total = $totalModel->where('store_id' ,'IN', $storeId)->sum('person_total');
        $normalDataset['personTotal'] = $total * $count;
        //获取到店顾客年龄比例
        $ageDataset = $dataService->getAgeDataset(false, $color, $count, $setflag);
        //获取到店顾客性别比例
        $genderDataset = $dataService->getGenderDataset(false, $color, TRUE, $count, $setflag);
        //获取新老客户比例
        $customerDataset = $dataService->getCustomerDataset(false, $color, TRUE, $count, $setflag);
        $field = 'DV.fuser_id, SM.user_id, SM.is_admin, DV.capture_time, U.realname, U.nickname, U.face_img, DV.avatar';
        $field .= ', U.age, DV.age as tempage, U.gender, DV.gender as temgender, DV.emotion';
        $field .= ', DV.visit_counts, DV.recent_in_time, DV.last_out_time, DV.recent_time';
        $datas = $dataService->getPersonDayList($storeId, 0, FALSE, $field, $num);
        $personList = [];
        if ($datas) {
            $baseFaceApi = new \app\common\api\BaseFaceApi();
            foreach ($datas as $key => $value) {
                $nickname = $value['realname'] ? trim($value['realname']) : trim($value['nickname']);
                $showname = $value['user_id'] > 0 ? ($value['is_admin'] > 0 ? lang('员工') : lang('会员')) . ($nickname ? ' (' . $nickname . ')' : '') : lang('游客');
                $gender = ($value['gender'] > 0 ? $value['gender'] : $value['temgender']);
                $outTime = $value['last_out_time'] ? $value['last_out_time'] : $value['recent_time'];
                $emotion = $value['emotion'] ? $value['emotion'] : 0;
//                 $avatar = $value['face_img']? $value['face_img'] : $value['avatar'];
                $avatar = $value['avatar'];
                $personList[] = [
                    'avatar' => $avatar,
                    'name' => $showname,
                    'age' => ($value['age'] > 0 ? $value['age'] : $value['tempage']) . lang('岁'),
                    'gender' => $baseFaceApi->_getDataDetail('gender', $gender, 'name'),
                    'emotion' => $baseFaceApi->_getDataDetail('emotion', $emotion, 'name'),
                    'visit_counts' => $value['visit_counts'],
                    'in_time' => $value['recent_in_time'] ? date('H:i:s', $value['recent_in_time']) : '',
                    'out_time' => $outTime ? date('H:i:s', $outTime) : '',
                ];
            }
        }
        //获取当日客户访问量
        //当日开始时间戳
        //$dayBeginTime = mktime(0, 0, 0, date("m"), date("d")-2, date("y"));
        //huangyihao
        if (input('lang') == 'en-us') {
            $timestamp = time() - 3600 * 8;
            $dayBeginTime = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("y", $timestamp));
        } else {
            $timestamp = time();
            $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
        }

        $offset = 0;//统计开始时间
        //$start = $dayBeginTime + $offset*3600;
        $thistime = $timestamp;
        $index = 24;//统计截止时间
        $visitCount = [];
        $dataset[0] = [
            'name' => lang('访问人次'),
            'type' => 'line',
            'itemStyle' => [
            ],
            'smooth' => 0
        ];
        for ($i = $offset; $i < $index; $i++) {
            if ($i > $offset) {
                $beginTime = $dayBeginTime + (($i - 1) * 3600);//整点开始时间
            } else {
                $beginTime = $dayBeginTime;
            }
            $endTime = $dayBeginTime + ($i * 3600);//整点结束时间
            $showtime = $endTime;
            if ($beginTime > $thistime) {
                continue;
            }
            if ($endTime > $thistime) {
                $showtime = $endTime = $timestamp;
            }
            //huangyihao
            if (input('lang') == 'en-us') {
                $beginTime = $beginTime + 3600 * 8;
                $endTime = $endTime + 3600 * 8;
            }

            if (empty($storeVisit)) {
                $where = [
                    ['store_id', 'IN', $storeId],
                    ['add_time', 'between', [$beginTime, ($endTime - 1)]]
                ];
            } else {
                $where = [
                    ['store_id', 'in', $storeVisit],
                    ['add_time', 'between', [$beginTime, ($endTime - 1)]]
                ];
            }

            //获取时间段门店访问人次
            $cachetime = 24*60*60;
            if ($beginTime <= time() && $endTime >= time()) {
                //获取时间段门店访问人次
                $faceVisit = db('face_token')->where($where)->count();
            }else{
                //获取时间段门店访问人次
                $faceVisit = db('face_token')->where($where)->cache($cachetime)->count();
            }
            $label[] = date('H:i:s', $showtime);
            $faceVisit = $faceVisit > 0 ? $faceVisit * $count : 0;
            $dataset[0]['data'][] = $faceVisit;
//             $data[] = [
//                 'time' => date('H:i:s', $showtime),
//                 'count' => $faceVisit,
//             ];
        }


        $color = $color ? $color : ['#33ccff'];
        $chart = new \app\service\service\Chart("group", [lang('访问人次')], $label, $dataset, $color, false,lang('(time)'),lang('(total visits)'));
        $visitCount = $chart->getOption();
        $data = [
            'normalData'    => $normalDataset,
            'ageData'       => $ageDataset,
            'genderData'    => $genderDataset,
            'customerData'  => $customerDataset,
            'personList'    => $personList,
            'visitCounts'   => $visitCount,
        ];
        return json_encode([
            'status'    => 1,
            'datas'     => $data,
        ]);
    }
    
    //底层通用参数初始化
    protected function initBase() {
        defined('USER')              or define('USER', 0);              //前台会员
        defined('SYSTEM_SUPER_ADMIN')or define('SYSTEM_SUPER_ADMIN', 1);//平台超级管理员
        defined('STORE_SUPER_ADMIN') or define('STORE_SUPER_ADMIN', 2); //连锁店/门店超级管理员
        defined('STORE_MANAGER')     or define('STORE_MANAGER', 3);     //店长
        defined('STORE_CLERK')       or define('STORE_CLERK', 4);       //店员
        
        
        defined('NOW_TIME')or define('NOW_TIME', $_SERVER['REQUEST_TIME']);
    	defined('IS_POST') or define('IS_POST', $this->request->isPost());
    	defined('IS_AJAX') or define('IS_AJAX', $this->request->isAjax());
    	defined('IS_GET')  or define('IS_GET', $this->request->isGet());
    	defined('IS_MOBILE')or define('IS_MOBILE', $this->request->isMobile());
    }
    /**
     * 发送微信小程序模板通知
     * @param array $post
     * @param string $tplType
     * @param array $extra
     * @return boolean
     */
    protected function _sendWechatAppletNotify($post, $tplType, $extra = [])
    {
        if (!$post) {
            return FALSE;
        }
        $notifyModel = db('log_notify');
        $wechatApi = new \app\common\api\WechatApi('wechat_applet');
        $notifyData = [
            'type'      => 'wechat_applet',
            'to_user'   => $post['touser'],
            'tpl_type'  => $tplType,
            'content'   => $post ? json_encode($post) : '',
            'add_time'  => time(),
            'status'     => 0,
        ];
        $notifyId = $notifyModel->insertGetId($notifyData);
        if ($notifyId) {
            $result = $wechatApi->sendAppletTemplateMessage($post);
            if (!$result) {
                $result['error'] = $wechatApi->error;
            }
            if ($extra) {
                $result = $result + $extra;
            }
            $data = [
                'result' => $result ? json_encode($result) : '',
            ];
            if ($result && isset($result['errcode']) && $result['errcode'] === 0) {
                $data['status'] = 1;
            }
            $notifyModel->where(['notify_id' => $notifyId])->update($data);
            return TRUE;
        }else{
            return FALSE;
        }
    }
    /**
     * 发送微信模板通知
     * @param array $post
     * @param string $tplType
     * @param array $extra
     * @return boolean
     */
    protected function _sendWechatNotify($post, $tplType, $extra = [])
    {
        if (!$post) {
            return FALSE;
        }
        $notifyModel = db('log_notify');
        $wechatApi = new \app\common\api\WechatApi('wechat');
        $notifyData = [
            'type'      => 'wechat',
            'to_user'   => $post['touser'],
            'tpl_type'  => $tplType,
            'content'   => $post ? json_encode($post) : '',
            'add_time'  => time(),
            'status'     => 0,
        ];
        $notifyId = $notifyModel->insertGetId($notifyData);
        if ($notifyId) {
            $result = $wechatApi->sendTemplateMessage($post);
            if (!$result) {
                $result['error'] = $wechatApi->error;
            }
            if ($extra) {
                $result = $result + $extra;
            }
            $data = [
                'result' => $result ? json_encode($result) : '',
            ];
            if ($result && isset($result['errcode']) && $result['errcode'] === 0) {
                $data['status'] = 1;
            }
            $notifyModel->where(['notify_id' => $notifyId])->update($data);
            return TRUE;
        }else{
            return FALSE;
        }
    }
    /**
     * 发送短信通知
     * @param string $phone
     * @param array $params
     * @param string $tplType
     * @param array $extra
     * @return boolean
     */
    protected function _sendSmsNotify($phone, $params, $tplType, $extra = [])
    {
        $notifyModel = db('log_notify');
        $smsApi = new \app\common\api\SmsApi();
        $notifyData = [
            'type'      => 'sms',
            'to_user'   => $phone,
            'tpl_type'  => $tplType,
            'content'   => $params ? json_encode($params) : '',
            'add_time'  => time(),
            'status'     => 0,
        ];
        $notifyId = $notifyModel->insertGetId($notifyData);
        if ($notifyId) {
            $result = $smsApi->send($phone, $tplType, $params);
            if ($extra) {
                $result = $result + $extra;
            }
            $data = [
                'result' => $result ? json_encode($result) : '',
            ];
            if ($result && isset($result['Code']) && $result['Code'] == 'OK' && $result['BizId']) {
                $data['status'] = 1;
            }
            $notifyModel->where(['notify_id' => $notifyId])->update($data);
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    /**
     *    导入资源到模板
     */
    protected function import_resource($resources, $spec_type = null)
    {
    	$headtag = '';
    	if (is_string($resources) || $spec_type)
    	{
    		!$spec_type && $spec_type = 'script';
    		$resources = $this->_get_resource_data($resources);
    		foreach ($resources as $params)
    		{
    			$headtag .= $this->_get_resource_code($spec_type, $params) . "\r\n";
    		}
    		$this->headtag($headtag);
    	}
    	elseif (is_array($resources))
    	{
    		foreach ($resources as $type => $res)
    		{
    			$headtag .= $this->import_resource($res, $type);
    		}
    		$this->headtag($headtag);
    	}
    
    	return $headtag;
    }
    
    /**
     *    head标签内的内容
     */
    private function headtag($string)
    {
    	$this->assign('_head_tags', $string);
    }
    /**
     *    获取资源数据
     */
    private function _get_resource_data($resources)
    {
    	$return = array();
    	if (is_string($resources))
    	{
    		$items = explode(',', $resources);
    		array_walk($items, create_function('&$val, $key', '$val = trim($val);'));
    		foreach ($items as $path)
    		{
    			$return[] = array('path' => $path, 'attr' => '');
    		}
    	}
    	elseif (is_array($resources))
    	{
    		foreach ($resources as $item)
    		{
    			!isset($item['attr']) && $item['attr'] = '';
    			$return[] = $item;
    		}
    	}
    
    	return $return;
    }
    
    /**
     *    获取资源文件的HTML代码
     */
    private function _get_resource_code($type, $params)
    {
    	switch ($type)
    	{
    		case 'script':
    			$pre = '<script charset="utf-8" type="text/javascript"';
    			$path= ' src="' . $this->_get_resource_url($params['path'],"js") . '"';
    			$attr= ' ' . $params['attr'];
    			$tail= '></script>';
    			break;
    		case 'style':
    			$pre = '<link rel="stylesheet" type="text/css"';
    			$path= ' href="' . $this->_get_resource_url($params['path'],"css") . '"';
    			$attr= ' ' . $params['attr'];
    			$tail= ' />';
    			break;
    	}
    	$html = $pre . $path . $attr . $tail;
    
    	return $html;
    }
    
    /**
     *    获取真实的资源路径
     */
    private function _get_resource_url($res,$type)
    {
    	$res_par = explode(':', $res);
    	$url_type = $res_par[0];
    	$return  = '';
    	switch ($url_type)
    	{
    		case 'url':
    			$return = $res_par[1];
    			break;
    		case 'lib':
    			$return = '/static/lib/' . $res_par[1];
    			break;
    		case 'base':
    			$return = '/static/base/' . $res_par[1];
    			break;
    		default:
    			$res_path = empty($res_par[1]) ? $res : $res_par[1];
    			$return = "/static/".$this->request->module()."/".$type."/".$res_path;
    			break;
    	}
    
    	return $return;
    }
}
