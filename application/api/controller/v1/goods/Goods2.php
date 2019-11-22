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

//shop
class Goods2 extends Api
{
    protected $noAuth = ['categorylist','factory_goodslist','goodslist','goodsadd','goodsInfo','goodsedit','goodsdel','goods_onsale','goods_offsale'];
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /*
     * 分类列表
     */
    public function categorylist()
    {
        $params = $this -> postParams;
        $page = !empty($params['this_page']) ? intval($params['this_page']) : 1;
        $size = !empty($params['page_rows']) ? intval($params['page_rows']) : 10;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'page' => $page,
            'page_rows' => $size,
        ];
        $data = $obj -> cate_list($params,$token);

        return $data;
    }


    /*
     * 选品中心
     */
    public function factory_goodslist()
    {
        $params = $this -> postParams;
        $page = !empty($params['this_page']) ? intval($params['this_page']) : 1;
        $size = !empty($params['page_rows']) ? intval($params['page_rows']) : 10;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];
        $name = $params['name'] ?? '';
        $cateid = $params['cateid'] ?? '';
        $brandid = $params['brandid'] ?? '';

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'page' => $page,
            'page_rows' => $size,
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
        $data = $obj -> goodsList($params,$token);

        return $data;
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
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];
        $name = $params['name'] ?? '';
        $cateid = $params['cateid'] ?? '';
        $brandid = $params['brandid'] ?? '';

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'shop_code' => $shopCode,
            'page' => $page,
            'page_rows' => $size,
            'getskus' => 1,
            'getcates' => 1,
            'getbrand' => 1,
            'sortdata' => ['time'=>'asc','shop_sale'=>'desc','shop_price'=>'asc'],
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
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];
        $goodName = $params['name'] ?? '';
        $goodStock = $params['goods_stock'] ?? 0;
        $goodsSn = $params['goods_sn'] ?? '';
        $price = $params['min_price'] ?? 0;
        $specs = $params['specs_json'] ?? '';


        $imgs = '';
        $goodsThumb = $params['imgs'][0] ?? '';
        if($goodsThumb){
            foreach($params['imgs'] as $k=>$v){
                $imgs .= $v;
                $imgs .= ',';
            }
        }

        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_name'=>$goodName,
            'goods_sn'=>$goodsSn,
            'goods_stock'=>$goodStock,
            'imgs'=> trim($imgs,','),
            'goods_thumb'=> $goodsThumb,
            'price'=>$price,
            'status'=>0,
            'skus_json'=>$specs,
        ];
        $data = $obj -> create_goods($params,$token);
        $goodsCode = json_decode($data,1);
        if(isset($goodsCode['code']) && $goodsCode['code']==0){
            $params = [
                'source'=>'shop',
                'openid'=>$openId,
                'shop_code'=>$shopCode,
                'goods_code'=>$goodsCode['data']['goods_code'],
            ];
            $data = $obj -> onsale_goods($params,$token);
            return $data;
        }

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
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$goodsCode,
            'getskus'=>1,
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

        $goodsCode = $params['goods_code'] ?? 0;
        $goodsName = $params['name'] ?? 0;
        $goodsSn = $params['goods_sn'] ?? 0;
        $goodsStock = $params['goods_stock'] ?? 0;
        $content = $params['content'] ?? 0;
        $spec = $params['specs_json'] ?? '';
        $price = $params['min_price'] ?? 0;
        $goodsThumb = $params['goods_thumb'] ?? 0;
        $marketPrice = $params['market_price'] ?? 0;
        $supplyPrice = $params['supply_price'] ?? 0;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'goods_code'=>$goodsCode,
            'goods_name'=>$goodsName,
            'goods_stock'=>$goodsStock,
            'price'=>$price,
            'goods_sn'=>$goodsSn,
            'goods_thumb'=>$goodsThumb,
            'market_price'=>$marketPrice,
            'supply_price'=>$supplyPrice,
            'content'=>$content,
            'skus_json' => $spec,
            'no_update_spec'=>1,
            'extra'=>'',
        ];
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
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];
        $goodsCode = $params['goods_code'] ?? '';

        $params = [
            'source'=>'shop',
            'openid'=>$openId,
            'shop_code'=>$shopCode,
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
        $shopCode = $this->userInfo['shop_code'];
        $token = $params['token'];
        $goodsCode = $params['goods_code'] ?? '';

        $params = [
            'source'=>'shop',
            'openid'=>$openId,
            'shop_code'=>$shopCode,
            'goods_code'=>$goodsCode,
        ];
        $data = $obj -> offsale_goods($params,$token);
        return $data;
    }




}