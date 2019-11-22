<?php

namespace app\api\controller\v1\order;

use app\api\controller\Api;
use think\Request;
use app\service\service\Zhongtai;

//shop
class Order1 extends Api
{
    protected $noAuth = ['orderlist','orderinfo','createOrder','cancel','finish'];
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /*
     * 订单列表
     */
    public function orderlist()
    {
        $params = $this -> postParams;
        $page = !empty($params['this_page']) ? intval($params['this_page']) : 1;
        $size = !empty($params['page_rows']) ? intval($params['page_rows']) : 10;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];


        $status = $params['status'] ?? '';
        $start_time = $params['start_time'] ?? '';
        $end_time = $params['end_time'] ?? '';
        $goods_name = $params['goods_name'] ?? '';


        $params = [
            'source' => 'user',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'page' => $page,
            'page_rows' => $size,
        ];

        if($status){
            $params['status'] = $status;
        }
        if($start_time){
            $params['start_time'] = $start_time;
        }
        if($end_time){
            $params['end_time'] = $end_time;
        }
        if($goods_name){
            $params['goods_name'] = $goods_name;
        }
        $data = $obj -> order_list($params,$token);


        return $data;
    }

    /*
     * 订单详情
     */
    public function orderinfo()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];

        $orderSn = $params['order_sn'] ?? '';

        $params = [
            'source' => 'user',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'order_sn' => $orderSn,
            'getskus' => 1,  //是否返回订单商品列表(1是 0否)
            'getlogs' => 1,  //是否返回订单日志列表(1是 0否)
        ];

        $data = $obj -> get_order($params,$token);
        return $data;
    }

    /*
     * 创建订单
     */
    public function createOrder()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];

        $skuList = $params['sku_list'] ?? [];
        $totalAmount = $params['total_amount'] ?? 0;
        $addrId = $params['addr_id'] ?? 0;

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'sku_list' => $skuList,     //下单商品列表数组(商品SKU编号)[必填]
            'total_amount' => $totalAmount,     //总订单金额[必填]
            'addr_id' => $addrId,       //收货地址ID[必填]
        ];

        $data = $obj -> order_create($params,$token);
        return $data;
    }

    /*
     * 取消订单
     */
    public function cancel()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];

        $orderSn = $params['order_sn'] ?? '';
        $remark = $params['remark'] ?? '';

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'order_sn' => $orderSn,
            'remark' => $remark,
        ];

        $data = $obj -> order_cancel($params,$token);
        return $data;
    }

    /*
     * 订单完成
     */
    public function finish()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];

        $orderSn = $params['order_sn'] ?? '';
        $remark = $params['remark'] ?? '';

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'order_sn' => $orderSn,
            'remark' => $remark,
        ];

        $data = $obj -> order_finish($params,$token);

        return $data;
    }



}