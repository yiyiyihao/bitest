<?php
namespace app\service\service;
use app\common\api\BaseFaceApi;
use app\service\service\Chart;

/**
 * 数据报表接口
 */
class Dataset{
    
    private $storeId;
    private $blockId;
    private $cameraId;
    
    public $error;
    
    public $dayVisits;//用户访问列表分析
    public $dayTotal;//用户访问统计处理

    public $date;
    public $dayList;
    public $camera;     //当前相机(或者区域)
    public $cameraList;

    private $storeVisit;
    
    /**
     * 初始化查询参数
     * @param number $storeId 门店id
     * @param number $blockId 门店划分区域id
     * @param number $cameraId 门店相机id/串码
     * @param string $date 查询日期
     */
    public function initialize($storeId = 0, $blockId = 0, $cameraId = 0, $date = '')
    {
        if(empty($storeId)){
            echo json_encode(['code'=>1,'msg'=>'门店ID不能为空']);die;
        }
        $this->storeId = $storeId;
        if($blockId){
            //#TODO 验证区域是否归属店铺/或者归属店铺管辖子店铺
            $this->blockId = $blockId;
        }
        if($cameraId){
            //#TODO 验证相机是否归属店铺/或者归属店铺管辖子店铺
            $this->cameraId = $cameraId;
        }
        $this->date = $date ? $date : date('Y-m-d');
//         $this->date = $date ? $date : '2018-11-08';
//         $this->date = date('Y-m-d',strtotime("-2 day"));
        $this->dayVisits = [];

        //初始化全局检索日期范围
        //$this->dayList = $this->getDayList(7, 'day', 'Y-m-d', $this->date);
        $this->dayList = $this->getDayList(7, 'day', 'Y-m-d', date('Y-m-d'));
        //初始化全局处理相机(或区域)列表
        $this->cameraList = $this->getCameraList($storeId);
        $this->camera     = $this->cameraList ? reset($this->cameraList) : '';
    }
    
    /**
     * 检查是否有新人到店
     */
    public function newface() {
        $user = db("user")->where('user_id','>',3)->where('is_del',0)->select();
        return $user;
    }
    
    /**
     * 获取日常客流数据
     */
    public function getNormalDataset($count = 1) {
        $customerTotal = $personTotal = $avaTotal =  $totalStayTime = $avaStayTime = 0;
        if (!$this->dayTotal) {
            $this->_getDayTotal();
        }
        if ($this->dayTotal) {
            $customerTotal = $this->dayTotal['customer_total'];
            $personTotal = $this->dayTotal['person_total'];
            $totalStayTime = $this->dayTotal['stay_times'];
        }
        $avaTotal = $customerTotal && $personTotal ? round($personTotal/$customerTotal, 2) : 0;//人均到访次数
        $avaStayTime = $totalStayTime && $customerTotal ? $totalStayTime/$customerTotal: 0;
        $avaStayTime = $avaStayTime > 0 ? timediff($avaStayTime, 2) : 0;
        $data = [
            'customerTotal' => $customerTotal * $count,  //到店顾客数量
            'personTotal'   => $personTotal* $count,    //到访人次(不去重)
            'aveVisit'      => $avaTotal* $count,       //平均到访次数
            'aveTimeline'   => $avaStayTime* $count,    //平均停留时长(分钟)
        ];
        return $data;
    }
    /**
     * 获取设备热力图数据
     * @param string $deviceCode
     * @param int $startTime
     * @param int $endTime
     * @param string $field
     * @return array
     */
    public function getHotMap($deviceId = '', $day = '', $startTime = 0, $endTime = 0, $field = "device_code, img_x, img_y")
    {
        $where[] = [
            'device_id','=', $deviceId,
        ];
        if ($startTime && $endTime && $startTime < $endTime) {
            $where[] = ['add_time','between', [$startTime, $endTime]];
        }elseif ($day){
            $dayStart = strtotime($day);
            $dayEnd = $dayStart + 3600*24 -1;
            $where[] = ['add_time','between', [$dayStart, $dayEnd]];
        }
        $list = db('face_token')->field($field)->where($where)->select();
        return $list;
    }
    
    /**
     * 获取到店顾客年龄比例
     */
    public function getAgeDataset($return = FALSE,$color = false, $countValue = 1, $setflag = false) {
        $faceApi =  new \app\common\api\FaceApi();
        $ageLvels = $faceApi->ageLvels;
        if (!$this->dayTotal) {
            $this->_getDayTotal();
        }
        $ages = $this->dayTotal && $this->dayTotal['age_json'] ? json_decode($this->dayTotal['age_json'], true): [];
        //计算年龄比例
        $names = $counts = [];
        foreach ($ageLvels as $key => &$value) {
            $names[] = $value['name'];
            if (isset($ages[$key])) {
                $count = $ages[$key];
            }else{
                $count = $value['count'];
            }
            $count = $count * $countValue;
            $counts[] = $count;
            if ($return) {
                unset($ageLvels[$key]['min'], $ageLvels[$key]['max']);
                $ageLvels[$key]['count'] = $count;
            }
        }
        if ($return) {
            return $ageLvels;
        }
        if ($setflag) {
            $data = [
                 'legend'        =>  $names,
                 'data'          =>  $counts,
                 'type'          =>  'bar',
                 'name'          =>  '人数',
             ];
           $chart = New Chart("bar",$names,lang('人数'),$counts,$color, false,lang('(age)'),lang('(number)'));
           $data = $chart->getOption();
        }else{
            $data = [];
        }
        foreach($names as $ke => $val){
            $data[$ke]['name'] = $val;
            $data[$ke]['value'] = $counts[$ke];
        }
        return $data;
    }
    
    /**
     * 获取到店顾客性别比例
     */
    public function getGenderDataset($return = FALSE,$color = FALSE,$legend = true, $countValue = 1, $setflag = false) {
        $faceApi =  new \app\common\api\FaceApi();
        $genders = $faceApi->genders;

        if (!$this->dayTotal) {
            $this->_getDayTotal();
        }
        $genderDatas = $this->dayTotal && $this->dayTotal['gender_json'] ? json_decode($this->dayTotal['gender_json'], true): [];
        $names = $counts = [];
        foreach ($genders as $key => &$value) {
            $names[] = $value['name'];
            if (isset($genderDatas[$key])) {
                $count = $genderDatas[$key];
            }else{
                $count= $value['count'];
            }
            $count = $count * $countValue;
            $dataset[]= [
                'name' => $value['name'],
                'value' => $count,
            ];
        }
        if ($return) {
            return $dataset;
        }
        $color = $color ? $color : ['rgba(0,85,120,0.6)','rgba(225,85,85,0.8)'];
        if ($setflag) {
            $data = [
                'legend'        =>  $names,
                'data'          =>  $dataset,
                'color'         =>  $color,
                'type'          =>  'pie',
                'name'          =>  '性别',
            ];
            $chart = New Chart("pie",$names,lang('性别'),$dataset,$color,$legend);
            $data = $chart->getOption();
        }else{
            $data = $dataset;
        }
        return $data;
    }
    
    /**
     * 获取新老客户比例
     */
    public function getCustomerDataset($return = FALSE,$color = FALSE,$legend = true, $countValue = 1, $setflag = false) {
        $datas = [
            1 => [
                'name' => lang("新客户"),
                'value' => 0,
                'color' => 'rgba(0,85,120,0.6)',
            ],
            2 => [
                'name' => lang('老客户'),
                'value' => 0,
                'color' => 'rgba(225,85,85,0.8)',
            ],
        ];
        if (!$this->dayTotal) {
            $this->_getDayTotal();
        }
        $types = $this->dayTotal && $this->dayTotal['user_type_json'] ? json_decode($this->dayTotal['user_type_json'], true): [];
        $names = [];
        foreach ($datas as $key => &$value) {
            $names[] = $value['name'];
            if (isset($types[$key])) {
                $count = $types[$key];
            }else{
                $count = $value['value'];
            }
            $count = $count * $countValue;
            $datas[$key]['value'] = $count;
            $colors[] = $value['color'];
            unset($value['color']);
        }
        if ($return) {
            return $datas;
        }
        $color = $color ? $color : ['rgba(0,85,120,0.6)','rgba(225,85,85,0.8)'];
        sort($datas);
        if ($setflag) {
            $data = [
                'legend'        =>  $names,
                'data'          =>  $datas,
                'color'         =>  $colors,
                'type'          =>  'pie',
                'name'          =>  '人数',
            ];
            $chart = New Chart("pie",$names,lang('人数'),$datas,$color);
            $data = $chart->getOption();
        }else{
            $data = $datas;
        }
        return $data;
    }
    
    /**
     * 获取客户列表
     */
    public function getPersonDatalist($storeId = 0, $userType = 0, $date = FALSE, $field = FALSE,$page=1,$size=10, $flag = FALSE){
        $date = $date ? $date : $this->date;
        $list = $this->_getVisits($storeId, $userType, $date, $field,$page,$size, $flag);
        return $list;
    }
    
    public function getPersonDayList($storeId, $userType = 0, $date = FALSE, $field = FALSE, $num = 0){
        $date = $date ? $date : $this->date;
        $storeId = $storeId ? $storeId : ($this->storeId ? $this->storeId: 1);
        $where = [
            ['capture_date' ,'=', $date],
//            'DV.store_id' => $storeId,
            ['DV.is_del' ,'=', 0],
        ];

        //huangyihao
        if (is_int($this -> storeVisit) || $this -> storeVisit === true) {
            $where[] = ['DV.store_id','=',$this -> storeVisit];
        }elseif (is_array($this -> storeVisit)){
            $where[] = ['DV.store_id','IN', $this -> storeVisit];
        }else{
            $where[] = ['DV.store_id','IN',$storeId];
        }

        if ($userType) {
            $where[] = ['user_type','=',$userType];
        }
        $field = $field ? $field : 'DV.*, SM.member_id';
        $visitModel = db('day_visit');
        $join = [
            ['store_member SM', 'DV.fuser_id = SM.fuser_id', 'LEFT'],
            ['user U', 'SM.fuser_id = U.fuser_id', 'LEFT'],
        ];
        $order = 'recent_time DESC';
        $order = 'DV.add_time DESC';
        if (!$num) {
            $list = $visitModel->field($field)->alias('DV')->join($join)->where($where)->where('user_type', '<>', 3)->order($order)->select();
        }else{
            $list = $visitModel->field($field)->alias('DV')->join($join)->where($where)->where('user_type', '<>', 3)->limit(0, $num)->order($order)->select();
        }
        return $list;
    }
    
    /**
     * 获取指定客户的行为/数据
     */
    public function getPersonInfo($fuid = 0, $date = FALSE, $storeId = 0){
        $date = $date ? $date : $this->date;
        $where = [
            ['fuser_id','=', $fuid],
            ['is_del','=', 0],
        ];
        //获取用户详情
        $fuser = db('face_user')->where($where)->find();
        //获取用户当天的到访记录(到访时间,到访位置[x,y],到访截图)
        $faceModel = db('face_token');
        $storeId = $storeId ? $storeId : ($this->storeId ? $this->storeId : 1);

        $startTime = strtotime($date);
        $endTime   = strtotime("+1 day",$startTime);
        $faceModel->field("face_token, capture_time, img_x, img_y, img_url, device_code ,name");
        $faceModel->where('capture_time','between',["$startTime" ,"$endTime"]);
        $faceModel->where('fuser_id', $fuid);
//        $faceModel->where('cloud_face_token.store_id', $storeId);
        //huangyihao
        if (is_int($this -> storeVisit) || $this -> storeVisit === true) {
            $faceModel -> where('cloud_face_token.store_id','=',$this -> storeVisit);
        }elseif (is_array($this -> storeVisit)){
            $faceModel -> where('cloud_face_token.store_id','IN', $this -> storeVisit);
        }else{
            $faceModel->where('cloud_face_token.store_id', $storeId);
        }
        $history = $faceModel->join('cloud_store_block','cloud_face_token.block_id = cloud_store_block.block_id','left')->order('cloud_face_token.add_time ASC')->select();
        //获取用户的历史到店记录(日期列表)
        $visitModel = db('day_visit');

        $where = [
            ['V.fuser_id','=', $fuid],
            ['V.is_del','=', 0],
        ];
        if (is_int($this -> storeVisit) || $this -> storeVisit === true) {
            $where[] = ['V.store_id', '=', $this -> storeVisit];
        }elseif (is_array($this -> storeVisit)){
            $where[] = ['V.store_id','IN', $this -> storeVisit];
        }else{
            $where[] = ['V.store_id','=',$storeId];
        }
        $join= [
            ['store S', 'S.store_id = V.store_id', 'LEFT'],
        ];
        
        $list = $visitModel->alias('V')->join($join)->field('V.*, S.name as store_name')->where($where)->order("V.capture_time desc")->select();
        
        $list = $this->_doVisits($list);
        if ($fuser) {
            $faceApi = new BaseFaceApi();
            $fuser['age_level'] = $faceApi->_getAgeData($fuser['age'], 'name');
            $fuser['gender'] = $faceApi->_getDataDetail('gender', $fuser['gender'], 'name');
            if($list){
                $fuser['counts'] = $list[0]['total_visit_counts'];
            }
        }
        $data = [
            'fuser'     =>  $fuser,
            'history'   =>  $history,
            'personList'=>  $list,
        ];
        return $data;
//         pre($list);
        #TODO获取用户的历史消费记录
    }
    
    /**
     * 获取今天实时数据
     */
    public function getTodayDataset($storeId = 0){
        $legend     = [];
        $dataset    = [];
        $label      = [];
        
    }
    
    /**
     * 获取最近一周客流数据
     */
    public function getWeekDataset($storeId = 0, $genderList = false, $agelevelList = false, $ethnicityList = false,$days = 7,$endDate=''){
        $legend     = [];
        $dataset    = [];
        $label      = $this->getDayList($days, 'day', 'Y-m-d', $endDate);;
        //获取相机列表
        $cameraList = $this->cameraList;
        $deviceData = [];
        $genderList = $genderList ? array_filter($genderList) : [];
        $agelevelList = $agelevelList ? array_filter($agelevelList) : [];
        $ethnicityList = $ethnicityList ? array_filter($ethnicityList) : [];

        //huangyihao
        $where = [];
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'];
        $storeIds = cache($authorization)['admin_user']['store_ids'];
        $group_id = cache($authorization)['admin_user']['group_id'];
        if($userId <> 1 && $group_id <> 5){
            $where[] = ['store_id','IN', $storeIds];
//            if (is_int($this -> storeVisit) || $this -> storeVisit === true) {
//                $where[] = ['store_id','=',$this -> storeVisit];
//            }elseif (is_array($this -> storeVisit)){
//                $where[] = ['store_id','IN', $this -> storeVisit];
//            }else{
//                $where[] =['store_id','=', $storeId];
//            }
        }elseif($group_id == 5) { //体验帐号
            $device_ids = explode(',', cache($authorization)['admin_user']['email']);
            $where[] = ['device_id','IN',$device_ids];
        }
        if ($genderList || $agelevelList || $ethnicityList) {
            $flag = TRUE;
//            $where = [
//                'store_id' => $storeId ? $storeId : $this->storeId,
//            ];

            if ($genderList) {
                $where[] = ['gender','IN', $genderList];
            }
            if ($agelevelList) {
                $where[] = ['age_level','IN', $agelevelList];
            }
            if ($ethnicityList) {
                $where[] = ['ethnicity','IN', $ethnicityList];
            }
        }else{
            $flag = FALSE;
        }
        foreach ($label as $key => $value) {
            $deviceArray = [];
            if ($flag) {
                $tempWhere = $where;
                $tempWhere[] = ['capture_date','=',$value];
                $deviceArray = db('day_capture')->where($tempWhere)->group('device_id')->column('device_id, sum(capture_counts)');
            }else{
                #todo  判断超管，跳过storeId
//                $dayTotal = $this->_getDayTotal($storeId, $value);
//                $deviceArray = $dayTotal && isset($dayTotal['device_json']) ? json_decode($dayTotal['device_json'], TRUE) : [];
                $tempWhere = $where;
                $tempWhere[] = ['capture_date','=',$value];
                $deviceArray = db('day_capture')->where($tempWhere)->group('device_id')->column('device_id, sum(capture_counts)');

            }
            $deviceData[$value] = $deviceArray;
        }
        if ($cameraList) {
            foreach ($cameraList as $k=>$v){
                $deviceId = $k;
                $legend[] = $v;
                $cameraList[$k] = [
                    'name'    => $v,
                    'type'    => 'line',
                    'itemStyle' =>  [
//                         'normal'    => ["areaStyle"=>'default']
                    ],
                    'smooth'  => 0.5
                ];
                foreach ($label as $key => $val){
                    $cameraList[$k]['data'][] = $deviceData && isset($deviceData[$val][$deviceId]) ? $deviceData[$val][$deviceId] : 0;
                }
            }
        }
        sort($cameraList);
        $dataset = $cameraList ? $cameraList: [];
        /* $data = [
            'legend'        =>  $legend,
            'label'         =>  $label,
            'data'          =>  $dataset,
            'type'          =>  'group',
        ]; */
//        $chart = New Chart("group", $legend, $label, $dataset, null, true);
//        $data = $chart->getOption();
        $data = [$legend,$label, $dataset];
        return $data;
    }    
    
    /**
     * 获取指定相机 指定日期的客户数
     */
    public function getCameraData($storeId, $cameraId, $date){
//         $totalModel = db('day_capture');
//         $dayTotal = $totalModel->where($field)->find();
        $dayTotal = $this->_getDayTotal($storeId, $date);
        $deviceArray = $dayTotal && isset($dayTotal['device_json']) ? json_decode($dayTotal['device_json'], TRUE) : [];
        return $deviceArray;
        
        $captureModel = db('day_capture');
        $count = $captureModel->field('sum(capture_counts) as capture_counts')->where(['capture_date' => $date, 'device_id' => $cameraId])->where('user_type', '<>', 3)->sum('capture_counts');
        return $count;
    }
    
    /**
     * 获取指定区域 指定日期的客户数
     */
    public function getBlockData($blockId,$date){
        $captureModel = db('day_capture');
        $count = $captureModel->field('sum(capture_counts) as capture_counts')->where(['capture_date' => $date, 'block_id' => $blockId])->where('user_type', '<>', 3)->sum('capture_counts');
        return $count;
    }
    
    /**
     * 获取店铺/区域下的相机列表
     */
    public function getCameraList($storeId = 0){
        $deviceModel = db('device');
        $where[] = [
            ['is_del' ,'=', 0]
        ];
        //huangyihao 投屏数据获取不到子门店的设备
        if(defined('ADMIN_ID')){
            $request = new \think\Request();
            $obj = new \app\api\controller\Api($request);
            $storeVisit = $obj -> _checkStoreVisit();
            //存一个属性，下面的日人数统计要用
            $this -> storeVisit = $storeVisit;
            if (is_int($storeVisit)) {
                $where[] = ['store_id','=',$storeVisit];
            }elseif (is_array($storeVisit)){
                $where[] = ['store_id','IN', $storeVisit];
            }
        }else{
            $where[] = ['store_id','IN',$storeId];
        }

        //原来的
//        if ($storeVisit) {
//            $where['store_id'] = $storeId;
//        }
        $cameraList = $deviceModel->where($where)->column('device_id as id, name');
        return $cameraList;
    }
    
    /**
     * 获取今天之前的日期列表
     */
    public function getDayList($num = 7, $type = 'day', $date = 'Y-m-d', $day = false){
        $list = [];
        for ($i=0; $i < $num; $i++) {
//             $timeNow = strtotime("-".$i." ".$type);
            $timeNow = strtotime("-".$i." ".$type, strtotime($day));
            $list[] = date($date,$timeNow);
        }
        //日期倒序
        sort($list);
        return $list;
    }
    
    //以下为私有方法    

    /**
     * 获取满足条件的用户访问列表
     */
    private function _getVisits($storeid = 0, $userType = 0, $date = FALSE, $field = FALSE,$page=1,$size=10, $flag = FALSE)
    {
        if ($this->dayVisits) {
            return $this->dayVisits;
        }
        $storeId = $storeid ? $storeid : $this->storeId;
        if($date == 'all'){
//            $where[] = ['DV.is_del','=', 0];
        }elseif($date == '1'){
            $where = [
                ['capture_date' ,'=', date('Y-m-d')],
//                ['DV.is_del' ,'=', 0],
            ];
        }else{
            $where = [
                ['capture_date' ,'=', $date ? $date : $this->date],
//            'DV.store_id' => $storeId,
//                ['DV.is_del' ,'=', 0],
            ];
        }
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $group_id = cache($authorization)['admin_user']['group_id'];
//        $storeIds = cache($authorization)['admin_user']['store_ids'];
        if($group_id == 5){ //体验帐号
            $device_ids = explode(',',cache($authorization)['admin_user']['email']);
            $where[] = ['DC.device_id','in',$device_ids];
            $where[] = ['DC.is_del','=',0];
            $field = $field ? $field : 'DC.*, SM.member_id,SM.is_admin,SM.group_id,UG.name as grade_name';
            $this->dayVisits = db('day_capture')->field($field)->alias('DC')->join('store_member SM', 'DC.fuser_id = SM.fuser_id', 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->join('store S','S.store_id = DC.store_id','LEFT')->where($where)->where('user_type', '<>', 3)->group('DC.fuser_id')->order('DC.recent_time desc')->limit(0,25)->select();
//             $this->dayVisits = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id AND SM.store_id = '.$this->storeId, 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->where($where)->where('user_type', '<>', 3)->order('DV.recent_time desc')->select();
            if ($this->dayVisits) {
//                $this->dayVisits = $this->_doVisits();
            }
            return $this->dayVisits;
        }else{
            $where[] = ['DV.is_del','=', 0];
            $where[] = ['DV.store_id','IN',$storeId];
        }
//        else{
//            //huangyihao
//            $where[] = ['DV.is_del','=', 0];
//            if (is_int($this -> storeVisit)) {
//                $where[] = ['DV.store_id','=',$this -> storeVisit];
//            }elseif (is_array($this -> storeVisit)){
//                $where[] = ['DV.store_id','IN', $this -> storeVisit];
//            }else{
//                $where[] = ['DV.store_id','=',$storeId];
//            }
//        }


        if ($userType) {
            $where[] = ['user_type','=',$userType];
        }


        if($date == 'all' || $date == '1' || $flag){
            $field = $field ? $field : 'DV.*, SM.member_id,SM.is_admin,SM.group_id,UG.name as grade_name,S.name as sname';
//             $count = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id AND SM.store_id = '.$this->storeId, 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->join('store S','S.store_id = DV.store_id','LEFT')->where($where)->group('DV.fuser_id')->count();
//             $this->dayVisits = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id AND SM.store_id = '.$this->storeId, 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->join('store S','S.store_id = DV.store_id','LEFT')->where($where)->group('DV.fuser_id')->limit(($page-1)*$size,$size)->order('DV.capture_time desc')->select();
            $count = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id', 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->join('store S','S.store_id = DV.store_id','LEFT')->where($where)->count();
            $this->dayVisits = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id', 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->join('store S','S.store_id = DV.store_id','LEFT')->where($where)->limit(($page-1)*$size,$size)->order('DV.recent_time desc')->select();
            if ($this->dayVisits) {
                $this->dayVisits = $this->_doVisits();
            }
            return ['count'=>$count,'list'=>$this->dayVisits];
        }else{
            $field = $field ? $field : 'DV.*, SM.member_id,SM.is_admin,SM.group_id,UG.name as grade_name,S.name as sname,U.realname';
                        $this->dayVisits = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id', 'LEFT')->join('user U', 'U.user_id = SM.user_id', 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->join('store S','S.store_id = DV.store_id','LEFT')->where($where)->where('user_type', '<>', 3)->order('DV.recent_time desc')->limit(0,25)->select();
//             $this->dayVisits = db('day_visit')->field($field)->alias('DV')->join('store_member SM', 'DV.fuser_id = SM.fuser_id AND SM.store_id = '.$this->storeId, 'LEFT')->join('user_grade UG','UG.grade_id = SM.grade_id','LEFT')->where($where)->where('user_type', '<>', 3)->order('DV.recent_time desc')->select();
            if ($this->dayVisits) {
                $this->dayVisits = $this->_doVisits(false,true);
            }
            return $this->dayVisits;
        }

    }
    
    private function _doVisits($vists = FALSE,$temp = false){
        $vists = $vists ? $vists : $this->dayVisits;
        $faceApi =  new \app\common\api\FaceApi();
        $ageLevels = $faceApi->ageLvels;
        $list = [];
        if(!empty($vists)) {
            foreach ($vists as $key => $value) {
                $block = db('store_block') -> where('is_del','=',0) -> where('store_id','IN',$vists['0']['store_id']) -> column('block_id,name');
                $stayArray = [];
                if (isset($value['stay_json'])) {
                    $vists[$key]['stays'] = $stayArray = $value['stay_json'] ? json_decode($value['stay_json'], TRUE) : [];
                }
                if (isset($value['time_json'])) {
                    $vists[$key]['perstays'] = $this->_getPerStays($stayArray, json_decode($value['time_json'], TRUE), $value['capture_date']);
                }
                $vists[$key]['age_text'] = $ageLevels[$value['age_level']]['name'];
                $vists[$key]['gender_text'] = $faceApi->_getDataDetail('gender', $value['gender'], 'name');
                if (isset($value['stay_times'])) {
                    $vists[$key]['stay_times_text'] = timediff($value['stay_times'], 1);
                }

                if($value['fuser_id']){
                    $return = db('face_token') -> where('fuser_id','=',$value['fuser_id'])->order('add_time desc') -> find();
                    $vists[$key]['block_name'] = $return['block_id'];
                }

                if($value['fuser_id']){
                    $return = db('face_token') -> where('fuser_id','=',$value['fuser_id'])->order('add_time desc') -> find();
                    $vists[$key]['block_name'] = isset($block[$return['block_id']]) ? $block[$return['block_id']] : '';
                }

                if(isset($vists[$key]['group_id']) && $temp){
                    $vists[$key]['role_name'] = empty($vists[$key]['group_id']) ? lang('会员') . "({$vists[$key]['realname']})" : lang('员工') . "({$vists[$key]['realname']})";
                }
                elseif($vists[$key]['total_visit_counts'] > 1){
                    $vists[$key]['role_name'] = lang('访客');
                }
                else{
                    $vists[$key]['role_name'] = lang('新客户');
                }

                //unset($vists[$key]['time_json'], $vists[$key]['stay_json']);
            }
        }
        return $vists;
    }
    /**
     * 
     * @param number $storeId
     * @param string $date
     */
    private function _getPerStays($stayArray = [],$timeArray = [],$date)
    {
        if(empty($stayArray) && empty($timeArray)) return ;
        $newArr = [];
        if(!empty($stayArray)){
            foreach ($stayArray as $k=>$v){
                if(isset($v['in_time']) && isset($v['out_time'])){
                    $newArr[$v['in_time']] = $v;
                }
            }
        }
        if(!empty($timeArray)){
            foreach ($timeArray as $k=>$v){
                if(!empty($v)){
                    foreach ($v as $time){
                        if(!isset($newArr[$time])){
                            $newArr[$time]['pass_time'] = $time;
                        }
                        $newArr[$time]['type']  = $k;
                    }
                }
            }
        }
        if(empty($newArr)) return ;
        asort($newArr);
        $startTime = strtotime($date);
        $endTime   = strtotime("+1 day",$startTime);
        $diffTime  = $endTime - $startTime;
        $timeLine  = [];
        foreach ($newArr as $k=>$v){
            if(isset($v['in_time']) && !empty($v['in_time'])){
                $timeLine[$v['in_time']]['is_stay'] = 0;
                $timeLine[$v['in_time']]['perLine'] = round(($v['in_time'] - $startTime)/$diffTime*100,2);
                $startTime = $v['in_time'];
            }
            if(isset($v['out_time']) && !empty($v['out_time'])){
                $timeLine[$v['out_time']]['is_stay'] = 1;
                $timeLine[$v['out_time']]['perLine'] = round(($v['out_time'] - $startTime)/$diffTime*100,2);
                $timeLine[$v['out_time']]['stayTime']= timediff($v['out_time'] - $startTime,1);
                $timeLine[$v['out_time']]['in_time_text'] = date('H:i:s',$v['in_time']);
                $timeLine[$v['out_time']]['out_time_text'] = date('H:i:s',$v['out_time']);
                $startTime = $v['out_time'];
            }
            //计算本次和上次到访的时间差
            if(isset($v['pass_time']) && !empty($v['pass_time'])){
                $timeLine[$v['pass_time']]['is_stay'] = 0;
                $timeLine[$v['pass_time']]['perLine']   = round(($v['pass_time'] - $startTime)/$diffTime*100,2);
                $lineDiff = $v['pass_time'] - $startTime;
                if($lineDiff < 60){#TODO 这里的时间差后期改为后台可配置参数
                    $diffStart = isset($diffStart) ? $diffStart : $startTime;
                    unset($timeLine[$startTime]);       //清除上次的结束记录
                    unset($timeLine[$v['pass_time']]);  //清除本次的开始记录
                    $startTime = $diffStart;
                }else{
                    $diffStart = null;
                    $startTime = $v['pass_time'];
                }
                $passend = $v['pass_time'] + 1;
                $timeLine[$passend]['is_stay'] = 1;
                $timeLine[$passend]['perLine']   = round(($passend - $startTime)/$diffTime*100,2);
                $timeLine[$passend]['stayTime']= timediff($passend - $startTime,1);;
                $timeLine[$passend]['in_time_text'] = date('H:i:s',$startTime);
                $timeLine[$passend]['out_time_text'] = date('H:i:s',$passend);
                $startTime = $passend;
            }
            if(isset($v['pass_time']) && !empty($v['pass_time'])){
                
            }
        }
        return $timeLine;
    }
    
    private function _getDayTotal($storeId = 0, $date = false)
    {
        if($this->dayTotal && !$storeId && !$date){
            return $this->dayTotal;
        }
        $totalModel = db('day_total');
//        if(input('lang') == 'en-us'){
//            $startTime = strtotime(date('Y-m-d 00:00:00',time()-8*3600)) + (8*3600); //美国0点对应的中国服务器时间
//            $endTime = $startTime + 3600*24;
//            $where['capture_time'] = ['between',[$startTime,$endTime]];
//        }else{
            $where = [
                ['capture_date','=', $date ? $date : $this->date],
                ['store_id' ,'IN', $storeId ? $storeId : $this->storeId],         //原来
            ];
//        }

//        if (is_int($this -> storeVisit) || $this -> storeVisit === true) {
//            $where[] = ['store_id','=',$this -> storeVisit];
//        }elseif (is_array($this -> storeVisit)){
//            $where[] = ['store_id','IN', $this -> storeVisit];
//        }else{
//            $where[] =['store_id','=',$storeId ? $storeId : $this->storeId];
//        }
        $this->dayTotal = $totalModel->where($where)->find();
        return $this->dayTotal;
    }
}
