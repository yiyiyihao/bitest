<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/3/27 0027
 * Time: 15:14
 */

namespace app\api\controller\v1\help;

use app\api\controller\Api;
use think\Request;

class Help extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function cateList()
    {
        $where = [
            ['status','=',1],
            ['is_del','=',0],
        ];
        $list = db('help_cate') -> where($where)->select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' =>  $list]);die;
    }

    public function titleList()
    {
        $params = $this->postParams;
        $helpc_id = isset($params['helpc_id']) && !empty($params['helpc_id']) ? $params['helpc_id'] :0;
        if(!$helpc_id)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $where = [
          ['status','=',1],
          ['is_del','=',0],
          ['cate_id','=',$helpc_id],
        ];
        $list = db('help')->where($where)->select();
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' =>  $list]);die;
    }


}