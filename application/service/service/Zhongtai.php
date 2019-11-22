<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/11/15 0015
 * Time: 16:22
 */
namespace app\service\service;
class zhongtai
{

    private $appid = 'echopjtgkutoevivr0';
    private $secret = 'fCBt1NzQNS2QX1wzBPOqo9wIV5e0JaeI';
    private $uri = 'http://api.weiput.com';
    /*
     * 获取token
     */
    public function get_token()
    {
        $appid = $this->appid;
        $secret = $this->secret;
        $url = $this->uri . '/v1/token/get';
        /*if(cache('gettoken')){
            return cache('gettoken');
        }*/
        $result = curl_request($url, ['appid' => $appid, 'secret' => $secret]);
        $result1 = json_decode($result,true);
        if(isset($result1['code']) && $result1['code'] == 0){
            /*cache('gettoken',$result1['data']['token'],7000);*/
            return $result1['data']['token'];
        }
        return $result;

    }

    /*
     * 注册为管理员用户
     */
    public function register($username,$phone,$password,$repassword,$token)
    {
        $url = $this->uri . '/v1/user/register/register';
        $data = [
            'source' => 'admin',
            'username' => $username,
            'phone' => $phone,
            'password' => $password,
            'repassword' => $repassword,
        ];
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $data, $headers);
        $result1 = json_decode($result,true);
        if(isset($result1['code']) && $result1['code'] == 0){
            return $result1['data']['openid'];
        }
        return $result;
    }

    /*
     * 获取商品分类列表
     */
    public function cate_list($params,$token)
    {
        $url = $this->uri . '/v1/item/cate/list';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 添加商品分类
     */
    public function create_cate($params,$token)
    {
        $url = $this->uri . '/v1/item/cate/create';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 商品分类详情
     */
    public function get_cate($params,$token)
    {
        $url = $this->uri . '/v1/item/cate/get';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 修改商品分类
     */
    public function update_cate($params,$token)
    {
        $url = $this->uri . '/v1/item/cate/update';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 删除商品分类
     */
    public function del_cate($params,$token)
    {
        $url = $this->uri . '/v1/item/cate/del';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 开通店铺
     */
    public function create_shop($params,$token)
    {
        $url = $this->uri . '/v1/shop/index/create';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        $result1 = json_decode($result,true);
        if(isset($result1['code']) && $result1['code'] == 0){
            return $result1['data']['shop_code'];
        }
        return $result;
    }

    /*
     * 新增商品
     */
    public function create_goods($params,$token)
    {
        $url = $this->uri . '/v1/item/index/create';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 上架商品
     */
    public function onsale_goods($params,$token)
    {
        $url = $this->uri . '/v1/item/index/onsale';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 选品中心
     */
    public function goodsList($params,$token)
    {
        $url = $this->uri . '/v1/item/index/goodslist';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 商品列表
     */
    public function goods_list($params,$token)
    {
        $url = $this->uri . '/v1/item/index/list';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 商品详情
     */
    public function get_goods($params,$token)
    {
        $url = $this->uri . '/v1/item/index/get';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 商品编辑
     */
    public function goods_edit($params,$token)
    {
        $url = $this->uri . '/v1/item/index/update';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 解绑商品分类
     */
    public function unbind_cate($params,$token)
    {
        $url = $this->uri . '/v1/item/index/unbindCate';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 商品删除
     */
    public function goods_del($params,$token)
    {
        $url = $this->uri . '/v1/item/index/del';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 下架商品
     */
    public function offsale_goods($params,$token)
    {
        $url = $this->uri . '/v1/item/index/offsale';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }


    /*
     * 下架商品
     */
    public function change_stock($params,$token)
    {
        $url = $this->uri . '/v1/item/index/changestock';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }


    /*
     * 下架商品
     */
    public function change_stock($params,$token)
    {
        $url = $this->uri . '/v1/item/index/changestock';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }
















    /*
     * 订单列表
     */
    public function order_list($params,$token)
    {
        $url = $this->uri . '/v1/transaction/order/list';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 订单支付
     */
    public function order_pay($params,$token)
    {
        $url = $this->uri . '/v1/transaction/order/pay';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 订单详情
     */
    public function get_order($params,$token)
    {
        $url = $this->uri . '/v1/transaction/order/get';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 创建订单
     */
    public function order_create($params,$token)
    {
        $url = $this->uri . '/v1/transaction/order/create';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 取消订单
     */
    public function order_cancel($params,$token)
    {
        $url = $this->uri . '/v1/transaction/order/cancel';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

    /*
     * 订单完成
     */
    public function order_finish($params,$token)
    {
        $url = $this->uri . '/v1/transaction/order/finish';
        $headers = ['Authorization:'.$token];
        $result = curl_request($url, $params, $headers);
        return $result;
    }

}
