<?php
namespace app\api\controller;

use think\Facade\Request;

/**
 * App接口
 * @author xiaojun
 */
class App extends ApiBase
{
    var $method;
    var $reduceStock;
    var $page;
    var $pageSize;

    var $signKeyList;
    var $signKey;
    var $fromSource;
    public function __construct(){
        parent::__construct();
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origin = array(
            'http://m.bi.cn',
            'http://m.bi.com',
            'http://mn.bi.com',
            'http://test.com',
        );
        if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);
            header('Access-Control-Allow-Methods:POST');
            header('Access-Control-Allow-Headers:x-requested-with,content-type');
        }
        $this->_checkPostParams();
        $this->method = trim($this->postParams['method']);
        $this->reduceStock = 1;//加入购物车时减少商品库存
        $this->reduceStock = 2;//下单时减少商品库存
        $this->page = isset($this->postParams['page']) && $this->postParams['page'] ? intval($this->postParams['page']) : 0;
        $this->pageSize = isset($this->postParams['page_size']) && $this->postParams['page_size'] ? intval($this->postParams['page_size']) : 0;
        if ($this->pageSize > 50) {
            $this->_returnMsg(['code' => 1, 'msg' => '单页显示数量(page_size)不能大于50']);
        }
        $this->signKey = isset($this->postParams['signkey']) && $this->postParams['signkey'] ? trim($this->postParams['signkey']) : '';
        //客户端签名密钥get_nonce_str(12)
        $this->signKeyList = array(
            'Applets'   => '8c45pve673q1',
            'H5'        => 'hsktz5jkuxcq',
            'Android'   => 'r4q1xpri0clt',
            'Ios'       => 'usn9es45fxhn',
            'TEST'      => 'ds7p7auqyjj8',
        );
        $this->verifySignParam($this->postParams);
        //请求参数验证
        foreach($this->signKeyList as $key => $value) {
            if($this->signKey == $value) {
                $this->fromSource = trim($key);
                break;
            }else{
                continue;
            }
        }
    }
    public function index()
    {
        if (!$this->method) {
            $this->_returnMsg(['code' => 1, 'msg' => '接口方法(method)缺失']);
        }
        if ($this->method == 'index' || substr($this->method, 0, 1) == '_') {
            $this->_returnMsg(['code' => 1, 'msg' => '接口方法(method)错误']);
        }
        $method = $this->method;
        //判断方法是否存在
        if (!method_exists($this, $method)) {
            $this->_returnMsg(['code' => 1, 'msg' => '接口方法(method)错误']);
        }
        $this->$method();
    }
    //根据第三方账号openid返回账号信息
    protected function authorizedDetail()
    {
        $thirdType  = isset($this->postParams['third_type']) ? trim($this->postParams['third_type']) : '';
        $thirdOpenid= isset($this->postParams['third_openid']) ? trim($this->postParams['third_openid']) : '';
        $thirdTypes = [
            'wechat_applet' => '微信小程序',
        ];
        if (!isset($thirdTypes[$thirdType])) {
            $this->_returnMsg(['code' => 1, 'msg' => '第三方授权登录类型(third_type)错误']);
        }
        //判断userData表第三方账号是否存在
        $where = [
            'third_openid'  => $thirdOpenid,
            'third_type'    => $thirdType,
            'is_del'        => 0,
        ];
        $userDataModel = db('user_data');
        $exist = $userDataModel->where($where)->find();
        if ($exist){
            $userId = $exist['user_id'];
            $profile = $this->getUserProfile($exist['openid']);
            $this->_returnMsg(['code' => 0, 'profile' => $profile]);
        }else{
            $this->authorizedLogin();
            //$this->_returnMsg(['code' => 1,'msg' => '无对应用户信息']);
        }
    }
    
    //第三方登录
    protected function authorizedLogin()
    {
        $thirdType      = isset($this->postParams['third_type']) ? trim($this->postParams['third_type']) : '';
        $thirdOpenid    = isset($this->postParams['third_openid']) ? trim($this->postParams['third_openid']) : '';
        $nickname       = isset($this->postParams['nickname']) ? trim($this->postParams['nickname']) : '';
        $avatar         = isset($this->postParams['avatar']) ? trim($this->postParams['avatar']) : '';
        $gender         = isset($this->postParams['gender']) ? trim($this->postParams['gender']) : 0;
        $unionid        = isset($this->postParams['unionid']) ? trim($this->postParams['unionid']) : '';
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) ? intval($this->postParams['sid']) : 1;
        if (!$thirdType){
            $this->_returnMsg(['code' => 1, 'msg' => '第三方授权登录类型(third_type)缺失']);
        }
        if (!$thirdOpenid){
            $this->_returnMsg(['code' => 1, 'msg' => '第三方账号唯一标识(third_openid)缺失']);
        }
        $thirdTypes = [
            'wechat_applet' => '微信小程序',
        ];
        if (!isset($thirdTypes[$thirdType])) {
            $this->_returnMsg(['code' => 1, 'msg' => '第三方授权登录类型(third_type)错误']);
        }
        //判断userData表第三方账号是否存在
        $where = [
            'third_openid'  => $thirdOpenid,
            'third_type'    => $thirdType,
            'is_del'        => 0,
        ];
        $userDataModel = db('user_data');
        $userModel = db('user');
        $exist = $userDataModel->where($where)->find();
        $userId = 0;
        $createFlag = FALSE;
        if (!$exist){
            if ($unionid) {
                $info = $userDataModel->where([['unionid' ,'=', $unionid], ['is_del' ,'=', 0], ['third_type' ,'<>', $thirdType]])->find();
                if ($info) {
                    $userId = isset($info['user_id']) && $info['user_id'] ? intval($info['user_id']) : 0;
                }
            }
        }else{
            $userId = isset($exist['user_id']) && $exist['user_id'] ? intval($exist['user_id']) : 0;
        }
        $userService = new \app\common\service\User();
        $phone = $fuser = '';
        $openid = $userService->_getUserOpenid();
        if (!$userId){
            $data = [
                'nickname'      => $nickname,
                'avatar'        => $avatar,
                'gender'        => $gender,
                'uncheck_name'  => TRUE,
                'store_id'      => $storeId,
            ];
            $userId = $userService->register($openid, FALSE, $data);
            if (!$userId) {
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);
            }
        }else{
            $userinfo = db('user')->where(['user_id' => $userId])->find();
        }
        $update = [];
        $userUpdate = ['last_login_time' => time()];
        if (!$exist) {
            $params = [
//                'store_id'      => $storeId,
                'user_id'       => $userId,
                'nickname'      => $nickname,
                'avatar'        => $avatar,
                'gender'        => $gender,
                'openid'        => $openid,
                'third_openid'  => $thirdOpenid,
                'third_type'    => $thirdType,
                'unionid'       => $unionid,
                'add_time'      => time(),
                'update_time'   => time(),
            ];
            $udataId = $userDataModel->insertGetId($params);
            if (!$udataId) {
                $this->_returnMsg(['code' => 1, 'msg' => $userDataModel->e]);
            }
        }else{
            if ($nickname) {
                if ($exist['nickname'] != $nickname) {
                    $update['nickname'] = $nickname;
                }
                if ($userinfo && $userinfo['nickname'] != $nickname) {
                    $userUpdate['nickname'] = $nickname;
                }
            }
            if ($avatar) {
                if ($exist['avatar'] != $avatar) {
                    $update['avatar'] = $avatar;
                }
                if ($userinfo && $userinfo['avatar'] != $avatar) {
                    $userUpdate['avatar'] = $avatar;
                }
            }
            if ($gender) {
                if ($exist['gender'] != $gender) {
                    $update['gender'] = $gender;
                }
                if ($userinfo && $userinfo['gender'] != $gender) {
                    $userUpdate['gender'] = $gender;
                }
            }
            if ($unionid) {
                if ($exist['unionid'] != $unionid) {
                    $update['unionid'] = $unionid;
                }
                if ($userinfo && $userinfo['unionid'] != $unionid) {
                    $userUpdate['unionid'] = $unionid;
                }
            }
            if ($update) {
                $result = $userDataModel->where(['udata_id' => $exist['udata_id']])->update($update);
            }
            $openid = $exist['openid'];
        }
        // 更新登录信息
        $result = $userModel->where(['user_id' => $userId])->update($userUpdate);
        
        $profile = $this->getUserProfile($openid);
        $this->_returnMsg(['code' => 0, 'profile' => $profile]);
    }
    //短信验证码发送
    protected function sendSms()
    {
        $phone = isset($this->postParams['phone']) ? trim($this->postParams['phone']) : '';
        $type = isset($this->postParams['type']) ? trim($this->postParams['type']) : 'register';
        if (!$phone) {
            $this->_returnMsg(['code' => 1, 'msg' => '手机号(phone)缺失']);
        }
        if (!$type) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码(type)缺失']);
        }
        //验证手机号格式
        $userService = new \app\common\service\User();
        $result = $userService->_checkFormat(['phone' => $phone]);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);
        }
        $types = [
            'register'  => '注册',
            'activity'  => '参加活动',
        ];
        if(!isset($types[$type])){
            $this->_returnMsg(['code' => 1, 'msg' => '短信类型(type)错误']);
        }
        if ($type == 'activity') {
            //验证手机号是否参与活动
            $winModel = db('win_log');
            //判断手机号是否已中奖
            $data = [
                'type' => 2,
                'phone' => $phone,
            ];
            $exist = $winModel->where($data)->find();
            if ($exist) {
                $this->_returnMsg(['code' => 0, 'join' => 1, 'msg' => '当前手机号已参与过抽奖活动，请使用其它号码参与']);
            }
        }
        //判断短信验证码发送时间间隔
        $exist = db('log_code')->where(['phone' => $phone])->order('add_time DESC')->find();
        if ($exist && $exist['add_time'] + 60 >= time()) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码发送太频繁，请稍后再试']);
        }
        $smsApi = new \app\common\api\SmsApi();
        $code = $smsApi->getSmsCode();
        $param = [
            'code' => $code,
        ];
        $data = [
            'code'  => $code,
            'phone' => $phone,
            'type'  => $type,
            'add_time' => time(),
            'status' => 0,
        ];
        $smsId = db('log_code')->insertGetId($data);
        if ($smsId === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码发送异常']);
        }
        $result = $smsApi->send($phone, 'send_code', $param);
        $data = [
            'result' => $result ? json_encode($result) : '',
        ];
        if ($result && isset($result['Code']) && $result['Code'] == 'OK' && $result['BizId']) {
            $data['status'] = 1;
            db('log_code')->where(['sms_id' => $smsId])->update($data);
            $this->_returnMsg(['code' => 0, 'msg' => '验证码发送成功,5分钟内有效']);
        }else{
            db('log_code')->where(['sms_id' => $smsId])->update($data);
            $this->_returnMsg(['code' => 1, 'msg' => '验证码发送失败:'.$result['Message']]);
        }
    }
    //短信验证码验证
    protected function checkSmsCode()
    {
        $phone = isset($this->postParams['phone']) ? trim($this->postParams['phone']) : '';
        $code = isset($this->postParams['code']) ? trim($this->postParams['code']) : '';
        $type = isset($this->postParams['type']) ? trim($this->postParams['type']) : 'register';
        if (!$phone) {
            $this->_returnMsg(['code' => 1, 'msg' => '手机号(phone)缺失']);
        }
        if (!$code) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码(code)缺失']);
        }
        if (strlen($code) != 6) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码(code)格式错误']);
        }
        if (!$type) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码类型(type)缺失']);
        }
        $types = [
            'register'  => '注册',
            'activity'  => '参加活动',
        ];
        if(!isset($types[$type])){
            $this->_returnMsg(['code' => 1, 'msg' => '短信类型(type)错误']);
        }
        if ($type == 'activity') {
            $winModel = db('win_log');
            //判断手机号是否已抽奖
            $data = [
                'type' => 2,
                'phone' => $phone,
            ];
            $exist = $winModel->where($data)->find();
            if ($exist){
                $this->_returnMsg(['code' => 1, 'msg' => '当前手机号码已参与过活动']);
            }
        }
        //判断验证码是否存在
        //判断短信验证码是否存在
        $exist = db('log_code')->where(['phone' => $phone, 'code' => $code, 'type' => $type])->order('add_time DESC')->find();
        if (!$exist) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码错误']);
        }
        db('log_code')->where(['phone' => $phone, 'type' => $type])->delete();
        if ($exist['add_time'] + 5*60 < time()) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码已失效']);
        }
        //删除当前手机号已失效的验证码
        db('log_code')->where(['phone' => $phone, 'add_time' => ['<', time()-5*60]])->delete();
        $this->_returnMsg(['code' => 0, 'msg' => '验证成功']);
    }
    //手机号绑定
    protected function bindPhone()
    {
        $user = $this->_checkOpenid();
        $openid = $user['openid'];
        $phone = isset($this->postParams['phone']) ? trim($this->postParams['phone']) : '';
        $fromid = isset($this->postParams['fromid']) ? trim($this->postParams['fromid']) : '';
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) ? intval($this->postParams['sid']) : 1;
        if (!$phone) {
            $this->_returnMsg(['code' => 1, 'msg' => '手机号(phone)缺失']);
        }
        if ($user['third_type'] == 'wechat_applet' && !$fromid) {
            $this->_returnMsg(['code' => 1, 'msg' => '小程序表单提交标志(fromid)缺失']);
        }
        if ($user['phone']) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前账户已绑定手机号,不能重复绑定']);
        }
        //判断手机号是否已经绑定其它账号
        $where = ['phone' => $phone, 'is_del' => 0];
        if ($storeId == 2) {
            $where['store_id'] = $storeId;
        }
        $exist = db('user')->where($where)->find();
        if ($exist) {
            $this->_returnMsg(['code' => 1, 'msg' => '手机号已绑定其他账号，不能再绑定啦~']);
        }
        $data = ['phone' => $phone];
        if ($phone && $openid == $user['username']) {
            $data['username'] = $phone;
        }
        $result = db('user')->where(['user_id' => $user['user_id']])->update($data);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '手机号定失败，请稍后重试']);
        }else{
            //展会流程:手机号绑定成功后,会员卡开卡(默认门店)
            $userService = new \app\common\service\User();
            $cardNo = $userService->_getUserCardNo();
            //判断门店会员是否存在
            $data = [
                'fuser_id'  => $user['fuser_id'],
                'user_id'   => $user['user_id'],
                'card_no'   => $cardNo,
                'store_id'  => $storeId,
                'add_time'  => time(),
                'update_time'=> time(),
            ];
            $result = db('store_member')->insertGetId($data);
            if ($user['third_type'] == 'wechat_applet' && $fromid) {
                $templateId = '3e-Mz3zDSRCWuRk8BPJJTNbgE2H7aEplpZIPyJgUvTg';
                //直接发送会员通知
                $post = [
                    'touser'        => $user['third_openid'],
                    'template_id'   => $templateId,
                    'page'          => '/pages/user/index',
                    'form_id'       => $fromid,
                    'data' => [
                        'keyword1' => [
                            'value' => $user['nickname'],
                        ],
                        'keyword2' => [
                            'value' => $phone,
                        ],
                        'keyword3' => [
                            'value' => $cardNo,
                        ],
                        'keyword4' => [
                            'value' => '恭喜您开卡成功！',
                        ],
                    ],
                ];
                $this->_sendWechatAppletNotify($post, 'register_notice', $data);
            }
            $this->_returnMsg(['code' => 0, 'msg' => '手机号绑定成功', 'profile' => ['card_no' => $cardNo]]);
        }
    }
    //上传人脸图片解析并绑定
    protected function faceDetect()
    {
        $user = $this->_checkOpenid();
        $file = request()->file('face_img');
        $fromid = isset($this->postParams['fromid']) ? trim($this->postParams['fromid']) : '';
        if (!$file) {
            $this->_returnMsg(['code' => 1, 'msg' => '请选择上传的头像']);
        }
        if ($user['third_type'] == 'wechat_applet' && !$fromid) {
            $this->_returnMsg(['code' => 1, 'msg' => '小程序表单提交标志(fromid)缺失']);
        }
        $userModel = db('user');
        //判断当前用户是否绑定人脸头像
        if ($user['fuser_id']) {
            $this->_returnMsg(['code' => 0, 'msg' => '当前账号已经绑定人脸头像信息', 'profile' => ['fuser' => 1, 'phone' => trim($user['phone']), 'exist' => 1]]);
        }
        // 要上传图片的本地路径
        $faceImg = $file->getRealPath();
        $name = $file->getInfo('name');
        $fileSize = $file->getInfo('size');
        $ext = pathinfo($name, PATHINFO_EXTENSION);  //后缀
        //图片上传到七牛app\api\v1\controller
        $upload = new \app\api\controller\v1\Upload();
        $result = $upload->qiniuUpload($faceImg, 'wechat_face_'.$name, ''.$fileSize, 'avatar_thumb');
        if (!$result || !$result['status']) {
            $this->_returnMsg(['code' => 1, 'msg' => $result['info']]);
        }
        $faceImg = isset($result['file']) ? $result['file'] : '';
        $faceThumb = isset($result['thumb']) ? $result['thumb'] : $faceImg;
        if (!$faceImg) {
            $this->_returnMsg(['code' => 1, 'msg' => '头像不存在']);
        }
        $faceApi = new \app\common\api\BaseFaceApi();
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) > 0 ? intval($this->postParams['sid']) : 1;
        $result = $faceApi->faceRecognition($faceImg, '', $storeId);
        if (isset($result['code'])) {
            $this->_returnMsg($result);
        }
        $fuserId = isset($result['fuser_id']) ? $result['fuser_id'] : 0;
        if (!$fuserId) {
            $this->_returnMsg(['code' => 1, 'msg' => '图片解析异常，请重新拍照']);
        }
        //判断解析的人脸用户是否已经绑定过账号
        $exist = $userModel->where(['fuser_id' => $fuserId, 'is_del' => 0])->find();
        if ($exist) {
            $this->_returnMsg(['code' => 1, 'msg' => '系统匹配的人脸信息已经绑定账号']);
        }
        if ($user['third_type'] == 'wechat_applet' && $fromid) {
            //人脸表单formid记录数据库用来发中奖模板消息
            $dataArray = [
                'formid' => $fromid,
                'time' => time(),
            ];
            $formdata = json_encode($dataArray);
            $udata = [
                'form_data' => trim($formdata),
                'update_time' => time(),
            ];
            $result = db('user_data')->where(['openid' => $user['openid']])->update($udata);
        }
        $data = [
            'fuser_id' => $fuserId,
            'face_img' => $faceImg,
        ];
        $result = db('user')->where(['user_id' => $user['user_id']])->update($data);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '系统异常，请稍后重试']);
        }
        $this->_returnMsg(['code' => 0, 'msg' => '人脸信息解析并绑定成功', 'profile' => ['fuser' => 1, 'phone' => trim($user['phone'])]]);
    }
    //更新个人资料
    protected function updateUserProfile()
    {
        $user = $this->_checkOpenid();
        $realname = isset($this->postParams['realname']) ? trim($this->postParams['realname']) : '';
        if (!$realname) {
            $this->_returnMsg(['code' => 1, 'msg' => '用户真实姓名(realname)缺失']);
        }
        $age = isset($this->postParams['age']) ? trim($this->postParams['age']) : '';
        $email = isset($this->postParams['email']) ? trim($this->postParams['email']) : '';
        $userService = new \app\common\service\User();
        $result = $userService->update($user['user_id'], FALSE, ['realname' => $realname, 'age' => $age, 'email' => $email]);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);
        }
        $this->_returnMsg(['code' => 0, 'msg' => '资料更新成功']);
    }
    //用户个人信息
    protected function getUserProfile($openid = '')
    {
        $user = $this->_checkOpenid($openid);
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) > 0 ? intval($this->postParams['sid']) : 1;
        if ($storeId) {
            $store = $this->_checkStore($storeId);
        }
        $where = ['user_id' => $user['user_id'], 'is_del' => 0];
        if ($storeId && isset($store) && $store) {
            $where['store_id'] = $storeId;
        }
        $cardNo = db('store_member')->where($where)->value('card_no');
        $profile = [
            'openid'    => $user['openid'],
            'card_no'   => trim($cardNo),
            'nickname'  => $user['nickname'],
            'gender'    => $user['gender'],
            'phone'     => $user['phone'],
            'avatar'    => $user['avatar'],
            'face_img'  => $user['face_img'],
            'fuser'     => $user['fuser_id'] ? 1: 0,
            'realname'  => $user['realname'],
            'age'       => $user['age'],
            'email'     => $user['email'],
        ];
        if ($openid) {
            return $profile;
        }
        $this->_returnMsg(['profile' => $profile]);
    }
    //获取门店详情数据
    protected function getStoreDetail($sid = 0)
    {
        $store = $this->_checkStore(false, 'store_id, name');
        $this->_returnMsg(['detail' => $store]);
    }
    //获取门店统计数据
    protected function getStoreDatas()
    {
        $obj = \think\Container::get('app') -> controller('\think\Lang');
        if(isset($this->postParams['lang']) && $this->postParams['lang'] == 'en-us'){
            $obj -> range('en-us');
            $file = dirname(dirname(dirname(__FILE__))).'/admin/lang/en-us.php';
            $obj -> load($file);
        }else{
            $file = dirname(dirname(dirname(__FILE__))).'/admin/lang/zh-cn.php';
            $obj -> load($file);
        }
        $color = ['rgba(255,255,255,0.6)','rgba(255,179,179,0.8)'];
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) ? intval($this->postParams['sid']) : 1;
        $result = $this->getVisitData($storeId, 6, $color, '', TRUE);
        $result = $result ? json_decode($result, 1) : [];
        if (!isset($result['status']) || !$result['status']) {
            $this->_returnMsg(['code' => 1, 'msg' => '数据获取失败']);
        }
        $this->_returnMsg(['code' => 0, 'datas' => $result['datas']]);
    }
    //获取商品列表
    protected function getGoodsList()
    {
        $name = isset($this->postParams['name']) && trim($this->postParams['name']) ? trim($this->postParams['name']) : '';
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) ? intval($this->postParams['sid']) : 0;
        $where = [
            ['is_del' ,'=', 0],
            ['status' ,'=', 1],
        ];
        if ($name) {
            $where[] = ['name','like','%'.$name.'%'];
        }
        if ($storeId > 0){
            $store = $this->_checkStore($storeId, false);
            $where[] = ['store_id','=',$storeId];
        }
        $filed = 'goods_id, name, goods_sn, thumb, imgs, min_price, max_price, goods_stock, sales';
        $list = $this->_getModelList(db('goods'), $where, $filed, 'sort_order ASC, add_time DESC');
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['imgs'] = $value['imgs'] ? json_decode($value['imgs'], TRUE) : [];
            }
        }
        $this->_returnMsg(['list' => $list]);
    }
    //获取商品详情
    protected function getGoodsDetail($gid = 0)
    {
        $goodsId = $gid ? $gid : (isset($this->postParams['goods_id']) ? intval($this->postParams['goods_id']) : 0);
        if ($goodsId <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品ID(goods_id)缺失']);
        }
        $goods = db('goods')->where(['is_del' => 0, 'goods_id' => $goodsId])->find();
        if (!$goods){
            $this->_returnMsg(['code' => 1, 'msg' => '商品不存在或已删除']);
        }
        $goods['specs_json'] = $goods['specs_json'] ? json_decode($goods['specs_json'], TRUE) : [];
        $goods['imgs'] = $goods['imgs'] ? json_decode($goods['imgs'], TRUE) : [];
        
        $data = $this->getGoodsSkus($goodsId);
        if (is_int($data)) {
            $goods['sku_id'] = $data;
            $goods['skus'] = [];
        }elseif (is_array($data)){
            $goods['sku_id'] = 0;
            $goods['skus'] = $data;
        }
        unset($goods['is_del'], $goods['add_time'], $goods['update_time']);
        if ($gid) {
            return $goods;
        }
        $this->_returnMsg(['detail' => $goods]);
    }
    //获取商品规格属性
    protected function getGoodsSkus($gid = false)
    {
        $goodsId = $gid ? $gid : (isset($this->postParams['goods_id']) ? intval($this->postParams['goods_id']) : 0);
        if ($goodsId <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品ID(goods_id)缺失']);
        }
        $goods = db('goods')->where(['is_del' => 0, 'goods_id' => $goodsId])->find();
        if (!$goods){
            $this->_returnMsg(['code' => 1, 'msg' => '商品不存在或已删除']);
        }
        if (!$goods['status']) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品已下架']);
        }
        $where = [['is_del' ,'=', 0], ['status' ,'=', 1], ['goods_id' ,'=', $goodsId]];
        if (!$gid) {
            $where[] = ['spec_value','<>', ''];
        }
        $skus = db('goods_sku')->field('sku_id, sku_name, sku_sn, sku_thumb, sku_stock, price, spec_value, sales')->order('sort_order ASC, update_time DESC')->where($where)->select();
        if ($gid) {
            if ($skus && count($skus) == 1) {
                $sku = reset($skus);
                if ($sku && $sku['spec_value'] == "") {
                    return $sku['sku_id'];
                }
            }
            return $skus;
        }
        $this->_returnMsg(['skus' => $skus]);
    }
    //添加商品至购物车
    protected function addToCart()
    {
        $user = $this->_checkUser();
        $sku = $this->_checkSku();
        $num = isset($this->postParams['num']) ? intval($this->postParams['num']) : 0;
        if ($num <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品数量(num)必须大于0']);
        }
        $skuStock = $sku['sku_stock'];
        if ($skuStock <= 0 || $skuStock < $num) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品库存不足(剩余库存:'.$skuStock.')']);
        }

        $userId = $user['user_id'];
        $skuId = $sku['sku_id'];
        //判断购物车商品是否存在
        $cart = db('cart')->where(['user_id' => $userId, 'sku_id' => $skuId])->find();
        if ($this->reduceStock == 1) {
            $goodsModel = new \app\common\model\Goods();
            $result = $goodsModel->_setGoodsStock($sku, -$num);
            if ($result === FALSE) {
                $this->_returnMsg(['code' => 1, 'msg' => '添加商品至购物车失败，请稍后重试']);
            }
        }else{
            $result = TRUE;
        }
        if ($cart) {
            $total = $cart['num'] + $num;
            $result = db('cart')->where(['cart_id' => $cart['cart_id']])->update(['update_time' => time(), 'num' => $total]);
        }else{
            $result = db('cart')->insertGetId([
                'user_id'   => $userId,
                'store_id'  => $sku['store_id'],
                'goods_id'  => $sku['goods_id'],
                'sku_id'    => $skuId,
                'num'  => $num,
                'add_time'  => time(),
                'update_time'  => time(),
            ]);
        }
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '添加商品至购物车失败']);
        }else{
            $carts = $this->_getCartDatas();
            $this->_returnMsg(['msg' => '添加商品至购物车成功', 'datas' => $carts]);
        }
    }
    //购物车商品数量(增加或减少)
    protected function setCartNum()
    {
        $user = $this->_checkUser();
        $cartId = isset($this->postParams['cart_id']) ? intval($this->postParams['cart_id']) : 0;
        if (!$cartId){
            $this->_returnMsg(['code' => 1, 'msg' => '购物车Id(cart_id)缺失']);
        }
        $sku = $this->_checkSku(FALSE);
        $type = isset($this->postParams['type']) ?  strtolower(trim($this->postParams['type'])): '';
        if (!$type){
            $this->_returnMsg(['code' => 1, 'msg' => '修改商品类型(type)缺失']);
        }
        $num = isset($this->postParams['num']) ? intval($this->postParams['num']) : 0;
        if ($num <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品数量(num)必须大于0']);
        }

        //判断购物车商品是否存在
        $cart = db('cart')->where(['user_id' => $user['user_id'], 'cart_id' => $cartId, 'sku_id' => $sku['sku_id']])->find();
        if (!$cart) {
            $this->_returnMsg(['code' => 1, 'msg' => '购物车内商品不存在,请刷新后重试']);
        }
        if ($type == 'inc') {
            $lastnum = $cart['num'] + $num;
            $skuStock = $sku['sku_stock'];
            if ($skuStock <= 0 || $skuStock < $lastnum) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品库存不足(剩余库存:'.$skuStock.')']);
            }
        }elseif ($type == 'dec'){
            $lastnum = $cart['num'] - $num;
            $min = 1;
            if ($cart['num'] <= $min || $lastnum <= 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品数量已达最小值('.$min.')']);
            }
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '修改商品类型(type)错误']);
        }
        $result = db('cart')->where(['cart_id' => $cartId])->update(['update_time' => time(), 'num' => [$type, $num]]);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品数量更新失败']);
        }else{
            if ($this->reduceStock == 1) {
                $goodsModel = new \app\common\model\Goods();
                if ($type == 'inc') {
                    $result = $goodsModel->_setGoodsStock(['sku_id' => $cart['sku_id'], 'goods_id' => $cart['goods_id']], -$num);
                }elseif ($type == 'dec'){
                    $result = $goodsModel->_setGoodsStock(['sku_id' => $cart['sku_id'], 'goods_id' => $cart['goods_id']], $num);
                }
            }
            $this->_returnMsg(['msg' => '操作成功', 'sku_num' => $lastnum]);
        }
    }
    //删除购物车商品(批量)
    protected function delCartSku($cartIds = '', $return = false)
    {
        $user = $this->_checkUser();
        $cartIds = $cartIds ? $cartIds : (isset($this->postParams['cart_ids']) ? trim($this->postParams['cart_ids']) : 0);
        if (!$cartIds){
            $this->_returnMsg(['code' => 1, 'msg' => '购物车Id(cart_ids)缺失']);
        }
        $cartIds = $cartIds ? explode(',', $cartIds) : [];
        $cartIds = $cartIds ? array_filter($cartIds) : [];
        $cartIds = $cartIds ? array_unique($cartIds) : [];
        if (!$cartIds){
            $this->_returnMsg(['code' => 1, 'msg' => '购物车Id(cart_ids)缺失']);
        }
        $userId = $user['user_id'];
        //判断购物车商品是否存在
        $list =  db('cart')->where([['user_id' ,'=', $userId], ['cart_id' , 'IN', $cartIds]])->select();
        if (count($list) != count($cartIds)) {
            $this->_returnMsg(['code' => 1, 'msg' => '购物车商品删除错误']);
        }
        $result = db('cart')->where([['cart_id' , 'IN', $cartIds]])->delete();
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '购物车商品删除失败']);
        }else{
            if ($this->reduceStock == 1) {
                $goodsModel = new \app\common\model\Goods();
                foreach ($list as $key => $value) {
                    $result = $goodsModel->_setGoodsStock(['sku_id' => $value['sku_id'], 'goods_id' => $value['goods_id']], $value['num']);
                }
            }
//            if ($result) {
//                return TRUE;
//            }
            if(!$result) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品库存操作失败']);
            }
            $carts = $this->_getCartDatas();
            if($return){
                return true;
            }
            $this->_returnMsg(['msg' => '操作成功',  'datas' => $carts]);
        }
    }
    //清理购物车失效商品(商品下架/商品禁用/商品库存为0为失效商品)
    protected function clearCart()
    {
        $user = $this->_checkUser();
        //判断是否存在失效商品
        $this->postParams['expired'] = 1;
        $expires = $this->_getCartDatas();
        if (!$expires) {
            $this->_returnMsg(['code' => 1, 'msg' => '购物车内无失效商品']);
        }
        $cartIds = $skus = [];
        foreach ($expires as $key => $value) {
            $cartIds[] = $value['cart_id'];
        }
        $result = db('cart')->where([['cart_id' , 'IN', $cartIds]])->delete();
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '购物车失效商品清除失败']);
        }else{
            if ($this->reduceStock == 1) {
                $goodsModel = new \app\common\model\Goods();
                foreach ($expires as $key => $value) {
                    $result = $goodsModel->_setGoodsStock(['sku_id' => $value['sku_id'], 'goods_id' => $value['goods_id']], $value['num']);
                }
            }
            $this->_returnMsg(['msg' => '清除失效商品成功']);
        }
    }
    //获取购物车商品数据
    protected function getCartList()
    {
        $return = $this->_getCartDatas();
        $this->_returnMsg(['datas' => $return]);
    }
    protected function getCartNum()
    {
        $user = $this->_checkUser();
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) > 0 ? intval($this->postParams['sid']) : 0;
        if ($storeId) {
            $store = $this->_checkStore();
        }
        $join = [['goods_sku S', 'C.sku_id = S.sku_id', 'INNER'], ['goods G', 'G.goods_id = C.goods_id', 'INNER']];
        $field = 'C.cart_id, C.store_id, S.sku_id, G.goods_id, G.name, S.sku_name, S.price, C.num, S.sku_thumb, G.thumb, S.sku_stock, S.spec_value, G.is_del as gdel, G.status, S.is_del as sdel';
        $where[] = ['user_id' ,'=', $user['user_id']];
        if ($storeId && isset($store) && $store) {
            $where[] = ['C.store_id','=',$storeId];
            $where[] = ['S.store_id','=',$storeId];
        }
        $where[] = ['G.is_del','=',0];
        $where[] = ['G.status','=',1];
        $where[] = ['G.goods_stock','>', 0];
        $num = db('cart')->alias('C')->join($join)->field($field)->where($where)->sum('num');
//         $num = db('cart')->where($where)->sum('num');
        $this->_returnMsg(['num' => $num]);
    }
    //创建订单
    protected function createOrder()
    {
        $user = $this->_checkUser();
        $from = isset($this->postParams['from']) ? trim($this->postParams['from']): 'cart';             //下单来源
        $skuIds = isset($this->postParams['sku_ids']) ? trim($this->postParams['sku_ids']): '';         //商品属性ID列表
        $addressId = isset($this->postParams['address_id']) ? trim($this->postParams['address_id']): '';//商品收货地址
        $submit = isset($this->postParams['submit']) ? intval($this->postParams['submit']): 0;          //是否确认下单
        
        if (!in_array($from, ['cart', 'goods'])) {
            $this->_returnMsg(['code' => 1, 'msg' => '下单来源(from)错误']);
        }
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' =>  '商品属性ID(sku_ids)缺失']);
        }
        $skuIds = $skuIds ? explode(',', $skuIds) : [];
        $skuIds = $skuIds ? array_filter($skuIds) : [];
        $skuIds = $skuIds ? array_unique($skuIds) : [];
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品属性ID(sku_ids)数据错误']);
        }
        if ($addressId) {
            $address = $this->_checkAddress($user['user_id'], $addressId);
        }
        if ($from == 'goods') {
            //立即购买时仅能购买一件商品
            if (count($skuIds) != 1) {
                $this->_returnMsg(['code' => 1, 'msg' => '仅能购买一件商品']);
            }
            $num = isset($this->postParams['num']) ? intval($this->postParams['num']) : 0;
            if ($num <= 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品购买数量(num)必须大于0']);
            }
            $skuId = intval($skuIds[0]);
            $sku = $this->_checkSku($skuId, 'sku_id, sku_stock, goods_stock');
            $stock = min($sku['sku_stock'], $sku['goods_stock']);
            //判断商品库存
            if ($stock <= 0 || $stock < $num) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品库存不足(剩余库存:'.$stock.')']);
            }
            $carts = $this->_getCartDatas($user, $skuId, FALSE, $num);
        }else{
            $carts = $this->_getCartDatas($user, $skuIds);
        }
        $storeIds = $carts['store_ids'] ? array_filter($carts['store_ids']): [];
        $storeIds = $storeIds ? array_unique($storeIds): [];
        if (count($storeIds) > 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '请勿跨店购买商品']);
        }
        if (!$submit) {
            $addressInfo = $this->_getDefaultAddress($user ,$addressId);
            $carts['address'] = $addressInfo;
            $this->_returnMsg(['datas' => $carts]);
        }else{
            //选择收货地址
            if (!$addressId) {
                $this->_returnMsg(['code' => 1, 'msg' =>  '收货地址ID(address_id)缺失']);
            }
            if ($carts['all_amount'] <= 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '订单支付金额不能小于等于0']);
            }
            //创建订单
            $orderSn = $this->_buildOrderSn();
            $data = [
                'order_sn'      => $orderSn,
                'user_id'       => $user['user_id'],
                'goods_amount'  => $carts['all_amount'],
                'delivery_amount' => $carts['delivery_amount'],
                'real_amount'   => $carts['pay_amount'],
                'address_name'  => $address['name'],
                'address_phone' => $address['phone'],
                'address_detail'=> $address['region_name'].' '.$address['address'],
                'add_time'      => time(),
                'update_time'   => time(),
                'extra'         => '',
            ];
            $orderModel = db('order');
            $orderSubModel = db('order_sub');
            $orderSkuModel = db('order_sku');
            $orderLogModel = db('order_log');
            $database = new \think\Db;
            $database::startTrans();
            try{
                $orderService = new \app\common\service\Order();
                $skus = $storeIdArray = $cartIds = [];
                $orderId = $orderModel->insertGetId($data);
                if ($orderId === false) {
                    $this->_returnMsg(['code' => 1, 'msg' =>  '订单创建失败']);
                }
                foreach ($carts['list'] as $key => $value) {
                    $storeId = $key;
                    $storeIdArray[$storeId] = $storeId;
                    $subSn = $this->_buildOrderSn(TRUE);
                    $subData = [
                        'sub_sn'        => $subSn,
                        'user_id'       => $user['user_id'],
                        'order_id'      => $orderId,
                        'order_sn'      => $orderSn,
                        'store_id'      => $storeId,
                        'goods_amount'  => $value['detail']['sku_amount'],
                        'delivery_amount' => $value['detail']['delivery_amount'],
                        'real_amount'   => $value['detail']['pay_amount'],
                        'add_time'      => time(),
                        'update_time'   => time(),
                    ];
                    $subId = $orderSubModel->insertGetId($subData);
                    if (!$subId) {
                        break;
                    }
                    if ($value) {
                        foreach ($value['skus'] as $k1 => $v1) {
                            $goodsAmount = $v1['num']*$v1['price'];
                            $deliveryAmount = isset($v1['delivery_amount']) ? $v1['delivery_amount'] : 0;
                            $skuInfo = $this->getGoodsDetail($v1['goods_id']);
                            $skuData = [
                                'sub_id'        => $subId,
                                'sub_sn'        => $subSn,
                                'user_id'       => $user['user_id'],
                                'store_id'      => $storeId,
                                'order_id'      => $orderId,
                                'order_sn'      => $orderSn,
                                
                                'goods_id'      => $v1['goods_id'],
                                'sku_id'        => $v1['sku_id'],
                                'sku_name'      => $v1['name'],
                                'sku_thumb'     => $skuInfo['thumb'] ? $skuInfo['thumb'] : '',
//                                 'sku_spec'      => $v1['sku_name'],
                                'sku_spec'      => $v1['spec_value'],
                                'sku_info'      => $skuInfo ? json_encode($skuInfo) : '',
                                'num'           => $v1['num'],
                                'price'         => $v1['price'],
                                'pay_price'     => $v1['pay_price'],
                                'delivery_amount' => $deliveryAmount,
                                'real_amount'   => $goodsAmount+$deliveryAmount,
                                
                                'add_time'      => time(),
                                'update_time'   => time(),
                            ];
                            $oskuId = $orderSkuModel->insertGetId($skuData);
                            if (!$oskuId) {
                                break;
                            }
                            $skus[$k1] = [
                                'sku_id'    => $v1['sku_id'],
                                'goods_id'  => $v1['goods_id'],
                                'num'       => $v1['num'],
                            ];
                            if (isset($v1['cart_id']) && $v1['cart_id']) {
                                $cartIds[] = $v1['cart_id'];
                            }
                        }
                        $logId = $orderService->orderLog($subData, $user, '创建订单', '提交购买商品并生成订单');
                        $trackId = $orderService->orderTrack($subData, 0, '订单已提交, 系统正在等待付款');
                    }
                }
                $storeIdArray = $storeIdArray ? array_filter($storeIdArray) : [];
                $storeIds = $storeIdArray ? implode(',', $storeIdArray) : '';
                if ($storeIds) {
                    $result = $orderModel->where(['order_id' => $orderId])->update(['store_ids' => $storeIds]);
                }
                $database::commit();
                if ($from == 'goods' || ($this->reduceStock == 2 && $skus)) {
                    $goodsModel = new \app\common\model\Goods();
                    foreach ($skus as $key => $value) {
                        $result = $goodsModel->_setGoodsStock($value, -$value['num']);
                    }
                }
                if ($from == 'cart' && $skus && $cartIds) {
                    //清理购物车商品
                    $cartIds = implode(',', $cartIds);
                    $result = $this->delCartSku($cartIds, true);
                }
                $this->_returnMsg(['order_sn' => $orderSn, 'msg' => '订单创建成功']);
            }catch(\Exception $e){
                $error = $e->getMessage();
                $database::rollback();
                $this->_returnMsg(['code' => 1, 'msg' => '订单创建失败 '.$error]);
            }
        }
    }
    //订单列表
    protected function getOrderList()
    {
        $user = $this->_checkUser();
        $status = isset($this->postParams['status']) && $this->postParams['status'] ? $this->postParams['status'] : 0;
        $where[] = ['OS.user_id' ,'=', $user['user_id']];
        switch ($status) {
            case 1://待付款
                $where[] = ['O.status','=',1];
                $where[] = ['O.pay_status','=',0];
            break;
            case 2://待发货
                $where[] = ['O.status','=',1];
                $where[] = ['O.pay_status','=',1];
                $where[] = ['O.delivery_status','=',0];
                $where[] = ['O.finish_status','=',0];
            break;
            case 3://待收货
                $where[] = ['O.status','=',1];
                $where[] = ['O.pay_status','=',1];
                $where[] = ['O.delivery_status','IN', [1,2]];
                $where[] = ['O.finish_status','=',0];
            break;
            case 4://已完成
                $where[] = ['O.status','=',1];
                $where[] = ['O.finish_status','=',2];
            break;
            case 5://已取消
                $where[] = ['O.status','=',2];
            break;
            default:
//                $map['O.status'] = ['NOT IN', [3, 4]];
                $where[] = ['O.status','NOT IN', [3, 4]];
            break;
        }
        $orderSubModel = db('order_sub');
        $field = 'sub_id, sub_sn, S.name as store_name, OS.goods_amount, OS.delivery_amount, OS.real_amount, OS.add_time, OS.status, O.pay_type, OS.pay_status, OS.delivery_status, OS.finish_status';
        $join = [
            ['order O', 'O.order_id = OS.order_id', 'INNER'],
            ['store S', 'S.store_id = OS.store_id', 'LEFT']
        ];
        $list = $this->_getModelList($orderSubModel, $where, $field, 'OS.add_time DESC, OS.sub_id DESC', 'OS', $join);
        if ($list) {
            $orderSkuModel = db('order_sku');
            foreach ($list as $key => $value) {
                $skus = $orderSkuModel->field('osku_id, sku_id, goods_id, sku_name, sku_spec, sku_thumb, num, price, pay_price, real_amount')->where(['sub_id' => $value['sub_id']])->select();
                $list[$key]['status_text'] = get_order_status($value)['status_text'];
                $list[$key]['skus'] = $skus ? $skus : [];
                unset($list[$key]['pay_type'], $list[$key]['sub_id']);
            }
        }
        $this->_returnMsg(['list' => $list]);
    }
    //获取订单详情
    protected function getOrderDetail()
    {
        $user = $this->_checkUser();
        $order = $this->_checkOrder(false, $user['user_id']);
        $order['status_text'] = get_order_status($order)['status_text'];
        $payments = $this->_getPayments();
        $detail = [
            'order'     => $order,
            'payments'  => $payments,
        ];
        $this->_returnMsg(['detail' => $detail]);
    }
    //获取子订单详情
    protected function getSubDetail()
    {
        $user = $this->_checkUser();
        $sub = $this->_checkSubOrder(false, $user['user_id'], 'order_id, sub_id, sub_sn, store_id, goods_amount, delivery_amount, real_amount, status, pay_status, delivery_status, finish_status, add_time, pay_time, cancel_time, delivery_time, finish_time');
        //获取门店信息
        $store = db('store')->where(['store_id' => $sub['store_id']])->field('store_id, name')->find();
        //获取收货地址
        $order = db('order')->field('order_sn, goods_amount, delivery_amount, real_amount, paid_amount, address_name, address_phone, address_detail, status, pay_type, pay_status, delivery_status, finish_status, add_time, pay_time')->where(['order_id' => $sub['order_id']])->find();
        $orderSkuModel = db('order_sku');
        $skus = $orderSkuModel->field('osku_id, sku_id, goods_id, sku_name, sku_thumb, num, price, pay_price, real_amount')->where(['sub_id' => $sub['sub_id']])->select();
        $sub['pay_type'] = $order['pay_type'];
        $sub['status_text'] = get_order_status($sub)['status_text'];
        unset($order['pay_type']);
        unset($sub['order_id'], $sub['sub_id'], $sub['store_id']);
        $detail = [
            'order'     => $order,
            'skus'      => $skus ? $skus : [],
            'sub'       => $sub,
            'store'     => $store,
        ];
        $this->_returnMsg(['detail' => $detail]);
    }
    //取消订单功能
    protected function cancelOrder()
    {
        $user = $this->_checkUser();
        $sub = $this->_checkSubOrder(false, $user['user_id']);
        $orderService = new \app\common\service\Order();
        $result = $orderService->orderCancel($sub['sub_sn'], 0, $user['user_id']);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);
        }else{
            $this->_returnMsg(['msg' => '取消订单成功']);
        }
    }
    //支付订单功能
    protected function payOrder()
    {
        $user = $this->_checkUser();
        $order = $this->_checkOrder(FALSE, $user['user_id']);
        $payCode = isset($this->postParams['pay_code']) && trim($this->postParams['pay_code']) ? trim($this->postParams['pay_code']) : '';
        $thirdOpenid = isset($this->postParams['third_openid']) && trim($this->postParams['third_openid']) ? trim($this->postParams['third_openid']) : '';//TODO如果没传openid是否考虑使用登录用户的帮点openid尝试下单 $user['openid']
        if (!$payCode) {
            $this->_returnMsg(['code' => 1, 'msg' => '支付方式(pay_code)缺失']);
        }
        $payment = db('payment')->where(['is_del' => 0, 'status' => 1, 'store_id' => $order['store_ids'], 'pay_code' => $payCode])->find();
        if (!$payment) {
            $this->_returnMsg(['code' => 1, 'msg' => '支付方式(pay_code)不存在']);
        }
        if ($order['pay_status'] > 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单已支付，不能重复支付']);
        }
        $config = $payment['config_json'] ? json_decode($payment['config_json'], TRUE): [];
        $paymentService = new \app\common\api\PaymentApi($payCode, $order['store_ids'], $config);
        $order['subject'] = '购买商品';
        $order['openid'] = $thirdOpenid;//'oDDkf5RMJ5hLJ3oOOqGmTXyt3BJk';#小程序openid
        $params = $paymentService->init($order);
        if ($params === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $paymentService->error]);
        }
        if ($params === TRUE) {
            $this->_returnMsg(['msg' => '支付成功']);
        }else{
            $update = [
                'update_time' => time(), 
                'pay_method' => $payCode
            ];
            if (strtolower($payCode) == 'wechat_applet' && isset($params['package']) && $params['package']) {
                $package = $params['package'] ? explode('=', $params['package']) : [];
                if ($package && isset($package[1])) {
                    $extra = $order['extra'] ? json_decode($order['extra'], 1) : [];
                    $extra['prepay_id'] =  trim($package[1]);
                    $update['extra'] = $extra ? json_encode($extra) : ''; 
                }
            }
            $result = db('order')->where(['order_id' => $order['order_id']])->update($update);
            $this->_returnMsg(['params' => $params]);
        }
    }
    //确认收货功能
    protected function finishOrder()
    {
        $user = $this->_checkUser();
        $sub = $this->_checkSubOrder(false, $user['user_id']);
        $orderService = new \app\common\service\Order();
        $result = $orderService->orderFinish($sub['sub_sn'], 0, $user['user_id']);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);
        }else{
            $this->_returnMsg(['msg' => '确认收货完成']);
        }
    }
    //查询物流信息
    protected function getOrderDeliverys()
    {
        $user = $this->_checkUser();
        $sub = $this->_checkSubOrder(false, $user['user_id']);
        $odeliveryId = isset($this->postParams['odelivery_id']) && $this->postParams['odelivery_id'] ? intval($this->postParams['odelivery_id']) : 0;
        if(!$odeliveryId){
            $this->_returnMsg(['code' => 1, 'msg' => '物流配送ID(odelivery_id)缺失']);
        }
        $orderService = new \app\common\service\Order();
        if(isset($this->storeId)){
            $result = $orderService->updateApi100($sub['sub_sn'], $this->storeId, ADMIN_ID, $odeliveryId);
        }else{
            $result = $orderService->updateApi100($sub['sub_sn'], $sub['store_id'], $sub['user_id'], $odeliveryId);
        }

        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '物流配送信息查看失败']);
        }
        $delivery = db('order_sku_delivery')->where(['sub_sn' => $sub['sub_sn'], 'odelivery_id' => $odeliveryId, 'user_id' => $user['user_id']])->find();
        if (!$delivery) {
            $this->_returnMsg(['code' => 1, 'msg' => '物流信息不存在']);
        }
        //获取物流跟踪日志
        $logs = db('order_track')->field('sub_sn, msg, time')->where(['odelivery_id' => $delivery['odelivery_id']])->order('track_id DESC')->select();
        $this->_returnMsg(['list' => $logs]);
    }
    //申请售后操作
    protected function applyServiceOrder()
    {
        $user = $this->_checkUser();
        $sub = $this->_checkSubOrder(false, $user['user_id']);
        $serviceType = isset($this->postParams['stype']) ? intval($this->postParams['stype']) : 0;
        if (!$serviceType) {
            $this->_returnMsg(['code' => 1, 'msg' => '服务类型(stype)缺失']);
        }
        if (!in_array($serviceType, [1, 2])) {
            $this->_returnMsg(['code' => 1, 'msg' => '服务类型(stype)错误']);
        }
        //退款分批量和单个商品退款(批量退款无法修改商品数量,单个可以修改商品数量)
        $skuIds = isset($this->postParams['sku_ids']) ? trim($this->postParams['sku_ids']) : '';//英文逗号分隔
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品ID(sku_ids)缺失']);
        }
        $skuIds = array_explode($skuIds);
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品ID(sku_ids)格式错误']);
        }
        //退款分批量和单个商品退款(批量退款无法修改商品数量,单个可以修改商品数量)
        $num = isset($this->postParams['num']) ? intval($this->postParams['num']) : 0;
        if (count($skuIds) == 1 && $num <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品数量(num)缺失或格式错误']);
        }
        if ($serviceType == 1 && !isset($this->postParams['goods_status'])) {
            $this->_returnMsg(['code' => 1, 'msg' => '货物状态(goods_status)缺失']);
        }
        $orderService = new \app\common\service\ServiceOrder();
        $serviceId = $orderService->applyServiceOrder($sub['sub_sn'], $user['user_id'], $serviceType, $skuIds, $num, $this->postParams);
        if ($serviceId === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);
        }else{
            $this->_returnMsg(['service_id' => $serviceId, 'msg' => '售后申请成功']);
        }
    }
    //获取售后列表
    public function getServiceOrderList()
    {
        $user = $this->_checkUser();
        $where = [
            'OS.user_id' => $user['user_id'],
        ];
        $orderServiceModel = db('order_service');
        $field = 'S.name as store_name, OS.service_type, OS.return_amount, OS.status, apply_status, return_status, OS.add_time, OS.service_id';
        $join = [
            ['store S', 'S.store_id = OS.store_id', 'LEFT']
        ];
        $list = $this->_getModelList($orderServiceModel, $where, $field, 'OS.add_time DESC', 'OS', $join);
        if ($list) {
            $serviceSkuModel = db('order_service_sku');
            $orderLogModel = db('order_log');
            foreach ($list as $key => $value) {
                $skus = $serviceSkuModel->field('sku_id, goods_id, sku_name, sku_spec, sku_thumb, num, return_amount, apply_status, return_status, status, add_time')->where(['service_id' => $value['service_id']])->select();
                $list[$key]['skus'] = $skus ? $skus : [];
            }
        }
        $this->_returnMsg(['list' => $list]);
    }
    //获取售后详情
    public function getServiceOrderDetail()
    {
        $user = $this->_checkUser();
        $serviceId  = isset($this->postParams['service_id']) ? intval($this->postParams['service_id']) : 0;
        $subSn      = isset($this->postParams['sub_sn']) ? trim($this->postParams['sub_sn']) : '';
        $skuId     = isset($this->postParams['sku_id']) ? intval($this->postParams['sku_id']) : 0;
        if ($serviceId <= 0 && !$subSn) {
            $this->_returnMsg(['code' => 1, 'msg' => 'service_id和sub_sn不能同时为空']);
        }
        if ($subSn) {
            $sub = $this->_checkSubOrder($subSn, $user['user_id']);
            if (!$skuId) {
                $this->_returnMsg(['code' => 1, 'msg' => '订单商品ID(sku_id)缺失']);
            }
            //根据sub_sn和商品osku_id获取售后信息
            $serviceId = db('order_service_sku')->where(['sub_sn' => $subSn, 'sku_id' => $skuId, 'user_id' => $user['user_id'], 'status' => 0])->value('service_id');
            if (!$serviceId) {
                $this->_returnMsg(['code' => 1, 'msg' => '对应申请单不存在或已撤销']);
            }
        }
        $orderService = new \app\common\service\ServiceOrder();
        $detail = $orderService->getServiceOrderDetail($serviceId, 0, $user['user_id'], $skuId);
        if ($detail === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);
        }else{
            $this->_returnMsg(['detail' => $detail]);
        }
    }
    //撤销申请或取消售后
    public function cancelServiceOrder()
    {
        $user = $this->_checkUser();
        $serviceId = isset($this->postParams['service_id']) ? intval($this->postParams['service_id']) : 0;
        if ($serviceId <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '售后订单ID(service_id)缺失']);
        }
        //撤销申请对应商品(同一个售后申请，批量退换为多个，其中可撤销部分)
        $skuIds = isset($this->postParams['sku_ids']) ? trim($this->postParams['sku_ids']) : '';//英文逗号分隔
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单商品ID(sku_ids)缺失']);
        }
        $skuIds = array_explode($skuIds);
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单商品ID(sku_ids)格式错误']);
        }
        $orderService = new \app\common\service\ServiceOrder();
        $result = $orderService->cancelOrderService($serviceId, $user['user_id'], $skuIds);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);
        }else{
            $this->_returnMsg(['msg' => '撤销申请成功']);
        }
    }
    //退还售后商品
    public function returnServiceGoods()
    {
        $user = $this->_checkUser();
        $serviceId = isset($this->postParams['service_id']) ? intval($this->postParams['service_id']) : 0;
        if ($serviceId <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '售后订单ID(service_id)缺失']);
        }
        //退款分批量和单个商品退款(批量退款无法修改商品数量,单个可以修改商品数量)
        $skuIds = isset($this->postParams['sku_ids']) ? trim($this->postParams['sku_ids']) : '';//英文逗号分隔
        if ($skuIds) {
            $skuIds = array_explode($skuIds);
        }
        $deliveryName = isset($this->postParams['delivery_name']) ? trim($this->postParams['delivery_name']) : '';
        $deliverySn = isset($this->postParams['delivery_sn']) ? trim($this->postParams['delivery_sn']) : '';
        if (!$deliveryName) {
            $this->_returnMsg(['code' => 1, 'msg' => '物流公司名称(delivery_name)缺失']);
        }
        if (!$deliverySn) {
            $this->_returnMsg(['code' => 1, 'msg' => '物流单号(delivery_sn)缺失']);
        }
        $orderService = new \app\common\service\ServiceOrder();
        $detail = $orderService->getServiceOrderDetail($serviceId, 0, $user['user_id']);
        if ($detail === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);
        }else{
            $service = isset($detail['service']) ? $detail['service'] : [];
            $skus = $detail['skus'];
            if (!$service || !$skus) {
                $this->_returnMsg(['code' => 1, 'msg' => '售后申请不存在']);
            }
            if ($service['service_type'] == 1) {
                $this->_returnMsg(['code' => 1, 'msg' => '退款类申请，不能执行当前操作']);
            }
            if ($service['status'] == 2) {
                $this->_returnMsg(['code' => 1, 'msg' => '申请已撤销，不能执行当前操作']);
            }
            if ($service['apply_status'] == 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '审核中，不能执行当前操作']);
            }
            if ($service['apply_status'] == -2) {
                $this->_returnMsg(['code' => 1, 'msg' => '申请已拒绝，不能执行当前操作']);
            }
            $serviceSkuIds = [];
            foreach ($skus as $key => $value) {
                if ($value['status'] == 0 && $value['apply_status'] == 1 && $value['return_status'] == 0) {
                    $serviceSkuIds[] = $value['service_sku_id'];
                }
                if ($value['delivery_sn']) {
                    $this->_returnMsg(['code' => 1, 'msg' => '已填写物流信息，不能执行当前操作']);
                }
            }
            if (count($skus) != count($serviceSkuIds)) {
                $this->_returnMsg(['code' => 1, 'msg' => '数据异常，不能执行当前操作']);
            }
            $data = [
                'delivery_name' => $deliveryName,
                'delivery_sn'   => $deliverySn,
                'delivery_time' => time(),
            ];
            $result = db('order_service_sku')->where(['service_sku_id' => ['IN', $serviceSkuIds]])->update($data);
            if ($result === FALSE) {
                $this->_returnMsg(['code' => 1, 'msg' => '系统异常']);
            }
            $data = [
                'delivery_detail' => '物流公司:'.$deliveryName.',物流单号:'.$deliverySn,
                'update_time' => time(),
            ];
            $result = db('order_service')->where(['service_id' => $serviceId])->update($data);
            $orderService->serviceLog($service, 0, $user, '填写退货物流信息', '物流公司:'.$deliveryName.',物流单号:'.$deliverySn);
            $this->_returnMsg(['msg' => '退货物流填写成功']);
        }
    }
    //获取用户收货地址列表
    protected function getUserAddressList()
    {
        $user = $this->_checkUser();
        $list = db('UserAddress')->field('address_id, name, phone, region_name, address, isdefault, status')->where(['user_id' => $user['user_id'], 'is_del' => 0])->select();
        $this->_returnMsg(['list' => $list]);
    }
    protected function getUserAddressDetail()
    {
        $user = $this->_checkUser();
        $addressId = isset($this->postParams['address_id']) ? intval($this->postParams['address_id']) : 0;
        if ($addressId <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '收货地址ID(address_id)缺失']);
        }
        $address = $this->_checkAddress($user['user_id'], $addressId, '');
        $this->_returnMsg(['detail' => $address]);
    }
    //获取区域列表
    protected function getRegions()
    {
        $parentId = isset($this->postParams['parent_id']) && $this->postParams['parent_id'] ? intval($this->postParams['parent_id']) : 0;
        if ($parentId) {
            $where['parent_id'] = $parentId;
        }else{
            $where['parent_id'] = 2;
        }
        $regions = db('region')->where($where)->select();
        $this->_returnMsg(['list' => $regions]);
    }
    //更新用户收货地址(新增/编辑)
    protected function updateUserAddress()
    {
        $user = $this->_checkUser();
        $addressId = isset($this->postParams['address_id']) ? intval($this->postParams['address_id']) : 0;
        if ($addressId) {
            $address = $this->_checkAddress($user['user_id'], $addressId);
        }
        $name = isset($this->postParams['name']) ? trim($this->postParams['name']) : '';
        $phone = isset($this->postParams['phone']) ? trim($this->postParams['phone']) : '';
        $regionId = isset($this->postParams['region_id']) ? intval($this->postParams['region_id']) : '';
        $regionName = isset($this->postParams['region_name']) ? $this->postParams['region_name'] : '';
        $addressDetail = isset($this->postParams['address']) ? trim($this->postParams['address']) : '';
        $isdefault = isset($this->postParams['isdefault']) ? intval($this->postParams['isdefault']) : 0;
        $status = isset($this->postParams['status']) ? intval($this->postParams['status']) : 1;
        
        if (!$name){
            $this->_returnMsg(['code' => 1, 'msg' => '收货人姓名(name)缺失']);
        }
        if (!$phone){
            $this->_returnMsg(['code' => 1, 'msg' => '收货人电话(phone)缺失']);
        }
        #TODO 验证收货电话格式
//         if (strlen($phone) != 11) {
//             $this->_returnMsg(['code' => 1, 'msg' => '收货人手机号(phone)格式错误']);
//         }
        if (!$regionId){
            if($regionName){
                $regionMod = db("Region");
                if (is_string($regionName)) {
                    $result = $regionMod->where(['region_name' => $regionName])->find();
                    if ($result) {
                        $regionId = $result['region_id'];
                    }else{
                        $this->_returnMsg(['code' => 1, 'msg' => '收货人地址ID(region_id)缺失']);
                    }
                }elseif (is_array($regionName)){
                    $length = count($regionName);
                    if ($length != 3) {
                        $this->_returnMsg(['code' => 1, 'msg' => '收货人地址(region_name)格式错误']);
                    }
                    $list = $regionMod->where(['region_name' => $regionName[$length-1]])->select();
                    if ($list) {
                        if(count($list) > 1){
                            foreach ($list as $key => $value) {
                                $parent = $regionMod->where(['region_name' => $regionName[$length-2], 'region_id' => $value['parent_id']])->find();
                                if (!$parent) {
                                    unset($list[$key]);
                                }else{
                                    $first = $regionMod->where(['region_name' => $regionName[$length-3], 'region_id' => $parent['parent_id']])->find();
                                    if (!$first) {
                                        unset($list[$key]);
                                    }
                                }
                            }
                        }

                    }
                    if (!$list) {
                        $this->_returnMsg(['code' => 1, 'msg' => '收货人地址(region_name)不存在']);
                    }else{
                        //取条件匹配的第一个数据
                        $last = reset($list);
                        $regionId = $last['region_id'];
                    }
                }
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '收货人地址ID(region_id)缺失']);
            }
        }
        if (!$addressDetail){
            $this->_returnMsg(['code' => 1, 'msg' => '收货人详细地址(address)缺失']);
        }
        //根据region_id获取region_name
        $rname = $this->_getParentName($regionId);
        $rname = $rname ? $rname : $regionName;
        $addressModel = db('UserAddress');
        $data = [
            'name'      => $name,
            'phone'     => $phone,
            'region_id' => $regionId,
            'address'   => $addressDetail,
            'isdefault' => $isdefault,
            'status'    => $status,
            'region_name' => trim($rname),
            'update_time' => time(),
        ];
        //验证收货地址是否重复
        if(!$addressId && $addressDetail){
            $where = ['user_id' => $user['user_id'], 'is_del' => 0, 'name' => $name, 'phone' => $phone,'address' => $addressDetail];
            $result = $addressModel->where($where)->find();
            if($result){
                $addressId = $result['address_id'];
            }
        }
        if ($addressId) {
            $msg = '编辑';
            $result = $addressModel->where(['address_id' => $addressId])->update($data);
        }else{
            $msg = '新增';
            $maxNum = 10;
            //限制用户最多添加地址数量
            $count = $addressModel->where(['user_id' => $user['user_id'], 'is_del' => 0])->count();
            if ($count >= $maxNum) {
                $this->_returnMsg(['code' => 1, 'msg' => '用户最大收货地址数量不能超过'.$maxNum.'个']);
            }
            if ($count == 0) {
                //第一个添加的地址为默认地址
                $data['isdefault'] = 1;
            }
            $data['add_time'] = time();
            $data['user_id'] = $user['user_id'];
            $result = $addressId = $addressModel->insertGetId($data);
        }
        if ($result === false) {
            $this->_returnMsg(['code' => 1, 'msg' => '收货地址'.$msg.'失败']);
        }
        $data['address_id'] = $addressId;        
        if ($isdefault) {
            $addressModel->where(['user_id' => $user['user_id'], 'is_del' => 0, 'address_id' => ['<>', $addressId], 'isdefault' => ['<>', 0]])->update(['isdefault' => 0, 'update_time' => time()]);
        }
        $this->_returnMsg(['msg' => '收货地址'.$msg.'成功', 'address' => $data]);
    }
    //删除用户收货地址
    protected function delUserAddress()
    {
        $user = $this->_checkUser();
        $address = $this->_checkAddress($user['user_id']);
        $result = db('UserAddress')->where(['address_id' => $address['address_id']])->update(['update_time' => time(), 'is_del' => 1]);
        if ($result === false) {
            $this->_returnMsg(['code' => 1, 'msg' => '收货地址删除失败']);
        }
        $this->_returnMsg(['msg' => '收货地址删除成功']);
    }
    /****************************************************************====================================================================*************************************************************/
    
    /**
     * 验证openid对应用户信息
     * @return array
     */
    private function _checkOpenid($openid = '')
    {
        $openid = $openid ? $openid : (isset($this->postParams['openid']) ? trim($this->postParams['openid']) : '');
        if (!$openid) {
            $this->_returnMsg(['code' => 1, 'msg' => '登录用户唯一标识(openid)缺失']);
        }
        $user = db('user_data')->alias('UD')->join('user U', 'UD.user_id = U.user_id', 'LEFT')->field('UD.*, U.*')->where([['openid' ,'=', $openid], ['UD.user_id' , '>', 0]])->find();
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => '登录用户不存在']);
        }
        if (!$user['status']) {
            $this->_returnMsg(['code' => 1, 'msg' => '用户已禁用不允许登录']);
        }
        return $user;
    }
    /**
     * 验证门店信息
     */
    private function _checkStore($sid = 0, $field = '*')
    {
        $sid = $sid ? $sid : (isset($this->postParams['sid']) && intval($this->postParams['sid']) ? intval($this->postParams['sid']) : 0);
        if ($sid <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店ID(sid)参数错误']);
        }
        $store = db('store')->field($field)->where(['store_id' => $sid, 'is_del' => 0, 'status' => 1])->find();
        if (!$store) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店不存在或已禁用']);
        }
        return $store;
    }
    
    /**
     * 获取支付方式列表
     * @return array
     */
    private function _getPayments()
    {
        $where = [
            ['is_del' ,'=', 0],
            ['store_id' ,'=', 1],
            ['status' ,'=', 1],
        ];
        //余额支付(客户端通用支付方式)
        $payCodes = [
            'balance',
        ];
        if ($this->fromSource == 'H5') {
            $payCodes[] = 'wechat_js';
            $payCodes[] = 'wechat_applet';
        }elseif ($this->fromSource == 'Applets'){
            $payCodes[] = 'wechat_applet';
        }elseif(in_array($this->fromSource, ['Android', 'Ios'])){
            $payCodes[] = 'wechat_app';
        }else{
            #测试
            $payCodes[] = 'wechat_js';
            $payCodes[] = 'wechat_applet';
            $payCodes[] = 'wechat_app';
        }
        $where[] = ['pay_code','IN', $payCodes];
        $payments = db('payment')->field('pay_code, name')->where($where)->select();
        return $payments;
    }
    /**
     * 获取当前区域的区域前缀名称
     * @param int $id
     * @param string $name
     * @return string
     */
    private function _getParentName($id, &$name = '') {
        $region = db('region')->where(['region_id' => $id])->find();
        $name = $name ? $region['region_name'].' '.$name : $region['region_name'];
        if($region && $region['parent_id'] > 0 && $region['parent_id'] > 1) {
            $name = $this->_getParentName($region['parent_id'], $name);
        }
        return $name;
    }
    /**
     * 返回数据列表(可分页)
     * @param object $model
     * @param array $where
     * @param string $field
     * @param string $order
     * @param string $alias
     * @param array $join
     * @param string $group
     * @param string $having
     * @return array
     */
    private function _getModelList($model, $where = [], $field = '*', $order = false, $alias = false, $join = [], $group = false, $having = false)
    {
        if($alias)  $model->alias($alias);
        if($join)   $model->join($join);
        if($where)  $model->where($where);
        if($having) $model->having($having);
        if($order)  $model->order($order);
        if($group)  $model->group($group);
        if ($this->pageSize > 0) {
            $result = $model->field($field)->paginate($this->pageSize, false, ['page' => $this->page]);
            return $result;
        }else{
            return $model->field($field)->select();
        }
    }
    /**
     * 根据日期生成唯一订单号
     * @param boolean $refresh 	是否刷新再生成
     * @return string
     */
    private function _buildOrderSn($refresh = FALSE) {
        if ($refresh == TRUE) {
            $sn = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 12);
        }else{
            $sn = date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 6);
        }
        //判断sn是否存在
        if ($refresh) {
            $table = 'OrderSub';
            $field = 'sub_sn';
        }else{
            $table = 'Order';
            $field = 'order_sn';
        }
        $exist = db($table)->where(array($field => $sn))->find();
        if ($exist) {
            return $this->_buildOrderSn($refresh);
        }else{
            return $sn;
        }
    }
    /**
     * 获取购物车数据
     * @param array $user
     * @param array|int $skuIds
     * @param bool $incart
     * @param int $num
     * @return array
     */
    private function _getCartDatas($user = [], $skuIds = [], $incart = TRUE, $num = 0)
    {
        if (!$user) {
            $user = $this->_checkUser();
        }
        $storeId = isset($this->postParams['sid']) && intval($this->postParams['sid']) > 0 ? intval($this->postParams['sid']) : 0;
        if ($storeId) {
            $store = $this->_checkStore();
        }
        $userId = $user['user_id'];
        if ($incart) {
            $expired = isset($this->postParams['expired']) ? intval($this->postParams['expired']): 0;
            $join = [['goods_sku S', 'C.sku_id = S.sku_id', 'INNER'], ['goods G', 'G.goods_id = C.goods_id', 'INNER']];
            $field = 'C.cart_id, C.store_id, S.sku_id, G.goods_id, G.name, S.sku_name, S.price, C.num, S.sku_thumb, G.thumb, S.sku_stock, S.spec_value, G.is_del as gdel, G.status, S.is_del as sdel';
            $where[] = ['user_id' ,'=', $userId];
            if ($storeId && isset($store) && $store) {
                $where[] = ['C.store_id','=',$storeId];
                $where[] = ['S.store_id','=',$storeId];
            }
            if ($expired) {
                $list = db('cart')->alias('C')->join($join)->field($field)->where($where)->where(function($query){
                    $query->whereOR('G.is_del', '=' , 1, 'OR');
                    $query->whereOR('G.status', '=' , 0, 'OR');
                    $query->whereOR('G.goods_stock', '<=' , 0, 'OR');
                })->order('C.update_time DESC, C.cart_id DESC')->select();
            }else{
                if ($skuIds) {
                    $where[] = ['C.sku_id','IN', $skuIds];
                }
                $where[] = ['G.is_del','=',0];
                $where[] = ['G.status','=',1];
                $where[] = ['G.goods_stock','>', 0];
                $list = db('cart')->alias('C')->join($join)->field($field)->where($where)->order('C.update_time DESC, C.cart_id DESC')->select();
            }
        }else{
            $expired = 0;
            $where['sku_id'] = intval($skuIds);
            if ($storeId && isset($store) && $store) {
                $where['S.store_id'] = $storeId;
            }
            $join = [['goods G', 'G.goods_id = S.goods_id', 'INNER']];
            $field = 'S.store_id, S.sku_id, G.goods_id, G.name, S.sku_name, S.price, '.$num.' as num, S.sku_thumb, G.thumb, S.sku_stock, S.spec_value, G.is_del as gdel, G.status, S.is_del as sdel';
            $list = db('GoodsSku')->alias('S')->join($join)->field($field)->where($where)->limit(1)->select();
        }
        $carts = $datas = $storeIds = [];
        $skuCount = $skuTotal = $deliveryAmount = $skuAmount = $payAmount = 0;
        if ($list) {
            $storeModel = db('store');
            $skuList = $storeAmounts = [];
            foreach ($list as $key => $value) {
                $storeId = $value['store_id'];
                $storeIds[$storeId] = $storeId;
                $skuId = $value['sku_id'];
                $skuList[] = $value['sku_id'];
                $num = intval($value['num']);
                //商品库存为0/已删除/已禁用 则为 已失效
                if ($expired) {
                    unset($value['gdel'], $value['status'], $value['sdel']);
                    $carts[$skuId] = $value;
                }else{
                    $amount = 0;
                    $disable = $unsale = 0;
                    //若下单后减少库存则判断购物车数量是否大于库存数量
                    if ($value['sku_stock'] <= 0 || ($this->reduceStock == 1 && $value['sku_stock'] < $num)) {
                        $disable = 1; //库存不足
                    }elseif($value['sdel']){
                        $unsale = 1; //已下架
                    }else{
                        $amount = $num * $value['price'];
                        $skuCount++;
                        $skuTotal = $skuTotal + $num;
                        $skuAmount = $skuAmount + $amount;
                    }
                    $value['pay_price'] = $value['price'];
                    if (isset($value['sku_thumb']) && $value['sku_thumb']) {
                        $value['sku_thumb'] = trim($value['sku_thumb']);
                    }elseif (isset($value['thumb']) && $value['thumb']){
                        $value['sku_thumb'] = trim($value['thumb']);
                    }else{
                        $value['sku_thumb'] = '';
                    }
                    
                    $value['disable'] = $disable;
                    $value['unsale'] = $unsale;
                    unset($value['gdel'], $value['status'], $value['sdel'], $value['thumb']);
                    $carts[$storeId]['skus'][$skuId] = $value;
                    $carts[$storeId]['store'] = $storeModel->field('store_id, name')->where(['store_id' => $storeId])->find();
                    $carts[$storeId]['detail'] = [
                        'sku_total' => isset($carts[$storeId]['detail']['sku_total']) ? $carts[$storeId]['detail']['sku_total'] + $num: $num,
                        'sku_count' => 0, 
                        'all_amount' => 0, 
                        'delivery_amount' => 0, 
                        'sku_amount'=> isset($carts[$storeId]['detail']['sku_amount']) ? $carts[$storeId]['detail']['sku_amount'] + $amount: $amount,
                    ];
                }
            }
            $carts[$storeId]['detail']['sku_count'] = $carts[$storeId]['skus'] ? count($carts[$storeId]['skus']) : 1;
            $carts[$storeId]['detail']['delivery_amount'] = sprintf("%.2f", $deliveryAmount);
            $carts[$storeId]['detail']['all_amount'] = sprintf("%.2f", ($carts[$storeId]['detail']['sku_amount'] + $carts[$storeId]['detail']['delivery_amount']));
            $carts[$storeId]['detail']['sku_amount'] = sprintf("%.2f", $carts[$storeId]['detail']['sku_amount']);
            $carts[$storeId]['detail']['pay_amount'] = sprintf("%.2f", $carts[$storeId]['detail']['all_amount']);
            $skuIds = !empty($skuIds) ? $skuIds : implode(',', $skuList);
        }
        if ($expired) {
            $return =  $carts;
        }else{
            $payAmount = $skuAmount;
            $allAmount = $skuAmount + $deliveryAmount;
            $return = [
                'list'      => $carts,                  //商品列表
                'sku_total' => intval($skuTotal),       //商品总数量
                'sku_count' => intval($skuCount),       //商品种类数量(不重复)
                'all_amount'        => sprintf("%.2f",$allAmount),      //商品总金额
                'delivery_amount'   => sprintf("%.2f",$deliveryAmount), //物流费用
                'sku_amount'        => sprintf("%.2f",$skuAmount),      //商品总金额
                'pay_amount'        => sprintf("%.2f",$payAmount),      //需支付金额
                'sku_ids'           => $skuIds,
                'store_ids'         => $storeIds,
            ];
        }
        return $return;
    }
    /**
     * 取得用户默认收货地址/或者根据收货地址id取得收货地址
     * @param number $addressId
     */
    private function _getDefaultAddress($user = [],$addressId = 0){
        if (!$user) {
            $user = $this->_checkUser();
        }
        $where = [
            'user_id' => $user['user_id'],
            'is_del' => 0,
        ];
        if($addressId > 0){
            $where['address_id'] = $addressId;
        }else{
            $where['isdefault']  = 1;
        }
        $addressInfo = db('UserAddress')->field('address_id, name, phone, region_name, address, isdefault, status')->where($where)->find();
        return $addressInfo;
    }
    /**
     * 验证收货地址信息
     * @param int $userId
     * @param int $addressId
     * @return array
     */
    private function _checkAddress($userId = 0, $addressId = 0, $field = '*')
    {
        $addressId = $addressId ? $addressId : (isset($this->postParams['address_id']) ? intval($this->postParams['address_id']) : 0);
        if (!$addressId) {
            $this->_returnMsg(['code' => 1, 'msg' => '收货地址ID(address_id)缺失']);
        }
        $address = db('UserAddress')->field('address_id, name, phone, region_id, region_name, address, isdefault, add_time, update_time')->where(['user_id' => $userId, 'is_del' => 0, 'address_id' => $addressId])->find();
        if (!$address) {
            $this->_returnMsg(['code' => 1, 'msg' => '收货地址不存在或已删除']);
        }
        return $address;
    }
    /**
     * 验证用户信息
     * @param int $userId
     * @return array
     */
    private function _checkUser($userId = 0)
    {
        $userId = $userId ? $userId : (isset($this->postParams['user_id']) ? intval($this->postParams['user_id']) : 0);
        if (!$userId) {
            #TODO 20181102user_id改为openid,为兼容H5和线上已发布的小程序使用user_id和openid同时验证的格式，本地版本发布后可批量替换
            return $user = $this->_checkOpenid();
            $this->_returnMsg(['code' => 1, 'msg' => '用户ID(user_id)缺失']);
        }
        $user = db('User')->where(['user_id' => $userId, 'is_del' => 0])->find();
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => '用户不存在或已删除']);
        }
        if (!$user['status']) {
            $this->_returnMsg(['code' => 1, 'msg' => '用户已禁用']);
        }
        return $user;
    }
    /**
     * 验证商品属性信息
     * @param int $skuId
     * @param string $field
     * @param bool $disabled
     * @return array
     */
    private function _checkSku($skuId = 0, $field = '*', $disabled = TRUE)
    {
        $skuId = $skuId ? $skuId : (isset($this->postParams['sku_id']) ? intval($this->postParams['sku_id']) : 0);
        if (!$skuId) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品属性ID(sku_id)缺失']);
        }
        $field = $field.', G.is_del as gdel, G.status as gstatus, GS.is_del, GS.status';
        $sku = db('goods_sku')->alias('GS')->join('goods G', 'G.goods_id = GS.goods_id', 'LEFT')->field($field)->where(['sku_id' => $skuId])->find();
        if (!$sku) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品不存在']);
        }
        if ($disabled) {
            if ($sku['is_del'] || $sku['gdel']) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品已下架']);
            }
            if (!$sku['status'] || !$sku['gstatus']) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品已禁用']);
            }
        }
        return $sku;
    }
    /**
     * 验证子订单信息
     * @param string $subSn
     * @param number $userId
     * @param string $field
     * @return array
     */
    private function _checkSubOrder($subSn = '', $userId = 0, $field = '*')
    {
        $subSn = $subSn ? $subSn : (isset($this->postParams['sub_sn']) ? trim($this->postParams['sub_sn']) : '');
        if (!$subSn) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单号(sub_sn)缺失']);
        }
        $field = $field.', status';
        $sub = db('order_sub')->field($field)->where(['sub_sn' => $subSn, 'user_id' => $userId])->find();
        if (!$sub) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单不存在']);
        }
        if ($sub['status'] != 1 && $sub['status'] != 2) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单不存在']);
        }
        return $sub;
    }
    /**
     * 验证主订单信息
     * @param string $orderSn
     * @param number $userId
     * @param string $field
     * @return array
     */
    private function _checkOrder($orderSn = '', $userId = 0, $field = '*')
    {
        $orderSn = $orderSn ? $orderSn : (isset($this->postParams['order_sn']) ? trim($this->postParams['order_sn']) : '');
        if (!$orderSn) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单号(order_sn)缺失']);
        }
        $field = $field.', status';
        $order = db('order')->field($field)->where(['order_sn' => $orderSn, 'user_id' => $userId])->find();
        if (!$order) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单不存在']);
        }
        if ($order['status'] != 0 && $order['status'] != 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '订单不存在']);
        }
        return $order;
    }
    /**
     * 处理接口返回信息
     */
    protected function _returnMsg($data, $echo = TRUE){
        $result = parent::_returnMsg($data);
        $responseTime = $this->_getMillisecond() - $this->visitMicroTime;//响应时间(毫秒)
        $addData = [
            'request_time'  => $this->requestTime,
            'request_source'=> $this->fromSource ? $this->fromSource : '',
            'return_time'   => time(),
            'method'        => $this->method ? $this->method : '',
            'request_params'=> $this->postParams ? json_encode($this->postParams) : '',
            'return_params' => $result,
            'response_time' => $responseTime,
            'error'         => isset($data['code']) ? intval($data['code']) : 0,
        ];
        $apiLogId = db('apilog_app')->insertGetId($addData);
        exit();
    }
    
    /**
     * 参数签名生成算法
     * @param array $params  key-value形式的参数数组
     * @param string $signkey 参数签名密钥
     * @return string 最终的数据签名
     */
    protected function getSign($params, $signkey)
    {
        //除去待签名参数数组中的空值和签名参数(去掉空值与签名参数后的新签名参数组)
        $para = array();
//        while (list ($key, $val) = each ($params)) {
        foreach ($params as $key => $val) {
            if($key == 'sign' || $key == 'signkey' || $val === "")continue;
            else	$para [$key] = $params[$key];
        }
        //对待签名参数数组排序
        ksort($para);
        reset($para);
        
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr  = "";
//        while (list ($key, $val) = each ($para)) {
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                $prestr.= $key."=".implode(',', $val)."&";
            }else{
                $prestr.= $key."=".$val."&";
            }
        }
        //去掉最后一个&字符
        $prestr = substr($prestr,0,count($prestr)-2);
        
        //字符串末端补充signkey签名密钥
        $prestr = $prestr . $signkey;
        //生成MD5为最终的数据签名
        $mySgin = md5($prestr);
        return $mySgin;
    }
    /**
     * 验证系统级参数
     * @param array $data
     */
    protected function verifySignParam($data = [])
    {
        // 验证必填参数
        if (!$this->postParams) {
            $this->_returnMsg(array('code' => 1, 'msg' => '请求参数异常'));
        }
        $this->method = isset($this->postParams['method']) ?  trim($this->postParams['method']) : '';
        if (!$this->method) {
            $this->_returnMsg(array('code' => 1, 'msg' => '接口方法(method)缺失'));
        }
        if (!method_exists($this, $this->method)) {
            $this->_returnMsg(array('code' => 1, 'msg' => '接口方法(method)错误'));
        }
        /* //验证签名参数
        if($this->postParams['method'] == 'uploadImg') {
            unset($this->postParams['file']);#上传文件接口去掉file字段验证签名
        } */
        $timestamp = isset($this->postParams['timestamp']) ?  trim($this->postParams['timestamp']) : '';
        if(!$timestamp) {
            $this->_returnMsg(array('code' => 1,'msg' => '请求时间戳(timestamp)参数缺失'));
        }
        $len = strlen($timestamp);
        if($len != 10 && $len != 13) {//时间戳长度格式不对
            $this->_returnMsg(array('code' => 1, 'msg' => '时间戳格式错误'));
        }
        if (strlen($timestamp) == 13) {
            $this->postParams['timestamp'] = substr($timestamp, 0, 10);
        }
        if($timestamp + 180 < time()) {//时间戳已过期(60秒内过期)
            $this->_returnMsg(array('code' => 1, 'msg' => '请求已超时'));
        }
        if(!$this->signKey) {
            $this->_returnMsg(array('code' => 1,'msg' => '签名密钥(signkey)参数缺失'));
        }
        if(!in_array($this->signKey, $this->signKeyList)) {
            $this->_returnMsg(array('code' => 1,'msg' => '签名密钥错误'));
        }
        if (isset($data['file'])) {
            unset($data['file']);
        }
        $postSign = isset($this->postParams['sign']) ?  trim($this->postParams['sign']) : '';
        if (!$postSign) {
            $this->_returnMsg(array('code' => 1,'msg' => '签名(sign)参数缺失'));
        }
        $sign = $this->getSign($data, $this->signKey);
        if ($postSign != $sign) {
            $this->_returnMsg(array('code' => 1,'msg' => '签名错误', 'correct' => $sign));
        }
    }
    
    /**================================展会对应接口=================================*/
    //获取上一个整点的中奖信息
    protected function getActivityList()
    {
        $config = cache('win_data');
        $totalNum = isset($config['total_num']) ? $config['total_num']: 0;  //总中奖数量
        //今日开始时间戳
        $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
        
        //获取当前内最后一次抽奖数据
        $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
        $list = db('win_log')->field('"" as name, nickname, phone')->where(['type' => 1, 'add_time' => ['>', $dayBeginTime]])->order('add_time DESC') ->limit(0, $totalNum)->select();
        $return = [
            'title' => '活动说明：',
            'msg'   => [
                '1. 展会期间，我们每个整点会在参观值得看展区的客户中抽取5位幸运客户，赠送超值礼品！',
                '2. 请中奖的用户凭中奖通知到我们的服务台领奖。'
            ],
            'list'  => [],
        ];
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['nickname'] = $this->starReplace($value['nickname'], 1);
                $list[$key]['phone'] = substr_replace($value['phone'], '*** **** ', 0, 7);
            }
            $return['list'] = $list;
        }
        $this->_returnMsg(['list' => $return]);
    }
    //提交手机号抽奖
    protected function joinActivity()
    {
        $phone = isset($this->postParams['phone']) ? trim($this->postParams['phone']) : '';
        if (!$phone){
            $this->_returnMsg(['code' => 1, 'msg' => '手机号(phone)缺失']);
        }
        if (strlen($phone) != 11) {
            $this->_returnMsg(['code' => 1, 'msg' => '手机号(phone)格式错误']);
        }
        $winModel = db('win_log');
        //判断手机号是否已抽奖
        $data = [
            'type' => 2,
            'phone' => $phone,
        ];
        $exist = $winModel->where($data)->find();
        if ($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前手机号码已参与过活动']);
        }
        $config = cache('win_data');
        $probability = isset($config['probability']) ? floatval($config['probability']) : 1;
        $lastNum = $totalNum = 10;
        if ($totalNum <= 0) {
            $probability = 0;
        }else{
            //获取已中奖数量
            $existNum = $winModel->where(['type' => 2, 'phone' => ['NEQ', ''], 'status' => ['NEQ', -1]])->count();
            $lastNum = $totalNum > $existNum ? $totalNum - $existNum : 0;
            if ($lastNum <= 0) {
                $probability = 0;
            }else{
                switch (date('Ymd')) {
                    case '20181203':
                        $dayTotalNum = 10;
                        break;
                    case '20181206':
                        $dayTotalNum = 5;
                        break;
                    case '20181207':
                        $dayTotalNum = 5;
                        $dayTotalNum += ($lastNum -  5);
                        break;
                    case '20181208':
                        $dayTotalNum = 0;
                        $dayTotalNum += $lastNum;
                        break;
                    default:
                        $dayTotalNum = 0;
                        break;
                }
                if ($dayTotalNum <= 0) {
                    $probability = 0;
                }else{
                    //获取今日已中奖数量
                    $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
                    $todayExistNum = $winModel->where(['type' => 2, 'phone' => ['NEQ', ''], 'status' => ['NEQ', -1], 'add_time' => ['>', $dayBeginTime]])->count();
                    $dayLastNum = $dayTotalNum > $todayExistNum ? $dayTotalNum - $todayExistNum : 0;
                    if ($dayLastNum <= 0) {
                        $probability = 0;
                    }
                }
            }
        }
        if ($probability > 0) {
            $num = intval($probability * 100);
            $total = 100 * 100;
            $num1 = $total - $num;
            $prizeArr = [
                ['id' => 1, 'prize' => 1, 'v' => $num],
                ['id' => 2, 'prize' => 0, 'v' => $num1],
            ];
            $arr = [];
            foreach ($prizeArr as $key => $val) {
                $arr[$val['id']] = $val['v'];
            }
            $rid = $this->_getRand($arr); //根据概率获取奖项id
            $flag = $prizeArr[$rid-1]['prize'];//中奖结果(是否中奖)
        }else{
            $flag = 0;
        }
        
        if (!$exist) {
            $data = [
                'type'      => 2,
                'phone'     => $phone,
                'add_time'  => time(),
            ];
            if ($flag) {
                $data['status'] = 0;//中奖未领奖
            }else{
                $data['status'] = -1;//没中奖
            }
            $result = $winModel->insertGetId($data);
        }elseif ($exist && $exist['status'] == -1 && $flag){
            $result = $winModel->where(['log_id' => $exist['log_id']])->update(['add_time' => time(), 'status' => 0]);
        }
        $this->_returnMsg(['prize' => $flag]);
    }
    private function _getRand($proArr) {
        $result = '';
        
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        
        return $result;
    }
    
    /*
     * 作用：用*号替代姓名除第一个字之外的字符
     * 参数：
     * 返回值：string
     */
    private function starReplace($name, $num = 0)
    {
        $doubleSurname = [
            '欧阳', '太史', '端木', '上官', '司马', '东方', '独孤', '南宫',
            '万俟', '闻人', '夏侯', '诸葛', '尉迟', '公羊', '赫连', '澹台', '皇甫', '宗政', '濮阳',
            '公冶', '太叔', '申屠', '公孙', '慕容', '仲孙', '钟离', '长孙', '宇文', '司徒', '鲜于',
            '司空', '闾丘', '子车', '亓官', '司寇', '巫马', '公西', '颛孙', '壤驷', '公良', '漆雕', '乐正',
            '宰父', '谷梁', '拓跋', '夹谷', '轩辕', '令狐', '段干', '百里', '呼延', '东郭', '南门', '羊舌',
            '微生', '公户', '公玉', '公仪', '梁丘', '公仲', '公上', '公门', '公山', '公坚', '左丘', '公伯',
            '西门', '公祖', '第五', '公乘', '贯丘', '公皙', '南荣', '东里', '东宫', '仲长', '子书', '子桑',
            '即墨', '达奚', '褚师', '吴铭'
        ];
        
        $surname = mb_substr($name, 0, 2);
        if (in_array($surname, $doubleSurname)) {
            $name = mb_substr($name, 0, 2) . str_repeat('*', (mb_strlen($name, 'UTF-8') - 2));
        } else {
            $name = mb_substr($name, 0, 1) . str_repeat('*', (mb_strlen($name, 'UTF-8') - 1));
        }
        return $name;
    }
    
}    