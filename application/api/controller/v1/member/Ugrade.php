<?php
namespace app\api\controller\v1\member;

use app\api\controller\Api;
use think\Request;

class Ugrade extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


    public function gradelist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $count = db('user_grade') -> where($this->_getWhere($params)) -> count();
        $list = db('user_grade') -> where($this->_getWhere($params)) -> limit(($page-1)*$size,$size) -> select();

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $list]]);die;

    }


    public function add()
    {

        $data = $this -> _getData();
        $pkId = db('user_grade')->insertGetId($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['ugrade_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
    }

    public function edit()
    {

        $data = $this -> _getData();
        $pkId = db('user_grade')->update($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['grade_id' => $data['grade_id']]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
    }


    function _getWhere($params){

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];

        $where = [
            ['is_del', '=', 0],
            ['store_id' ,'in', [0, $storeId]],
        ];

        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['name','like','%'.$name.'%'];
            }
        }
        return $where;
    }
    function _getData()
    {

        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;

        if ($pkId == 1) {
            //$this->error('系统等级，不允许编辑');
            $this->_returnMsg(['code' => 1, 'msg' => '系统等级，不允许编辑']);die;
        }
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        if (!$name && !$pkId) {
            //$this->error('等级名称不能为空');
            $this->_returnMsg(['code' => 1, 'msg' => '等级名称不能为空']);die;
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];

        $where = [
            ['is_del','=',0],
            ['name','=',$name],
            ['store_id','in', [0, $storeId]],
        ];
        if($pkId){
            $where[] = ['grade_id','neq', $pkId];
        }
        $exist = db('user_grade')->where($where)->find();
        if($exist){
            //$this->error('当前等级名称已存在');
            $this->_returnMsg(['code' => 1, 'msg' => '当前等级名称已存在']);die;
        }

        //组装数据返回

        if (!$pkId) {
            $data['name'] = $name;
            $data['store_id'] = 1;
            $data['grade_id'] = $pkId;
            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['description'] = $params && isset($params['description']) ? trim($params['description']) : '';
            $data['status'] = $params && isset($params['status']) ? intval($params['status']) : 1;
            $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;
        }else{
            $info = db('user_grade') -> where([['grade_id','=',$pkId],['is_del','=',0]])-> find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '分组id错误']);die;}
            $data = [
                'grade_id' => $pkId,
                'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                'description' => isset($params['description']) ? trim($params['description']) : $info['description'],
                'update_time'=> time(),
                'status'    => isset($params['status']) ? intval($params['status']) : $info['status'],
                'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
            ];
        }
        return $data;
    }

    function info(){
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('user_grade') -> where([['grade_id','=',$pkId],['is_del','=',0]]) -> find();
        if ($info && $info['grade_id'] == 1) {
            //$this->error('系统等级，不允许编辑');
            $this->_returnMsg(['code' => 1, 'msg' => '系统等级，不允许编辑']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['info' => $info]]);die;
    }
    function del(){
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if ($pkId == 1) {
            //$this->error('系统等级，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '系统等级，不允许删除']);die;
        } elseif(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        //$info = parent::_assignInfo($pkId);
        //判断当前等级下是否存在用户
        $exist = db('store_member')->where(['grade_id' => $pkId, 'is_del' => 0])->find();
        if ($exist) {
            //$this->error('等级下存在用户，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '等级下存在用户，不允许删除']);die;
        }
        $result = db('user_grade') -> where('grade_id','=',$pkId) -> update(['is_del'=>1]);
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['grade_id' => $pkId]]);die;
    }
}
