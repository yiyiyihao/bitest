<?php
namespace app\common\service;
class Order
{
    var $error;
    var $deliverys; //物流公司
    public function __construct(){
        $this->deliverys = [
            'shunfeng' => [
                'identif' => 'shunfeng',
                'name' => '顺丰快递',
            ],
            'ems' => [
                'identif' => 'ems',
                'name' => 'EMS邮政',
            ],
            'debang' => [
                'identif' => 'debang',
                'name' => '德邦快递',
            ],
            'yuantong' => [
                'identif' => 'yuantong',
                'name' => '圆通快递',
            ],
            'zhongtong' => [
                'identif' => 'zhongtong',
                'name' => '中通快递',
            ],
            'yunda' => [
                'identif' => 'yunda',
                'name' => '韵达快递',
            ],
            'tiantian' => [
                'identif' => 'tiantian',
                'name' => '天天快递',
            ],
            'shentong' => [
                'identif' => 'shentong',
                'name' => '申通快递',
            ],
            'yunda' => [
                'identif' => 'yunda',
                'name' => '韵达快运',
            ],
        ];
    }
    /**
     * 获取字订单详情信息
     * @param string $subSn     子订单号
     * @param number $storeId   门店ID
     * @param number $userId    用户ID
     * @return array
     */
    public function getOrderDetail($subSn = '', $storeId = 0, $userId = 0)
    {
        $sub = $this->_checkSub($subSn, $storeId, $userId);
        if (!$sub) {
            $this->error = '订单号错误';
            return FALSE;
        }
        $order = db('order')->where(['order_sn' => $sub['order_sn']])->find();
        $order['_status'] = get_order_status($order);
        $detail['order'] = $order;
        $detail['sub'] = $sub;
        $detail['skus'] = db('order_sku')->where(['sub_sn' => $subSn])->select();
        $detail['logs'] = db('order_log')->where(['sub_sn' => $subSn])->order('add_time DESC')->select();
        $detail['user'] = db('user')->where(['user_id' => $sub['user_id']])->field('username, nickname, realname, avatar, face_img, phone')->find();
        return $detail;
    }
    /**
     * 订单跟踪信息入库
     * @param array $sub            子订单信息
     * @param number $odeliveryId   订单交易物流ID(order_sku_delivery表自增长ID)
     * @param string $remark        备注信息
     * @param string $time          信息跟踪时间
     * @return boolean
     */
    public function orderTrack($sub = [], $odeliveryId = 0, $remark = '', $time = false){
        if (!$sub){
            $this->error = '参数错误';
            return FALSE;
        }
        $time = $time ? $time : time();
        //添加订单跟踪记录
        $trackData = [
            'odelivery_id'  => $odeliveryId,
            'order_id'  => isset($sub['order_id']) ? $sub['order_id'] : '',
            'order_sn'  => isset($sub['order_sn']) ? $sub['order_sn'] : '',
            'sub_sn'    => $sub['sub_sn'],
            'msg'       => $remark ? $remark : '',
            'time'      => $time,
            'add_time'  => time(),
        ];
        $trackId = db('order_track')->insertGetId($trackData);
        if (!$trackId){
            $this->error = '订单信息跟踪:系统异常';
            return FALSE;
        }
        return TRUE;
    }
    /**
     * 订单日志信息入库
     * @param array $sub    子订单信息
     * @param array $user   用户操作用户信息
     * @param string $action操作方法
     * @param string $remark操作备注
     * @param int $serviceId售后ID
     * @return boolean
     */
    public function orderLog($sub = [], $user = [], $action = '', $remark = ''){
        if (!$sub || !$user || !$action){
            $this->error = '参数错误';
            return FALSE;
        }
        $name = $user['nickname'] ? $user['nickname'] : $user['username'];
        //添加订单日志
        $logData = [
            'order_id'  => isset($sub['order_id']) ? $sub['order_id'] : '',
            'order_sn'  => isset($sub['order_sn']) ? $sub['order_sn'] : '',
            'sub_sn'    => isset($sub['sub_sn']) && $sub['sub_sn'] ? $sub['sub_sn'] : '',
            'user_id'   => $user['user_id'],
            'nickname'  => trim($name),
            'action'    => $action ? $action : '操作订单',
            'msg'       => $remark ? $remark : '',
            'add_time'  => time(),
        ];
        $logId = db('order_log')->insertGetId($logData);
        if (!$logId){
            $this->error = '订单日志:系统异常';
            return FALSE;
        }
        return TRUE;
    }
    /**
     * 订单完成/确认收货操作
     * @param string $subSn     子订单号
     * @param number $storeId   门店ID
     * @param number $userId    操作用户ID
     * @param array $extra      其它参数信息
     * @return boolean
     */
    public function orderFinish($subSn = '', $storeId = 0, $userId = 0, $extra = [])
    {
        $sub = $this->_checkSub($subSn, $storeId, $userId);
        if (!$sub) {
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if ($sub['status'] != 1) {
            $this->error = '订单已取消, 不能执行当前操作';
            return FALSE;
        }
        if (!$sub['pay_status']) {
            $this->error = '订单未支付, 不能执行当前操作';
            return FALSE;
        }
        if (!$sub['delivery_status']) {
            $this->error = '订单未发货, 不能执行当前操作';
            return FALSE;
        }
        if ($sub['finish_status']) {
            $this->error = '订单已完成, 不能执行当前操作';
            return FALSE;
        }
        $data = [
            'finish_status' => 2,
            'finish_time' => time(),
            'update_time' => time(),
            'sub_id' => $sub['sub_id']
        ];
        $result = db('order_sub')->where(['sub_id' => $sub['sub_id']])->update($data);
        //子订单总数
        $subCount = db('order_sub')->where(['order_sn' => $sub['order_sn']])->count();
        //已完成子订单的总数
        $alreadyCount = db('order_sub')->where(['order_sn' => $sub['order_sn'], 'finish_status' => 2])->count();
        $data = [
            'update_time' => time(),
            'order_id' => $sub['order_id']
        ];
        //判断是否所有子订单都已取消
        if ($subCount == $alreadyCount) {
            $data['finish_status'] = 2;
        }else{
            $data['finish_status'] = 1;
        }
        $result = db('order')->where(array('order_id' => $sub['order_id']))->update($data);
        if (!$result) {
            $this->error = '子订单取消错误';
            return FALSE;
        }
        //修改订单商品表 已发货状态改为已收货
        $result = db('order_sku')->where(['sub_id' => $sub['sub_id'], 'delivery_status' => 1])->update(['delivery_status' => 2]);
        //修改订单商品物流表 收货状态改为1
        $result = db('order_sku_delivery')->where(['sub_sn' => $subSn])->update(['isreceive' => 1, 'receive_time' => time()]);
        
        $remark = isset($extra['remark']) && trim($extra['remark']) ? trim($extra['remark']) : '';
        $action = $storeId ? '确认完成' : '确认收货';
        $this->orderTrack($sub, 0, $remark);
        return $this->orderLog($sub, $user, $action, $remark);
    }
    /**
     * 订单发货操作
     * @param string $subSn     子订单号
     * @param number $storeId   门店ID
     * @param number $userId    操作用户ID
     * @param array $extra      其它参数信息
     * @return boolean
     */
    public function orderDelivery($subSn = '', $storeId = 0, $userId = 0, $extra = [])
    {
        $sub = $this->_checkSub($subSn, $storeId, $userId);
        if (!$sub) {
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if ($sub['status'] != 1) {
            $this->error = '订单已取消, 不能执行当前操作';
            return FALSE;
        }
        if (!$sub['pay_status']) {
            $this->error = '订单未支付, 不能执行当前操作';
            return FALSE;
        }
        if ($sub['delivery_status'] == 2 || $sub['finish_status']) {
            $this->error = '订单所有商品已发货, 不能执行当前操作';
            return FALSE;
        }
        $oskuIds = isset($extra['osku_id']) && $extra['osku_id'] ? $extra['osku_id'] : [];
        $oskuIds = $oskuIds ? array_filter($oskuIds) :[];
        $oskuIds = $oskuIds ? array_unique($oskuIds) :[];
        if (!$oskuIds) {
            $this->error = '请选择发货商品';
            return FALSE;
        }
        $oskuIds = implode(',', $oskuIds);
        $skus = db('order_sku')->where([['osku_id' , 'IN', $oskuIds], ['delivery_status', '=', 0]])->select();
        if (!$skus || ($skus && count($skus) != count($oskuIds))) {
            $this->error = '选择商品已发货';
            return FALSE;
        }
        foreach ($skus as $key => $value) {
            if ($value['return_status'] == 1) {
                $this->error = '商品正在申请退款，不能发货';
                return FALSE;
            }
            if ($value['return_status'] == 2) {
                $this->error = '商品已退款，不能发货';
                return FALSE;
            }
        }
        $isDelivery = isset($extra['is_delivery']) && intval($extra['is_delivery']) ? intval($extra['is_delivery']) : 0;
        $odeliveryId = 0;
        if ($isDelivery) {
            $deliveryIdentif = isset($extra['delivery_identif']) && trim($extra['delivery_identif']) ? trim($extra['delivery_identif']) : '';
            if (!$deliveryIdentif) {
                $this->error = '请选择物流公司';
                return FALSE;
            }
            if (!$this->deliverys[$deliveryIdentif]) {
                $this->error = '物流公司错误';
                return FALSE;
            }
            $deliverySn = isset($extra['delivery_sn']) && trim($extra['delivery_sn']) ? trim($extra['delivery_sn']) : '';
            if (!$deliverySn) {
                $this->error = '请输入第三方物流单号';
                return FALSE;
            }

            $deliveryData = [
                'order_id'  => $sub['order_id'],
                'order_sn'  => $sub['order_sn'],
                'sub_id'    => $sub['sub_id'],
                'sub_sn'    => $sub['sub_sn'],
                'user_id'   => $sub['user_id'],
                'osku_ids'  => $oskuIds ? $oskuIds : '',
                'delivery_identif'  => $deliveryIdentif,
                'delivery_name'     => $this->deliverys[$deliveryIdentif]['name'],
                'delivery_sn'       => $deliverySn,
                'delivery_time'     => time(),
                'add_time'          => time(),
            ];
            $odeliveryId = db('order_sku_delivery')->insertGetId($deliveryData);
            if (!$odeliveryId) {
                $this->error = '数据异常';
                return FALSE;
            }
        }
        $data = [
            'odelivery_id'      => $odeliveryId,
            'delivery_status'   => 1,
            'delivery_time'     => time(),
            'update_time'       => time(),
        ];
        $result = db('order_sku')->where('osku_id' ,'IN', $oskuIds)->update($data);
        if ($result === FALSE) {
            db('order_sku_delivery')->where(['odelivery_id' => $odeliveryId])->delete();
            $this->error = '操作异常';
            return FALSE;
        }
        $subData = [
            'update_time'   => time(),
            'delivery_time' => time(),
        ];
        //获取子订单商品总数
        $subCounts = db('order_sku')->where(['sub_id' => $sub['sub_id']])->count();
        //获取子订单发货商品总数
        $deliverySubCounts = db('order_sku')->where([['sub_id' ,'=', $sub['sub_id']], ['delivery_status' , '>', 0]])->count();
        if ($subCounts > $deliverySubCounts) {
            $subData['delivery_status'] = 1;
        }else{
            $subData['delivery_status'] = 2;
        }
        $subData['sub_id'] = $sub['sub_id'];
        $result = db('order_sub')->where('sub_id' ,'=', $sub['sub_id'])->update($subData);
        if (!$result) {
            $this->error = '修改发货状态错误';
            return FALSE;
        }
        $orderData = [
            'update_time'   => time(),
            'order_id' => $sub['order_id']
        ];
        //获取主订单商品总数
        $orderCounts = db('order_sku')->where(['order_id' => $sub['order_id']])->count();
        //获取主订单发货商品总数
        $deliveryOrderCounts = db('order_sku')->where([['order_id' ,'=', $sub['order_id']], ['delivery_status' , '>', 0]])->count();
        if ($orderCounts > $deliveryOrderCounts) {
            $orderData['delivery_status'] = 1;
        }else{
            $orderData['delivery_status'] = 2;
        }
        $result = db('order')->where(array('order_id' => $sub['order_id']))->update($orderData);
        if (!$result) {
            $this->error = '修改主订单发货错误';
            return FALSE;
        }
        $remark = isset($extra['remark']) && trim($extra['remark']) ? trim($extra['remark']) : '';
        //订单日志
        $this->orderLog($sub, $user, '订单发货', $remark);
        // 订单跟踪
        $msg = $odeliveryId ? ',等待商品揽收' : ''; 
        $this->orderTrack($sub, $odeliveryId, '商家已发货'.$msg);
        return TRUE;
    }
    /**
     * 订单修改支付金额操作
     * @param string $orderSn
     * @param int $storeId
     * @param int $userId
     * @param array $extra
     * @return boolean
     */
    public function orderUpdatePrice($orderSn = '', $storeId = 0, $userId = 0, $extra = [])
    {
        $order = $this->_checkOrder($orderSn, $storeId, $userId);
        if (!$order) {
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if ($order['pay_status']) {
            $this->error = '已支付, 不能调整支付金额';
            return FALSE;
        }
        if ($order['delivery_status'] || $order['finish_status']) {
            $this->error = '已发货, 不能调整支付金额';
            return FALSE;
        }
        $remark = isset($extra['remark']) && trim($extra['remark']) ? trim($extra['remark']) : '管理员调整订单支付金额,[订单原价:'.$order['real_amount'].'元]';
        $realAmount = isset($extra['real_amount']) && floatval($extra['real_amount']) > 0 ? floatval($extra['real_amount']) : 0;
        if ($realAmount <= 0) {
            $this->error = '调整后的支付金额必须大于0';
            return FALSE;
        }
        if ($order['real_amount'] == $realAmount) {
            $this->error = '调整后的支付金额跟原有订单支付金额一致';
            return FALSE;
        }
        $data = [
            'real_amount' => $realAmount,
            'update_time' => time(),
            'order_id' => $order['order_id']
        ];
        //调整订单支付金额
        $result = db('order')->where(['order_id' => $order['order_id']])->update($data);
        if ($result === FALSE) {
            $this->error = '订单操作异常';
            return FALSE;
        }
        $this->orderLog($order, $user, '调整订单支付金额', $remark);
        return TRUE;
    }
    /**
     * 订单支付操作
     * @param string $orderSn   主订单号
     * @param number $storeId   门店ID
     * @param number $userId    操作用户ID
     * @param array $extra      其它参数信息
     *                          
     * @return boolean
     */
    public function orderPay($orderSn = '', $storeId = 0, $userId = 0, $extra = [])
    {
        $order = $this->_checkOrder($orderSn, $storeId, $userId);
        if (!$order) {
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if ($order['status'] != 1) {
            $this->error = '不能支付当前订单';
            return FALSE;
        }
        if ($order['pay_status']) {
            $this->error = '已支付, 不能重复支付';
            return FALSE;
        }
        if ($order['delivery_status'] || $order['finish_status']) {
            $this->error = '已发货, 不能支付当前订单';
            return FALSE;
        }
        $payMethod = isset($extra['pay_method']) ? trim($extra['pay_method']) : '';
        $paySn = '';
        if ($order['pay_type'] == 1 && $payMethod != 'balance') {
            $paySn = isset($extra['pay_sn']) && trim($extra['pay_sn']) ? trim($extra['pay_sn']) : '';
            if (!$paySn) {
                $this->error = '第三方交易号不能为空';
                return FALSE;
            }
        }
        $remark = isset($extra['remark']) && trim($extra['remark']) ? trim($extra['remark']) : '';
        $paidAmount = isset($extra['paid_amount']) && floatval($extra['paid_amount']) > 0 ? floatval($extra['paid_amount']) : $order['real_amount'];
        $data = [
            'pay_status' => 1,
            'pay_time'   => time(),
            'update_time'=> time(),
            'order_id' => $order['order_id']
        ];
        $subs = db('order_sub')->where(['order_id' => $order['order_id'], 'pay_status' => ['<>', 1]])->select();
        if (!$subs) {
            $this->error = '订单已支付';
            return FALSE;
        }
        //将订单下的子订单全部设置为已支付状态
        $result = db('order_sub')->where(['order_id' => $order['order_id'], 'pay_status' => ['<>', 1]])->update($data);
        if ($result === FALSE) {
            $this->error = '子订单操作异常';
            return FALSE;
        }
        if ($paySn) {
            $data['pay_sn'] = $paySn;
        }
        if ($paidAmount > 0) {//实际支付金额
            $data['paid_amount'] = $paidAmount;
        }
        if ($payMethod) {
            $data['pay_method'] = $payMethod;
        }
        //设置当前主订单为已支付状态
        $result = db('order')->where([['order_id' ,'=', $order['order_id']], ['pay_status' , '<>', 1]])->update($data);
        if ($result === FALSE) {
            $result = db('order_sub')->where(['order_id' => $order['order_id']])->update(['pay_status' => 0,'pay_time' => 0,'update_time'=> time()]);
            $this->error = '主订单操作异常';
            return FALSE;
        }
        foreach ($subs as $key => $value) {
            $this->orderLog($value, $user, '支付订单', $remark);
            $this->orderTrack($value, 0, '您的订单已付款, 请耐心等待商家发货');
        }
        return TRUE;
    }
    /**
     * 订单取消操作
     * @param string $subSn     子订单号
     * @param number $storeId   门店ID
     * @param number $userId    操作用户ID
     * @param string $remark    操作备注
     * @return boolean
     */
    public function orderCancel($subSn = '', $storeId = 0, $userId = 0, $remark = ''){
        $sub = $this->_checkSub($subSn, $storeId, $userId);
        if (!$sub) {
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if ($sub['status'] != 1) {
            $this->error = '不能取消当前订单';
            return FALSE;
        }
        if ($sub['pay_status']) {
            $this->error = '已支付, 不能取消当前订单';
            return FALSE;
        }
        if ($sub['delivery_status'] || $sub['finish_status']) {
            $this->error = '已发货, 不能取消当前订单';
            return FALSE;
        }
        $data = [
            'status' => 2,
            'cancel_time' => time(),
            'update_time' => time(),
        ];
        //取消当前子订单
        $result = db('order_sub')->where(['sub_id' => $sub['sub_id']])->update($data);
        //子订单总数
        $subCount = db('order_sub')->where(['order_sn' => $sub['order_sn']])->count();
        //已取消子订单的总数
        $alreadyCount = db('order_sub')->where(['order_sn' => $sub['order_sn'], 'status' => 2])->count();
        //判断是否所有子订单都已取消
        if ($subCount == $alreadyCount) {
            $result = db('order')->where(array('order_id' => $sub['order_id']))->update(['status' => 2, 'update_time' => time()]);
            if (!$result) {
                $this->error = '修改取消所有的子订单错误';
                return FALSE;
            }
        }
        //取消订单，商品库存增加
        $skus = db('order_sku')->where(['sub_sn' => $subSn])->select();
        if ($skus) {
            $goodsModel = new \app\common\model\Goods();
            foreach ($skus as $key => $value) {
                $goodsModel->_setGoodsStock($value, $value['num']);
            }
        }
        $this->orderTrack($sub, 0, $remark);
        return $this->orderLog($sub, $user, '取消订单', $remark);
    }
    /**
     * 商品售后退款
     * @param string $subSn
     * @param int $skuId
     * @param int $storeId
     * @param int $userId
     * @param string $remark
     * @return boolean
     */
    public function orderSkuReturn($subSn = '', $skuId = 0, $storeId = 0, $userId = 0, $remark = ''){
        $sub = $this->_checkSub($subSn, $storeId, $userId);
        if (!$sub) {
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if ($sub['status'] == 2) {
            $this->error = '订单已取消，不能执行当前操作';
            return FALSE;
        }
        if ($sub['status'] == 3) {
            $this->error = '订单已关闭，不能执行当前操作';
            return FALSE;
        }
        if ($sub['status'] != 1) {
            $this->error = '不能执行当前操作';
            return FALSE;
        }
        if (!$sub['pay_status']) {
            $this->error = '未支付, 不能执行当前操作';
            return FALSE;
        }
        if ($sub['finish_status']) {
            $this->error = '订单已完成, 不能执行当前操作';
            return FALSE;
        }
        $sku = db('order_sku')->where(['sub_id' => $sub['sub_id'], 'sku_id' => $skuId])->find();
        if ($sku['return_status'] == 2) {
            $this->error = '商品已退款, 不能执行当前操作';
            return FALSE;
        }
        $result = db('order_sku')->where(['osku_id' => $sku['osku_id']])->update(['update_time' => time(), 'return_status' => 2]);
        if ($result === FALSE) {
            $this->error = '修改退货错误';
            return FALSE;
        }
        //判断子订单商品数量
        $skuCount = db('order_sku')->where(['sub_id' => $sub['sub_id']])->count();
        //获取已退款商品数量
        $returnCount = db('order_sku')->where(['sub_id' => $sub['sub_id'], 'return_status' => 2])->count();
        $remark = '商品售后退款';
        if ($skuCount <= $returnCount) {
            //修改子订单为关闭状态
            $result = db('order_sub')->where(['sub_id' => $sub['sub_id']])->update(['update_time' => time(), 'status' => 3]);
            if ($result === FALSE) {
                $this->error = '修改子订单为关闭状态失败';
                return FALSE;
            }
            $this->orderTrack($sub, 0, '关闭订单', $remark);
            //子订单总数
            $subCount = db('order_sub')->where(['order_sn' => $sub['order_sn']])->count();
            //已关闭的子订单的总数
            $alreadyCount = db('order_sub')->where(['order_sn' => $sub['order_sn'], 'status' => 3])->count();
            //判断是否所有子订单都已取消
            if ($subCount <= $alreadyCount) {
                $result = db('order')->where(array('order_id' => $sub['order_id']))->update(['status' => 3, 'update_time' => time()]);
                if (!$result) {
                    $this->error = '修改取消所有子订单错误';
                    return FALSE;
                }
                return $this->orderLog($sub, $user, '关闭订单', $remark);
            }
        }
        return TRUE;
    }
    /**
     * 更新快递100数据
     * @param  string 	$sub_sn  子订单号 (必传)
     * @param  string 	$o_d_id  订单物流主键id (必传)
     * @return [boolean]
     */
    public function updateApi100($subSn, $storeId, $userId, $odeliveryId) {
        $sub = $this->_checkSub($subSn, $storeId, $userId);
        if (!$sub) {
            return FALSE;
        }
        if (!$odeliveryId) {
            $this->error = '物流数据不能为空';
            return FALSE;
        }
        $delivery = db('order_sku_delivery')->where(['odelivery_id' => $odeliveryId])->find();
        if (!$delivery) {
            $this->error = '物流信息不存在';
            return FALSE;
        }
        if(!$delivery['delivery_sn']){
            $this->error = '物流单号不存在';
            return FALSE;
        }
        // 获取物流标识
        $identif = $delivery['delivery_identif'];
        if (!$identif) {
            $this->error = '物流唯一标志号码不存在';
            return FALSE;
        }
        if (in_array($delivery['delivery_status'], [3, 6])) {
            return TRUE;
        }
        // 统计当前已发货物流跟踪条数、和最后一条记录
        $where = [
            'odelivery_id' => $odeliveryId,
            'sub_sn' => $subSn,
        ];
        $trackModel = db('order_track');
        $count = $trackModel->where($where)->count();
        $track = $trackModel->where($where)->field('order_sn, time')->order('track_id DESC')->find();
        $datas = $this->_kuaidi100($identif, $delivery['delivery_sn']);
        if ($datas === FALSE){
            return FALSE;
        }
        krsort($datas['data']);
        foreach($datas['data'] as $v){
            $_time = isset($v['time']) && $v['time'] ? strtotime($v['time']): '';
            if ($count != 1 && $_time <= $track['time']) {
                continue;
            }
            $time = $_time ? $_time : time();
            $this->orderTrack($sub, $odeliveryId, $v['context'], $time);
        }
        $data = [];
        if (isset($datas['message']) && $datas['message'] && $delivery['delivery_msg'] != $datas['message']) {
            $data['delivery_msg'] = $datas['message'];
        }
        if (isset($datas['state']) && $datas['state'] && $delivery['delivery_status'] != $datas['state']) {
            $data['delivery_status'] = $datas['state'];
        }
        if ($data) {
            db('order_sku_delivery')->where(['odelivery_id' => $odeliveryId])->update($data);
        }
        return TRUE;
    }
    /**
     * 根据快递单号查询快递100获取快递信息
     * @param  string 	$com  	快递代码 (必传)
     * @param  string 	$nu  	快递单号 (必传)
     * @return [result]
     */
    private function _kuaidi100($identif = '' , $deliverySn = '') {
        if(empty($identif) || empty($deliverySn)) {
            $this->error = '快递请求错误';
            return FALSE;
        }
        $url = 'http://www.kuaidi100.com/query?';
        $params = [
            'id' => 1,
            'type' => $identif,
            'postid' => $deliverySn,
        ];
//        $params = [
//            'id' => 1,
//            'type' => 'zhongtong',
//            'postid' => '75127174265081',
//        ];
        $url .= http_build_query($params);
        $result = curl_post($url, []);
        if($result) {
            if ($result['status'] == 200) {
                unset($result['status']);
                switch ($result['state']) {
                    case '0':
                        $result['message'] = '运输中';
                        break;
                    case '1':
                        $result['message'] = '已揽件';
                        break;
                    case '2':
                        $result['message'] = '疑难件';
                        break;
                    case '3':
                        $result['message'] = '已签收';
                        break;
                    case '4':
                        $result['message'] = '已退签';
                        break;
                    case '5':
                        $result['message'] = '派送中';
                        break;
                    case '6':
                        $result['message'] = '已退回';
                        break;
                    default:
                        $result['message'] = '其他';
                        break;
                }
                return $result;
            } else if($result['status'] == 201) {
                $this->error = $result['message'];
                return FALSE;
            } else if($result['status'] == 2) {
                $this->error = '接口出现异常';
                return FALSE;
            } else {
                $this->error = '物流单暂无结果'.$result['message'];
                return FALSE;
            }
        } else {
            $this->error = '查询失败，请稍候重试';
            return FALSE;
        }
    }
    /**
     * 检查主订单信息
     * @param string $orderSn 主订单号
     * @param number $storeId 门店ID
     * @param number $userId  用户ID
     * @return array
     */
    public function _checkOrder($orderSn = '', $storeId = 0, $userId = 0)
    {
        if (!$orderSn) {
            $this->error = '订单号不能为空';
            return FALSE;
        }
        if (!$userId){
            $this->error = '参数错误';
            return FALSE;
        }
        $where = ['order_sn' => $orderSn];
        if ($storeId) {
            db('order')->where('FIND_IN_SET('.$storeId.',store_ids)');
        }else{
            $where['user_id'] = $userId;
        }
        $sub = db('order')->where($where)->find();
        if (!$sub) {
            $this->error = '非法的订单号';
            return FALSE;
        }
        return $sub;
    }
    /**
     * 检查子订单信息
     * @param string $subSn     子订单号
     * @param number $storeId   门店ID
     * @param number $userId    用户ID
     * @return array
     */
    public function _checkSub($subSn = '', $storeId = 0, $userId = 0)
    {
        if (!$subSn) {
            $this->error = '订单号不能为空';
            return FALSE;
        }
        if (!$userId){
            $this->error = '参数错误';
            return FALSE;
        }
        $where = ['sub_sn' => $subSn];
        if ($storeId) {
            if (defined('ADMIN_ID') && ADMIN_ID == 1) {
                
            }else{
                $where['store_id'] = $storeId;
            }
        }else{
            $where['user_id'] = $userId;
        }
        $sub = db('order_sub')->where($where)->find();
        if (!$sub) {
            $this->error ='非法子订单号';
            return FALSE;
        }
        return $sub;
    }
}