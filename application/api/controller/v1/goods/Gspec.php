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

class Gspec extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


    public function speclist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('goods_spec') -> alias('GS') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
        $data = db('goods_spec') -> alias('GS') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> order('GS.sort_order ASC, GS.add_time DESC')-> join($this ->_getJoin($params))->limit(($page-1)*$size,$size) -> select();

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page,'list' => $data]]);die;
    }

    public function add()
    {

        $data = $this -> _getData();
        $pkId = db('goods_spec')->insertGetId($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['spec_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
    }


    public function edit()
    {
        $params = $this -> postParams;

        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if($pkId){
            $data = $this->_getData();
            $rs = db('goods_spec')->update($data);
            if($rs){
                $this->_returnMsg(['code' => 0, 'msg' => '修改成功','data' => ['spec_id' => $pkId]]);die;
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
            }


        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }


    public function specinfo($pkId = 0)
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('goods_spec') ->where(['spec_id' => $pkId])->find();
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['goodsInfo' => $info]]);die;
    }

    public function del()
    {
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
            $where = ['spec_id' => $pkId,'developer_id' => $developer_id,'store_id' =>$storeId];
        }else{
            $storeId = $this->_checkStoreVisit();
            if (is_array($storeId)) {
                $where[] = ['store_id', 'IN', $storeId];
            }
            $where[] = ['spec_id' ,'=', $pkId];
            $where[] = ['developer_id' ,'=', $developer_id];

        }

        $data['is_del'] = 1;
        $data['update_time'] = time();
        $result = db('goods_spec') -> where($where) -> update($data);
        if($result){
            $this->_returnMsg(['code' => 0, 'msg' => '删除成功','data' => ['spec_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
    }

    //商品规格详情
    public function goodsSpecInfo()
    {
        $params = $this -> postParams;
        $id = isset($params['id']) ? intval($params['id']) : 0;

        if(!$id){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
            //$this->error("参数错误");
        }
        //取得商品详情
        $map = $this->_checkStoreVisit();
        $where = [['goods_id','=', $id], ['is_del','=', 0]];
        if (is_array($map)) {
            $where[] = ['store_id','IN', $map];
        }
        $goodsInfo = db('goods')->where($where)->find();
        if (!$goodsInfo) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品不存在或删除']);die;
            //$this->error('商品不存在或删除');
        }

        //取得规格参数表
        $specList = db('goods_spec')->where(array('status' => 1, 'is_del' => 0, 'store_id' => $goodsInfo['store_id'],'developer_id' => $goodsInfo['developer_id']))->order("sort_order")->select();
        if ($specList) {
            foreach ($specList as $k=>$v){
                $specList[$k]['spec_value'] = explode(',', $v['value']);
            }
        }

        //取得属性详情
        $skuList = db('goods_sku')->where([['goods_id' ,'=', $id], ['is_del' ,'=', 0], ['status' ,'=', 1], ['store_id' ,'=', $goodsInfo['store_id']]])->where([['spec_json', 'neq', ""]])->order("sku_id")->select();
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => compact('goodsInfo','specList','skuList')]);die;
    }

    //商品属性管理
//    public function spec()
//    {
//        $params = $this -> postParams;
//        $id = isset($params['id']) ? intval($params['id']) : 0;
//        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : 0;
//
//        if(!$id){
//            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
//            //$this->error("参数错误");
//        }
//        //取得商品详情
//        $map = $this->_checkStoreVisit();
//        $where = [['goods_id','=', $id], ['is_del','=', 0]];
//        if (is_array($map)) {
//            $where[] = ['store_id','IN', $map];
//        }
//        $goodsInfo = db('goods')->where($where)->find();
//        if (!$goodsInfo) {
//            $this->_returnMsg(['code' => 1, 'msg' => '商品不存在或删除']);die;
//            //$this->error('商品不存在或删除');
//        }
//
//        $dataSet    = [];
//        $specSns    = isset($params['sku_sn']) ? $params['sku_sn'] : [];
//        $minPrice   = $maxPrice = $goodsStock = 0;
//        $skuIds     = isset($params['skuid']) ? $params['skuid'] : [];
//        if(!empty($specSns) && is_array($specSns)){
//            $specJson   = $params['spec_json'];
//            $specPrice  = $params['price'];
//            $specName   = $params['spec_name'];
//            $specSku    = $params['sku_stock'];
//            if (!$specJson) {
//                $this->_returnMsg(['code' => 1, 'msg' => '规格异常']);die;
//                //$this->error('规格异常');
//            }
//            //清空当前商品属性
//            if ($skuIds) {
//                $where[] = ['sku_id','NOT IN', $skuIds];
//            }
//
//            db('goods_sku')->where($where)->update(['is_del' => 1, 'update_time' => time()]);
//            foreach ($specSns as $k => $v){
//                $price = floatval($specPrice[$k]);
//                $stock = intval($specSku[$k]);
//                $minPrice = !$minPrice ? $price : min($minPrice, $price);
//                $maxPrice = !$maxPrice ? $price : max($maxPrice, $price);
//                if ($price < 0) {
//                    $this->_returnMsg(['code' => 1, 'msg' => '第'.($k+1).'行,商品价格小于0']);die;
//                    //$this->error('第'.($k+1).'行,商品价格小于0');
//                }
//                if ($stock < 0) {
//                    $this->_returnMsg(['code' => 1, 'msg' => '第'.($k+1).'行,商品库存小于0']);die;
//                    //$this->error('第'.($k+1).'行,商品库存小于0');
//                }
//                $specValue = [];
//                if ($specJson[$k]) {
//                    $spec = json_decode($specJson[$k], true);
//                    if ($spec) {
//                        foreach ($spec as $k1 => $v1) {
//                            $specValue[] = $v1;
//                        }
//                    }
//                }
//                $data = [
//                    'sku_name'      => $specName[$k],
//                    'sku_sn'        => trim($v),
//                    'spec_json'     => $specJson[$k],
//                    'sku_stock'     => $stock,
//                    'spec_value'    => $specValue ? implode(';', $specValue) : '',
//                    'price'         => $price,
//                    'update_time'   => time(),
//                ];
//                if ($skuIds) {
//                    db('goods_sku')->where(['sku_id' => $skuIds[$k]])->update($data);
//                }else{
//                    $data['store_id'] = $goodsInfo['store_id'];
//                    $data['goods_id'] = $id;
//                    $data['add_time'] = time();
//                    $dataSet[] = $data;
//                }
//                $goodsStock += $stock;
//            }
//            if ($dataSet && !$skuIds) {
//                $result = db('goods_sku')->insertAll($dataSet);
//                if ($result === false) {
//                    $this->_returnMsg(['code' => 1, 'msg' => '系统错误']);die;
//                    //$this->error('系统错误');
//                }
//            }
//            //更新商品属性
//            $goodsData = array(
//                'specs_json'    => trim($params['specs_json']),
//                'min_price'     => $minPrice,
//                'max_price'     => $maxPrice,
//                'goods_stock'   => $goodsStock,
//                'update_time'   => time(),
//            );
//            db('goods')->where(['goods_id' => $id])->update($goodsData);
//            $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
//            //$this->success("商品属性修改成功!", url("index"), TRUE);
//        }else{
//            //更新商品属性
//            $goodsData = array(
//                'specs_json'    => '',
//                'max_price'     => $goodsInfo['min_price'],
//                'update_time'   => time(),
//            );
//            db('goods')->where(['goods_id' => $id])->update($goodsData);
//            db('goods_sku')->where([['goods_id' ,'=', $id], ['is_del' ,'=', 0]])->where([['spec_json' , 'neq', ""]])->update(['is_del' => 1, 'update_time' => time()]);
//            $update = [
//                'is_del'        => 0,
//                'update_time'   => time(),
//                'sku_stock'     => $goodsInfo['goods_stock'],
//                'price'         => $goodsInfo['min_price'],
//            ];
//            db('goods_sku')->where([['goods_id' ,'=', $id], ['is_del' ,'=', 1]])->where([['spec_json' , 'eq', ""]])->update($update);
//            $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
//            //$this->success("商品属性修改成功!", url("index"), TRUE);
//        }
//
//
//    }
    public function spec()
    {
        $params = $this -> postParams;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : 0;

        if(!$id){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
            //$this->error("参数错误");
        }
        //取得商品详情
        $map = $this->_checkStoreVisit();
        $where = [['goods_id','=', $id], ['is_del','=', 0]];
        if (is_array($map)) {
            $where[] = ['store_id','IN', $map];
        }
        $goodsInfo = db('goods')->where($where)->find();
        if (!$goodsInfo) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品不存在或删除']);die;
            //$this->error('商品不存在或删除');
        }
        $spec = isset($params['spec_json']) ? json_decode($params['spec_json'],true) :[];

        $dataSet    = [];
        $specSns    = isset($params['sku_sn']) ? $params['sku_sn'] : [];
        $minPrice   = $maxPrice = $goodsStock = 0;
        $skuIds     = isset($params['skuid']) ? $params['skuid'] : [];
        if(!empty($spec) && is_array($spec)){
//            $specJson   = $params['spec_json'];
//            $specPrice  = $params['price'];
//            $specName   = $params['spec_name'];
//            $specSku    = $params['sku_stock'];
//            if (!$specJson) {
//                $this->_returnMsg(['code' => 1, 'msg' => '规格异常']);die;
//                //$this->error('规格异常');
//            }
            //清空当前商品属性
            if ($skuIds) {
                $where[] = ['sku_id','NOT IN', $skuIds];
            }

            db('goods_sku')->where($where)->update(['is_del' => 1, 'update_time' => time()]);
            foreach ($spec as $k => $v){
                $price = floatval($v['price']);
                $stock = intval($v['sku_stock']);
                $minPrice = !$minPrice ? $price : min($minPrice, $price);
                $maxPrice = !$maxPrice ? $price : max($maxPrice, $price);
                if ($price < 0) {
                    $this->_returnMsg(['code' => 1, 'msg' => '第'.($k+1).'行,商品价格小于0']);die;
                    //$this->error('第'.($k+1).'行,商品价格小于0');
                }
                if ($stock < 0) {
                    $this->_returnMsg(['code' => 1, 'msg' => '第'.($k+1).'行,商品库存小于0']);die;
                    //$this->error('第'.($k+1).'行,商品库存小于0');
                }
                $specValue = [];
                if ($v['spec_json']) {
                    $spec = json_decode($v['spec_json'], true);
                    if ($spec) {
                        foreach ($spec as $k1 => $v1) {
                            $specValue[] = $v1;
                        }
                    }
                }
                $data = [
                    'sku_name'      => $v['spec_name'],
                    'sku_sn'        => $v['sku_sn'],
                    'spec_json'     => $v['spec_json'],
                    'sku_stock'     => $stock,
                    'spec_value'    => $specValue ? implode(';', $specValue) : '',
                    'price'         => $price,
                    'update_time'   => time(),
                ];
                if ($skuIds) {
                    db('goods_sku')->where(['sku_id' => $skuIds[$k]])->update($data);
                }else{
                    $data['store_id'] = $goodsInfo['store_id'];
                    $data['goods_id'] = $id;
                    $data['add_time'] = time();
                    $dataSet[] = $data;
                }
                $goodsStock += $stock;
            }
            if ($dataSet && !$skuIds) {
                $result = db('goods_sku')->insertAll($dataSet);
                if ($result === false) {
                    $this->_returnMsg(['code' => 1, 'msg' => '系统错误']);die;
                    //$this->error('系统错误');
                }
            }
            //更新商品属性
            $goodsData = array(
                'specs_json'    => trim($params['specs_json']),
                'min_price'     => $minPrice,
                'max_price'     => $maxPrice,
                'goods_stock'   => $goodsStock,
                'update_time'   => time(),
            );
            db('goods')->where(['goods_id' => $id])->update($goodsData);
            $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
            //$this->success("商品属性修改成功!", url("index"), TRUE);
        }else{
            //更新商品属性
            $goodsData = array(
                'specs_json'    => '',
                'max_price'     => $goodsInfo['min_price'],
                'update_time'   => time(),
            );
            db('goods')->where(['goods_id' => $id])->update($goodsData);
            db('goods_sku')->where([['goods_id' ,'=', $id], ['is_del' ,'=', 0]])->where([['spec_json' , 'neq', ""]])->update(['is_del' => 1, 'update_time' => time()]);
            $update = [
                'is_del'        => 0,
                'update_time'   => time(),
                'sku_stock'     => $goodsInfo['goods_stock'],
                'price'         => $goodsInfo['min_price'],
            ];
            db('goods_sku')->where([['goods_id' ,'=', $id], ['is_del' ,'=', 1]])->where([['spec_json' , 'eq', ""]])->update($update);
            $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
            //$this->success("商品属性修改成功!", url("index"), TRUE);
        }


    }

    //获取商品规格参数列表
    public function getSpecListByGoods(){
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
//
//        $count = db('goods_spec') -> alias('GS') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
//        $data = db('goods_spec') -> alias('GS') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> order('GS.sort_order ASC, GS.add_time DESC')-> join($this ->_getJoin($params))->limit(($page-1)*$size,$size) -> select();
//
//        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page,'list' => $data]]);die;
        $goods_id = !empty($params['goods_id']) ? intval($params['goods_id']) : 0;
        if($goods_id == 0){
            $this->_returnMsg(['code' => 1, 'msg' => '商品id缺失']);die;
        }
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $count = db('goods_sku')->where(['goods_id' => $goods_id, 'is_del' => 0, 'status' => 1, 'store_id' => $storeId])->where('spec_json','neq', "") -> count();
        $skuList = db('goods_sku')->where(['goods_id' => $goods_id, 'is_del' => 0, 'status' => 1, 'store_id' => $storeId])->where('spec_json','neq', "")->order("sku_id")->limit(($page-1)*$size,$size)->select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page,'list' => $skuList]]);die;
    }


    function _getWhere($params){

        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : 0;

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($userId == 1 && $username == 'developer' && empty($developer_id)){
            $this->_returnMsg(['code' => 1, 'msg' => '开发者id缺失']);die;
        }elseif($userId == 1 && $username == 'developer' && !empty($developer_id)){
            $storeId = $params && isset($params['store_id']) ? trim($params['store_id']) : 0;
            $where[] = ['GS.developer_id','=',$developer_id];
        }else{
            $where[] = ['GS.developer_id','=',0];
        }

        $where[] = ['GS.is_del','=', 0];
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;

        if ($showOther) {
            $where[] = ['GS.store_id','<>', $storeId];
            $sname = isset($params['sname']) ? trim($params['sname']) : '';
            if($sname){
                $where[] = ['S.name','like','%'.$sname.'%'];
            }
        }else{
            $where[] = ['GS.store_id','=',$storeId];
        }
        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['GS.name','like','%'.$name.'%'];
            }
        }
        return $where;
    }
    function _getData()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        $developer_id = $params && isset($params['developer_id']) ? trim($params['developer_id']) : 0;
        $specValue = $params && isset($params['specname']) ? $params['specname'] : [];
        if (!$specValue && !$pkId) {
            //$this->error('请输入规格属性');
            $this->_returnMsg(['code' => 1, 'msg' => '请输入规格属性']);die;
        }
        $specValue = implode(',',$specValue);


        if (!$name && !$pkId) {
            $this->_returnMsg(['code' => 1, 'msg' => '规格名称不能为空']);die;
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $userId = cache($authorization)['admin_user']['user_id'];
        $username = cache($authorization)['admin_user']['username'];

        if($pkId){
            $info = db('goods_spec') -> where([['spec_id','=',$pkId],['is_del','=',0],['developer_id','=',$developer_id]])-> find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '规格id错误']);die;}
            $where[] = ['spec_id','neq', $pkId];
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



        $exist = db('goods_spec') -> where($where) -> find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前规格名称已存在']);die;
        }


        //组装数据返回
        if($pkId){
            $data = [
                'spec_id' => $pkId,
                'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                'value'  => !empty($specValue) ? trim($specValue) : $info['value'],
                'status'    => isset($params['status']) ? intval($params['status']) : $info['status'],
                'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
            ];
        }else{
            $data['spec_id'] = $pkId;
            $data['name'] = $name;
            $data['value'] = $specValue;
            $data['status'] = $params && isset($params['status']) ? $params['status'] : 1;
            $data['sort_order'] = $params && isset($params['sort_order']) ? $params['sort_order'] : 255;
            $data['add_time'] = time();
            $data['store_id'] = $storeId;
            $data['developer_id'] = $developer_id;
        }

        $data['update_time'] = time();
        return $data;
    }


    function _getField($params)
    {
        $field = 'GS.*';
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $field .= ', S.name as sname';
        }
        return $field;
    }

    function _getJoin($params){
        $join = [];
        $showOther = isset($params['other']) && intval($params['other']) ? 1 : 0;
        if ($showOther) {
            $join[] = ['store S', 'S.store_id = GS.store_id', 'INNER'];
        }
        return $join;
    }

}