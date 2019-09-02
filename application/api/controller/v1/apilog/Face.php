<?php
namespace app\api\controller\v1\apilog;
use app\api\controller\Api;
use think\Request;

class Face extends Api
{

    public function __construct(Request $request){
        parent::__construct($request);
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $userId = cache($authorization)['admin_user']['user_id'];

        if ($userId !== 1){
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
        }
    }
    public function list(){
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $count = db('apilog_device')-> where($this->_getWhere())-> count();
        $field = 'log_id, type, request_time, device_code, capture_time, capture_img, img_x, img_y, img_pixel, response_time, error';
        $data = db('apilog_device')-> where($this->_getWhere()) -> field($field)->order('request_time DESC') -> limit(($page-1)*$size,$size) -> select();
        if ($data) {
            foreach ($data as $key => $value) {
                $data[$key]['response_time'] = ($value['response_time'] > 0 ? $value['response_time']/1000: 0).'秒';
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }
    public function detail()
    {
        $logId = isset($this->postParams['log_id']) ? intval($this->postParams['log_id']) : 0;
        if(!$logId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('apilog_device')->field('log_id, request_params, return_params')->where('log_id', $logId)->find();
        if(!$info){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info['request_params'] = $info['request_params'] ? json_decode($info['request_params'], true) : [];
        $info['return_params'] = $info['return_params'] ? json_decode($info['return_params'], true) : [];
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => $info]);die;
    }
    function _getWhere(){
        $where = [];
        $params = $this -> postParams;

        $type = isset($params['type']) ? trim($params['type']) : '';
        $macId = isset($params['mac_id']) ? trim($params['mac_id']) : '';
        if (isset($params['status'])) {
            $status = intval($params['status']);
            if ($status >= 0) {
                $where[] = ['error','=',$status];
            }
        }
        if ($type) {
            $where[] = ['type','=',$type];
        }
        if ($macId) {
            $where[] = ['device_code','like', '%'.$macId.'%'];
        }

        return $where;
    }
}