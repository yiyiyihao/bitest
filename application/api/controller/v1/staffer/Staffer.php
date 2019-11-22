<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/28 0028
 * Time: 18:04
 */
namespace app\api\controller\v1\staffer;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class Staffer extends Api
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    public function getgroups()
    {
        $where = [
            ['ugroup_id', 'IN', [2, 3, 4]],
        ];
        $list = db('user_group') -> where($where)->field('ugroup_id, name')-> select();
        
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['list' => $list]]);die;
    }
    public function stafferList()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
//        $params['store_id'] = $this->userInfo['store_ids'];

        $request = new \think\Request;
        $storeInfoModel = new \app\api\controller\v1\Login($request);
        $params['store_id'] = $storeInfoModel->getUserStores($this->userInfo)['store_ids'];

        $count = db('store_member') -> alias('SM') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
        $data = db('store_member') -> alias('SM') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> join($this ->_getJoin($params)) ->order('U.add_time DESC') -> limit(($page-1)*$size,$size) -> select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }

    public function staffAdd($sid = 0, $gid = 0)
    {
        $params = $this -> postParams;
        $token = $params['token'];
        $type = isset($params['type']) ? intval($params['type']) : 0;
//        if ($this->userInfo['user_id'] !== 1) {
//            $storeId = $this->userInfo['store_id'];
//        }else{
            $storeId = isset($params['sid']) ? intval($params['sid']) : 0;
//        }

        if(!$storeId){
            $this->_returnMsg(['code' => 1, 'msg' => '门店id缺失']);die;
        }
        $stafferType = $this->stafferType($storeId);
        if ($type > 0 && isset($stafferType[$type]) && $stafferType[$type]) {
            $name = $stafferType[$type]['name'];
            $isAdmin = $stafferType[$type]['is_admin'];
            $groupId = $stafferType[$type]['group_id'];
            $max = $stafferType[$type]['max_num'];

        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        //判断当前门店是否存在员工(验证员工最大数量)
        $count = db('store_member')->where(['store_id' => $storeId, 'is_del' => 0, 'group_id' => $groupId, 'is_admin' => $isAdmin])->count();
        if ($count >= $max) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店 '.$name.' 数量已达最大值('.$max.')']);die;
        }
        $user = '';
        $fuserId = isset($params['fuser_id']) ? intval($params['fuser_id']) : 0;    //识别的人脸用户Id(face_user表ID)
        if ($fuserId) {
            $fuserInfo = db('face_user')->where(['fuser_id' => $fuserId])->find();
            if (!$fuserInfo) {
                $this->_returnMsg(['code' => 1, 'msg' => '参数fuser_id错误，请刷新后重试']);die;
            }
            $user = db('user')->where(['fuser_id' => $fuserId, 'is_del' => 0])->find();
            if ($user) {
                $userId = $user['user_id'];
                //判断当前人脸用户在当前门店是否已创建会员
                $exist = db('store_member')->where(['is_del' => 0, 'fuser_id' => $fuserId, 'user_id' => $userId])->find();
                if ($exist && ($exist['is_admin'] > 0 || $exist['group_id'] > 0)) {
                    $this->_returnMsg(['code' => 1, 'msg' => '当前用户已经是员工，到员工列表设置']);die;
                }
            }
        }

        $faceApi = new \app\common\api\BaseFaceApi();
        $avatar = isset($params['avatar']) ? trim($params['avatar']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        $password = isset($params['password']) ? trim($params['password']) : '';
        $realname = isset($params['realname']) ? trim($params['realname']) : '';
        $faceId = isset($params['face_id']) ? trim($params['face_id']) : '';        //识别的人脸用户唯一标识(腾讯)
        if (!$fuserId) { //无可绑定的现有人脸信息(需要使用当前上传的头像进行图片系别)
            if (!$avatar) {
                $this->_returnMsg(['code' => 1, 'msg' => '请上传会员头像']);die;
            }
        }else{
            $params['avatar'] = $fuserInfo['avatar'];
            $faceId = $faceId ? $faceId : db('face_token')->where(['face_token' => $fuserInfo['face_token']])->value('face_id');
        }
        $userService = new \app\common\service\User();
        if (!$user) {
            //如果图片对应face_id没有创建user账户(则须验证会员信息)
            if (!$phone) {
                $this->_returnMsg(['code' => 1, 'msg' => $name.'手机号不能为空']);die;
            }
//            if (!$password && ($type != 4)) {
//                //$this->error($name.'登录密码不能为空');
//                $this->_returnMsg(['code' => 1, 'msg' => $name.'登录密码不能为空']);die;
//            }
            if (!$realname) {
                $this->_returnMsg(['code' => 1, 'msg' => $name.'真实姓名不能为空']);die;
            }
            $params['username'] = $phone;
        }
        $checkResult = $userService->_checkFormat($params);
        if ($checkResult === FALSE) {
            //$this->error($userService->error);
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }
//        $faceData = [
//            'is_admin' => $isAdmin,
//            'group_id' => $groupId,
//        ];
//        if ($faceId && $fuserId) {
//            $faceData['search_face_id'] = $faceId;
//            $faceData['search_fuser_id'] = $fuserId;
//        }
        //无人脸信息或已识别但是不绑定
//        $faceResult = $faceApi->faceRecognition($avatar, false, $storeId, $faceData);
//        if ($faceResult['errCode'] > 0) {
//            //$this->error($faceResult['msg']);
//            $this->_returnMsg(['code' => 1, 'msg' => $faceResult['msg']]);die;
//        }else{
//            $fuserId = $faceResult['fuser_id'];
////                 if ($pfid && $pfid == $fuserId) {
////                     $this->error('上传头像与系统匹配头像 相似度超过99%,不允许重新添加');
////                 }
//        }
        if (!$user) {
            $params['fuser_id'] = $fuserId;
            $params['is_admin'] = $isAdmin;
            $params['group_id'] = $groupId;
            //重新创建User信息
            $obj = new \app\service\service\Zhongtai();
            $openid = $obj->register($phone,$phone,md5($password),md5($password),$token);
            $userId = $userService->register($phone, $password, $params,0,1,$token,$openid);
            if (!$userId) {
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
            }
        }else{
            $udata = [
                'update_time' => time(),
                'fuser_id'  => $fuserId,
                'is_admin'  => $isAdmin,
                'group_id'  => $groupId,
            ];
            if ($password) {
                $udata['password'] = $password;
            }
            $result = $userService->update($userId, FALSE, $udata);
            if (!$result) {
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
            }
        }
        if (!isset($exist) || !$exist) {
            $data = [
                'store_id'  => $storeId,
                'fuser_id'  => $fuserId,
                'user_id'   => $userId,
                'add_time'  => time(),
                'update_time'=> time(),
                'status'    => isset($params['status']) ? intval($params['status']): 1,
                'is_admin'  => $isAdmin,
                'group_id'  => $groupId,
            ];
            $result = db('store_member')->insertGetId($data);
        }else{
            $data = [
                'update_time'=> time(),
                'status'    => isset($params['status']) ? intval($params['status']): 1,
                'is_admin'  => $isAdmin,
                'group_id'  => $groupId,
            ];
            $result = db('store_member')->where(['member_id' => $exist['member_id']])->update($data);
        }
        if($result === false){
            $this->_returnMsg(['code' => 1, 'msg' => $name.'添加失败'.db('store_member')->error]);die;
        }else{
            $labels = isset($params['labels']) ? explode(',',$params['labels']) : [];
            foreach($labels as $v){
                $resul = db('member_label') -> insert(['member_id'=>$result,'label_id'=>$v]);
                $re = db('label') -> where('label_id','=',$v) -> setInc('quote',1);
            }
            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['res' => $result]]);die;
        }
    }
    public function stafferEdit()
    {
        $params = $this -> postParams;
        $storeId = isset($params['sid']) ? $params['sid'] : 0;
        $memberId = isset($params['id']) ? $params['id'] : 0;
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        $password = isset($params['pwd']) ? trim($params['pwd']) : '';
        $pwd_comfirm = isset($params['pwd_ok']) ? trim($params['pwd_ok']) : '';
        $realname = isset($params['realname']) ? trim($params['realname']) : '';
        $groupId = isset($params['group_id']) ? trim($params['group_id']) : '';

        if(!$storeId || !$memberId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数缺失']);die;
        }
        $info = db('store_member') -> where([['member_id','=',$memberId],['is_del','=',0]]) -> find();
        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $user = db('user')->where(['user_id' => $info['user_id'], 'is_del' => 0,]) -> where('is_admin' , '<>', 0)->find();
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => '账号不存在']);die;
        }
        if ($info['user_id'] == 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '平台管理员账户不允许修改']);die;
        }
        if ($password != $pwd_comfirm){
            $this->_returnMsg(['code' => 1, 'msg' => '密码和确认密码不一致']);die;
        }
//        $groupId = $user['group_id'];
        if ($groupId == STORE_SUPER_ADMIN) {
            $type = 1;
            $isadmin = 1;
        }elseif ($groupId == STORE_MANAGER){
            $type = 2;
            $isadmin = 2;
        }elseif ($groupId == STORE_CLERK){
            $type = 3;
            $isadmin = 2;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '不允许切换到该角色']);die;
        }
        //判断当前门店是否存在员工(验证员工最大数量)
        $stafferType = $this->stafferType($storeId);
        if ($type > 0 && isset($stafferType[$type]) && $stafferType[$type]) {
            $name = $stafferType[$type]['name'];
            $isAdmin1 = $stafferType[$type]['is_admin'];
            $groupId1 = $stafferType[$type]['group_id'];
            $max = $stafferType[$type]['max_num'];

        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $count = db('store_member')->where(['store_id' => $storeId, 'is_del' => 0, 'group_id' => $groupId1, 'is_admin' => $isAdmin1])->where('member_id','<>',$memberId)->count();
        if ($count >= $max) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店 '.$name.' 数量已达最大值('.$max.')']);die;
        }


        $extra = [
            'realname' => $realname,
            'group_id' => $groupId,
            'is_admin' => $isadmin,
        ];
        if ($user['phone'] != $phone) {
            $extra['phone'] = $phone;
            $extra['username'] = $phone;
        }
        if ($password) {
            $extra['password'] = $password;
        }
        $userService = new \app\common\service\User();
        $result = $userService->_checkFormat($extra);
        if ($result === false) {
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }
        $result = $userService->update($user['user_id'], $password, $extra,1);
        if ($result === false) {
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }

        $data = [
            'is_admin' => $isadmin,
            'group_id' => isset($groupId) ? $groupId : $info['group_id'],
            'update_time'=> time(),
            'status'    => isset($params['status']) ? intval($params['status']): $info['status'],
        ];
        $memberId = db('store_member')->where(['member_id' => $info['member_id']])->update($data);
        if($memberId === false){
            $this->_returnMsg(['code' => 1, 'msg' => $name.'编辑失败']);die;
        }else{
            $this->_returnMsg(['code' => 0, 'msg' => $name.'编辑成功']);die;
        }
    }
    public function stafferDel(){
        $params = $this -> postParams;
        $storeId = isset($params['sid']) ? $params['sid'] : 0;
        $memberId = isset($params['id']) ? $params['id'] : 0;
        $info = db('store_member') -> where('member_id','=',$memberId) -> find();
        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $user = db('user')->where(['user_id' => $info['user_id'], 'is_del' => 0])->find();
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => '账号不存在']);die;
        }
        if ($info['user_id'] == 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '平台管理员账户不允许删除']);die;
        }
        $groupId = $user['group_id'];
        if ($groupId == STORE_SUPER_ADMIN) {
            $type = 1;
        }elseif ($groupId == STORE_MANAGER){
            $type = 2;
        }elseif ($groupId == STORE_CLERK){
            $type = 3;
        }else{
            $type = 0;
        }
        if (!isset($this->stafferType($storeId)[$type])) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $name = $this->stafferType($storeId)[$type]['name'];
        if ($this->userInfo['user_id'] !== 1) {
            if ($info['user_id'] == $this->userInfo['user_id']) {
                $this->_returnMsg(['code' => 1, 'msg' => '不允许删除自己']);die;
            }
            if ($this->stafferType($storeId)[$type]['group_id'] == 2) {
                $this->_returnMsg(['code' => 1, 'msg' => '不允许删除门店管理员']);die;
            }
        }
        $user = db('user')->where(['user_id' => $info['user_id'], 'is_del' => 0])->find();
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => $name.'对应账户不存在,请检查数据']);die;
        }
//         $result = db('store_member') ->where(['member_id' => $info['member_id']])->update(['update_time' => time(), 'is_admin' => 0, 'group_id' => 0]);
        $result = db('store_member') ->where(['member_id' => $info['member_id']])->update(['update_time' => time(), 'is_del' => 1]);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }else{
            $flag = TRUE;
            //判断管理员角色是否为店长(店长可管理多家门店)
            if ($user['group_id'] == STORE_MANAGER) {
                $exist = db('store_member')->where(['user_id' => $info['user_id'], 'is_del' => 0, 'is_admin' => 2, 'group_id' => STORE_MANAGER])->find();
                if ($exist) {
                    $flag = FALSE;
                }
            }
            if ($flag) {
                $result = db('user')->where(['user_id' => $info['user_id']])->update(['update_time' => time(), 'is_admin' => 0, 'group_id' => 0]);
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '删除成功']);die;
    }
    public function setGroup()
    {
        $params = $this -> postParams;
        $memberId = isset($params['id']) ? $params['id'] : 0;
        $groupId = isset($params['group_id']) ? $params['group_id'] : -1;
        if(!$memberId || $groupId == -1){
            $this->_returnMsg(['code' => 1, 'msg' => '参数缺失']);die;
        }
        $info = db('store_member') -> where('member_id','=',$memberId) -> find();

        if ($groupId < 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $isAdmin = 2;
        if ($groupId == STORE_MANAGER) {
            //设置成为店长
            $name = '成为店长';
            //判断当前用户是否为员工
            if ($info['group_id'] != STORE_CLERK) {
                $this->_returnMsg(['code' => 1, 'msg' => '用户不是店员,不能成为店长']);die;
            }
            if ($info['group_id'] == STORE_MANAGER) {
                $this->_returnMsg(['code' => 1, 'msg' => '已经是店长，不能重复操作']);die;
            }
            //判断店长数量
            $count = db('store_member')->where(['group_id' => STORE_MANAGER, 'store_id' => $info['store_id'], 'member_id' => ['<>', $info['member_id']]])->count();
            if ($count >= 1) {
                $this->_returnMsg(['code' => 1, 'msg' => '当前门店已经有店长，请先取消后再操作']);die;
            }
        }elseif ($groupId == STORE_CLERK){
            //取消店长成为普通员工
            $name = '取消店长';
            //判断当前用户是否为店长
            if ($info['group_id'] != STORE_MANAGER) {
                $this->_returnMsg(['code' => 1, 'msg' => '用户不是店长,不能取消']);die;
            }
        }elseif ($groupId == USER){
            //会员成为店员
            $name = '成为店员';
            if ($info['group_id'] != USER) {
                $this->_returnMsg(['code' => 1, 'msg' => '用户不是会员,不能成为店员']);die;
            }
            $groupId = STORE_CLERK;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $result = db('store_member')->where(['member_id' => $info['member_id']])->update(['group_id' => $groupId, 'is_admin' => $isAdmin, 'update_time' => time()]);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $name.'失败']);die;
        }else{
            $result = db('user')->where(['user_id' => $info['user_id']])->update(['group_id' => $groupId, 'is_admin' => $isAdmin, 'update_time' => time()]);
            $this->_returnMsg(['code' => 1, 'msg' => $name.'成功']);die;
        }
    }

    public function stafferType($storeId = 0)
    {
        $typeArray = [];
        $typeArray[1] = [
            'name' => '管理员',
            'is_admin' => 1,
            'group_id' => $storeId == 1 ? 1 : 2,
            'max_num' => 1,
        ];
        $typeArray[2] = [
            'name' => '店长',
            'is_admin' => 2,
            'group_id' => 3,
            'max_num' => 1,
        ];
        $typeArray[3] = [
            'name' => '店员',
            'is_admin' => 2,
            'group_id' => 4,
            'max_num' => 10,
        ];
        $typeArray[4] = [
            'name' => '会员',
            'is_admin' => 0,
            'group_id' => 0,
            'max_num' => 1000000,
        ];
        return $typeArray;
    }

    //修改当前登入用户信息
    public function userinfo()
    {
        $info = db('user')->where(['user_id' => ADMIN_ID])->find();
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['info'=>$info]]);die;
    }
    /**
     * 修改当前登入用户信息
     */
    public function profile()
    {
        $params = $this -> postParams;

        $oldPwd = isset($params['old_pwd']) ? trim($params['old_pwd']) : '';
        $newPwd = isset($params['new_pwd']) ? trim($params['new_pwd']) : '';
        $comfirmPwd = isset($params['comfirm_pwd']) ? trim($params['comfirm_pwd']) : '';
        $token = isset($params['token']) ? trim($params['token']) : '';
        if(cache($token)['admin_user']['password'] != $oldPwd){
            $this->_returnMsg(['code' => 1, 'msg' => '原来的密码错误']);die;
        }
        if($newPwd != $comfirmPwd){
            $this->_returnMsg(['code' => 1, 'msg' => '设置的新密码，确认密码不一致']);die;
        }
        $userService = new \app\common\service\User();
        $result = $userService->update(ADMIN_ID, $newPwd);
        if($result === false){
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }else{
            $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
        }
    }

    function _getField($params){
        return 'U.*, SM.*, UG.name, S.name as sname';
    }
    function _getJoin($params)
    {
        $join = [
            ['user U', 'SM.user_id = U.user_id', 'LEFT'],
            ['store S', 'SM.store_id = S.store_id', 'LEFT'],
        ];
        $action = $this->request->action();
        if ($action == 'index') {
            $join[] = ['user_grade UG', 'UG.grade_id = SM.grade_id', 'LEFT'];
        }else{
            $join[] = ['user_group UG', 'UG.ugroup_id = SM.group_id', 'LEFT'];
        }
        return $join;
    }
    function _getWhere($params)
    {
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $store_id = isset($params['sid']) ? intval($params['sid']) : 0;
        $where = [
            ['U.is_admin','<>', 0],
            ['U.user_id','<>', 1],
            ['U.is_del' ,'=',0],
            ['SM.is_del','=',0],
        ];

//        $store = db('store')->where(['store_id' => $store_id, 'is_del' => 0])->find();
//        if ($store_id) {
//            $where[] = ['SM.store_id','=',isset($store['store_id'])?$store['store_id']:-1];
//        }else{
//            $where[] = ['SM.store_id','=',$storeId];
//        }
        $storeIds = isset($params['store_id']) ? $params['store_id'] : [];
        $where[] = ['SM.store_id','IN',$storeIds];
        $name = isset($params['name']) ? trim($params['name']) : '';
        if($name){
            $where[] = ['U.realname|U.nickname|U.phone|U.username','like','%'.$name.'%'];
        }
        return $where;
    }

    //短信验证码发送
    public function resetPwd()
    {
        $params = $this -> postParams;
        $phone = isset($params['manager_phone']) ? trim($params['manager_phone']) : '';
        $manager_id = isset($params['manager_id']) ? trim($params['manager_id']) : '';
        $manager_name = isset($params['manager_name1']) ? trim($params['manager_name1']) : '';
        if($manager_id==1){
            $this->_returnMsg(['code' => 1, 'msg' => 'admin不给重置密码']);die;
        }

        if(!$manager_id){
            $this->_returnMsg(['code' => 1, 'msg' => '管理员id缺失']);die;
        }
        if(!$phone){
            $this->_returnMsg(['code' => 1, 'msg' => '手机号缺失']);die;
        }
        //验证手机号格式
        $userService = new \app\common\service\User();
        $result = $userService->_checkFormat(['phone' => $phone]);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }
//        //判断短信验证码发送时间间隔
        $exist = db('log_code')->where(['phone' => $phone])->order('add_time DESC')->find();
        if ($exist && $exist['add_time'] + 120 >= time()) {
            $this->_returnMsg(['code' => 1, 'msg' => '验证码发送太频繁，请稍后再试']);die;
        }
        //生成随机密码
        $code = get_nonce_str(6, 1);
//        $res = db('user') -> where('username','=',$phone) -> update(['password'])
        $result = $userService->update($manager_id, $code);
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }
        $smsApi = new \app\common\api\SmsApi();
//        $code = $smsApi->getSmsCode();
        $param = [
            'name' => $manager_name,
            'password' => $code
        ];
        $data = [
            'code'  => $code,
            'phone' => $phone,
            'type'  => 'resetpwd',
            'add_time' => time(),
            'status' => 0,
        ];
        $smsId = db('log_code')->insertGetId($data);
//        if ($smsId === FALSE) {
//            $this->_returnMsg(['code' => 1, 'msg' => '验证码发送异常']);
//        }
        $result = $smsApi->send($phone, 'resetpwd', $param);
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

    public function experiencerList()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $where = [['group_id','=',EXPERIENCER],['is_del','=',0],['status','=',1]];
        $count = db('user') -> where($where) -> count();
        $data = db('user') -> where($where) -> field('user_id,username,email') ->order('add_time DESC') -> limit(($page-1)*$size,$size) -> select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }
    //添加体验帐号
    public function addExperiencer()
    {
        $params = $this -> postParams;
        $username = !empty($params['username']) ? trim($params['username']) : '';
        $password = !empty($params['password']) ? trim($params['password']) : '';
        $devices = !empty($params['devices']) ? $params['devices'] : [];

        if(!$username || !$password){
            $this->_returnMsg(['code' => 1, 'msg' => '用户名，密码不能为空']);die;
        }
        $return = db('user') -> where('username','=',$username)->where('is_del','=',0)->find();
        if($return){
            $this->_returnMsg(['code' => 1, 'msg' => '帐号名称已存在']);die;
        }

//        $userService = new \app\common\service\User();
//        $userId = $userService->register($phone, $password, $params);



        $data = [
            'username'  => $username,
            'password'  => md5($password),
            'nickname'  => $username,
            'realname'  => isset($extra['realname']) ? $username : '',
            'avatar'    => 'http://face.worthcloud.net/2019-04-19-11-11-35_01_1366_0630_140_176.jpeg',
            'phone'     => $username,
            'email'     => !empty($devices) ? implode(',',$devices) : '',
            'age'       => isset($extra['age']) ? intval($params['age']) : 0,
            'gender'    => isset($params['gender']) ? intval($params['gender']) : 0,
            'group_id'  => 5,
            'is_admin'  => 1,
            'add_time'  => time(),
            'update_time'=> time(),
            'fuser_id'  => isset($params['fuser_id']) ? intval($params['fuser_id']) : 0,
        ];
        $userId = db('user')->insertGetId($data);

        $data1 = [
            'store_id'  => 1,
            'fuser_id'  => 0,
            'user_id'   => $userId,
            'add_time'  => time(),
            'update_time'=> time(),
            'status'    => isset($params['status']) ? intval($params['status']): 1,
            'is_admin'  => 1,
            'group_id'  => 5,
        ];
        $result = db('store_member')->insertGetId($data1);

        if(!$userId || !$result){
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;

    }

    //编辑体验帐号（设置拥有设备）
    public function editExperiencer()
    {
        $params = $this -> postParams;
        $id = !empty($params['id']) ? trim($params['id']) : '';
        $info = db('user') -> where('user_id','=',$id)->where('is_del','=',0)->find();
        if(!$info){
            $this->_returnMsg(['code' => 1, 'msg' => '用户不存在']);die;
        }

        $password = !empty($params['password']) ? trim($params['password']) : $info['password'];
        $status = !empty($params['status']) ? trim($params['status']) : $info['status'];
        $devices = !empty($params['devices']) ? implode(',',$params['devices']) : $info['email'];

        $update = ['email'=>$devices,'status'=>$status,'password'=>$password];
        $res = db('user') -> where('user_id','=',$id)->update($update);
        if($res === false){
            $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
    }


    public function stafferAdd()
    {
        $params = $this -> postParams;
        $type = isset($params['type']) ? intval($params['type']) : 0;
        $gradeId = isset($params['grade_id']) ? intval($params['grade_id']) : 0;
        $storeId = isset($params['sid']) ? intval($params['sid']) : 0;
        $token = $params['token'];
        if(!$storeId){
            $this->_returnMsg(['code' => 1, 'msg' => '门店id缺失']);die;
        }
        $stafferType = $this->stafferType($storeId);
        if ($type > 0 && isset($stafferType[$type]) && $stafferType[$type]) {
            $name = $stafferType[$type]['name'];
            $isAdmin = $stafferType[$type]['is_admin'];
            $groupId = $stafferType[$type]['group_id'];
            $max = $stafferType[$type]['max_num'];

        }else{
            $this->_returnMsg(['code' => 1, 'msg' => 'type参数错误']);die;
        }

        //判断当前门店是否存在员工(验证员工最大数量)
        $count = db('store_member')->where(['store_id' => $storeId, 'is_del' => 0, 'group_id' => $groupId, 'is_admin' => $isAdmin])->count();
        if ($count >= $max) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店 '.$name.' 数量已达最大值('.$max.')']);die;
        }
        $user = '';
        $fuserId = isset($params['fuser_id']) ? intval($params['fuser_id']) : 0;    //识别的人脸用户Id(face_user表ID)
        if ($fuserId) {
            $fuserInfo = db('face_user')->where(['fuser_id' => $fuserId])->find();
            if (!$fuserInfo) {
                $this->_returnMsg(['code' => 1, 'msg' => '参数fuser_id错误，请刷新后重试']);die;
            }
            $user = db('user')->where(['fuser_id' => $fuserId, 'is_del' => 0])->find();
            if ($user) {
                $userId = $user['user_id'];
                //判断当前人脸用户在当前门店是否已创建会员
                $exist = db('store_member')->where(['is_del' => 0, 'fuser_id' => $fuserId, 'user_id' => $userId])->find();
                if ($exist && ($exist['is_admin'] > 0 || $exist['group_id'] > 0)) {
                    $this->_returnMsg(['code' => 1, 'msg' => '当前用户已经是员工，到员工列表设置']);die;
                }
            }
        }

        $faceApi = new \app\common\api\BaseFaceApi();
        $avatar = isset($params['avatar']) ? trim($params['avatar']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        $password = isset($params['password']) ? trim($params['password']) : '';
        $passwordConfirm = isset($params['password_confirm']) ? trim($params['password_confirm']) : '';
        $realname = isset($params['realname']) ? trim($params['realname']) : '';
        $faceId = isset($params['face_id']) ? trim($params['face_id']) : '';        //识别的人脸用户唯一标识(腾讯)
        if (!$fuserId) { //无可绑定的现有人脸信息(需要使用当前上传的头像进行图片系别)
            if (!$avatar) {
                $this->_returnMsg(['code' => 1, 'msg' => '请上传会员头像']);die;
            }
        }else{
            $params['avatar'] = $fuserInfo['avatar'];
            $faceId = $faceId ? $faceId : db('face_token')->where(['face_token' => $fuserInfo['face_token']])->value('face_id');
        }
        $userService = new \app\common\service\User();
        if (!$user) {
            //如果图片对应face_id没有创建user账户(则须验证会员信息)
            if (!$phone) {
                $this->_returnMsg(['code' => 1, 'msg' => $name.'手机号不能为空']);die;
            }
            if ($type != 4) {
                if(!$password){
                    //$this->error($name.'登录密码不能为空');
                    $this->_returnMsg(['code' => 1, 'msg' => $name.'登录密码不能为空']);die;
                }
                if($password <> $passwordConfirm){
                    $this->_returnMsg(['code' => 1, 'msg' => $name.'确认密码不正确']);die;
                }
            }
            if (!$realname) {
                $this->_returnMsg(['code' => 1, 'msg' => $name.'真实姓名不能为空']);die;
            }
            $params['username'] = $phone;
        }
        $checkResult = $userService->_checkFormat($params);
        if ($checkResult === FALSE) {
            //$this->error($userService->error);
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }
        $faceData = [
            'is_admin' => $isAdmin,
            'group_id' => $groupId,
        ];
        if ($faceId && $fuserId) {
            $faceData['search_face_id'] = $faceId;
            $faceData['search_fuser_id'] = $fuserId;
        }
        //无人脸信息或已识别但是不绑定
        $faceResult = $faceApi->faceRecognition($avatar, false, $storeId, $faceData);
        if ($faceResult['code'] > 0) {
            //$this->error($faceResult['msg']);
            $this->_returnMsg(['code' => 1, 'msg' => $faceResult['error_msg']]);die;
        }else{
            $fuserId = $faceResult['fuser_id'];
//                 if ($pfid && $pfid == $fuserId) {
//                     $this->error('上传头像与系统匹配头像 相似度超过99%,不允许重新添加');
//                 }
        }
        if (!$user) {
            $params['fuser_id'] = $fuserId;
            $params['is_admin'] = $isAdmin;
            $params['group_id'] = $groupId;
            //重新创建User信息
            $obj = new \app\service\service\Zhongtai();
            $openid = $obj->register($phone,$phone,md5($password),md5($password),$token);
            $userId = $userService->register($phone, $password, $params,0,1,$token,$openid);
            if (!$userId) {
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
            }
        }else{
            $udata = [
                'update_time' => time(),
                'fuser_id'  => $fuserId,
                'is_admin'  => $isAdmin,
                'group_id'  => $groupId,
            ];
            if ($password) {
                $udata['password'] = $password;
            }
            $result = $userService->update($userId, FALSE, $udata);
            if (!$result) {
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
            }
        }
        if (!isset($exist) || !$exist) {
            $data = [
                'store_id'  => $storeId,
                'fuser_id'  => $fuserId,
                'user_id'   => $userId,
                'add_time'  => time(),
                'update_time'=> time(),
                'status'    => isset($params['status']) ? intval($params['status']): 1,
                'is_admin'  => $isAdmin,
                'group_id'  => $groupId,
                'grade_id'  => $gradeId,
            ];
            $result = db('store_member')->insertGetId($data);
        }else{
            $data = [
                'update_time'=> time(),
                'status'    => isset($params['status']) ? intval($params['status']): 1,
                'is_admin'  => $isAdmin,
                'group_id'  => $groupId,
//                'grade_id'  => $gradeId,
            ];
            $result = db('store_member')->where(['member_id' => $exist['member_id']])->update($data);
        }
        if($result === false){
            $this->_returnMsg(['code' => 1, 'msg' => $name.'添加失败'.db('store_member')->error]);die;
        }else{
            $labels = isset($params['labels']) ? explode(',',$params['labels']) : [];
            foreach($labels as $v){
                $resul = db('member_label') -> insert(['member_id'=>$result,'label_id'=>$v]);
                $re = db('label') -> where('label_id','=',$v) -> setInc('quote',1);
            }
            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['res' => $result]]);die;
        }
    }
}