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
use app\common\service\Cate;
use app\service\service\Zhongtai;

class Category1 extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function categorylist()
    {
        $params = $this -> postParams;
        $page = !empty($params['this_page']) ? intval($params['this_page']) : 1;
        $size = !empty($params['page_rows']) ? intval($params['page_rows']) : 10;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $params = [
            'source' => 'admin',
            'openid' => $openId,
            'page' => $page,
            'page_rows' => $size,
        ];
        $data = $obj -> cate_list($params,$token);
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

    public function add()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $cateName = $params['cate_name'] ?? '';
        $sortOrder = $params['sort_order'] ?? 255;
        $status = $params['status'] ?? 1;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'cate_name'=>$cateName,
            'sort_order'=>$sortOrder,
            'status'=>$status,
        ];
        $data = $obj -> create_cate($params,$token);
        return $data;
    }


    public function categoryinfo($pkId = 0){
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $cateId = $params['cate_id'] ?? 0;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'cate_id'=>$cateId,
        ];
        $data = $obj -> get_cate($params,$token);
        return $data;
    }


    public function edit()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $cateId = $params['cate_id'] ?? '';
        $cateName = $params['cate_name'] ?? '';
        $sortOrder = $params['sort_order'] ?? 255;
        $status = $params['status'] ?? 1;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'cate_id'=>$cateId,
            'cate_name'=>$cateName,
            'sort_order'=>$sortOrder,
            'status'=>$status,
        ];
        $data = $obj -> update_cate($params,$token);
        return $data;

    }


    public function del()
    {
        $params = $this -> postParams;

        $obj = new Zhongtai();
        $openId = $this->userInfo['openid'];
        $token = $params['token'];

        $cateId = $params['cate_id'] ?? 0;
        $params = [
            'source'=>'admin',
            'openid'=>$openId,
            'cate_id'=>$cateId,
        ];
        $data = $obj -> del_cate($params,$token);
        return $data;
    }


}