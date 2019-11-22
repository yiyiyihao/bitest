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
use app\service\service\Zhongtai;

//admin
class Goods1 extends Api
{
    //protected $noAuth = ['goodslist','goodsadd','goodsInfo','goodsedit','goodsdel','goods_onsale','goods_offsale'];
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /*
     * 商品列表
     */
    public function goodslist()
    {
        $params = $this -> postParams;
        $page = !empty($params['this_page']) ? intval($params['this_page']) : 1;
        $size = !empty($params['page_rows']) ? intval($params['page_rows']) : 10;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $status = $params['status'] ?? 10;
        $name = $params['name'] ?? '';
        $cateid = $params['cateid'] ?? '';
        $brandid = $params['brandid'] ?? '';
        $getskus = $params['getskus'] ?? null;
        $getcates = $params['getcates'] ?? null;
        $getbrand = $params['getbrand'] ?? null;

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'status' => $status,
            'page' => $page,
            'page_rows' => $size,
            'getskus' => $getskus,
            'getcates' => $getcates,
            'getbrand' => $getbrand,
        ];
        if($name){
            $params['name'] = $name;
        }
        if($cateid){
            $params['cateid'] = $cateid;
        }
        if($brandid){
            $params['brandid'] = $brandid;
        }
        $data = $obj -> goods_list($params,$token);
        //以下都是适应之前bi的前端页面字段，类似的其他方法也有。
        /*$data = json_decode($data,1);
        if(isset($data['code']) && $data['code'] ==0){
            $data['data']['count'] = $data['data']['total'];
            $data['data']['page'] = $data['data']['this_page'];
            foreach($data['data']['list'] as $k => $v){
                $data['data']['list'][$k]['name'] = $v['cate_name'];
            }
        }
        $data = json_encode($data);*/


        return $data;
    }

    /*
     * 添加商品
     */
    public function goodsadd()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];
        $temp = [
            'source'=>'admin',
            'openid'=>$openId,
        ];

        unset($params['token']);
        $params = array_merge($params,$temp);

        $data = $obj -> create_goods($params,$token);

        return $data;
    }

    /*
     * 商品详情
     */
    public function goodsInfo(){
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $goodsCode = $params['goods_code'] ?? 0;
        $getskus = $params['getskus'] ?? null;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$goodsCode,
            'getskus'=>$getskus,
        ];
        $data = $obj -> get_goods($params,$token);
        return $data;
    }


    /*
     * 编辑商品
     */
    public function goodsedit()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        //先解绑商品之前的分类
        $temp = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$params['goods_code'],
            'getskus'=>1,
        ];
        $data = $obj -> get_goods($temp,$token);
        $data = json_decode($data,1);
        if(isset($data['code']) && $data['code'] == 0){
            $cates = $data['data']['cates'];
            foreach($cates as $k=>$v){
                $temp = [
                    'source'=>'admin',
                    'openid'=>$openId,
                    'goods_code'=>$params['goods_code'],
                    'cate_id'=>$v['cate_id'],
                ];
                $data = $obj -> unbind_cate($temp,$token);
            }


        }

        $temp = [
            'source'=>'admin',
            'openid'=>$openId,
        ];
        unset($params['token']);
        $params = array_merge($params,$temp);


        $data = $obj -> goods_edit($params,$token);
        return $data;

    }


    /*
     * 删除商品
     */
    public function goodsdel()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $goodsCode = $params['goods_code'] ?? 0;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$goodsCode,
        ];
        $data = $obj -> goods_del($params,$token);
        return $data;
    }

    /*
     * 上架商品
     */
    public function goods_onsale()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];
        $goodsCode = $params['goods_code'] ?? '';

        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$goodsCode,
        ];
        $data = $obj -> onsale_goods($params,$token);
        return $data;
    }


    /*
     * 下架商品
     */
    public function goods_offsale()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];
        $goodsCode = $params['goods_code'] ?? '';

        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$goodsCode,
        ];
        $data = $obj -> offsale_goods($params,$token);
        return $data;
    }


    /*
     * 修改商品库存
     */
    public function change_stock()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];
        $skuId = $params['sku_id'] ?? null;
        $stock = $params['stock'] ?? null;

        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'sku_id'=>$skuId,
            'stock'=>$stock,
        ];
        $data = $obj -> change_stock($params,$token);
        return $data;
    }



}