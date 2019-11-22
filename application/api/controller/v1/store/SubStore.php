<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/26 0026
 * Time: 15:21
 */

namespace app\api\controller\v1\store;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class SubStore extends Api
{
    protected $request;
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->request = $request;
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
    public function checkadd($return = FALSE)
    {
        $flag = 1;
        $stores = [];
        if ($this->userInfo['user_id'] === 1) {
            $flag = 1;
        }else{
            //判断当前登录用户是否管理虚拟门店
            $storeIds = $this->userInfo['store_ids'];
            if (!$storeIds) {
                $flag = 0;
            }else{
                //判断当前用户是否有新增子店功能权限
                $purview = $this->userInfo['groupPurview'] ? json_decode($this->userInfo['groupPurview'], 1): [];
                if (!$purview) {
                    $flag = 0;
                }else{
                    $route = 'v1.store.substore_addsubstore';
                    if (!in_array($route, $purview)) {
                        $flag = 0;
                    }else{
                        $where = [
                            ['store_id', 'IN', $storeIds],
                            ['is_del', '=', 0],
                            ['parent_id', '=', 0],
                            ['store_type', 'IN', [2, 3]],//虚拟门店/商场才有新增子店功能
                        ];
                        $stores = db('store')->where($where)->field('store_id, name')->select();
                        if (!$stores) {
                            $flag = 0;
                        }else{
                            $flag = 1;
                        }
                    }
                }
            }
        }
        if ($return) {
            return $flag;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['has' => $flag, 'stores' => $stores]]);die;
    }

    /**
     * 子门店列表
     */
    function subStoreList()
    {

        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('store') -> alias('S') -> where($this->_getWhere($params)) -> count();
        $data = db('store') -> alias('S') -> where($this->_getWhere($params)) -> limit(($page-1)*$size,$size) -> select();
        if(!empty($data)){

            foreach ($data as $key => $value) {
                $data[$key]['type'] = $this->storeTypes[$value['store_type']]['name'];
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
                    $data[$key]['name'] = $adminUser['email'];
                }
                $userinfo = db('store_member')->alias('SM')->join('user U', 'SM.user_id = U.user_id', 'INNER')->where($where)->find();
                $data[$key]['manager_name'] = $userinfo['username'];
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;

    }

    /**
     * 添加子门店
     */
    function addSubStore()
    {
        $flag = $this->checkadd(TRUE);
        if (!$flag) {
            $this->_returnMsg(['code' => 1, 'msg' => '没有新增子店功能权限']);die;
        }
        $data = $this -> _getData();
        $pkId = db('store')->insertGetId($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['store_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }


        $params = $this -> postParams;
        $parentId = isset($params['parent_id']) ? intval($params['parent_id']) : 0;
        $parent = db('store') -> where(['store_id' => $parentId,'is_del' => 0]) -> find();
        if (!$parent) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数异常，请刷新后重试']);die;
        }
        if ($parent['parent_id'] || $parent['store_type'] == 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '实体门店不允许添加子门店']);die;
        }


        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        $storeType = $params && isset($params['store_type']) ? intval($params['store_type']) : 0;
        $address = $params && isset($params['address']) ? trim($params['address']) : '';
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        $data = $params;
        if (!$name) {
            //$this->error('门店名称不能为空');
            $this->_returnMsg(['code' => 1, 'msg' => '门店名称不能为空']);die;
        }
        $obj = new Store($this->request);
        if (!isset($obj->storeType(true)[$storeType])) {
            //$this->error('门店类型错误');
            $this->_returnMsg(['code' => 1, 'msg' => '门店类型错误']);die;
        }
        if ($storeType != 2 && !$address) {
            //$this->error('请输入门店地址');
            $this->_returnMsg(['code' => 1, 'msg' => '请输入门店地址']);die;
        }elseif ($storeType == 2) {
            $data['address'] = '';
        }
        $where = ['name' => $name, 'is_del' => 0];
        if($pkId){
            $where['store_id'] = ['neq', $pkId];
        }
        $exist = db('store')->where($where)->find();
        if($exist){
            //$this->error('当前门店名称已存在');
            $this->_returnMsg(['code' => 1, 'msg' => '当前门店名称已存在']);die;
        }
        $data['name'] = $name;
        $data['add_time'] = $data['update_time'] = time();
        unset($data['store_id']);

        $result = db('store') -> insertGetId($data);
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_id' => $result]]);die;
    }

    public function del()
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

        //删除门店
        $result = db('store')->where(array('store_id' => $pkId))->update(array('is_del' => 1, 'update_time' => time()));
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_id' => $pkId]]);die;
    }

    public function edit()
    {
        $params = $this -> postParams;

        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if($pkId){

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

    function _getWhere($params)
    {
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $storeIds = cache($authorization)['admin_user']['store_ids'];

        $pId = isset($params['pid']) ? intval($params['pid']) : 0;
        if(!$pId){
            $this->_returnMsg(['code' => 1, 'msg' => '父级门店id缺失']);die;
        }
        $parent = db('store')->where(['is_del' => 0, 'store_id' => $pId])->find();
        $storeVisit = $this->_checkStoreVisit($pId);

        $where[] = ['is_del','=',0];
        if (is_array($storeVisit)) {
            if ($pId) {
                $where[] = ['parent_id','=',$pId];
            }else{
                if ($storeIds) {
                    $where[] = ['store_id','IN', $storeIds];
                }else{
                    $where[] = ['store_id','=',$storeId];
                }
            }
        }elseif (is_bool($storeVisit)){
            if ($pId) {
                $where[] = ['parent_id','=',$pId];
            }else{
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

    function _getData()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $storeType = $params && isset($params['store_type']) ? intval($params['store_type']) : 0;
        $address = $params && isset($params['address']) ? trim($params['address']) : '';
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        $parentId = isset($params['parent_id']) ? intval($params['parent_id']) : 0;

        $parent = db('store') -> where(['store_id' => $parentId,'is_del' => 0]) -> find();
        if (!$parent) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数异常，请刷新后重试']);die;
        }
        if ($parent['parent_id'] || $parent['store_type'] == 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '实体门店不允许添加子门店']);die;
        }

        if (!$name) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店名称不能为空']);die;
        }
        if (!isset($this->storeType(true)[$storeType])) {
            $this->_returnMsg(['code' => 1, 'msg' => '门店类型错误']);die;
        }
        if ($storeType != 2 && !$address) {
            $this->_returnMsg(['code' => 1, 'msg' => '请输入门店地址']);die;
        }elseif ($storeType == 2) {
            $address = '';
        }
        $where = ['name' => $name, 'is_del' => 0];
        if($pkId){
            $where['store_id'] = ['neq', $pkId];
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
            $data['parent_id'] = $parentId;
        }

        $data['update_time'] = time();

        return $data;
    }

    public function storeType($tag = false)
    {
        $storeTypes = [
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
        if($tag){
            return $storeTypes;
        }else{
            $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['store_type' => $storeTypes]]);die;
        }

    }


}