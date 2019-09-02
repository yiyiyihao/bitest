<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/2/22 0022
 * Time: 14:56
 */
namespace app\api\controller\v1\label;

use app\api\controller\Api;
use think\Request;

class UserLabel extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function labellist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('label') -> alias('L') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
        $data = db('label') -> alias('L') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> join($this ->_getJoin($params))->limit(($page-1)*$size,$size) -> select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page,'list' => $data]]);die;
    }

    public function add() {

        $data = $this -> _getData();
        $pkId = db('label')->insertGetId($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['label_id' => $pkId,'name'=>$data['name']]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
    }


    public function edit() {
        $params = $this -> postParams;

        $pkId = isset($params['id']) ? intval($params['id']) : null;
        $developer_id = isset($params['developer_id']) ? intval($params['developer_id']) : 0;

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $data = $this->_getData();


        $rs = db('label')->where(['label_id' => $pkId, 'is_del' => 0, 'developer_id' => $developer_id])->update($data);

        if ($rs) {
            $this->_returnMsg(['code' => 0, 'msg' => '修改成功', 'data' => ['label_id' => $pkId]]);
            die;
            //$this->success($msg, url("index", $routes), TRUE);
        } else {
            $this->_returnMsg(['code' => 1, 'msg' => '标签不存在,修改失败']);
            die;
            //$this->error($msg);
        }


    }


    public function labelinfo($pkId = 0)
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('label') ->where(['label_id' => $pkId])->find();
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['goodsInfo' => $info]]);die;

    }

    public function del(){
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : 0;
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id错误']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where = ['label_id' => $pkId,'developer_id' => $developer_id,'store_id' => $storeId];
        }else{
            $storeId = $this->_checkStoreVisit();
            if (is_array($storeId)) {
                $where[] = ['store_id', 'IN', $storeId];
            }
            $where[] = ['label_id' ,'=', $pkId];
            $where[] = ['developer_id' ,'=', $developer_id];

        }


        $data['is_del'] = 1;
        $data['update_time'] = time();
        $result = db('label') -> where($where) -> update($data);
        if($result){
            $this->_returnMsg(['code' => 0, 'msg' => '删除成功','data' => ['label_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '没有对应的标签，删除失败']);die;
        }
    }




    function _getWhere($params){

        $other = isset($params['other']) ? intval($params['other']) : '';
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';
        $type = $params && isset($params['type']) ? intval($params['type']) : 0;
        $goods_id = $params && isset($params['goods_id']) ? intval($params['goods_id']) : 0;

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id缺失']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where[] = ['L.developer_id','=',$developer_id];
        }else{
            $where[] = ['L.developer_id','=',0];
        }

        if(!empty($type)){
            $where[] = ['type','=',$type];
        }
        if(!empty($goods_id)){
            $label_ids = [];
            $label_ids = db('goods') -> where([['goods_id','=',$goods_id],['is_del','=',0]]) -> field('label_ids') -> find();
            $where[] = ['label_id','in',$label_ids['label_ids']];
        }

        $where[] = ['is_del','=',0];
        $where[] = ['type','=',2];
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $where[] = ['L.store_id','<>', $storeId];
            $sname = isset($params['sname']) ? trim($params['sname']) : '';
            if($sname){
                $where[] = ['S.name','like','%'.$sname.'%'];
            }
        }else{
            $where[] = ['L.store_id','=',$storeId];
        }
        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['L.name','like','%'.$name.'%'];
            }
        }
        return $where;
    }

    function _getField($params){
        $field = 'L.*';
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $field .= ', S.name as sname';
        }
        return $field;
    }

    function _getJoin($params){
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        $join = [];
        if ($showOther) {
            $join[] = ['store S', 'S.store_id = L.store_id', 'INNER'];
        }
        return $join;
    }

    //增加，修改接受验证数据
    function _getData(){

        $params = $this -> postParams;

        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $name = $params && isset($params['name']) ? trim($params['name']) : '';

        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';

        if(!$name && !$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '标签名称不能为空']);die;
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($pkId){
            $info = db('label') -> where([['label_id','=',$pkId],['is_del','=',0],['developer_id','=',$developer_id]])-> find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '标签id错误']);die;}
            $where[] = ['label_id','neq', $pkId];
            $where[] = ['store_id','=',$info['store_id']];
        }elseif($userId == 1 && $username == 'developer'){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where[] = ['store_id','=',$storeId];
        }

        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id错误']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $where[] = ['name','=',$name];
            $where[] = ['is_del','=',0];
            $where[] = ['developer_id','=',$developer_id];
        }else{
            $where[] = ['name','=',$name];
            $where[] = ['is_del','=',0];
            $where[] = ['store_id','=',$storeId];
            $where[] = ['developer_id','=',0];

        }

        $exist = db('label')->where($where)->find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前标签名称已存在','data'=>['label_id'=>$exist['label_id'],'name'=>$name]]);die;
        }


        //组装数据返回
        if($pkId){
            $data = [
                'label_id' => $pkId,
                'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                'type' => $params && isset($params['type']) ? intval($params['type']) : $info['type'],
                'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
            ];
        }else{
            $data['label_id'] = $pkId;
            $data['name'] = $name;
            $data['type'] = 2;
            $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;

            $data['store_id'] = $storeId;
            $data['add_time'] = time();
            $data['developer_id'] = $developer_id;

        }

        $data['update_time'] = time();

        return $data;
    }


}