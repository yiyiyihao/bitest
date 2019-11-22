<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/9/9 0009
 * Time: 15:02
 */

namespace app\api\controller\v1\Rule;

use app\api\controller\Api;
use think\Request;

class Rule extends Api
{
    public $noAuth = ['add_rule','rule_list','edit_rule','del_rule','rule_info','open_charge_rule'];
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //添加权限
    public function add_rule()
    {
        $params = $this->postParams;
        //验证参数
        $route = $params['route'] ?? '';
        $title = $params['title'] ?? '';
        $pId = $params['p_id'] ?? 0;
        $menuStatus = $params['menu_status'] ?? 0;
        $pMenuIndex= $params['p_menu_index'] ?? '';
        $menuIndex= $params['menu_index'] ?? '';
        $icon= $params['icon'] ?? '';
        $path= $params['path'] ?? '';
        $isFees= $params['is_fees'] ?? 0;

        if(empty($route)){
            $this->_returnMsg(['code' => 1, 'msg' => '权限缺失']);die;
        }

        if(empty($title)){
            $this->_returnMsg(['code' => 1, 'msg' => '权限名称缺失']);die;
        }


        $data = [
            'route' => $route,
            'title' => $title,
            'p_id' => $pId,
            'menu_status' => $menuStatus,
            'menu_index' => $menuIndex,
            'p_menu_index' => $pMenuIndex,
            'icon' => $icon,
            'path' => $path,
            'is_fees' => $isFees,
            'add_time' => time(),
        ];
        $result = db('auth_rule')->insertGetId($data);
        if($result === false){
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data'=>['id'=>$result]]);die;
    }

    //权限列表
    public function rule_list()
    {
        $params = $this->postParams;
        //收费非收费的权限列表，收费需要跳收费界面
        $is_fees = [1]; //0不收费，1收费
        $where = [
            ['is_fees','in',$is_fees],
            ['is_del','=',0],
        ];
        $data = db('auth_rule') -> where($where) -> select();
        if($data === false){
            $this->_returnMsg(['code' => 0, 'msg' => '查询失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' =>  $data]);die;
    }

    //权限详情
    public function rule_info()
    {
        $params = $this->postParams;
        $id = $params['id'] ?? 0;
        if($id === 0){
            $this->_returnMsg(['code' => 1, 'msg' => 'id缺失']);die;
        }
        $data = db('auth_rule') -> where('id',$id) ->where('is_del',0) -> find();
        if($data === false){
            $this->_returnMsg(['code' => 0, 'msg' => '查询失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' =>  $data]);die;
    }

    //编辑权限
    public function edit_rule()
    {
        $params = $this->postParams;
        $id = $params['id'] ?? 0;
        $route = $params['route'] ?? '';
        $title = $params['title'] ?? '';
        $pId = $params['p_id'] ?? 0;
        $menuStatus = $params['menu_status'] ?? 0;
        $isFees= $params['is_fees'] ?? 0;

        //验证参数
        if($id === 0){
            $this->_returnMsg(['code' => 1, 'msg' => 'id缺失']);die;
        }

        if(empty($route)){
            $this->_returnMsg(['code' => 1, 'msg' => '权限缺失']);die;
        }

        if(empty($title)){
            $this->_returnMsg(['code' => 1, 'msg' => '权限名称缺失']);die;
        }

        $data = [
            'route' => $route,
            'title' => $title,
            'p_id' => $pId,
            'menu_status' => $menuStatus,
            'is_fees' => $isFees,
            'update_time' => time(),
        ];

        $result = db('auth_rule')->where('id',$id)->update($data);
        if($result === false){
            $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功']);die;
    }

    //删除权限
    public function del_rule()
    {
        $params = $this->postParams;
        //验证参数
        $id = $params['id'] ?? 0;
        if($id === 0){
            $this->_returnMsg(['code' => 1, 'msg' => 'id缺失']);die;
        }
        $result = db('auth_rule')->where('id',$id)->update(['is_del'=>1]);
        if($result === false){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '删除成功','data'=>['id'=>$id]]);die;
    }

    //用户自己开通收费接口
    public function open_charge_rule()
    {
        $params = $this->postParams;
        //验证参数
        $id = $params['id'] ?? 0;
        $expireTime = $params['expire_time'] ?? 1;

        $storeId = $this->userInfo['store_id'];
        //权限的id
        if($id === 0){
            $this->_returnMsg(['code' => 1, 'msg' => 'id缺失']);die;
        }

        $data = [
            'store_id' => $storeId,
            'auth_rule_id' => $id,
            'expire_time' => $expireTime,
        ];

        $result = db('store_fees_rule')->insert($data);
        if($result === false){
            $this->_returnMsg(['code' => 0, 'msg' => '查询失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;

    }


}