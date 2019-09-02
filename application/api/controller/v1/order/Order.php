<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/2/13 0013
 * Time: 11:20
 */
namespace app\api\controller\v1\order;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class Order extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function orderlist()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $count = db('order') -> alias('O') -> where($this->_getWhere($params)) -> join($this ->_getJoin($params)) -> count();
        $list = db('order') -> alias('O') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> order('O.update_time DESC')-> join($this ->_getJoin($params)) -> limit(($page-1)*$size,$size) -> select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['subs'] = db('order_sub')->where(['order_sn' => $value['order_sn']])->select();
                $list[$key]['_status'] = get_order_status($value);
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $list]]);die;

    }


    public function orderinfo()
    {
        $params = $this -> postParams;
        $subSn = isset($params['sub_sn']) ? trim($params['sub_sn']) : '';

        $orderService = new \app\common\service\Order();
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $detail = $orderService -> getOrderDetail($subSn, $storeId, ADMIN_ID);
        if ($detail === false) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }
        $detail['order']['pay_method'] = $detail['order']['pay_method'] ? $detail['order']['pay_method'] : (!$detail['sub']['pay_status'] ? '' : '管理员确认收款');
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['info' => $detail]]);die;
    }

    public function updatePrice()
    {
        $params = $this -> postParams;
        $orderSn = isset($params['order_sn']) ? trim($params['order_sn']) : '';
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();
        $result = $orderService->orderUpdatePrice($orderSn, $storeId, ADMIN_ID, $params);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }else{
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['order_sn' => $orderSn]]);die;
        }

    }

    //取消订单
    public function cancel()
    {
        $params = $this -> postParams;
        $subSn = isset($params['sub_sn']) ? trim($params['sub_sn']) : '';
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();
        $result = $orderService->orderCancel($subSn, $storeId, ADMIN_ID);
        if ($result === FALSE) {
            //$this->error($this->orderService->error);
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }else{
            //$this->success('取消订单成功');
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['sub_sn' => $subSn]]);die;
        }
    }

    //确认收款
    public function pay()
    {
        $params = $this -> postParams;
        $orderSn = isset($params['order_sn']) ? trim($params['order_sn']) : '';

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();
        $result = $orderService->orderPay($orderSn, $storeId, ADMIN_ID, $params);
        if ($result === FALSE) {
            //$this->error($this->orderService->error);
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }else{
            //$this->success('确认付款成功', url('index'));
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['order_sn' => $orderSn]]);die;
        }

    }

    //确认发货信息页面
    public function deliveryInfo()
    {
        $params = $this -> postParams;
        $subSn = isset($params['sub_sn']) ? trim($params['sub_sn']) : '';

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();
        $order = $orderService->_checkSub($subSn, $storeId, ADMIN_ID);
        if ($order === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }
        $detail = $orderService->getOrderDetail($subSn, $storeId, ADMIN_ID);
        if ($detail === false) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }
        $detail['order']['pay_method'] = $detail['order']['pay_method'] ? $detail['order']['pay_method'] : (!$detail['sub']['pay_status'] ? '' : '管理员确认收款');
        //物流公司
        $deliverys = $orderService->deliverys;
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => compact('params','detail','deliverys')]);die;
    }

    //商品发货
    public function delivery()
    {
        $params = $this -> postParams;

        $subSn = isset($params['sub_sn']) ? trim($params['sub_sn']) : '';

        $oskuIds = isset($params['osku_id']) ? $params['osku_id'] : '';
        if (!$oskuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '请选择发货商品']);die;
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();
        $result = $orderService->orderDelivery($subSn, $storeId, ADMIN_ID, $params);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }else{
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['sub_sn' => $subSn]]);die;
        }

    }
    public function finish()
    {
        $params = $this -> postParams;
        $subSn = isset($params['sub_sn']) ? trim($params['sub_sn']) : '';
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();

        $result = $orderService->orderFinish($subSn, $storeId, ADMIN_ID, $params);
        if ($result === FALSE) {
            //$this->error($this->orderService->error);
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }else{
            //$this->success('订单确认完成操作成功', url('detail', ['sub_sn' => $subSn]));
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['sub_sn' => $subSn]]);die;
        }
    }

    //创建订单
    public function createOrder()
    {
        $params = $this -> postParams;
        $userId = isset($params['user_id']) ? intval($params['user_id']) : 0;
        if (!$userId) {
            $this->_returnMsg(['code' => 1, 'msg' => '用户ID(user_id)缺失']);die;
        }

        $user = db('User')->where(['user_id' => $userId, 'is_del' => 0])->find();

        $from = isset($params['from']) ? trim($params['from']): 'cart';             //下单来源
        $skuIds = isset($params['sku_ids']) ? trim($params['sku_ids']): '';         //商品属性ID列表
        $addressId = isset($params['address_id']) ? trim($params['address_id']): '';//商品收货地址
        $submit = isset($params['submit']) ? intval($params['submit']): 0;          //是否确认下单

        if (!in_array($from, ['cart', 'goods'])) {
            $this->_returnMsg(['code' => 1, 'msg' => '下单来源(from)错误']);die;
        }
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' =>  '商品属性ID(sku_ids)缺失']);die;
        }
        $skuIds = $skuIds ? explode(',', $skuIds) : [];
        $skuIds = $skuIds ? array_filter($skuIds) : [];
        $skuIds = $skuIds ? array_unique($skuIds) : [];
        if (!$skuIds) {
            $this->_returnMsg(['code' => 1, 'msg' => '商品属性ID(sku_ids)数据错误']);die;
        }
        if ($addressId) {
            $address = $this->_checkAddress($user['user_id'], $addressId);
        }
        if ($from == 'goods') {
            //立即购买时仅能购买一件商品
            if (count($skuIds) != 1) {
                $this->_returnMsg(['code' => 1, 'msg' => '仅能购买一件商品']);die;
            }
            $num = isset($params['num']) ? intval($params['num']) : 0;
            if ($num <= 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品购买数量(num)必须大于0']);die;
            }
            $skuId = intval($skuIds[0]);
            $sku = $this->_checkSku($skuId, 'sku_id, sku_stock, goods_stock');
            $stock = min($sku['sku_stock'], $sku['goods_stock']);
            //判断商品库存
            if ($stock <= 0 || $stock < $num) {
                $this->_returnMsg(['code' => 1, 'msg' => '商品库存不足(剩余库存:'.$stock.')']);die;
            }
            $carts = $this->_getCartDatas($user, $skuId, FALSE, $num);
        }else{
            $carts = $this->_getCartDatas($user, $skuIds);
        }
        $storeIds = $carts['store_ids'] ? array_filter($carts['store_ids']): [];
        $storeIds = $storeIds ? array_unique($storeIds): [];
        if (count($storeIds) > 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '请勿跨店购买商品']);
        }
        if (!$submit) {
            $addressInfo = $this->_getDefaultAddress($user ,$addressId);
            $carts['address'] = $addressInfo;
            $this->_returnMsg(['datas' => $carts]);die;
        }else{
            //选择收货地址
            if (!$addressId) {
                $this->_returnMsg(['code' => 1, 'msg' =>  '收货地址ID(address_id)缺失']);die;
            }
            if ($carts['all_amount'] <= 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '订单支付金额不能小于等于0']);die;
            }
            //创建订单
            $orderSn = $this->_buildOrderSn();
            $data = [
                'order_sn'      => $orderSn,
                'user_id'       => $user['user_id'],
                'goods_amount'  => $carts['all_amount'],
                'delivery_amount' => $carts['delivery_amount'],
                'real_amount'   => $carts['pay_amount'],
                'address_name'  => $address['name'],
                'address_phone' => $address['phone'],
                'address_detail'=> $address['region_name'].' '.$address['address'],
                'add_time'      => time(),
                'update_time'   => time(),
                'extra'         => '',
            ];
            $orderModel = db('order');
            $orderSubModel = db('order_sub');
            $orderSkuModel = db('order_sku');
            $orderLogModel = db('order_log');
            $database = new \think\Db;
            $database::startTrans();
            try{
                $orderService = new \app\common\service\Order();
                $skus = $storeIdArray = $cartIds = [];
                $orderId = $orderModel->insertGetId($data);
                if ($orderId === false) {
                    $this->_returnMsg(['code' => 1, 'msg' =>  '订单创建失败']);die;
                }
                foreach ($carts['list'] as $key => $value) {
                    $storeId = $key;
                    $storeIdArray[$storeId] = $storeId;
                    $subSn = $this->_buildOrderSn(TRUE);
                    $subData = [
                        'sub_sn'        => $subSn,
                        'user_id'       => $user['user_id'],
                        'order_id'      => $orderId,
                        'order_sn'      => $orderSn,
                        'store_id'      => $storeId,
                        'goods_amount'  => $value['detail']['sku_amount'],
                        'delivery_amount' => $value['detail']['delivery_amount'],
                        'real_amount'   => $value['detail']['pay_amount'],
                        'add_time'      => time(),
                        'update_time'   => time(),
                    ];
                    $subId = $orderSubModel->insertGetId($subData);
                    if (!$subId) {
                        break;
                    }
                    if ($value) {
                        foreach ($value['skus'] as $k1 => $v1) {
                            $goodsAmount = $v1['num']*$v1['price'];
                            $deliveryAmount = isset($v1['delivery_amount']) ? $v1['delivery_amount'] : 0;
                            $skuInfo = $this->getGoodsDetail($v1['goods_id']);
                            $skuData = [
                                'sub_id'        => $subId,
                                'sub_sn'        => $subSn,
                                'user_id'       => $user['user_id'],
                                'store_id'      => $storeId,
                                'order_id'      => $orderId,
                                'order_sn'      => $orderSn,

                                'goods_id'      => $v1['goods_id'],
                                'sku_id'        => $v1['sku_id'],
                                'sku_name'      => $v1['name'],
                                'sku_thumb'     => $skuInfo['thumb'] ? $skuInfo['thumb'] : '',
//                                 'sku_spec'      => $v1['sku_name'],
                                'sku_spec'      => $v1['spec_value'],
                                'sku_info'      => $skuInfo ? json_encode($skuInfo) : '',
                                'num'           => $v1['num'],
                                'price'         => $v1['price'],
                                'pay_price'     => $v1['pay_price'],
                                'delivery_amount' => $deliveryAmount,
                                'real_amount'   => $goodsAmount+$deliveryAmount,

                                'add_time'      => time(),
                                'update_time'   => time(),
                            ];
                            $oskuId = $orderSkuModel->insertGetId($skuData);
                            if (!$oskuId) {
                                break;
                            }
                            $skus[$k1] = [
                                'sku_id'    => $v1['sku_id'],
                                'goods_id'  => $v1['goods_id'],
                                'num'       => $v1['num'],
                            ];
                            if (isset($v1['cart_id']) && $v1['cart_id']) {
                                $cartIds[] = $v1['cart_id'];
                            }
                        }
                        $logId = $orderService->orderLog($subData, $user, '创建订单', '提交购买商品并生成订单');
                        $trackId = $orderService->orderTrack($subData, 0, '订单已提交, 系统正在等待付款');
                    }
                }
                $storeIdArray = $storeIdArray ? array_filter($storeIdArray) : [];
                $storeIds = $storeIdArray ? implode(',', $storeIdArray) : '';
                if ($storeIds) {
                    $result = $orderModel->where(['order_id' => $orderId])->update(['store_ids' => $storeIds]);
                }
                $database::commit();
                if ($from == 'goods' || ($this->reduceStock == 2 && $skus)) {
                    $goodsModel = new \app\common\model\Goods();
                    foreach ($skus as $key => $value) {
                        $result = $goodsModel->_setGoodsStock($value, -$value['num']);
                    }
                }
                if ($from == 'cart' && $skus && $cartIds) {
                    //清理购物车商品
                    $cartIds = implode(',', $cartIds);
                    $result = $this->delCartSku($cartIds, true);
                }
                $this->_returnMsg(['order_sn' => $orderSn, 'msg' => '订单创建成功']);die;
            }catch(\Exception $e){
                $error = $e->getMessage();
                $database::rollback();
                $this->_returnMsg(['code' => 1, 'msg' => '订单创建失败 '.$error]);die;
            }
        }
    }

    //商品物流信息
    public function deliveryLogs(){
        $params = $this -> postParams;
        $odeliveryId = isset($params['odelivery_id']) ? intval($params['odelivery_id']) : 0;
        if(!$odeliveryId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $delivery = db('order_sku_delivery')->where(['odelivery_id' => $odeliveryId])->find();


        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $orderService = new \app\common\service\Order();

        $result = $orderService->updateApi100($delivery['sub_sn'], $storeId, ADMIN_ID, $odeliveryId);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => $orderService->error]);die;
        }
//        if ($result === FALSE) {
//            //$this->error('物流配送信息查看失败');
//            $this->_returnMsg(['code' => 1, 'msg' => '物流配送信息查看失败']);die;
//        }
        //获取物流配送商品列表
        $skus = db('order_sku')->where([['osku_id' ,'IN', $delivery['osku_ids']]])->select();
        //获取物流跟踪日志
        $logs = db('order_track')->where(['odelivery_id' => $delivery['odelivery_id']])->order('track_id DESC')->select();
//        $this->assign('info', $delivery);
//        $this->assign('skus', $skus);
//        $this->assign('list', $logs);
//        return $this->fetch('delivery_log');
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['info' => $delivery,'skus' => $skus,'list' => $logs]]);die;
    }


    function _getWhere($params){

        $where = $this->buildmap($params);
        if ($params && !isset($where['O.status'])) {
            $where[] = ['O.status','neq','4'];
        }
        if ($params) {
            $sn = isset($params['sn']) ? trim($params['sn']) : '';
            if($sn){
                $where[] = ['O.order_sn|OS.sub_sn','like','%'.$sn.'%'];
            }
            $payNo = isset($params['pay_no']) ? trim($params['pay_no']) : '';
            if($payNo){
                $where[] = ['O.pay_sn','like','%'.$payNo.'%'];
            }
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['O.address_name','like','%'.$name.'%'];
            }
            $phone = isset($params['phone']) ? trim($params['phone']) : '';
            if($phone){
                $where[] = ['O.address_phone','like','%'.$phone.'%'];
            }
        }
        return $where;
    }

    private function buildmap($param = []){
        $map = [];
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $showOther = isset($param['other']) && intval($param['other']) ? 1 : 0;
        if ($showOther) {
            $map[] = ['OS.store_id','neq', $storeId];
            $sname = isset($param['sname']) ? trim($param['sname']) : '';
            if($sname){
                $map[] = ['S.name','like','%'.$sname.'%'];
            }
        }else{
            $map[] = ['OS.store_id','=',$storeId];
        }
//         $map = $this->storeId ? ['OS.store_id' => $this->storeId] : [];
        if(isset($param['pay_status'])){
            $map[] = ['O.status','=',1];
            $map[] = ['O.pay_status','=',$param['pay_status']];
        }elseif(isset($param['delivery_status'])){
            if ($param['delivery_status']) {
                $map[] = ['O.delivery_status','=',2];
            }else{
                $map[] = ['O.delivery_status','IN', [1,0]];
            }
            $map[] = ['O.pay_status','=',1];
            $map[] = ['O.finish_status','=',0];
        }elseif(isset($param['finish_status'])){
            $map[] = ['O.finish_status','=',2];
        }elseif(isset($param['status'])){
            $map[] = ['O.status','=',$param['status']];
        }
        return $map;
    }

    function _getField(){
        return 'U.nickname, OS.*, O.*, S.name as sname';
    }
    function _getJoin(){
        return [
            ['order_sub OS', 'O.order_id = OS.order_id', 'LEFT'],
            ['store S', 'S.store_id = OS.store_id', 'LEFT'],
            ['user U', 'O.user_id = U.user_id', 'LEFT'],
        ];
    }



}