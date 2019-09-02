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

class Goods extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function goodslist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('goods') -> alias('G') -> where($this->_getWhere($params)) -> count();
        $data = db('goods') -> alias('G') -> where($this->_getWhere($params))->order('sort_order ASC, add_time DESC') ->limit(($page-1)*$size,$size) -> select();
        if(!empty($data)){
            foreach($data as $k=>$v){

                $category = db('category') -> field('name') -> where('cate_id','IN',$v['cate_id']) -> select();
                $label = db('label') -> field('name') -> where('label_id','IN',$v['label_ids']) -> select();

                $data[$k]['cate_name'] = '';
                $data[$k]['label_name'] = '';

                foreach($category as $key => $value){
                    $data[$k]['cate_name'] .= $value['name'].',';
                }
                if(!empty($label)){
                    foreach($label as $kk => $vv){
                        $data[$k]['label_name'] .= $vv['name'].',';
                    }
                }

                $data[$k]['label_name'] = trim($data[$k]['label_name'],',');
                $data[$k]['cate_name'] = trim($data[$k]['cate_name'],',');

            }

        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page,'list'=>$data]]);die;
    }

    /**
     * 新增内容
     */
    public function goodsadd() {

        $data = $this->_getData();
        $pkId = db('goods')->insertGetId($data);
        if($pkId){

            //添加商品属性
            $skuData = array(
                'goods_id'      => $pkId,
                'sku_sn'        => $data['goods_sn'],
                'sku_name'      => '',
                'spec_json'     => '',
                'sku_stock'     => $data['goods_stock'],
                'price'         => $data['min_price'],
                'add_time'      => time(),
                'update_time'   => time(),
                'store_id'      => $data['store_id'],
            );
            $skuId = db('goods_sku')->insertGetId($skuData);

            $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['goods_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }

    }

    /**
     * 编辑内容
     */
    public function goodsedit() {
        $params = $this -> postParams;

        $pkId = isset($params['id'])?intval($params['id']):null;

        if($pkId){

            $data = $this->_getData();

            $rs = db('goods')->update($data);
            if($rs){
//                $this->_editAfter($pkId, $data);
//                $msg .= lang('success');
//                unset($routes['id']);
//                $this->success($msg, url("index", $routes), TRUE);
                //修改商品属性
                if(isset($data['goods_stock'])){
                    $data = array(
                        'sku_sn'        => $data['goods_sn'],
                        'sku_stock'     => $data['goods_stock'],
                        'price'         => $data['min_price'],
                        'update_time'   => time(),
                    );
                }else{
                    $data = array(
                        'sku_sn'        => $data['goods_sn'],
                        'price'         => $data['min_price'],
                        'update_time'   => time(),
                    );
                }

                $result = db('goods_sku')->where(['goods_id' => $pkId, 'is_del' => 0, 'status' => 1, 'spec_json' => ['eq', ""]])->update($data);
                $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['goods_id' => $pkId]]);die;
            }else{
//                $msg .= lang('fail');
//                $this->error($msg);
                $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
            }

        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }



    public function goodsInfo(){

        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if($pkId){
            $info = db('goods')->where(array('goods_id' => $pkId))->find();
            if ($info) {
                $info['imgs'] = $info['imgs'] ? json_decode($info['imgs'], 1) : [];
            }
            $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['goodsInfo' => $info]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }

    /**
     * 删除内容
     */
    public function goodsdel() {
        $params = $this -> postParams;
        $pkId = intval($params['id']);
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        $data['is_del'] = 1;
        $data['update_time'] = time();
        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id错误']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where = ['goods_id' => $pkId,'developer_id' => $developer_id,'store_id' => $storeId];
        }else{
            $storeId = $this->_checkStoreVisit();
            if (is_array($storeId)) {
                $where[] = ['store_id', 'IN', $storeId];
            }
            $where[] = ['goods_id' ,'=', $pkId];
            $where[] = ['developer_id' ,'=', $developer_id];
        }

        $result = db('goods') -> where($where) -> update($data);
        if($result){
            $this->_returnMsg(['code' => 0, 'msg' => '删除成功','data' => ['goods_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
    }

    function _getWhere($params = []){

        $other = isset($params['other']) ? intval($params['other']) : '';
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';
        $cate_id = $params && isset($params['cate_id']) ? intval($params['cate_id']) : 0;
        $label_id = $params && isset($params['label_id']) ? intval($params['label_id']) : 0;

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id缺失']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where[] = ['G.developer_id','=',$developer_id];
        }else{
            $where[] = ['G.developer_id','=',0];
        }

        if(!empty($cate_id)){
            $where[] = ['G.cate_id','like','%'.$cate_id.'%'];
        }
        if(!empty($label_id)){
            $where[] = ['G.label_ids','like','%'.$label_id.'%'];
        }

        $where[] = ['G.is_del','=', 0];
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $where[] = ['G.store_id','<>', $storeId];
            $sname = isset($params['sname']) ? trim($params['sname']) : '';
            if($sname){
                $where[] = ['S.name','like','%'.$sname.'%'];
            }
        }else{
            $where[] = ['G.store_id','=', $storeId];
        }
        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['G.name','like','%'.$name.'%'];
            }
        }
        return $where;
    }
    function _getField($params = []){
        $field = 'G.*, C.name as cate_name,L.name as label_name';
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $field .= ', S.name as sname';
        }
        return $field;
    }

    function _getJoin($params = []){
        $join[] = ['category C', 'C.cate_id = G.cate_id', 'INNER'];
        $join[] = ['label L', 'L.label_id = G.label_ids', 'INNER'];
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $join[] = ['store S', 'S.store_id = G.store_id', 'INNER'];
        }
        return $join;
    }

    function _getData()
    {
        $params = $this->postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : '0';
        $cate_ids = $params && isset($params['cate_ids']) ? $params['cate_ids'] : [];



        if(!$name && empty($pkId)){
            $this->_returnMsg(['code' => 1, 'msg' => '商品名称不能为空']);die;
        }
        if(empty($cate_ids) && empty($pkId)){
            $this->_returnMsg(['code' => 1, 'msg' => '商品所属分类cate_ids不能为空']);die;
        }


        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($pkId){
            $info = db('goods')->where([['goods_id','=',$pkId],['is_del','=',0],['developer_id','=',$developer_id]])->find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '货品id错误']);die;}
            $where[] = ['goods_id','neq', $pkId];
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


        $exist = db('goods')->where($where)->find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前商品名称已存在']);die;
        }

        if(!empty($cate_ids)){
            foreach($cate_ids as $k =>$v){
                $cate_exist = db('category') -> where([['cate_id','=',$v],['store_id','=',$storeId],['developer_id','=',$developer_id],['is_del','=',0]]) -> find();
                if(!$cate_exist){
                    $this->_returnMsg(['code' => 1, 'msg' => "商品所属分类id $v 不存在"]);die;
                }
            }
        }

        $cate_ids = implode(',',$cate_ids);
        $label_ids = $params && isset($params['label_ids']) ? $params['label_ids'] : [];
        $label_ids = implode(',',$label_ids);
        //组装数据返回
        if(!$pkId){
            $data['goods_id'] = $pkId;
            $data['cate_id'] = $cate_ids;
            $data['name'] = $name;
            $data['goods_sn'] = $params && isset($params['goods_sn']) ? trim($params['goods_sn']) : '';
            $data['min_price'] = $params && isset($params['min_price']) ? $params['min_price'] : 0;
            $data['max_price'] = $params && isset($params['max_price']) ? $params['max_price'] : $data['min_price'];
            $data['goods_stock'] = $params && isset($params['goods_stock']) ? $params['goods_stock'] : 0;
            $data['description'] = $params && isset($params['description']) ? $params['description'] : '';
            $data['content'] = $params && isset($params['content']) ? $params['content'] : '';
            $data['status'] = $params && isset($params['status']) ? $params['status'] : 1;
            $data['sort_order'] = $params && isset($params['sort_order']) ? $params['sort_order'] : 255;
            $data['add_time'] = time();
            $data['store_id'] = $storeId;
            $data['developer_id'] = $developer_id;
            $data['label_ids'] = $label_ids;
        }else{
            $skuinfo = db('goods_sku')->where([['goods_id','=', $pkId], ['is_del','=', 0], ['status','=', 1],['spec_json' ,'neq', ""]])->find();

            $data = [
                'goods_id' => $pkId,
                'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                'cate_id'  => isset($cate_ids) ? trim($cate_ids) : $info['cate_id'],
                'description' => isset($params['description']) ? trim($params['description']) : $info['description'],
                'goods_sn' => isset($params['goods_sn']) ? trim($params['goods_sn']) : $info['goods_sn'],
                'min_price' => isset($params['min_price']) ? $params['min_price'] : $info['min_price'],
                'description' => isset($params['description']) ? $params['description'] : $info['description'],
                'content' => isset($params['content']) ? $params['content'] : $info['content'],
                'status'    => isset($params['status']) ? intval($params['status']) : $info['status'],
                'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
            ];

            if (!$skuinfo) {
                $data['goods_stock'] = $params && isset($params['goods_stock']) ? $params['goods_stock'] : $info['goods_stock'];
            }
        }




        $data['update_time'] = time();

//        $goodsSn = isset($data['goods_sn']) ? $data['goods_sn'] : '';
//         if (!$goodsSn) {
//             $this->error('商品货号不能为空');
//         }


//         $where = ['is_del' => 0, 'store_id' => $this->storeId];
//         if ($goodsSn) {
//             $where['goods_sn'] = $goodsSn;
//         }
//         if($pkId){
//             $where['goods_id'] = ['neq', $pkId];
//         }
//         $exist = $this->model->where($where)->find();
//         if ($exist) {
//             $this->error('商品货号已存在，请重新填写');
//         }


        if (isset($params['imgs']) && $params['imgs']) {
            $data['imgs'] = array_filter($params['imgs']);
            $data['imgs'] = $params['imgs'] ? array_unique($params['imgs']) : [];
            $data['imgs'] =  $params['imgs']? json_encode($params['imgs']) : '';

            $data['thumb'] = isset($params['thumb']) && $params['thumb'] ? $params['thumb'] : (isset($params['imgs']) && $params['imgs'] ? $params['imgs'][0] : '');

        }

        return $data;
    }

}