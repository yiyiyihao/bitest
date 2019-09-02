<?php
namespace app\common\service;
class ServiceOrder
{
    var $error;
    var $returnTypes;
    public function __construct(){
        $this->returnTypes = [
            1 => [
                'value' => '退回余额',
                'checked' => 1
            ],
//             2 => [
//                 'value' => '原路退回',
//                 'checked' => 0
//             ],
        ];
    }
    /**
     * 申请售后
     * @param string $subSn
     * @param int $userId
     * @param int $serviceType
     * @param array $skuIds
     * @param int $num
     * @param array $extra
     * @return boolean|number|string
     */
    public function applyServiceOrder($subSn = '', $userId = 0, $serviceType = 0, $skuIds = [], $num = 0, $extra = [])
    {
        $name = $serviceType == 1? '退款' : '退货并退款';
        $sub = $this->_checkSub($subSn, 0, $userId);
        if (!$sub) {
            $this->error = '订单不存在';
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        $goodsStatus = isset($extra['goods_status']) && intval($extra['goods_status']) ? 1 : 0;
        $returnAmount = isset($extra['return_amount']) ? floatval($extra['return_amount']) : 0;
        if ($returnAmount <= 0) {
            $this->error = '退款金额必须大于0';
            return FALSE;
        }
        $reason = isset($extra['reason']) ? trim($extra['reason']) : '';
        if (empty($reason)) {
            $this->error = '退款原因不能为空';
            return FALSE;
        }
        //判断订单状态
        if ($sub['status'] != 1) {
            $this->error = '订单已取消, 不能执行当前操作';
            return FALSE;
        }
        if (!$sub['pay_status']) {
            $this->error = '订单未支付, 不能执行当前操作';
            return FALSE;
        }
        if (!$sub['delivery_status']) {
            $this->error = '订单商品未发货, 不能执行当前操作';
            return FALSE;
        }
        if ($sub['finish_status'] > 0) {
            $this->error = '订单已确认完成, 不能执行当前操作';
            return FALSE;
        }
        $length = count($skuIds);
        $map = [
            'user_id' => $user['user_id'],
            'sub_sn' => $sub['sub_sn'],
        ];
        $skuModel = db('order_sku');
        if ($length == 1) {
            $map['sku_id'] = intval(implode(',', $skuIds));
            $info = $skuModel->where($map)->find();
            if (!$info) {
                $this->error = '当前商品不属于当前订单';
                return FALSE;
            }
            if ($num > $info['num']) {
                $this->error = '退款商品数量大于最大退款商品数量';
                return FALSE;
            }
            $info['num'] = $num;
            $info['return_amount'] = $returnAmount;
            $list[] = $info;
        }else{
            $map['sku_id'] = ['IN', $skuIds];
            //判断商品ID是否属于当前订单，当前用户
            $list = $skuModel->where($map)->select();
            if (!$list) {
                $this->error = '请求参数错误(所有商品不属于当前订单)';
                return FALSE;
            }
            if ($length != count($list)) {
                $this->error = '请求参数错误(部分商品不属于当前订单)';
                return FALSE;
            }
        }
        $maxAmount = 0;
        //获取最大可退款金额
        foreach ($list as $key => $value) {
            $where = [
                'sub_sn'    => $value['sub_sn'], 
                'osku_id'   => $value['osku_id'], 
                'status'    => 0, //未取消
//                 'apply_status'  => ['<>', -1],//未拒绝(已拒绝可重新发起申请) 
            ];
            //判断当前商品是否已经申请售后/已完成售后
            $exist = db('order_service_sku')->field('apply_status, return_status')->where($where)->find();
            if ($exist) {
                if ($exist['apply_status'] == 0) {
                    $this->error = '部分商品审核中，不能执行当前操作';
                    return FALSE;
                }
                if ($exist['apply_status'] == -1) {
                    $this->error = '部分商品未通过审核，不能执行当前操作';
                    return FALSE;
                }
                if ($exist['apply_status'] == 1) {
                    $this->error = '部分商品已通过审核，不能执行当前操作';
                    return FALSE;
                }
                if ($exist['return_status'] == 1) {
                    $this->error = '部分商品已退款，不能执行当前操作';
                    return FALSE;
                }
                $this->error = '不能执行当前操作';
                return FALSE;
            }
            if ($serviceType == 2 && $info['delivery_status'] <= 0) {
                $this->error = '选择商品未发货，不能执行当前操作';
                return FALSE;
            }
            $maxAmount += $value['real_amount'];
        }
        if ($returnAmount > $maxAmount) {
            $this->error = '退款金额大于最大退款金额';
            return FALSE;
        }
        if ($length > 1 && $returnAmount < $maxAmount) {
            $return = [];
            //多个商品计算单个商品可退款最大金额(用户修改退款金额时)
            foreach ($list as $key => $value) {
                $realReturn = $returnAmount * $value['real_amount']/ $maxAmount;
                $return[$key]['return_amount'] = round($realReturn, 2);
            }
        }
        $images = isset($extra['imgs']) ? trim($extra['imgs']) : '';
        if ($images) {
            $images = array_explode($images);
            if ($images && count($images) > 5) {
                $this->error = '凭证图片不能超过5张';
                return FALSE;
            }
            $images = json_encode($images);
        }
        //生成售后服务单
        $data = [
            'order_id'  => $sub['order_id'],
            'order_sn'  => $sub['order_sn'],
            'sub_sn'    => $sub['sub_sn'],
            'store_id'  => $sub['store_id'],
            'user_id'   => $sub['user_id'],
            'service_type'  => $serviceType,
            'goods_status'  => $goodsStatus,
            'return_amount' => $returnAmount,
            'reason'    => trim($extra['reason']),
            'remark'    => isset($extra['remark']) ? trim($extra['remark']) : '',
            'imgs'      => $images,
            'add_time'  => time(),
            'update_time'   => time(),
        ];
        $serviceId = db('order_service')->insertGetId($data);
        if (!$serviceId) {
            $this->error = '系统异常';
            return FALSE;
        }
        $dataSet = [];
        foreach ($list as $key => $value) {
            $dataSet[] = [
                'service_id'=> $serviceId,
                'order_id'  => $value['order_id'],
                'order_sn'  => $value['order_sn'],
                'sub_id'    => $value['sub_id'],
                'sub_sn'    => $value['sub_sn'],
                'osku_id'   => $value['osku_id'],
                'user_id'   => $value['user_id'],
                'store_id'  => $value['store_id'],
                'goods_id'  => $value['goods_id'],
                'sku_id'    => $value['sku_id'],
                'sku_name'  => $value['sku_name'],
                'sku_thumb' => $value['sku_thumb'],
                'sku_spec'  => $value['sku_spec'],
                'sku_info'  => $value['sku_info'],
                'goods_status'  => $goodsStatus,
                'num'           => $value['num'],
                'return_amount' => $value['pay_price'],
                'add_time'      => time(),
                'update_time'   => time(),
            ];
        }
        if ($dataSet) {
            //将商品修改为退款中状态
            $result = $skuModel->where($map)->update(['update_time' => time(), 'return_status' => 1]);
            $result = db('order_service_sku')->insertAll($dataSet);
            if ($result === FALSE) {
                $this->error = '系统错误';
                return FALSE;
            }
            $data['service_id'] = $serviceId;
            $this->serviceLog($data, 0, $user, '申请'.$name, $reason, $serviceId);
            return $serviceId;
        }else{
            return FALSE;
        }
    }
    /**
     * 获取售后详情
     * @param int $serviceId
     * @param int $storeId
     * @param int $userId
     * @param int $skuId
     * @return array
     */
    public function getServiceOrderDetail($serviceId = 0, $storeId = 0, $userId = 0, $skuId = 0)
    {
        $field = 'service_id, order_id, order_sn, sub_sn, store_id, service_type, goods_status, return_amount, reason, remark, imgs, status, apply_status, return_status, add_time';
        $service = $this->_checkService($serviceId, $storeId, $userId, $field);
        if (!$service) {
            $this->error = '售后申请不存在';
            return FALSE;
        }
        $service['imgs'] = $service['imgs'] ? json_decode($service['imgs'], 1) : [];
        $detail['order'] = db('order')->where(['order_id' => $service['order_id']])->find();;
        $detail['sub'] = db('order_sub')->where(['sub_sn' => $service['sub_sn']])->find();;
        $detail['service'] = $service;
        $where = ['service_id' => $serviceId];
        if ($skuId > 0) {
            $where['sku_id'] = $skuId;
        }
        $skus = db('order_service_sku')->field('service_sku_id, osku_id, goods_id, sku_id, sku_name, sku_spec, sku_thumb, sku_info, num, return_amount, status, apply_status, return_status, delivery_name, delivery_sn, delivery_time, add_time')->where($where)->select();;
        if ($skus) {
            foreach ($skus as $key => $value) {
                $skus[$key]['sku_info'] = $value['sku_info'] ? json_decode($value['sku_info'], 1) : [];
            }
        }
        $detail['skus'] = $skus;
        
        $detail['logs'] = db('order_service_log')->where(['service_id' => $serviceId])->field('nickname, action, msg, add_time')->order('add_time DESC')->select();
        //获取门店信息
        $detail['store'] = db('store')->where(['store_id' => $service['store_id']])->field('store_id, name')->find();
        unset($detail['service']['store_id']);
        return $detail;
    }
    /**
     * 确认退款
     * @param int $serviceId
     * @param int $storeId
     * @param int $userId
     * @param int $returnType
     * @param int $skuId
     * @param array $extra
     * @return boolean
     */
    public function confirmReturn($serviceId = 0, $storeId = 0, $userId = 0, $returnType = 1, $skuId = 0, $extra = [])
    {
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        $service = $this->_checkService($serviceId, $storeId, $userId);
        if (!$skuId) {
            $this->error = '请选择退款商品';
            return FALSE;
        }
        $where = [
            'sku_id' => $skuId,
            'service_id' => $serviceId,
            'store_id' => $storeId,
            'status' => 0,
            'apply_status' => 1,
            'return_status' => 0,//未退款
        ];
        $serviceSku = db('order_service_sku')->where($where)->find();
        if (!$serviceSku) {
            $this->error = '参数错误';
            return FALSE;
        }
        $returnAmount = $serviceSku['return_amount'];
        if(!$returnType){
            $this->error = '退款方式不能为空';
            return FALSE;
        }
        if (!$this->returnTypes[$returnType]) {
            $this->error = '退款方式错误';
            return FALSE;
        }
        $extra = isset($serviceSku) && $serviceSku ? $serviceSku : $service;
        if ($returnType == 1) {
            //退回用户余额
            $userService = new \app\common\service\User();
            $result = $userService->assetChange($service['user_id'], 'balance', $returnAmount, '售后退款', '订单售后退款', $extra);
            if ($result === FALSE) {
                $this->error = $userService->error;
                return FALSE;
            }
        }elseif ($returnType == 2){
            #TODO接口原路退
        }
        #TODO 退款完成发送通知(微信模板消息/短信)
        $update = [
            'update_time' => time(),
            'return_status' => 2,
        ];
        //退款成功后修改申请状态
        if ($skuId && isset($serviceSku) && $serviceSku) {
            $result = db('order_service_sku')->where(['service_sku_id' => $serviceSku['service_sku_id']])->update(['update_time' => time(), 'return_status' => 1, 'return_type' => $returnType]);
            $totalCount = db('order_service_sku')->where(['service_id' => $serviceId])->count();
            $returnCount = db('order_service_sku')->where(['service_id' => $serviceId, 'return_status' => 1])->count();
            if ($totalCount > $returnCount) {
                $update['return_status'] = 1;
            }
            $action = '部分退款';
        }else{
            $action = '确认退款';
            $result = db('order_service_sku')->where(['service_id' => $serviceId])->update(['update_time' => time(), 'return_status' => 1, 'return_type' => $returnType]);
        }
        $remark = isset($extra['remark']) ? trim($extra['remark']) : '';
        $result = db('order_service')->where(['service_id' => $serviceId])->update($update);
        //商品退款
        $orderService = new \app\common\service\Order();
        $orderService->orderSkuReturn($serviceSku['sub_sn'], $serviceSku['sku_id'], $storeId, $userId, '售后退款');
        //退款日志
        $serviceSkuId = isset($serviceSku['service_sku_id']) && $serviceSku['service_sku_id'] ? intval($serviceSku['service_sku_id']) : 0;
        $this->serviceLog($service, $serviceSkuId, $user, $action, $remark);
        return TRUE;
    }
    /**
     * 售后申请审核
     * @param int $serviceId
     * @param int $storeId
     * @param int $userId
     * @param array $skuIds
     * @param array $extra
     * @return boolean
     */
    public function checkOrderService($serviceId = 0, $storeId = 0, $userId = 0, $skuIds = [], $extra = [])
    {
        $service = $this->_checkService($serviceId, $storeId);
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$service || !$user || !$skuIds) {
            $this->error = '请求参数错误';
            return FALSE;
        }
        $status = isset($extra['status']) ? intval($extra['status']) : 0;
        $adminRemark = isset($extra['admin_remark']) ?trim($extra['admin_remark']) : '';
        
        if ($service['status'] == 2) {
            $this->error = '申请已撤销，不能执行操作';
            return FALSE;
        }
        if ($service['apply_status'] == 2) {
            $this->error = '申请已通过，不能执行操作';
            return FALSE;
        }
        if ($service['apply_status'] == -2) {
            $this->error = '申请已拒绝，不能执行操作';
            return FALSE;
        }
        if ($service['return_status'] == 2) {
            $this->error = '已退款，不能执行操作';
            return FALSE;
        }
        $count = count($skuIds);//获取撤销商品数量
        $where = ['service_id' => $serviceId, 'sku_id' => ['IN', $skuIds], 'status' => 0, 'apply_status' => 0, 'return_status' => 0];
        $skus = db('order_service_sku')->where($where)->select();
        if (!$skus || $count != count($skus)) {
            $this->error = '不能执行操作';
            return FALSE;
        }
        foreach ($skus as $key => $value) {
            if ($value['status'] != 0) {
                $this->error = '部分商品申请已撤销,不能执行操作';
                return FALSE;
            }
            if ($value['apply_status'] != 0) {
                $this->error = '部分商品申请已审核,不能执行操作';
                return FALSE;
            }
            if ($value['return_status'] != 0) {
                $this->error = '部分商品申请已退款,不能执行操作';
                return FALSE;
            }
        }
        $totalCount = db('order_service_sku')->where(['service_id' => $serviceId])->count();
        $result = db('order_service_sku')->where($where)->update(['update_time' => time(), 'apply_status' => $status, 'admin_remark' => $adminRemark]);
        $readyCount = db('order_service_sku')->where(['service_id' => $serviceId, 'status' => 0, 'apply_status' => 1, 'return_status' => 0])->count();
        $data = [
            'update_time' => time(),
        ];
        $name = $status > 0 ? '通过' : '拒绝';
        if ($totalCount > $readyCount) {
            $data['apply_status'] = $status > 0 ? 1 : -1;
            $action = $name.'审核[部分]';
        }else{
            $data['apply_status'] = $status > 0 ? 2 : -2;
            $action = $name.'审核';
        }
        $result = db('order_service')->where(array('service_id' => $serviceId))->update($data);
        if (!$result) {
            $this->error = db('order_service')->getError();
            return FALSE;
        }
        foreach ($skus as $key => $value) {
            $this->serviceLog($service, $value['service_sku_id'], $user, $action, $adminRemark);
        }
        return TRUE;
    }
    /**
     * 撤销申请
     * @param = 0 $serviceId
     * @param = 0 $userId
     * @param array $skuIds
     * @return boolean
     */
    public function cancelOrderService($serviceId = 0, $userId = 0, $skuIds = [])
    {
        $service = $this->_checkService($serviceId, 0, $userId);
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '参数错误';
            return FALSE;
        }
        if (!$skuIds) {
            $this->error = '请求参数错误';
            return FALSE;
        }
        if ($service['status'] == 2) {
            $this->error = '申请已撤销，不能执行当前操作';
            return FALSE;
        }
        if ($service['return_status'] == 2) {
            $this->error = '已完成退款，不能执行当前操作';
            return FALSE;
        }
        $sub = $this->_checkSub($service['sub_sn'], 0, $userId);
        $where = [
            ['service_id' ,'=', $serviceId],
            ['sku_id' ,'in', $skuIds],
            ['status' ,'=', 0],
            ['return_status' ,'=', 0],
        ];
        //获取未取消的订单商品数量[订单商品范围内]
        $skus = db('order_service_sku')->where($where)->select();
        if (!$skus || count($skuIds) != count($skus)) {
            $this->error = '不能执行当前操作';
            return FALSE;
        }
        $result = db('order_service_sku')->where($where)->update(['status' => 1, 'update_time' => time()]);
        if ($result === FALSE) {
            $this->error = '系统异常';
            return FALSE;
        }
        //商品申请总数
        $totalCount = db('order_service_sku')->where(['service_id' => $serviceId])->count();
        //已取消商品申请总数
        $alreadyCount = db('order_service_sku')->where(['service_id' => $serviceId, 'status' => 1])->count();
        //判断是否所有商品申请都已取消
        $data = ['status' => 2, 'update_time' => time()];
        if ($totalCount > $alreadyCount) {
            $data['status'] = 1;
        }
        $result = db('order_service')->where(array('service_id' => $serviceId))->update($data);
        if (!$result) {
            $this->error = db('order_service')->getError();
            return FALSE;
        }
        if ($totalCount == count($skus)) {
            $action = '撤销申请';
        }else{
            $action = '撤销申请[部分]';
        }
        $orderService = new \app\common\service\Order();
        $name = $service['service_type'] == 1? '退款' : '售后';
        foreach ($skus as $key => $value) {
            //撤销退款状态
            $result = db('order_sku')->where(['osku_id' => $value['osku_id']])->update(['update_time' => time(), 'return_status' => 0]);
            $orderService->orderLog($sub, $user, '撤销'.$name.'申请');
            $this->serviceLog($service, $value['service_sku_id'], $user, $action);
        }
        return TRUE;
    }
    /**
     * 售后日志
     * @param array $service
     * @param int $serviceSkuId
     * @param array $user
     * @param string $action
     * @param string $remark
     * @return boolean
     */
    public function serviceLog($service = [], $serviceSkuId = 0, $user = [], $action = '', $remark = ''){
        if (!$service || !$user || !$action){
            $this->error = '参数错误';
            return FALSE;
        }
        $name = isset($user['nickname']) && $user['nickname'] ? $user['nickname'] : $user['username'];
        //添加订单日志
        $logData = [
            'order_id'  => isset($service['order_id']) ? $service['order_id'] : 0,
            'order_sn'  => isset($service['order_sn']) ? $service['order_sn'] : '',
            'sub_sn'    => isset($service['sub_sn']) && $service['sub_sn'] ? $service['sub_sn'] : '',
            'service_id'=> isset($service['service_id']) ? $service['service_id'] : 0,
            'service_sku_id'=> intval($serviceSkuId),
            'user_id'   => $user['user_id'],
            'nickname'  => trim($name),
            'action'    => $action ? $action : '售后操作',
            'msg'       => $remark ? $remark : '',
            'add_time'  => time(),
        ];
        $logId = db('order_service_log')->insertGetId($logData);
        if (!$logId){
            $this->error = '订单售后日志:系统异常';
            return FALSE;
        }
        return TRUE;
    }
    /**
     * 检查售后信息
     * @param int $serviceId
     * @param int $storeId
     * @param int $userId
     * @param string $field
     * @return boolean|array|\think\db\false|PDOStatement|string|\think\Model
     */
    public function _checkService($serviceId, $storeId = 0, $userId = 0, $field = '*')
    {
        if (!$serviceId) {
            $this->error = '售后申请ID不能为空';
            return FALSE;
        }
        $where = ['service_id' => $serviceId];
        if ($storeId) {
            $where['store_id'] = $storeId;
        }elseif ($userId){
            $where['user_id'] = $userId;
        }else{
            $this->error = '参数异常';
            return FALSE;
        }
        $service = db('order_service')->field($field)->where($where)->find();
        if (!$service) {
            $this->error = '售后申请不存在';
            return FALSE;
        }
        return $service;
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
            $where['store_id'] = $storeId;
        }else{
            $where['user_id'] = $userId;
        }
        $sub = db('order_sub')->where($where)->find();
        if (!$sub) {
            $this->error = lang('NO ACCESS');
            return FALSE;
        }
        return $sub;
    }
}