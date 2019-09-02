<?php
namespace app\api\controller\v1\data;

use ai\face\recognition\Client;
use app\service\service\Dataset;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class Ajaxdata extends Api
{
    private $dataService;
    private $date;
    private $storeId;
    private $storeVisits;
    public $noAuth = ['homedata','getTitle'];
    public function __construct(Request $request){
        parent::__construct($request);
        //取得请求日$date = Request::post('date');期
        $date = isset($this -> postParams['date']) ? $this -> postParams['date'] : date('Y-m-d');
        $this->date = $date;
        //$storeId 之前是从session中去，现在也可以在其登入的接口写入session或者跟token一起写入cache缓存
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'] ?? 1;
        $storeId = cache($authorization)['admin_user']['store_id'] ?? 1;
        $storeIds = cache($authorization)['admin_user']['store_ids'] ?? [1,16,18];

        if($userId == 1){
            $storeVisits = db('store') -> where('is_del','=',0)->where('store_type','=',1) -> field('store_id,name')->select();
        }else{
            $storeVisits = db('store') -> where('is_del','=',0)->where('store_id','in',$storeIds)->where('store_type','=',1) -> field('store_id,name')->select();
        }
        $this->storeVisits = $storeVisits;

        $storeId = isset($this -> postParams['store_id']) ? $this -> postParams['store_id'] : (isset($storeVisits[0]['store_id']) ?$storeVisits[0]['store_id']:$storeId);
        if(count($storeIds) > 1 && !isset($this -> postParams['store_id'])){
            $this->storeId = $storeIds;
        }else{
            $this->storeId = $storeId;
        }
        $this->dataService = new Dataset();
        $this->dataService->initialize($this->storeId, 0, 0, $date);

    }

    /**
     * 异步获取首页数据
     */
    public function homedata(){
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $group_id = cache($authorization)['admin_user']['group_id'] ?? 1;
        $storeIds = cache($authorization)['admin_user']['store_ids'] ?? [18,18];
        if(isset($this->postParams['lang']) && $this->postParams['lang'] == 'en-us'){
            \think\facade\Lang::range('en-us');
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/en-us.php';
            \think\facade\Lang::load($file);
        }else{
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/zh-cn.php';
            \think\facade\Lang::load($file);
        }

        if($group_id == 5){ //体验帐号
            $faceApi =  new \app\common\api\FaceApi();
            $ageLevels = $faceApi->ageLvels;
            $device_ids = explode(',',cache($authorization)['admin_user']['email']);
            $where = [['device_id','in',$device_ids],['capture_date','=',$this->date]];//$device_ids到时候全局替换成体验帐号下的device
            $data1 = db('day_capture')->where($where)->group('age_level')->field('age_level,count(*)')->select();//年龄段比
            $data2 = db('day_capture')->where($where)->group('user_type')->field('user_type,count(*)')->select();//新老用户
            $getVisitData = $this->getVisitData();
            $customerTotal = $getVisitData['people'];  //人数
            $personTotal = $getVisitData['total'];    //人次
            $aveTimeline = 1;
            $aveVisit = $customerTotal && $personTotal ? round($personTotal/$customerTotal, 2) : 0;
            $womans = $getVisitData['people_w'];
            $mans = $customerTotal - $womans;
            $temp['3'] = 0;
            $total = 0;
            if(!empty($data1)){
                foreach($data1 as $k => $v){
                    $temp[$v['age_level']] = $v['count(*)'];
                    $total += $v['count(*)'];
                }
            }

            $temp['3'] = $temp['3'] + ($customerTotal - $total);
            $countValue = 1; //杠杆
            foreach($ageLevels as $key => $value){
                $agedata[$key-1]['name'] = $value['name'];
                if (isset($temp[$key])) {
                    $count = $temp[$key];
                }else{
                    $count = $value['count'];
                }
                $count = $count * $countValue;
                $agedata[$key-1]['value'] = $count;

            }
            $tem['1'] = 0;
            foreach($data2 as $kk => $vv){
                $tem[$vv['user_type']] = $vv['count(*)'];
            }

            $data = [
                'day'           => $this->date,
                'daylist'       => $this->dataService->dayList,
                'normaldata'    => ['aveTimeline'=>$aveTimeline,'aveVisit'=>$aveVisit,'customerTotal'=>$customerTotal,'personTotal'=>$personTotal],
                'agedata'       => $agedata,
                'genderdata'    => [['name'=>'男士','value'=>$mans],['name'=>'女士','value'=>$womans],],
                'customerdata'  => [['name'=>lang('新客户'),'value'=>$tem['1']],['name'=>lang('老客户'),'value'=>$customerTotal-$tem['1']],],
                'personList'    => $this->dataService->getPersonDatalist(),
                'todayVisit'    => $getVisitData,
                'storeVisits'   => $this->storeVisits,
            ];
            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
        }
        // 多门店
        if(count($storeIds) >1 && !isset($this -> postParams['store_id'])){
            $faceApi =  new \app\common\api\FaceApi();
            $ageLevels = $faceApi->ageLvels;
            $where = [['store_id','in',$storeIds],['capture_date','=',$this->date]];//$device_ids到时候全局替换成体验帐号下的device
            $data1 = db('day_capture')->where($where)->group('age_level')->field('age_level,count(*)')->select();//年龄段比
            $data2 = db('day_capture')->where($where)->group('user_type')->field('user_type,count(*)')->select();//新老用户
            $getVisitData = $this->getVisitData();
            $customerTotal = $getVisitData['people'];  //人数
            $personTotal = $getVisitData['total'];    //人次
            $aveTimeline = 1;
            $aveVisit = $customerTotal && $personTotal ? round($personTotal/$customerTotal, 2) : 0;
            $womans = $getVisitData['people_w'];
            $mans = $customerTotal - $womans;
            $temp['3'] = 0;
            $total = 0;
            if(!empty($data1)){
                foreach($data1 as $k => $v){
                    $temp[$v['age_level']] = $v['count(*)'];
                    $total += $v['count(*)'];
                }
            }

            $temp['3'] = $temp['3'] + ($customerTotal - $total);
            $countValue = 1; //杠杆
            foreach($ageLevels as $key => $value){
                $agedata[$key-1]['name'] = $value['name'];
                if (isset($temp[$key])) {
                    $count = $temp[$key];
                }else{
                    $count = $value['count'];
                }
                $count = $count * $countValue;
                $agedata[$key-1]['value'] = $count;

            }
            $tem['1'] = 0;
            foreach($data2 as $kk => $vv){
                $tem[$vv['user_type']] = $vv['count(*)'];
            }

            $data = [
                'day'           => $this->date,
                'daylist'       => $this->dataService->dayList,
                'normaldata'    => ['aveTimeline'=>$aveTimeline,'aveVisit'=>$aveVisit,'customerTotal'=>$customerTotal,'personTotal'=>$personTotal],
                'agedata'       => $agedata,
                'genderdata'    => [['name'=>lang('男士'),'value'=>$mans],['name'=>lang('女士'),'value'=>$womans],],
                'customerdata'  => [['name'=>lang('新客户'),'value'=>$tem['1']],['name'=>lang('老客户'),'value'=>$customerTotal-$tem['1']],],
                'personList'    => $this->dataService->getPersonDatalist(),
                'todayVisit'    => $getVisitData,
                'storeVisits'   => $this->storeVisits,
            ];
            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
        }
        //获取投屏标题
        $title = $this->getTitle($this->storeId);
        $data = [
            'day'           => $this->date,
            'daylist'       => $this->dataService->dayList,
            'normaldata'    => $this->dataService->getNormalDataset(),
            'agedata'       => $this->dataService->getAgeDataset(),
            'genderdata'    => $this->dataService->getGenderDataset(),
            'customerdata'  => $this->dataService->getCustomerDataset(),
            'personList'    => $this->dataService->getPersonDatalist(),
            'todayVisit'    => $this->getVisitData(),
            'storeVisits'   => $this->storeVisits,
            'title'         => $title,
        ];
        $data['todayVisit']['total'] = $data['normaldata']['customerTotal'];
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
    }
    //获取访客详情
    public function getVisitorDetail(Request $request)
    {
        $date = isset($this->postParams['date']) ? trim($this->postParams['date']) : '';
        $storeId = isset($this->postParams['store_id']) ? intval($this->postParams['store_id']) : 0;

        $page = !empty($this -> postParams['page']) ? intval($this -> postParams['page']) : 1;
        $size = !empty($this -> postParams['size']) ? intval($this -> postParams['size']) : 10;
        $userController = new \app\api\controller\v1\member\User($request);
        $result = $userController->detail(TRUE);
        $member = $result ? $result['user'] : [];
        $user = $result ? $result['info'] : [];
        $user['store_name'] = $member ? $member['store_name'] : '';
        //获取所属门店 停留时长
        $storeData = $this->_checkStoreVisit();
        //获取当前用户的访问日期
        $vwhere = [
            ['fuser_id', '=', $user['fuser_id']],
        ];
        $map = [
            ['D.is_del', '=', 0],
        ];
        if (is_int($storeData)) {
            $storeData = $this->userInfo['store_id'];
            $vwhere[] = ['store_id', '=', $storeData];
            $map[] = ['D.store_id', '=', $storeData];
            if ($storeId && $storeData !== $storeId) {
                $this->_returnMsg(['code' => 1, 'msg' => '请求参数错误']);die;
            }
        }elseif (is_array($storeData)){
            $vwhere[] = ['store_id', 'IN', $storeData];
            $map[] = ['D.store_id', 'IN', $storeData];
            if ($storeId && !in_array($storeId, $storeData)) {
                $this->_returnMsg(['code' => 1, 'msg' => '请求参数错误']);die;
            }
        }
        $store = $devices = [];
        $join = [
            ['store S', 'S.store_id = D.store_id', 'INNER'],
        ];
        $deviceList = db('device')->alias('D')->join($join)->where($map)->field('D.store_id, D.device_id, D.block_id, D.name as device_name, S.name as store_name')->select();
        if ($deviceList) {
            foreach ($deviceList as $key => $value) {
                $store[$value['store_id']] = $value['store_name'];
                $devices[$value['device_id']] = $value['device_name'];
            }
        }
        if ($date && $storeId) {
            $day = $date;
            if (is_array($storeData)) {
                $vwhere[] = ['store_id', '=', $storeId];
            }
        }else{
            $date = isset($this->postParams['date']) ? trim($this->postParams['date']) : '';
            $count = db('day_visit')->where($vwhere)->count();
            $dates = db('day_visit')->where($vwhere)->order('add_time DESC')->limit(($page-1)*$size,$size)->field('visit_id, store_id, capture_date, visit_counts, stay_times')->select();
            if ($dates) {
                foreach ($dates as $key => $value) {
                    $dates[$key]['store_name'] = $store && isset($store[$value['store_id']]) ? trim($store[$value['store_id']]) : '';
                    $dates[$key]['stay_times'] = $value['stay_times'] ? timediff($value['stay_times'], 2).' min' : '-';
                    unset($dates[$key]['visit_id']);
                }
                $first = reset($dates);
                $day = $first['capture_date'];
            }
        }
        $pics = [];
        if ($day) {
            $startTime = strtotime($day);
            $endTime = $startTime + 24*60*60 -1 ;
            $where = $vwhere;
            $where[] = ['is_del', '=', 0];
            //获取第一天的访问记录
//             $field = 'FROM_UNIXTIME(capture_time,"%Y-%m-%d %H:%i:%s") as capture_time, device_id, img_url';
            $field = 'FROM_UNIXTIME(capture_time,"%H:%i:%s") as capture_time, device_id, img_url';
            if ($day == date('Y-m-d')) {
                $pics = db('face_token')->field($field)->where($vwhere)->whereBetweenTime('add_time', $startTime, $endTime)->order('add_time DESC')->select();
            }else{
                $cachetime = 24*60*60 * 30;//缓存1个月
                $pics = db('face_token')->field($field)->where($vwhere)->whereBetweenTime('add_time', $startTime, $endTime)->order('add_time DESC')->cache(true, $cachetime)->select();
            }
            if ($pics) {
                foreach ($pics as $key => $value) {
                    $pics[$key]['device_name'] = $devices && isset($devices[$value['device_id']]) ? trim($devices[$value['device_id']]) : '';
                    unset($pics[$key]['device_id']);
                }
            }
        }
        //计算累计到访次数
        $user['total_visits'] = db('day_visit')->where($vwhere)->sum('visit_counts');

        if ($date && $storeId) {
            $return = [
                'pics' => $pics,
            ];
        }else{
            unset($user['is_admin'], $user['user_type'], $user['phone']);
            $return = [
                'user'  =>  $user,
                'count' =>  $count,
                'list'  =>  $dates,
                'page'  =>  $page,
                'pics'  =>  $pics,
            ];
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $return]);die;
    }
    /**
     * 异步获取门店筛选数据
     */
    public function storedata(){
        $params = $this -> postParams;

        $genderList = isset($params['gender']) && $params['gender'] ? explode(',', $params['gender']) : [];
        $agelevelList = isset($params['agelevel']) && $params['agelevel'] ? explode(',', $params['agelevel']) : [];
        $ethnicityList  = isset($params['ethnicity']) ? explode(',', $params['ethnicity']) : [];
//        $days = isset($params['days']) && !empty($params['days']) ? intval($params['days']) : 7;
        $startDate = isset($params['startDate']) && !empty($params['startDate']) ? trim($params['startDate']) : date('Y-m-d',time()-518400); //开始日期默认7天之前
        $endDate = isset($params['endDate']) && !empty($params['endDate']) ? trim($params['endDate']) : date('Y-m-d');//结束日期默认今天
        $days = (strtotime($endDate) - strtotime($startDate) + 86400)/86400;
        if($days > 30){
            $this->_returnMsg(['code' => 1, 'msg' => '日期跨度不得大于30天']);die;
        }
        if(empty($this->dataService)) {
            $this->dataService = new Dataset();
            $this->dataService->initialize($this->storeId);
        }

        //获取设备列表
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $group_id = cache($authorization)['admin_user']['group_id'];
        if($group_id == 5) { //体验帐号
            $device_ids = explode(',', cache($authorization)['admin_user']['email']);
            $cameraList = db('device')->where([['is_del','=',0],['device_id','in',$device_ids]])->column('device_id as id, name');
            $this->dataService->cameraList = $cameraList;
        }

        $weekDataset    = $this->dataService->getWeekDataset($this->storeId, $genderList, $agelevelList, $ethnicityList,$days,$endDate);
        $faceApi    =   new \app\common\api\FaceApi();
        //取得性别列表
        $genders    =   $faceApi->genders;
        //取得年龄列表
        $ageLevels  =   $faceApi->ageLvels;
        //取得人种列表
        $ethnicitys =   $faceApi->ethnicitys;

        $data = [
            'weekData'          => $weekDataset,
            'camera'            => $this->dataService->camera,
            'cameraList'        => $this->dataService->cameraList,
            'dayList'           => array_reverse($this->dataService->dayList),
            'day'               => $this->dataService->date,
            'genders'           => $genders,
            'ageLevels'         => $ageLevels,
            'ethnicitys'        => $ethnicitys,
        ];
	   $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
    }

    /**
     * 获取基础数据
     */
    public function normaldata(){
        //获取基础数据
        $normalDataset      = $this->dataService->getNormalDataset();
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['normalDataset'=>$normalDataset]]);die;
    }

    /**
     * 获取年龄比例
     */
    public function agedata(){
        //获取到店顾客年龄比例
        $ageDataset         = $this->dataService->getAgeDataset();
        //$this->success("请求成功",'',$ageDataset);
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['ageDataset'=>$ageDataset]]);die;
    }

    /**
     * 获取性别比例
     */
    public function genderdata(){
        //获取到店顾客年龄比例
        $genderDataset         = $this->dataService->getGenderDataset();
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['genderDataset'=>$genderDataset]]);die;
    }

    /**
     * 获取新老客户比例
     */
    public function customerdata(){
        //获取到店顾客年龄比例
        $customerDataset         = $this->dataService->getCustomerDataset();
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['customerDataset'=>$customerDataset]]);die;
    }

    /**
     * 获取热力图列表
     */
    public function getmaplist(){
        $params = $this -> postParams;
        $deviceId = isset($params['id']) ? intval($params['id']) : key($this->dataService->cameraList);
        $day = isset($params['date']) ? trim($params['date']) : $this->dataService->date;
        $startTime = isset($params['start_time']) ? trim($params['start_time']) : '';
        $endTime = isset($params['end_time']) ? trim($params['end_time']) : '';
        if(!$deviceId){
            $this -> _returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        #todo  $day改为上面情况下，下面这个判断没有用了
        if (!$day) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            if (!$startTime) {
                $this -> _returnMsg(['code' => 1, 'msg' => '开始时间格式错误']);die;
            }
            if (!$endTime) {
                $this -> _returnMsg(['code' => 1, 'msg' => '结束时间格式错误']);die;
            }
            if ($startTime >= $endTime) {
                $this -> _returnMsg(['code' => 1, 'msg' => '开始时间不能大于等于结束时间']);die;
            }
        }
        $img = db('device_img')->where(['device_id' => $deviceId])->field(('img_url, image_width, image_height'))->order('add_time DESC')->find();
        $img = $img ? $img : [];

        //将区域图的宽度等比定到800px  haungyihao
        $myWidth = isset($params['myWidth']) ? $params['myWidth'] : 800;
        if($img){
            $width = $img['image_width'] ? $img['image_width'] : 1;
            $ratio = $myWidth/$width;
        }
        $ratio = isset($ratio) ? $ratio : 0.4;
        $list = $this->dataService->getHotMap($deviceId, $day, $startTime, $endTime);
        $dataArray = [];
        if($list){
            foreach ($list as $k=>$v){
                $temp = [$v['img_x']*$ratio,$v['img_y']*$ratio,1];
                $dataArray[] = $temp;
            }
        }
        $data = [
            'camera'    => $this->dataService->camera,
            'cameraId'  => $deviceId,
            'cameraList'=> $this->dataService->cameraList,
            'dayList'   => array_reverse($this->dataService->dayList),
            'day'       => $this->dataService->date,
            'startTime' => $startTime,
            'endTime'   => $endTime,
            'mapImg'    => isset($img['img_url']) ? $img : '',
            'mapList'   => $list,
            'mapdata'   => $dataArray,
        ];
        $this -> _returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
    }

    /**
     * 获取轨迹图列表
     */
    public function getorbitlist()
    {
        $params = $this -> postParams;
        $deviceId = isset($params['id']) ? intval($params['id']) : key($this->dataService->cameraList);
        $day = isset($params['date']) ? trim($params['date']) : $this->dataService->date;
        $startTime = strtotime($day);
        $endTime = $startTime + 60*60*24 - 1;
        if(!$deviceId){
            //$this->error('参数错误');
            $this -> _returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        #todo  $day改为上面情况下，下面这个判断没有用了
        if (!$day) {
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            if (!$startTime) {
                //$this->error('开始时间格式错误');
                $this -> _returnMsg(['code' => 1, 'msg' => '开始时间格式错误']);die;
            }
            if (!$endTime) {
                //$this->error('结束时间格式错误');
                $this -> _returnMsg(['code' => 1, 'msg' => '结束时间格式错误']);die;
            }
            if ($startTime >= $endTime) {
                //$this->error('开始时间不能大于等于结束时间');
                $this -> _returnMsg(['code' => 1, 'msg' => '开始时间不能大于等于结束时间']);die;
            }
        }
        $img = db('device_img')->where(['device_id' => $deviceId])->field(('img_url, image_width, image_height'))->order('add_time DESC')->find();
        $img = $img ? $img : [];

        $where = [
            'is_del' => 0,
//            'device_id' => $deviceId,
            'device_id' => $deviceId,
//            'device_id' => ['in',$deviceIds],      //huangyihao
            'capture_date' => $day
        ];
        $field = 'fuser_id, age, age_level, gender, capture_date';
        $userList = db('day_capture')->field($field)->where($where)->select();

        //将区域图的宽度等比定到800px  haungyihao
        $_width = input('myWidth');
        $myWidth = isset($_width) ? $_width : 800;
        if($img){
            $width = $img['image_width'];
            $ratio = $myWidth/(empty($width)?1900:$width);
        }
        $ratio = isset($ratio) ? $ratio : 0.4;
        if ($userList) {
            foreach ($userList as $key => $value) {
                $fuserId = intval($value['fuser_id']);
                $where1 = [
                    ['device_id','=', $deviceId],
                    ['fuser_id','=', $fuserId],
                    ['capture_time','between', [$startTime, $endTime]]
                ];
                $field = 'fuser_id, img_x, img_y';
                $orbits = db('face_token')->field($field)->where($where1)->order('capture_time ASC')->select();
                if(isset($orbits)){
                    foreach($orbits as $kk => $vv ){
                        $orbits[$kk]['img_x'] = $vv['img_x'] * $ratio;
                        $orbits[$kk]['img_y'] = $vv['img_y'] * $ratio;
                    }
                }
                $userList[$key]['orbits'] = $orbits ? $orbits : [];
            }
        }

        $data = [
            'camera'    => $this->dataService->camera,
            'cameraId'  => $deviceId,
            'cameraList'=> $this->dataService->cameraList,
            'dayList'   => array_reverse($this->dataService->dayList),
            'day'       => $this->dataService->date,
            'startTime' => $startTime,
            'endTime'   => $endTime,
            'mapImg'    => isset($img['img_url']) ? $img : '',
            'mapList'   => $userList,
        ];

        $this -> _returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
    }

    /**
     * created by huangyihao
     * @description 投屏数据接口
     * @param int $storeId
     * @param int $num
     * @param bool $color
     * @param string $storeVisit
     * @return false|string
     */
    public function getVisitData($storeId = 0, $num = 0,$color = false,$storeVisit = "")
    {
        $storeId = $storeId ? $storeId : $this->storeId;
        $count = 1;
        //huangyihao
//        if(input('lang') == 'en-us'){
//            $timestamp = time() - 3600*8;
//            $dayBeginTime = mktime(0, 0, 0, date("m",$timestamp), date("d",$timestamp), date("y",$timestamp));
//        }else{
            $timestamp = time();
            $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
//        }

        $offset = 0;//统计开始时间
        $thistime = $timestamp;
        $index = 24;//统计截止时间
        $visitCount = [];
        $total = 0;
        for($i = $offset; $i < $index; $i++){
            if ($i > $offset) {
                $beginTime = $dayBeginTime + (($i-1) * 3600);//整点开始时间
            }else{
                $beginTime = $dayBeginTime;
            }
            $endTime  = $dayBeginTime + ($i * 3600);//整点结束时间
            $showtime = $endTime;
            if ($beginTime > $thistime) {
                continue;
            }
            if ($endTime > $thistime) {
                $showtime = $endTime = $timestamp;
            }
            //huangyihao
//            if(input('lang') == 'en-us'){
//                $beginTime = $beginTime + 3600*8;
//                $endTime = $endTime + 3600*8;
//            }
            $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
            $group_id = cache($authorization)['admin_user']['group_id'] ?? 1;
            $storeIds = cache($authorization)['admin_user']['store_ids'] ?? [18,18];
            if($group_id == 5){//体验帐号
                $device_ids = explode(',',cache($authorization)['admin_user']['email']);
                $where = [
                    ['device_id' ,'in', $device_ids],
                    ['add_time' ,'between', [$beginTime, ($endTime-1)]]
                ];
            }
//            elseif(count($storeIds) > 1){
//                $where = [
//                    ['store_id' ,'IN', $storeId],
//                    ['add_time' ,'between', [$beginTime, ($endTime-1)]]
//                ];
//            }
            else{
                $where = [
                    ['store_id' ,'IN', $storeId],
                    ['add_time' ,'between', [$beginTime, ($endTime-1)]]
                ];
            }


//            $where = [
//                ['device_id' ,'in', $devices],
//                ['add_time' ,'between', [$beginTime, ($endTime-1)]]
//            ];

            //获取时间段门店访问人次
            $where_man[] = ['gender','=',1];
            $cachetime = 24*60*60;
            if ($beginTime <= time() && $endTime >= time()) {
                //获取时间段门店访问人次
                $faceVisit = db('face_token')->where($where)->count();
                $man = db('face_token')->where($where)->where($where_man)->count();
            }else{
                //获取时间段门店访问人次
                $faceVisit = db('face_token')->where($where)->cache($cachetime)->count();
                $man = db('face_token')->where($where)->cache($cachetime)->where($where_man)->count();
            }

            $faceVisit = $faceVisit > 0 ? $faceVisit * $count : 0;
            $man = $man > 0 ? $man * $count : 0;
            $dataset[$i]['label'] = date('H:i:s', $showtime);
            $dataset[$i]['line'] = $faceVisit;
            $dataset[$i]['man'] = $man;
            $dataset[$i]['women'] = $faceVisit-$man;
            $total += $faceVisit;
        }
        if($group_id == 5){//体验帐号
            $people = db('face_token')->where([['device_id' ,'in', $device_ids],['add_time' , 'between', [strtotime($this->date), strtotime($this->date)+86400]]])->group('fuser_id')->count();//人数
            $people_w = db('face_token')->where([['device_id' ,'in', $device_ids],['add_time' , 'between', [strtotime($this->date), strtotime($this->date)+86400]],['gender','=',2]])->group('fuser_id')->count();//人数

            return ['total'=>$total,'count'=>$dataset,'people'=>$people,'people_w'=>$people_w];
        }elseif(count($storeIds) > 1){
            $people = db('face_token')->where([['store_id' ,'in', $storeIds],['add_time' , 'between', [strtotime($this->date), strtotime($this->date)+86400]]])->group('fuser_id')->count();//人数
            $people_w = db('face_token')->where([['store_id' ,'in', $storeIds],['add_time' , 'between', [strtotime($this->date), strtotime($this->date)+86400]],['gender','=',2]])->group('fuser_id')->count();//人数

            return ['total'=>$total,'count'=>$dataset,'people'=>$people,'people_w'=>$people_w];
        }


        return ['total'=>$total,'count'=>$dataset];
    }

    public function getUsers()
    {
        $params = $this -> postParams;
        $page   = !empty($params['page']) ? intval($params['page']) : 1;
        $size   = !empty($params['size']) ? intval($params['size']) : 10;
        $date   = isset($params['date']) && !empty($params['date']) ? trim($params['date']) : 'all';
        $data = $this->dataService->getPersonDatalist($this->storeId, 0, $date, FALSE,$page,$size, TRUE);
        $list = $data? $data['list'] : [];
        if ($list) {
            foreach ($list as $key => $value) {
                if (empty($value['member_id'])) {
                    $list[$key]['user_type'] = '访客';
                    $list[$key]['type'] = 0;
                }elseif ($value['is_admin'] === 0){
                    $list[$key]['user_type'] = '会员';
                    $list[$key]['type'] = 1;
                }else{
                    //                         $list[$key]['user_type'] = $value['group_name'];
                    $list[$key]['user_type'] = '员工';
                    $list[$key]['type'] = 2;
                }
                $list[$key]['member_id'] = intval($list[$key]['member_id']);
                $list[$key]['is_admin'] = intval($list[$key]['is_admin']);
                unset($list[$key]['group_name']);
            }
            $data['list'] = $list;
        }
        $data['page'] = $page;
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $data]);die;
    }

    public function delPerson()
    {
        $params = $this -> postParams;
        $visit_id = isset($params['visit_id']) ? $params['visit_id'] : '';
        if(!$visit_id){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $res = db('day_visit') -> where('visit_id','=',$visit_id) -> update(['is_del' => 1]);

        if(!$res){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['visit_id'=>$visit_id]]);die;

    }

    /**
     * 获取指定区域 指定日期的客户数，区域比对
     */
    public function getBlockData()
    {
        $date = isset($this -> postParams['date']) ? $this -> postParams['date'] : date('Y-m-d');
        $blockId = isset($this -> postParams['blockId']) ? $this -> postParams['blockId'] : 0;
        $count = db('day_capture')->field('sum(capture_counts) as capture_counts')->where(['capture_date' => $date, 'block_id' => $blockId])->where('user_type', '<>', 3)->sum('capture_counts');
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['date'=>$date,'block'=>$blockId,'count'=>$count]]);die;
    }

    /**
     * 获取指定相机 指定日期的客户数，设备比对
     */
    public function getCameraData()
    {
        $date = isset($this -> postParams['date']) ? $this -> postParams['date'] : date('Y-m-d');
        $cameraId = isset($this -> postParams['cameraId']) ? $this -> postParams['cameraId'] : 0;
        $count = db('day_capture')->field('sum(capture_counts) as capture_counts')->where(['capture_date' => $date, 'device_id' => $cameraId])->where('user_type', '<>', 3)->sum('capture_counts');
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['date'=>$date,'cameraId'=>$cameraId,'count'=>$count]]);die;
    }

    // 门店对比
    public function getStoreData()
    {
        $date = isset($this -> postParams['date']) ? $this -> postParams['date'] : date('Y-m-d');
        $storeId = isset($this -> postParams['storeId']) ? $this -> postParams['storeId'] : 0;
        $count = db('day_capture')->field('sum(capture_counts) as capture_counts')->where(['capture_date' => $date, 'store_id' => $storeId])->where('user_type', '<>', 3)->sum('capture_counts');
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['date'=>$date,'storeId'=>$storeId,'count'=>$count]]);die;
    }

    /**
     * created by
     * @description  获取菜单
     */
    public function get_menu()
    {
        if(isset($this->postParams['lang']) && $this->postParams['lang'] == 'en-us'){
            \think\facade\Lang::range('en-us');
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/en-us.php';
            \think\facade\Lang::load($file);
        }else{
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/zh-cn.php';
            \think\facade\Lang::load($file);
        }

        $userInfo = $this->userInfo;
        if($userInfo['group_id'] == 1){
            $obj = new \app\service\service\Purview();
            $menu = $obj->menu();
        }else{
            $groupInfo = db("user_group")->where(['ugroup_id' => $userInfo['group_id']])->find();

            $menuData = json_decode($groupInfo['menu'],true);
            if(!empty($menuData)){
                foreach($menuData as $k => $v){
                    $menuData[$k]['title'] = lang($v['title']);
                    if(is_array($v['menuItemList'])){
                        foreach($v['menuItemList'] as $key => $value){
                            $menuData[$k]['menuItemList'][$key]['name'] = lang($value['name']);
                        }
                    }
                }
            }
            $menu = $menuData;
        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $menu]);die;
    }

    //临时投屏标题,以后搞后台设置
    public function getTitle($storeId=0)
    {
        $storeId = $storeId ?? $this->storeId;
        if($storeId == 24){
            $title = ['title_left'=>lang('简播·值得看大数据'),'title_center'=>lang('简播·值得看智慧门店')];
//            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $title]);die;
            return $title;
        }
        $title = ['title_left'=>lang('万佳安·值得看大数据'),'title_center'=>lang('万佳安·值得看智慧门店')];
//        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $title]);die;
        return $title;
    }

}
