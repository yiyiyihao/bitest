<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/25 0025
 * Time: 15:48
 */

namespace app\api\controller\v1\store;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class Block extends Api
{
    
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    /**
     * 获取区域列表
     */
    public function getblocklist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        
        $count = db('store_block') -> alias('B') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
        $data = db('store_block') -> alias('B') -> where($this->_getWhere($params)) -> field($this->_getField($params))->order('sort_order ASC, add_time DESC') -> join($this ->_getJoin($params)) -> limit(($page-1)*$size,$size) -> select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }
    
    //添加，修改都请求这个接口就可以了,修改时的添加，修改时间那里还没有改
    public function addblock()
    {
        $data = $this -> _getData();
        $pkId = db('store_block')->insertGetId($data);
        if($pkId){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['block_id' => $pkId]]);die;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
    }
    
    public function delblock()
    {
        $params = $this -> postParams;
        $pkId = intval($params['id']);
        if(empty($pkId)){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        //判断当前区域下是否存在设备
        $device = db('device')->where(['block_id' => $pkId, 'is_del' => 0])->find();
        if ($device) {
            $this->_returnMsg(['code' => 1, 'msg' => '区域下存在授权设备，不允许删除']);die;
        }
        
        $result = db('store_block')->where(array('block_id' => $pkId))->update(array('is_del' => 1, 'update_time' => time()));
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['block_id' => $pkId]]);die;
    }
    
    public function editblock()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;
        if($pkId){
            $data = $this->_getData();
            $rs = db('store_block')->where(['block_id' => $pkId,'is_del' => 0])->update($data);
            if($rs){
                $this->_returnMsg(['code' => 0, 'msg' => '修改成功','data' => ['cate_id' => $pkId]]);die;
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
            }
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }
    
    public function info()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;
        if($pkId){
            $rs = db('store_block')->where(['block_id' => $pkId,'is_del' => 0])->find();
            if($rs){
                $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $rs]);die;
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '查询失败']);die;
            }
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
    }
    
    function _getField($params){
        return 'B.*, S.name as store_name';
    }
    
    function _getJoin($params){
        return [
            ['store S', 'S.store_id = B.store_id', 'LEFT'],
        ];
    }
    function _getWhere($params){
        $storeId = isset($params['sid']) ? $params['sid'] : 0;
        if(empty($storeId)){
            $this->_returnMsg(['code' => 1, 'msg' => '门店id缺失']);die;
        }
        $where[] = ['B.is_del','=', 0];
        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['B.name','like','%'.$name.'%'];
            }
            if ($storeId) {
                $storeVisit = $this->_checkStoreVisit($storeId);
                if($storeVisit === true){

                }elseif(is_array($storeVisit)){
                    $where[] = ['B.store_id','in',$storeVisit];
                }else{
                    $where[] = ['B.store_id','=',$storeId];
                }
            }
        }
        return $where;
    }
    
    function _getData()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $name = isset($params['name']) ? trim($params['name']) : '';
        $storeId = isset($params['sid']) ? intval($params['sid']) : 0;
        
        if (!$storeId) {
            $this->_returnMsg(['code' => 1, 'msg' => '店铺id不能为空']);die;
        }
        if (!$name) {
            $this->_returnMsg(['code' => 1, 'msg' => '区域名称不能为空']);die;
        }
        
        $where = [['name','=', $name], ['is_del','=', 0], ['store_id','=', $storeId]];
        if($pkId){
            $where[] = ['block_id','neq', $pkId];
        }
        $exist = db('store_block')->where($where)->find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前区域名称已存在']);die;
        }
        //组装返回数据
        $data['block_id'] = $pkId;
        $data['name'] = $name;
        $data['status'] = $params && isset($params['status']) ? trim($params['status']) : 1;
        $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;
        $data['store_id'] = $storeId;
        
        if (!$pkId) {
            $data['add_time'] = time();
        }
        $data['update_time'] = time();
        
        return $data;
    }

    public function blocklist()
    {
        $params = $this -> postParams;
        $storeId = isset($params['sid']) ? $params['sid'] : 0;
        if(empty($storeId)){
            $this->_returnMsg(['code' => 1, 'msg' => '门店id缺失']);die;
        }
        $where = [['B.is_del','=',0],['B.store_id','=',$storeId]];
        $data = db('store_block') -> alias('B') -> where($where) -> field($this->_getField($params)) -> join($this ->_getJoin($params)) -> select();

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['list' => $data]]);die;
    }
}