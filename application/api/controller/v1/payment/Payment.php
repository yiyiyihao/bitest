<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/2/13 0013
 * Time: 18:31
 */
namespace app\api\controller\v1\payment;

use app\api\controller\Api;
use think\Request;

//后台数据接口页
class Payment extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


    public function paymentlist()
    {
        $paymentService = new \app\common\api\PaymentApi();
        $payments = $paymentService->payments;
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $count = db('payment') -> where($this->_getWhere($params)) -> count();
        $list = db('payment') -> where($this->_getWhere($params)) ->limit(($page-1)*$size,$size) -> select();
        $data = [];
        if ($list) {
            foreach ($list as $key => $value) {
                $code = $value['pay_code'];
                $data[] = $value + $payments[$code];
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }

    public function info()
    {
        $params = $this -> postParams;
        $code = isset($params['code']) ? $params['code'] : '';

        $paymentService = new \app\common\api\PaymentApi();
        $payments = $paymentService->payments;

        if (!$code || !isset($payments[$code])){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误,不允许配置']);die;
            //$this->error('参数错误,不允许配置');
        }

        $payment = isset($payments[$code]) ? $payments[$code] : '';
        if (!$payment) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数异常']);die;
            //$this->error('参数异常');
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];

        $info = db('payment')->where(['store_id' => $storeId, 'is_del' => 0, 'pay_code' => $code])->find();

        $info['config'] = $info['config_json'] ? json_decode($info['config_json'], TRUE) : [];
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['payment' => $payment,'info' => $info]]);die;
    }


    public function config()
    {
        $params = $this -> postParams;
        $code = isset($params['code']) ? $params['code'] : '';

        $paymentService = new \app\common\api\PaymentApi();
        $payments = $paymentService->payments;

        if (!$code || !isset($payments[$code])){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误,不允许配置']);die;
            //$this->error('参数错误,不允许配置');
        }

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];

        $info = db('payment')->where(['store_id' => $storeId, 'is_del' => 0, 'pay_code' => $code])->find();

        if ($info) {
            $data = [
                'pay_id'        => $info['pay_id'],
                'name'          => isset($params['name']) ? trim($params['name']) : $info['name'],
                'config_json'   => isset($params['config']) && $params['config'] ? json_encode($params['config']) : $info['config_json'],
                'description'   => isset($params['description']) ? trim($params['description']) : $info['description'],
                'status'        => isset($params['status']) ? intval($params['status']) : $info['status'],
                'sort_order'    => isset($params['sort_order']) ? trim($params['sort_order']) : $info['sort_order'],
                'update_time'   => time(),
                ];
            $result = db('payment')->update($data);
        }else{
            $data = [
                'name'          => isset($params['name']) ? trim($params['name']) : '',
                'config_json'   => isset($params['config']) && $params['config'] ? json_encode($params['config']) : '',
                'description'   => isset($params['description']) ? trim($params['description']) : '',
                'status'        => isset($params['status']) ? intval($params['status']) : '',
                'sort_order'    => isset($params['sort_order']) ? trim($params['sort_order']) : '',
                'update_time'   => time(),
                'add_time'      => time(),
            ];
            $data['pay_code'] = $code;
            $data['store_id'] = $storeId;
            $result = db('payment')->insertGetId($data);
        }
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '支付方式配置错误']);die;
            //$this->error('支付方式配置错误');
        }else{
            //$this->success('支付方式配置成功', url('index'));
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['code' => $code]]);die;
        }

    }

    public function del()
    {
        $params = $this->postParams;
        $payId = isset($params['id']) ? $params['id'] : 0;
        if(!$payId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $info = db('payment') -> where('pay_id','=',$payId) -> update(['is_del'=> 1]);
//         $storeVisit = $this->_checkStoreVisit($info['store_id'], TRUE, FALSE);
        if(!$info){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 1, 'msg' => '成功','data' => ['payId' => $payId]]);die;

    }

    function _getWhere($params){
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $where = [['is_del' ,'=', 0], ['store_id' ,'=', $storeId]];
        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['name','like','%'.$name.'%'];
            }
        }
        return $where;
    }



}