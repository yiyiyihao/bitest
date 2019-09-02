<?php
namespace app\api\controller\v1\store;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class Store extends Api
{
    private $storeTypes;
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->storeTypes = [
            1 => [
                'name' => '实体门店',
            ],
            2 => [
                'name' => '虚拟门店',
            ],
            3 => [
                'name' => '商场',
            ],
        ];
    }

    public function getStoreList()
    {
        if(isset($this->postParams['lang']) && $this->postParams['lang'] == 'en-us'){
            \think\facade\Lang::range('en-us');
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/en-us.php';
            \think\facade\Lang::load($file);
        }else{
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/zh-cn.php';
            \think\facade\Lang::load($file);
        }

        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('store') -> alias('S') -> where($this->_getWhere($params)) -> count();
        $data = db('store') -> alias('S') -> where($this->_getWhere($params)) -> limit(($page-1)*$size,$size) -> select();
        //获取门店管理员名称
        if(!empty($data)){

                foreach ($data as $key => $value) {
                    $data[$key]['type'] = lang($this->storeTypes[$value['store_type']]['name']);
                    $where = [
                        'SM.store_id' => $value['store_id'],
                        'SM.is_del' => 0,
                    ];
                    if ($value['store_id'] == 1){
                        $where['SM.group_id'] = SYSTEM_SUPER_ADMIN;
                    }elseif ($value['store_type'] == 1) {
                        if ($value['parent_id'] != 0) {
                            $where['SM.group_id'] = STORE_MANAGER;
                        }else{
                            $where['SM.group_id'] = STORE_SUPER_ADMIN;
//                         $where['SM.group_id'] = ['IN', [STORE_SUPER_ADMIN, STORE_MANAGER]];
                        }
                    }else{
                        $where['SM.group_id'] = STORE_SUPER_ADMIN;
                    }
                    $adminUser = cache(input('token'))['admin_user'];
                    if($adminUser['group_id'] == 5){
                        $data[$key]['name'] = '体验帐号';
                        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>1,'page'=>1 ,'list' => [$data[$key]]]]);die;
                    }
                    $userinfo = db('store_member')->alias('SM')->join('user U', 'SM.user_id = U.user_id', 'INNER')->where($where)->find();
                    $data[$key]['manager_name'] = $userinfo['realname'].'  '.$userinfo['phone'];
                    $data[$key]['manager_id'] = $userinfo['user_id'];
                    $data[$key]['manager_phone'] = $userinfo['phone'];
                    $data[$key]['manager_name1'] = $userinfo['realname'];
//                    $codeObj = new Qrcode();
//                    $data[$key]['qrcode'] = $codeObj -> getQrcode($value['store_id']);
                }
        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;

    }

    //加一个判断，修改的情况就可以请求这个方法
    public function addStore()
    {
        if ($this->userInfo['user_id'] !== 1) {
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
        }
        $data = $this -> _getData();
        $params = $this -> postParams;
        $username = isset($params['username']) ? trim($params['username']) : '';
        $usermobile = isset($params['usermobile']) ? trim($params['usermobile']) : '';
        $password = isset($params['password']) ? trim($params['password']) : '';
        $pwd = isset($params['pwd']) ? trim($params['pwd']) : '';
        if(!$username){
            $this->_returnMsg(['code' => 1, 'msg' => '管理员名称缺失']);die;
        }
        if(!$usermobile){
            $this->_returnMsg(['code' => 1, 'msg' => '管理员手机号缺失']);die;
        }
        if(!$password || !$pwd){
            $this->_returnMsg(['code' => 1, 'msg' => '密码或确认密码缺失']);die;
        }

        if($password != $pwd){
            $this->_returnMsg(['code' => 1, 'msg' => '密码与确认密码不一致']);die;
        }

        $userService = new \app\common\service\User();
        $res = $userService->_checkFormat(['phone'=>$usermobile,'username'=>$usermobile,'password'=>$pwd]);
        if(!$res){
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }

        $pkId = db('store')->insertGetId($data);

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }

        //配置门店管理员

        $this->admin(null,null,$pkId,$username,$usermobile,$pwd);

    }

    public function delStore()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '门店id错误']);die;
        }
        if ($pkId == 1) {
            //$this->error('平台自营门店不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '平台自营门店不允许删除']);die;
        }
        //判断当前用户是否存在删除权限
        $storeVisit = $this->_checkStoreVisit($pkId, FALSE, FALSE);
        //c从缓存获取用户信息

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $groupId = cache($authorization)['admin_user']['group_id'];
        $storeIds = cache($authorization)['admin_user']['store_ids'];

        if ($groupId == 3 && $storeIds && in_array($pkId, $storeIds)) {
            //$this->error(lang('NO ACCESS'));
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
        }
        //判断当前门店下是否存在子
        $child = db('store')->where(['parent_id' => $pkId, 'is_del' => 0])->find();
        if ($child) {
            //$this->error('门店下存在子门店，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '门店下存在子门店，不允许删除']);die;
        }
        //判断当前门店下是否存在区域/设备
        $block = db('store_block')->where(['store_id' => $pkId, 'is_del' => 0])->find();
        if ($block) {
            //$this->error('门店下存在区域，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '门店下存在区域，不允许删除']);die;
        }
        $device = db('device')->where(['store_id' => $pkId, 'is_del' => 0])->find();
        if ($device) {
            //$this->error('门店下存在授权设备，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '门店下存在授权设备，不允许删除']);die;
        }
        //删除门店管理员后再删除门店
        $this->_delManager(['store_id' => $pkId]);
        //删除门店
        $result = db('store')->where(array('store_id' => $pkId))->update(array('is_del' => 1, 'update_time' => time()));
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_id' => $pkId]]);die;
    }

    public function editStore()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;
        if($pkId){
            if ($this->userInfo['user_id'] !== 1 && !in_array($pkId, $this->userInfo['store_ids'])) {
                $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
            }
            $data = $this->_getData();
            $username = isset($params['username']) ? trim($params['username']) : '';
            $usermobile = isset($params['usermobile']) ? trim($params['usermobile']) : '';
            $resul = db('user') -> where('username','=',$usermobile) -> update(['realname'=>$username]);

            $rs = db('store')->where(['store_id' => $pkId])->update($data);
            if($rs){
                $this->_returnMsg(['code' => 0, 'msg' => '修改成功','data' => ['store_id' => $pkId]]);die;
                //$this->success($msg, url("index", $routes), TRUE);
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
                //$this->error($msg);
            }
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }

    public function storeType($tag = false)
    {
        if(isset($this->postParams['lang']) && $this->postParams['lang'] == 'en-us'){
            \think\facade\Lang::range('en-us');
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/en-us.php';
            \think\facade\Lang::load($file);
        }else{
            $file = dirname(dirname(dirname(dirname(__FILE__)))).'/lang/zh-cn.php';
            \think\facade\Lang::load($file);
        }
        $storeTypes = [
            [
                'code' => 1,
                'name' => lang('实体门店'),
            ],
            [
                'code' => 2,
                'name' => lang('虚拟门店'),
            ],
            [
                'code' => 3,
                'name' => lang('商场'),
            ],
        ];
        if($tag){
            return $storeTypes;
        }else{
            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_type' => $storeTypes]]);die;
        }

    }
    //门店编辑是，门店信息
    public function info()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;
        if($pkId){

            $rs = db('store')->where(['store_id' => $pkId,'is_del' => 0])->find();
            if($rs){
                $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $rs]);die;
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '查询失败']);die;
            }
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }

    //配置管理员时，门店信息
    public function storeInfo($pkId = 0)
    {
        if(!$pkId){
            $params = $this -> postParams;
            $store_id = isset($params['id']) ? intval($params['id']) : null;
        }

        if(!$store_id){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $data = [];
        $store = db('store') -> where([['store_id','=',$store_id],['is_del','=',0]]) ->field('store_id,name') -> find();
        if(!$store_id){
            $this->_returnMsg(['code' => 1, 'msg' => '门店不存在']);die;
        }

        $where = ['SM.store_id' => $store_id, 'SM.is_del' => 0, 'SM.is_admin' => 1, 'SM.group_id' => 2];
        $result = db('store_member')->field('S.*,U.*, SM.*')->alias('SM')->join('user U', 'U.user_id = SM.user_id', 'left')->join('store S', 'S.store_id = SM.store_id', 'left')->where($where)->find();
        $data = $result;
        $data['store_name'] = $store['name'];
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_info' => $data]]);die;
    }
    /**
     * 配置管理员
     */
    public function admin($groupId = 0, $groupName = '',$store_id='',$username='',$usermobile='',$pwd='')
    {
        $groupId = $groupId ? $groupId : STORE_SUPER_ADMIN;
        $groupName = $groupName ? $groupName : '管理员';

        $params = $this->postParams;
        $fuserId = isset($params['fuser_id']) ? intval($params['fuser_id']) : 0;
//        $faceId = isset($params['face_id']) ? intval($params['face_id']) : '';        //识别的人脸用户唯一标识(腾讯)
        $faceImg = isset($params['avatar']) ? trim($params['avatar']) : '';
        $status = isset($params['status']) ? intval($params['status']) : 0;
        $userService = new \app\common\service\User();

        if(!empty($store_id)){
            $password = $pwd;
            $exist = '';
            $params['phone'] = $usermobile;
            $params['group_id'] = $groupId;
            $params['is_admin'] = 1;
            $params['realname'] = $username;
        }else {
            $store_id = isset($params['id']) ? intval($params['id']) : null;
            if (empty($store_id)) {
                $this->_returnMsg(['code' => 1, 'msg' => '门店id缺失']);
                die;
            }
            if ($store_id == 1 && $groupId == 2) {
                $this->_returnMsg(['code' => 1, 'msg' => '平台自营门店不允许修改管理员']);
                die;
            }
//        $this->_checkStoreVisit($store_id);


            //判断当前门店是否存在管理员
            $where = ['SM.store_id' => $store_id, 'SM.is_del' => 0, 'SM.is_admin' => 1, 'SM.group_id' => $groupId];
            $exist = db('store_member')->field('U.*, SM.*')->alias('SM')->join('user U', 'U.user_id = SM.user_id', 'INNER')->where($where)->find();


            $username = isset($params['username']) ? trim($params['username']) : '';
            $password = isset($params['password']) ? trim($params['password']) : '';
            if (!$exist) {
                $params['phone'] = $username;
                $params['group_id'] = $groupId;
                $params['is_admin'] = 1;
            }
        }
//        $result = $userService->_checkFormat();
//        if ($faceImg) {
//            if ($fuserId && $faceId) {
//                //判断当前人脸信息是否一帮绑定其他账号
//                $map = [
//                    ['is_del','=',0],
//                    ['fuser_id','=',$fuserId],
//                ];
//                if ($exist) {
//                    $map[] = ['store_id','<>', $exist['store_id']];
//                }
//                $userExist = db('store_member')->where($map)->find();
//                if ($userExist) {
//                    //$this->error('当前头像对应用户已经绑定其它账号啦~~');
//                    $this->_returnMsg(['code' => 1, 'msg' => '当前头像对应用户已经绑定其它账号啦~~']);die;
//                }
//            }else {
//                $faceData = [
//                    'is_admin' => 0,
//                ];
//                $faceApi = new \app\common\api\BaseFaceApi();
//                //无人脸信息或已识别但是不绑定
//                $faceResult = $faceApi->faceRecognition($faceImg, false, $store_id, $faceData);
//                if ($faceResult['code'] > 0) {
//                    //$this->error($faceResult['msg']);
//                    $this->_returnMsg(['code' => 1, 'msg' => $faceResult['msg']]);die;
//                }else{
//                    $fuserId = $faceResult['fuser_id'];
//                }
//            }
//        }
        if ($fuserId && $faceImg) {
            $params['face_img'] = $faceImg;
            $params['fuser_id'] = $fuserId;
        }
        if ($exist) {
            if (!$exist['avatar']) {
                $params['avatar'] = $faceImg;
            }
            if (!$exist['user_id']) {
                //$this->error('账号异常');
                $this->_returnMsg(['code' => 1, 'msg' => '账号异常']);die;
            }
            //修改管理员信息
            if (isset($params['status'])) {
                unset($params['status']);
            }
            $result = $userService->update($exist['user_id'], $password, $params);
            if ($result === false) {
                //$this->error($userService->error);
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
            }else{
                $data = ['update_time' => time()];
                if ($fuserId) {
                    $data['fuser_id'] = $fuserId;
                }

                db('store_member')->where([['member_id', '=', $exist['member_id']], ['status' ,'<>', $status]])->update($data);
                $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_id' => $store_id]]);die;
            }
        }else{
            $userId = $userService->register($usermobile, $password, $params);
            if ($userId === FALSE) {
                $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
            }
            //判断门店会员是否存在
            $data = [
                'card_no'   => $userService->_getUserCardNo(),
                'user_id'   => $userId,
                'store_id'  => $store_id,
                'group_id'  => $groupId,
                'is_admin'  => 1,
                'add_time'  => time(),
                'update_time'=> time(),
            ];
            if ($fuserId) {
                $data['fuser_id'] = $fuserId;
            }
            $result = db('store_member')->insertGetId($data);
            if ($result === false) {
                $this->_returnMsg(['code' => 1, 'msg' => '新增门店'.$groupName.'异常']);die;;
            }else{
                //  到app去注册用户
                $url = 'http://yoocam.worthcloud.tv/echodata/index.php/app/api/user_reg';
//                $url = 'http://www.echodata.cc/index.php/app/api/user_reg';
                $post_data = ['user_id'=>$userId,'user_name'=>$usermobile,'password'=>$password];
                curl_post_https($url, $post_data);
                $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_id' => $store_id]]);die;
            }
        }

    }

    /**
     * 删除门店管理员
     */
    public function deladmin()
    {
        $params = $this -> postParams;
        $storeId = isset($params['sid']) ? intval($params['sid']) : 0;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $parent_id = isset($params['pid']) ? intval($params['pid']) : 0;

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeIds = cache($authorization)['admin_user']['store_ids'];
        $group_id = cache($authorization)['admin_user']['group_id'];

        if (!$id || !$storeId) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数缺失']);die;
        }
        $info = db('store_member')->where(['member_id' => $id, 'is_del' => 0])->find();
        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '管理员不存在或已删除']);die;
        }
        $store = db('store') -> where('store_id','=',$storeId)-> find();

        $parent = db('store') -> where(['is_del' => 0, 'store_id' => $parent_id])->find();
        if (!$info && $group_id != 1 && !$parent) {
            //$this->error('没有操作权限');
            $this->_returnMsg(['code' => 1, 'msg' => '没有操作权限']);die;
        }
        if (!$store) {
            //$this->error('数据异常');
            $this->_returnMsg(['code' => 1, 'msg' => '数据异常']);die;
        }
        if ($info['store_id'] != $storeId) {
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
        }
        if ($storeId == 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '平台自营门店不允许修改管理员']);die;
        }
        $storeVisit = $this->_checkStoreVisit($store['store_id'], FALSE, FALSE);
        if (is_array($storeVisit) && $storeId == $store['store_id']) {
            $this->_returnMsg(['code' => 1, 'msg' => '平台自营门店不允许修改管理员']);die;
        }

        if ($group_id == 3 && $storeIds && in_array($store['store_id'], $storeIds)) {
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
        }
        $result = $this->_delManager($store, $id);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '不能重复删除']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_id' => $storeId]]);die;
    }

    private function _delManager($store = [], $memberId = 0)
    {
        if (!$store) {
            $this->_returnMsg(['code' => 1, 'msg' => '操作错误']);die;
        }
        $where = [
            'SM.store_id' => $store['store_id'],
            'U.is_del' => 0,
            'SM.is_del' => 0,
        ];
        if ($memberId) {
            $where['SM.member_id'] = $memberId;
        }
        $groupId = 2;
        $managerModel = db('store_member');
        $manager = $managerModel->alias('SM')->join('user U', 'SM.user_id = U.user_id', 'INNER')->where($where)->find();
        if ($memberId) {
            if ($manager && $groupId != $manager['group_id']) {
                return FALSE;
            }
        }else{
            if (!$manager) {
                return TRUE;
            }
        }

        if (!$manager) {
            //$this->error('门店管理员不存在，不能删除');
            $this->_returnMsg(['code' => 1, 'msg' => '门店管理员不存在，不能删除']);die;
        }
        $update = [
            'group_id' => 0,
            'is_admin' => 0,
            'update_time' => time(),
        ];
        if ($memberId) {
            $update['is_del'] = 0;
        }
        $result = db('store_member')->where(['member_id' => $manager['member_id']])->update($update);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '操作错误']);die;
        }
        $flag = TRUE;
//         if ($manager['group_id'] == STORE_MANAGER) {
//             //判断店长是否还有没有其它在管理的门店
//             $exist = $managerModel->where(['user_id' => $manager['user_id'], 'is_del' => 0, 'group_id' => $groupId])->find();
//             if ($exist) {
//                 $flag = FALSE;
//             }
//         }
        if ($flag) {
            $result = db('user')->where(['user_id' => $manager['user_id']])->update(['group_id' => 0, 'is_admin' => 0, 'update_time' => time()]);
        }
        return TRUE;
    }

    function _getData()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $storeType = $params && isset($params['store_type']) ? intval($params['store_type']) : 1;
        $address = $params && isset($params['address']) ? trim($params['address']) : '';
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        if (!$name) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店名称不能为空']);die;
        }
        if (!isset($this->storeTypes[$storeType])) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店类型错误']);die;
        }
        if ($storeType != 2 && !$address) {
            $this->_returnMsg(['code' => 1, 'msg' => '请输入门店地址']);die;
        }elseif ($storeType == 2) {
            $address = '';
        }
        $where[] = ['name','=', $name];
        $where[] = [ 'is_del' ,'=', 0];
        if($pkId){
            $where[] = ['store_id','neq', $pkId];
        }
        $exist = db('store')->where($where)->find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前门店名称已存在']);die;
        }

        //组装返回数据
        $data['address'] = $address;
        $data['store_type'] = $storeType;
        $data['name'] = $name;
        $data['status'] = $params && isset($params['status']) ? trim($params['status']) : 1;
        $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;
        if (!$pkId) {
            $data['add_time'] = time();
            $data['store_id'] = $pkId;
        }

        $data['update_time'] = time();

        return $data;
    }
    function _getWhere($params=[],$tag=0)
    {
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $storeIds = cache($authorization)['admin_user']['store_ids'];

        $pId = isset($params['pid']) ? intval($params['pid']) : 0;
        $parent = db('store')->where(['is_del' => 0, 'store_id' => $pId])->find();
        $storeVisit = $this->_checkStoreVisit($pId);

        $where[] = ['is_del','=',0];
        if (is_array($storeVisit)) {
            if(!$tag){
                $where[] = ['parent_id','=',$pId];
            }

            if (!$pId) {
                if ($storeIds) {
                    $where[] = ['store_id','IN', $storeIds];
                }else{
                    $where[] = ['store_id','=',$storeId];
                }
            }
        }elseif (is_bool($storeVisit)){
            if ($pId) {
                $where[] = ['parent_id','=',$pId];
            }elseif(!$tag){
                $where[] = ['parent_id','=',0];
            }
        }elseif (is_int($storeVisit)){
            $where[] = ['store_id','=',$storeVisit];
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
        }

        if ($parent['parent_id'] || $parent['store_type'] == 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '实体门店无子门店']);die;
        }
        $storeType = isset($params['store_type']) ? intval($params['store_type']) : '';
        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['name','like','%'.$name.'%'];
            }
        }
        if ($storeType) {
            $where[] = ['store_type','=',$storeType];
        }else{
            $storeTypes = $this->storeTypes ? array_keys($this->storeTypes): [];
            $where[] = ['store_type','IN', $storeTypes];
        }

        return $where;
    }

    //提供旗下所有实体门店名称，id给添加区域是选择
    public function realStore()
    {
        $params = ['store_type'=>1];
        $data = db('store') -> alias('S') -> where($this->_getWhere($params,1)) -> column('name','store_id');
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['list' => $data]]);die;
    }

    //提供旗下所有实体门店名称，id给添加yonghu是选择
    public function allStore()
    {
        $storeVisit = $this->_checkStoreVisit();
        if($storeVisit === TRUE){
            $data = db('store') -> where('is_del','=',0) -> field(['name','store_id']) ->select();
        }else{
            $data = db('store') -> where('is_del','=',0) -> where('store_id','in',$storeVisit) -> field(['name','store_id']) ->select();
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => $data]);die;
    }
}