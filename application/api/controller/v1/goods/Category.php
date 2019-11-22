<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/31 0031
 * Time: 14:43
 */

namespace app\api\controller\v1\goods;

use app\api\controller\Api;
use think\Request;
use app\common\service\Cate;

class Category extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function categorylist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('category') -> alias('C') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
        $data = db('category') -> alias('C') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> join($this ->_getJoin($params)) -> limit(($page-1)*$size,$size) -> select();
//        $cateService = new Cate();
//        $list = $cateService->getAllGoodsCateTree($data);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }

    public function add() {

        $data = $this -> _getData();
        $pkId = db('category')->insertGetId($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['category_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
    }


    public function edit() {
        $params = $this -> postParams;

        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $data = $this->_getData();
        $rs = db('category')->where(['cate_id' => $pkId])->update($data);
        if($rs){
            $this->_returnMsg(['code' => 0, 'msg' => '修改成功','data' => ['cate_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
        }

    }


    public function categoryinfo($pkId = 0){
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);
            die;
        }
        $info = db('category') ->where(['cate_id' => $pkId])->find();
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['goodsInfo' => $info]]);die;

    }

    public function del(){
        $params = $this -> postParams;
        $pkId = intval($params['id']);
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : 0;
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
            $where = ['cate_id' => $pkId,'developer_id' => $developer_id,'store_id' =>$storeId];
        }else{
            $storeId = $this->_checkStoreVisit();
            if (is_array($storeId)) {
                $where[] = ['store_id', 'IN', $storeId];
            }
            $where[] = ['cate_id' ,'=', $pkId];
            $where[] = ['developer_id' ,'=', $developer_id];

        }

        //判断当前分类下是否存在商品
        $block = db('goods')->where(['cate_id' => $pkId, 'is_del' => 0])->find();
        if ($block) {
            $this->_returnMsg(['code' => 1, 'msg' => lang('分类下存在商品，不允许删除')]);die;
        }
        //判断是否有子分类
        $subCate = db('category') -> where(['parent_id' => $pkId,'developer_id' => $developer_id,'is_del' => 0]) -> find();
        if($subCate){
            $this->_returnMsg(['code' => 1, 'msg' => '存在子分类，先删除子分类']);die;
        }

        $data['is_del'] = 1;
        $data['update_time'] = time();
        $result = db('category') -> where($where) -> update($data);
        if($result){
            $this->_returnMsg(['code' => 0, 'msg' => '删除成功','data' => ['cate_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '没有对应的分类，删除失败']);die;
        }
    }

    function _getWhere($params){

        $other = isset($params['other']) ? intval($params['other']) : '';
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';
        $goods_id = $params && isset($params['goods_id']) ? intval($params['goods_id']) : 0;

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id缺失']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where = [['C.is_del' ,'=', 0], ['C.type' ,'=', 1],['developer_id' ,'=', $developer_id]];
        }else{
            $where = [['C.is_del' ,'=', 0], ['C.type' ,'=', 1],['developer_id' ,'=', 0]];
        }


        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $where[] = ['C.store_id','<>', $storeId];
            $sname = isset($params['sname']) ? trim($params['sname']) : '';
            if($sname){
                $where[] = ['S.name','like','%'.$sname.'%'];
            }
        }else{
            $where[] = ['C.store_id','=',$storeId];
        }
        //根据商品id过滤分类
        if($goods_id){
            $goods = db('goods') -> field('cate_id') -> where('goods_id','=',$goods_id) -> find();
            $where[] = ['C.cate_id','IN',$goods['cate_id']];
        }

        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['C.name','like','%'.$name.'%'];
            }
        }
        return $where;
    }

    function _getField($params){
        $field = 'C.cate_id, C.name, C.parent_id, C.sort_order, C.thumb, C.status';
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
            $join[] = ['store S', 'S.store_id = C.store_id', 'INNER'];
        }
        return $join;
    }

    //增加，修改接受验证数据
    function _getData(){

        $params = $this -> postParams;

        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : 0;
        $parent_id = $params && isset($params['parent_id']) ? trim($params['parent_id']) : 0;

        if(!$name && !$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '分类名称不能为空']);die;
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($pkId){
            $info = db('category') -> where([['cate_id','=',$pkId],['is_del','=',0],['developer_id','=',$developer_id]])-> find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '分类id错误']);die;}
            $where[] = ['store_id','=',$info['store_id']];
            $where[] = ['cate_id','neq', $pkId];
            //检验parent_id是否是自己的子级id
            $category = db('category') -> where('developer_id','=',$developer_id) -> select();
            $cateService = new Cate();
            $tree = $cateService -> getChild($pkId,$category);
            if($tree){
                foreach($tree as $k => $v){
                    if($parent_id == $v['cate_id']){
                        $this->_returnMsg(['code' => 1, 'msg' => '不能设置自己的子类为父类']);die;
                    }
                }
            }
            if($parent_id == $pkId){
                $this->_returnMsg(['code' => 1, 'msg' => '不能设置自己为自己父类']);die;
            }
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


        $exist = db('category')->where($where)->find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前分类名称已存在']);die;
        }

        if($parent_id){
            $res = db('category') -> where( [['cate_id','=',$parent_id],['is_del','=',0], ['store_id','=',$storeId],['developer_id','=',$developer_id]]) -> find();
            if(!$res){
                $this->_returnMsg(['code' => 1, 'msg' => '当前开发者下的非法父级id']);die;
            }
        }


        //组装数据返回
        if($pkId){
            $data = [
                'cate_id' => $pkId,
                'parent_id' => isset($params['parent_id']) ? trim($params['parent_id']) : $info['parent_id'],
                'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                'status'    => isset($params['status']) ? intval($params['status']) : $info['status'],
                'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
            ];
        }else{
            $data['cate_id'] = $pkId;
            $data['parent_id'] = $parent_id;
            $data['name'] = $name;
            $data['status'] = $params && isset($params['status']) ? trim($params['status']) : 1;
            $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;
            $data['developer_id'] = $developer_id;
            $data['store_id'] = $storeId;
            $data['add_time'] = time();
        }

        $data['update_time'] = time();

        return $data;
    }


}