<?php
namespace app\api\controller;

use Workerman\Events\Swoole;

/**
 * 相机抠图处理接口
 * @author xiaojun
 */
class Face extends ApiBase
{
    protected $imgFile;
    protected $faceX;
    protected $faceY;
    protected $imgPixel;
    protected $captureTime;
    protected $deviceTime;
    protected $deviceCode;
    protected $faceApi;
    protected $apiType;

    public function __construct(){
        parent::__construct();
        $this->apiType = 'all';
//         $this->apiType = 'tencent';//腾讯云
//         $this->apiType = 'faecplus';//Face++
    }
    /**
     * 请求参数处理
     */
    protected function _checkPostParams()
    {
        parent::_checkPostParams();
        $this->imgFile = $this->postParams && isset($this->postParams['face_img']) ? trim($this->postParams['face_img']) : '';        //人脸图片
        $this->deviceCode = $this->postParams && isset($this->postParams['mac_id']) ? trim($this->postParams['mac_id']): '';          //设备串码
        $this->imgPixel = $this->postParams && isset($this->postParams['img_pixel']) ? trim($this->postParams['img_pixel']): '';      //抓拍抠图的原图分辨率
        $this->captureTime = $this->postParams && isset($this->postParams['timestamp']) ? trim($this->postParams['timestamp']): 0;    //抓拍时间戳
        if (!$this->imgFile) {
            $this->_returnMsg(['code' => 1, 'msg' => 'face_img: 参数缺失']);
        }
        if (!$this->deviceCode  && !isset($this->postParams['store_id'])) {
            $this->_returnMsg(['code' => 1, 'msg' => 'mac_id: 参数缺失']);
        }
        if (!$this->captureTime) {
            $this->_returnMsg(['code' => 1, 'msg' => 'timestamp: 参数缺失']);
        }
        if (strlen($this->captureTime) != 10) {
            $this->_returnMsg(['code' => 1, 'msg' => 'timestamp: 时间戳为10位有效数字']);
        }
        //判断时间戳格式是否正确
        if(strtotime(date('Y-m-d H:i:s',$this->captureTime)) != $this->captureTime) {
            $this->_returnMsg(['code' => 1, 'msg' => 'timestamp: 参数格式错误']);
        }
        $this->faceX = $this->postParams && isset($this->postParams['face_x']) ? trim($this->postParams['face_x']) : '';
        $this->faceY = $this->postParams && isset($this->postParams['face_y']) ? trim($this->postParams['face_y']) : '';
        if ($this->faceX < 0) {
            $this->_returnMsg(['code' => 1, 'msg' => 'face_x: 参数错误']);
        }
        if ($this->faceY < 0) {
            $this->_returnMsg(['code' => 1, 'msg' => 'face_y: 参数错误']);
        }
        $this->deviceTime = $this->captureTime - 8*3600;
        $this->captureTime = time();

    }
    public function index()
    {
        $deviceModel = db('device');
        if(isset($this->postParams['store_id'])){
            $storeId = $this->postParams['store_id'];         //设备授权门店ID
            $deviceId =  '88888888';      //设备ID
            $blockId =  0;          //设备所属区域ID
            $positionType = 3;//1为进店 2为离店 3为店内其它
        }else{
            //判断设备是否存在
            $deviceExist = $deviceModel->where(['device_code' => $this->deviceCode, 'is_del' => 0])->find();
            if (!$deviceExist) {
                $this->_returnMsg(['code' => 1, 'msg' => 'mac_id: 对应设备不存在或已删除']);
            }
            $storeId = $deviceExist ? intval($deviceExist['store_id']) : 0;         //设备授权门店ID
            $deviceId = $deviceExist ?  intval($deviceExist['device_id']) : 0;      //设备ID
            $blockId = $deviceExist ? intval($deviceExist['block_id']): 0;          //设备所属区域ID
            $positionType = $deviceExist ? intval($deviceExist['position_type']) :3;//1为进店 2为离店 3为店内其它
        }

        $deviceFaceModel = db('device_face');

        //判断当前图片是否已经存在
        $imgData = [
            'store_id' => $storeId,
            'block_id' => $blockId,
            'device_id' => $deviceId,
            'device_code' => $this->deviceCode,
            'position_type' => $positionType,
            'img_url' => $this->imgFile,
            'is_del' => 0,
        ];
        $dimgExist = $deviceFaceModel->where($imgData)->find();
        if($dimgExist || (cache('img') == $this->imgFile)){
            $this->_returnMsg(['code' => 1, 'msg' => 'face_img:图片重复']);
        }
        cache('img',$this->imgFile,360);
        $faceApi = new \app\common\api\BaseFaceApi();
        $params = [
            'capture_time'  => $this->captureTime,
            'face_x'        => $this->faceX,
            'face_y'        => $this->faceY,
            'img_pixel'     => $this->imgPixel,
            'block_id'      => $blockId,
            'device_id'     => $deviceId,
            'position_type' => $positionType
        ];
        $result = $faceApi->faceRecognition($this->imgFile, $this->deviceCode, $storeId, $params);
        if ($result['code'] > 0) {
            $this->_returnMsg($result);
        }
        $face = isset($result['face']) ? $result['face'] : [];
        $fuserId = isset($result['fuser_id']) ? $result['fuser_id'] : 0;
        $userId = isset($result['user_id']) ? $result['user_id'] : 0;

        $faceToken = trim($face['face_token']);//人脸唯一标识
        $attributes = $face['attributes'] ? $face['attributes'] : [];    //人脸属性特征

        $genderId = $faceApi->_getDataId('gender', $attributes['gender']['value']);   //性别ID
        $age = $attributes['age']['value'];             //年龄数据
        $emotion = $attributes['emotion'] ? $faceApi->_getDataId('emotion', face_get_max($attributes['emotion'])) : 0;                  //年龄数据
        $ageLevel = $faceApi->_getAgeData($age, 'level');  //年龄等级
        $ethnicity = strtolower($attributes['ethnicity']['value']);
        $ethnicity = $ethnicity === 'india' ? 'asian' : $ethnicity;//人种信息
        $ethnicityId = isset($attributes['ethnicity']) ? $faceApi->_getDataId('ethnicity', $ethnicity) : 0;
        $tags = strtolower($attributes['gender']['value']); //性别标签
        if ($fuserId) {
            //当日用户到访信息
            $result = $faceApi->_dayVisit($storeId, $fuserId, $this->captureTime, $positionType, $this->imgFile, $age, $ageLevel, $genderId, $faceToken, $attributes['facequality'], $emotion);
            $userType = $result && $result['user_type'] ? $result['user_type'] : 0;
            $customeStep = $result && $result['custome_step'] ? $result['custome_step'] : 0;
            $personStep = $result && $result['person_step'] ? $result['person_step'] : 0;
            $stayTimesValue = $result && $result['stay_time_value'] ? $result['stay_time_value'] : 0;
            //用户当日在当前设备抓拍记录
            $result = $faceApi->_dayCapture($storeId, $blockId, $deviceId, $fuserId, $this->captureTime, $age, $ageLevel, $genderId, $ethnicityId, $userType, trim($this->faceX), trim($this->faceY));
            //用户当日在当前门店统计记录
            $result = $faceApi->_dayTotal($storeId, $blockId, $deviceId, $this->captureTime, $customeStep, $personStep, $stayTimesValue, $ageLevel, $genderId, $userType, $ethnicityId);
            $result = $faceApi->_workLog($storeId, $fuserId, $this->captureTime, $this->imgFile);

            $this->_sendNotify($storeId, $fuserId, $this->imgFile);

        }
        $this->_returnMsg(['code' => 0, 'msg' => '图片解析成功', 'face_img' => $this->imgFile]);
    }
    /**
     * 会员到店提醒
     * @param int $storeId
     * @param int $fuserId
     * @param int $captureTime
     * @return boolean
     */
    protected function _sendNotify($storeId, $fuserId, $url)
    {
        //测试
        /*$smsApi = new \app\common\api\SmsApi();
        $phone = '15818688157';
        $param = [
            'name' => '黄益豪',
            'level' => 'VIP'
        ];
        $resul = $smsApi->send($phone, 'RemindShop', $param);
        $push = new \app\common\service\PushBase();
        $name = '黄益豪';
        $level = 'VIP';
        $sendData = [
            'url'            => $url,
            'data'           => "会员 " . $name . "会员等级 " . $level . "进店请予以关注！",
        ];
        $result = $push->sendToUid($storeId, json_encode($sendData));
        return true;*/



        //短信通知
        $smsApi = new \app\common\api\SmsApi();
        $result = db('store_member')->alias('SM')
            ->join([
                ['user U','U.user_id=SM.user_id','left'],
                ['user_grade UG','UG.grade_id=SM.grade_id','left'],
            ])->where([
                ['SM.store_id','=',$storeId],
                ['SM.is_admin','=',0],
                ['SM.is_del','=',0],
                ['SM.fuser_id','=',$fuserId],
            ])->field('U.realname,U.phone,UG.name')->find();

        if(!$result){
            return false;
        }

        $res = db('store_member')->alias('SM')
            ->join([
                ['user U','U.user_id=SM.user_id','left'],
            ])->where([
                ['SM.store_id','=',$storeId],
                ['SM.is_admin','=',2],
                ['SM.group_id','=',3],
                ['SM.is_del','=',0],
            ])->field('U.phone')->select();

        $param = [
            'name' => $result['realname'],
            'level' => $result['name']
        ];
        foreach($res as $kk => $vv){
            $phone = $vv['phone'];
            $resul = $smsApi->send($phone, 'RemindShop', $param);
        }

        //web端通知
        $push = new \app\common\service\PushBase();
        $name = $result['realname'];
        $level = $result['name'];
        $sendData = [
            'url'            => $url,
            'data'           => lang("会员") .":" . $name . lang("会员等级") .":" . $level . lang("进店请予以关注！"),
        ];
        $result = $push->sendToUid($storeId, json_encode($sendData));

        return FALSE;
        //判断当前门店是否存在会员到店提醒配置
        $config = db('config')->where(['is_del' => 0, 'status' => 1, 'config_name' => 'member_reminder'])->value('config_value');
        if (!$config) {
            return FALSE;
        }
        $config = json_decode($config, 1);
        if (!isset($config['tpl']) || !$config['tpl']) {
            return FALSE;
        }
        //获取门店管理员信息
        $manager = db('store_member')->where(['store_id' => $storeId, 'is_del' => 0, 'is_admin' => 1, 'group_id' => ['IN', [STORE_SUPER_ADMIN, SYSTEM_SUPER_ADMIN]]])->find();
        if (!$manager) {
            return FALSE;//门店没有管理员信息则不发送提醒
        }
        $manageUserId = $manager['user_id'];//管理员账户ID
        $manageUser = db('user')->where(['user_id' => $manageUserId, 'is_del' => 0, 'status' => 1])->find();
        if (!$manageUser) {
            return FALSE;
        }
        $flag = FALSE;
        $tpls = [];
        foreach ($config['tpl'] as $key => $value) {
            if ($value > 0) {
                if ($key == 'wechat') {
                    //判断门店管理员是否绑定微信号
                    $wechat = db('user_data')->where(['user_id' => $manageUserId, 'is_del' => 0, 'third_type' => 'wechat', 'status' => 1])->find();
                    if ($wechat) {
                        $flag = TRUE;
                        $tpls[] = $key;
                    }
                }elseif ($key == 'sms'){
                    //判断门店管理员是否绑定手机号
                    if ($manageUser['phone']) {
                        $flag = TRUE;
                        $tpls[] = $key;
                    }
                }
            }
        }
        if ($flag === FALSE) {
            return FALSE;
        }
        //验证成为会员条件
        $userCondition = isset($config['condition']['user']) && $config['condition']['user'] ? intval($config['condition']['user']) : 1;
        switch ($userCondition) {
            case 1://到店即为会员

            break;
            case 2://开通门店会员卡
                //判断当前fuser是否开通当前门店的会员卡
                $smember = db('store_member')->where(['fuser_id' => $fuserId, 'store_id' => $storeId, 'is_del' => 0, 'status' => 1])->find();
                if (!$smember) {
                    return FALSE;
                }
            break;
            case 3://购买过商品(订单已支付)
                //判断用户是否购买过商品
                $user = db('user')->where(['fuser_id' => $fuserId, 'is_del' => 0])->find();
                if (!$user) {
                    return FALSE;
                }
                $order = db('order')->where(['user_id' => $user['user_id'], 'pay_status' => 1])->find();
                if (!$order) {
                    return FALSE;
                }
            break;
            default:
                return FALSE;
            break;
        }
        if (in_array('wechat', $tpls) && isset($wechat) && $wechat) {
            //发送微信模板通知
            #TODO 申请微信模板
            $templateId = '3e-Mz3zDSRCWuRk8BPJJTNbgE2H7aEplpZIPyJgUvTg';
            $url = 'http://www.baidu.com';
            //直接发送会员通知
            $post = [
                'touser'        => $wechat['third_openid'],
                'template_id'   => $templateId,
//                 'url'           => $url,//模板跳转链接
//                 'miniprogram'   => [//跳小程序所需数据，不需跳小程序可不用传该数据
//                     'appid' => '',
//                     'pagepath' => '/pages/user/index'
//                 ],
                'data' => [
                    'keyword1' => [
                        'value' => $wechat['nickname'],
                    ],
                    'keyword2' => [
                        'value' => $manageUser['phone'],
                    ],
                    'keyword3' => [
                        'value' => '',
                    ],
                    'keyword4' => [
                        'value' => '恭喜您开卡成功！',
                    ],
                ],
            ];
//             $result = $this->_sendWechatNotify($post, 'member_reminder');
        }
        if (in_array('sms', $tpls) && $manageUser && $manageUser['phone']) {
            //发送微信模板通知
            #TODO 申请短信模板
//             $params = [
//                 'prize'     => $prizeName,
//                 'time'      => $validTime,
//                 'address'   => $address,
//             ];
//             $result = $this->_sendSmsNotify($manageUser['phone'], $params, 'member_reminder');
        }
        return TRUE;
    }
    /**
     * 处理接口返回信息
     */
    protected function _returnMsg($data, $echo = TRUE){
        $result = parent::_returnMsg($data);
        $responseTime = $this->_getMillisecond() - $this->visitMicroTime;//响应时间(毫秒)
        $addData = [
            'type'          => 'face',
            'request_time'  => $this->requestTime,
            'capture_time'  => $this->deviceTime ? $this->deviceTime: 0,
            'return_time'   => time(),
            'capture_img'   => $this->imgFile ? $this->imgFile : '',
            'device_code'   => $this->deviceCode ? $this->deviceCode : '',
            'img_x'         => $this->postParams && isset($this->faceX) ? $this->faceX : 0,
            'img_y'         => $this->postParams && isset($this->faceY) ? $this->faceY : 0,
            'img_pixel'     => $this->imgPixel ? $this->imgPixel : '',
            'request_params'=> $this->postParams ? json_encode($this->postParams) : '',
            'return_params' => $result,
            'response_time' => $responseTime,
            'error'         => isset($data['code']) ? intval($data['code']) : 0,
        ];
        $apiLogId = db('apilog_device')->insertGetId($addData);
        exit();
    }

}