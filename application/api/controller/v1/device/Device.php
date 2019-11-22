<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/29 0029
 * Time: 17:56
 */

namespace app\api\controller\v1\device;

use app\api\controller\Api;
use think\Exception;
use think\Request;

class Device extends Api
{
    protected $noAuth = ['getEntity'];
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function deviceList()
    {

        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $join = [
            ['store S', 'D.store_id = S.store_id', 'INNER'],
            ['store_block SB', 'D.block_id = SB.block_id', 'LEFT'],
        ];

        $count = db('device') -> alias('D') -> where($this->_getWhere($params))-> join($join) -> count();
        $data = db('device') -> alias('D') -> where($this->_getWhere($params)) -> field('D.*, S.name as sname, SB.name as bname')->order('sort_order ASC, add_time DESC') -> join($join) -> limit(($page-1)*$size,$size) -> select();
        if(!empty($data)) {
            foreach ($data as $k => $v) {
                //判断status在线离线，有无推流功能
                if($v['status'] == 1 && in_array($v['device_type'],['A2','B2'])){
                    $data[$k]['mac_status'] = 1;
                }else{
                    $data[$k]['mac_status'] = 0;
                }
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;

    }

    public function deviceAdd()
    {
        $data = $this -> _getData();
        $pkId = db('device')->insertGetId($data);
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['device_id' => $pkId]]);die;


    }

    public function deviceDel()
    {
        $params = $this->postParams;
        $macId = isset($params['code']) ? $params['code'] : 0;
        if(!$macId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $deviceInfo = db('device') -> where('device_code','=',$macId) -> where('is_del','=',0) -> find();
        if(!$deviceInfo){
            $this->_returnMsg(['code' => 1, 'msg' => '非法的设备串号']);die;
        }
        $userInfo = db('store_member')->alias('SM')->join('user U','U.user_id=SM.user_id','left') -> where('SM.store_id','=',$deviceInfo['store_id']) -> where('SM.is_del','=',0)->where('U.group_id','=',2)->find();
        $deviceApi = new \app\common\api\DeviceApi();
        $result = $deviceApi->del($macId,$userInfo['user_id']);
        $info = db('device') -> where('device_code','=',$macId) -> update(['is_del'=> 1]);
//         $storeVisit = $this->_checkStoreVisit($info['store_id'], TRUE, FALSE);
        if(!$info){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 1, 'msg' => '成功','data' => ['code' => $macId]]);die;

    }

    public function deviceEdit()
    {
        $params = $this->postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $data = $this->_getData();
        $rs = db('device')->update($data);
        if(!$rs){
            $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['device_id' => $pkId]]);die;

    }

    public function authorizeInfo()
    {
        $params = $this->postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '设备id缺失']);die;
        }
        $info = db('device') -> where('device_id','=',$pkId) -> find();
        if(!$info){
            $this->_returnMsg(['code' => 1, 'msg' => '非法的设备id']);die;
        }
        if ($info['store_id']) {
            $storeVisit = $this->_checkStoreVisit($info['store_id']);
        }
        $where = [['status' ,'=', 1], ['is_del' ,'=', 0], ['store_type' ,'in', [1,3]]];
        if (is_array($storeVisit)){
            $where[] = ['store_id','IN', $storeVisit];
        }elseif (is_int($storeVisit)){
            $where[] = ['store_id','=',$storeVisit];
        }
        //获取门店列表
        $stores = db('store')->where($where)->select();
        //huangyihao 上面调用无限极分类会丢掉父级为虚拟门店的子门店。只有两级直接找。
        if($stores){
            //组装虚拟门店的子店的显示名称
            foreach($stores as $kk => $vv){
                if($vv['parent_id'] != 0){
                    //不多直接循环查询
                    $parentName = db('store')->where('store_id','=',$vv['parent_id'])->field('name')->find();
                    $stores[$kk]['name'] = $parentName['name'].'--'.$vv['name'];
                }
            }
        }

        //获取区域列表
        $blocks = [];
        if ($info && $info['store_id']) {
            $blocks = db('store_block')->where(['status' => 1, 'is_del' => 0, 'store_id' => $info['store_id']])->select();
        }
        $positionTypes = [
            1 => '进店',
            2 => '离店',
            3 => '其它',
        ];
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['stores' => $stores,'blocks'=>$blocks,'positionTypes'=>$positionTypes]]);die;
    }


    /**
     * 设备授权门店
     */
    public function authorize()
    {
        $params = $this->postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : 0;
        $info = db('device') -> where('device_id','=',$pkId) -> find();
        if ($info['store_id']) {
            $storeVisit = $this->_checkStoreVisit($info['store_id']);
        }
        if (IS_POST) {
            $data = $params;
            $storeId = isset($data['store_id']) ? intval($data['store_id']) : 0;
            $blockId = isset($data['block_id']) ? intval($data['block_id']) : 0;
            $positionType = isset($data['position_type']) ? intval($data['position_type']): 3;
            if(!$storeId){
                //$this->error('请选择授权门店');
                $this->_returnMsg(['code' => 1, 'msg' => '请选择授权门店']);die;
            }
            if(!$blockId){
                //$this->error('请选择授权区域');
                $this->_returnMsg(['code' => 1, 'msg' => '请选择授权区域']);die;
            }
            $exist = db('store_block')->where(['status' => 1, 'is_del' => 0, 'store_id' => $storeId, 'block_id' => $blockId])->find();
            if(!$exist){
                //$this->error('授权区域不存在或已删除');
                $this->_returnMsg(['code' => 1, 'msg' => '授权区域不存在或已删除']);die;
            }
            if (!$this->positionTypes()[$positionType]) {
                //$this->error('设备属性错误');
                $this->_returnMsg(['code' => 1, 'msg' => '设备属性错误']);die;
            }
            $result = db('device')->where(['device_id' => $info['device_id']])->update(['store_id' => $storeId, 'block_id' => $blockId, 'position_type' => $positionType, 'update_time' => time()]);
            if(!$result){
                //$this->success('设备授权成功', url('index'), TRUE);
                $this->_returnMsg(['code' => 1, 'msg' => '设备授权失败']);die;
            }
            $this->_returnMsg(['code' => 0, 'msg' => '设备添加授权成功']);die;
        }
    }

    public function fulls()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $pkId = isset($params['id']) ? intval($params['id']) : 0;
        $info = db('device') -> where('device_id','=',$pkId) -> find();
        if (!$info || $info['is_del']) {
            //$this->error('设备不存在或已删除');
            $this->_returnMsg(['code' => 1, 'msg' => '设备不存在或已删除']);die;
        }

        $count = db('device_img') -> where('device_id','=',$pkId) -> count();
        $data = db('device_img') -> where('device_id','=',$pkId) -> limit(($page-1)*$size,$size) -> select();

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }
    public function faces()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $pkId = isset($params['id']) ? intval($params['id']) : 0;
        $info = db('device') -> where('device_id','=',$pkId) -> find();
        if (!$info || $info['is_del']) {
            //$this->error('设备不存在或已删除');
            $this->_returnMsg(['code' => 1, 'msg' => '设备不存在或已删除']);die;
        }

        $count = db('device_face') -> where('device_id','=',$pkId) -> count();
        $data = db('device_face') -> where('device_id','=',$pkId) -> limit(($page-1)*$size,$size) -> select();

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }

    /**
     * created by huangyihao
     * @description []开启云录像（开启推流)
     */
    public function open()
    {
        $params = $this->postParams;
        $mac_id = isset($params['id']) ? $params['id'] : '';
        if(!$mac_id){
            $this->_returnMsg(['code' => 1, 'msg' => '设备串号不能为空']);die;
        }
        $deviceApi = new \app\common\api\DeviceApi();
        //开启实时录像
        $result = $deviceApi -> openvideo($params['id']);
        $res = json_decode($result,true);
        if (empty($res) || (isset($res['code']) && $res['code'] != 0 )) {
            $msg = empty($res) ? '第三方开启录像接口原因2':$res['message'];
            $code = empty($res) ? '1':$res['code'];
            //$this -> error('错误编码：'.$code.',错误信息：'.$msg,'index');
            $this->_returnMsg(['code' => 1, 'msg' => '错误编码：'.$code.',错误信息：'.$msg]);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '开启成功','data' => ['mac_id'=>$params['id']]]);die;
    }

    function play()
    {
        $params = $this->postParams;

        $deviceApi = new \app\common\api\DeviceApi();
        //开启实时录像
        $result = $deviceApi -> openvideo($params['id']);
        $res = json_decode($result,true);
        if (empty($res) || (isset($res['code']) && $res['code'] != 0 )) {
            $msg = empty($res) ? '第三方开启录像接口原因2':$res['message'];
            $code = empty($res) ? '1':$res['code'];
            //$this -> error('错误编码：'.$code.',错误信息：'.$msg,'index');
            $this->_returnMsg(['code' => 1, 'msg' => '错误编码：'.$code.',错误信息：'.$msg]);die;
        }

        //获取拉流地址
        $res = $deviceApi -> getDevicePlaceUrl($params['id']);

        $res = json_decode($res,true);
        if (empty($res) || (isset($res['code']) && $res['code'] != 0 )) {
            $msg = empty($res) ? '第三方拉流接口原因':$res['message'];
            $code = empty($res) ? '1':$res['code'];
            //$this -> error('错误编码：'.$code.',错误信息：'.$msg,'index');
            $this->_returnMsg(['code' => 1, 'msg' => '错误编码：'.$code.',错误信息：'.$msg]);die;
        }

        $hls = $res['data']['play_url'];
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => ['mac_id'=>$params['id'],'hls'=>$hls]]);die;

    }

    function _getWhere($params)
    {

        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $group_id = cache($authorization)['admin_user']['group_id'];
        $device_ids = explode(',', cache($authorization)['admin_user']['email']);
        $where = [];
        if ($this->request->action() == 'devicelist' || $this->request->action() == 'getentity') {
            $where[] = ['D.is_del' ,'=', 0];
            $storeVisit = $this->_checkStoreVisit($storeId);
            if (is_int($storeVisit)) {
                $where[] = ['D.store_id','=', $storeVisit];
            }elseif (is_array($storeVisit)){
                $where[] = ['D.store_id', 'IN', $storeVisit];
            }elseif ($storeVisit ===true && $group_id == 5){
                $where[] = ['D.device_id','IN',$device_ids];
            }elseif($storeVisit === false){
                //$this->error(lang('NO ACCESS'));
                $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);
            }
            $params = $this->postParams;
            if ($params) {

                $name = isset($params['name']) ? trim($params['name']) : '';
                if($name){
                    $where[] = ['D.name|D.device_code|S.name','like','%'.$name.'%'];
                }

//                $name = isset($params['name']) ? trim($params['name']) : '';
//                if($name){
//                    $where[] = ['D.name','like','%'.$name.'%'];
//                }
//                $code = isset($params['code']) ? trim($params['code']) : '';
//                if($code){
//                    $where[] = ['D.device_code','like','%'.$code.'%'];
//                }
//                $store = isset($params['store']) ? trim($params['store']) : '';
//                if($store){
//                    $where[] = ['S.name','like','%'.$store.'%'];
//                }
            }
        }
        //huangyihao
        elseif ($this->request->action() == 'fulls') {
            $params = Request::instance()->param();
            $where[] = ['device_id' ,'=', $params['id']];
        }
        return $where;
    }


    function _getData()
    {
        $params = $this->postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;
        $storeId = $params && isset($params['store_id']) ? intval($params['store_id']) : null;

        if(!$storeId){
            $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
            $storeId = cache($authorization)['admin_user']['store_id'];
        }

        $deviceCode = isset($params['device_code']) ? trim($params['device_code']): '';
        $name = isset($params['name']) ? trim($params['name']) : '';
        if (!$pkId && !$name) {
            //$this->error('设备名称不能为空');
            $this->_returnMsg(['code' => 1, 'msg' => '设备名称不能为空']);die;
        }
        if(!$pkId && !$deviceCode){
            //$this->error('设备串码不能为空');
            $this->_returnMsg(['code' => 1, 'msg' => '设备串码不能为空']);die;
        }
        //判断设备名称是否重复
        $where = [['name','=', $name], ['is_del','=', 0], ['store_id','=', $storeId]];
        if($pkId){
            $where[] = ['device_id','neq', $pkId];
        }
        $exist = db('device')->where($where)->find();
        if($exist){
            //$this->error('当前设备名称已存在');
            $this->_returnMsg(['code' => 1, 'msg' => '当前设备名称已存在']);die;
        }
        //判断设备串码是否已被添加
        $where_1 = [['device_code' ,'=', $deviceCode], ['is_del' ,'=', 0]];
        if($pkId){
            $where_1[] = ['device_id','<>', $pkId];
        }
        $exist = db('device')->where($where_1)->find();
        if($exist){
            $this->_returnMsg(['code' => 1, 'msg' => '当前设备串码已被其它门店添加']);die;
        }

        if (!$pkId) {
            #TODO 接口判断设备串码对应硬件设备是否存在
            $userInfo = db('store_member')->alias('SM')->join('user U','U.user_id=SM.user_id','left') -> where('SM.store_id','=',$storeId) -> where('SM.is_del','=',0)->where('U.group_id','=',2)->find();
            $deviceApi = new \app\common\api\DeviceApi();
            $result = $deviceApi->getDeviceInfo($deviceCode,$name,$userInfo['user_id']);
            if($result){
                if (isset($result['code']) && $result['code'] != 0 ) {
                    //$this->error($result['message']);
                    $this->_returnMsg(['code' => 1, 'msg' => $result['message']]);die;
                }
                $resul = $deviceApi -> openvideo($deviceCode);
                //值得看添加设备成功，调用直播流接口，获取直播地址。
                $res = $deviceApi -> getDevicePlaceUrl($deviceCode);
                $res = json_decode($res,true);
                //判断获取直播地址，//这里允许获取不到，后期再获取。
//                 if ((isset($res['code']) && $res['code'] != 0 )) {
//                     $this->error($res['code'].$res['message']);
//                 }

                $hls_url = $res && isset($res['data']['play_url']) ? substr($res['data']['play_url'],0,strpos($res['data']['play_url'], '?')) : '';
//                $data['device_type'] = $result && isset($result['data']['mac_type']) ? $result['data']['mac_type'] : '';
//                $data['position'] = $result && isset($result['data']['position'] ) ? $result['data']['position'] : '';
            }else{
                //$this->error('设备查询异常');
                $this->_returnMsg(['code' => 1, 'msg' => '设备查询异常']);die;
            }
            $data['device_type'] = $result && isset($result['data']['mac_type']) ? $result['data']['mac_type'] : 'A2';
            $data['position'] = $result && isset($result['data']['position'] ) ? $result['data']['position'] : '';
            $data['device_code'] = $deviceCode;
        }


        //组装数据返回

        if (!$pkId) {
            $data['device_id'] = $pkId;
            $data['name'] = $name;
            $data['store_id'] = $storeId;
            $data['hls_url'] = $hls_url;
            $data['rtmp_url'] = $params && isset($params['rtmp']) ? $params['rtmp'] : '';
            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['status'] = $params && isset($params['status']) ? intval($params['status']) : 1;
            $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;
            $data['store_id'] = $params && isset($params['store_id']) && !empty($params['store_id']) ? intval($params['store_id']) : 1;
            $data['block_id'] = $params && isset($params['block_id']) && !empty($params['block_id'])? intval($params['block_id']) : 0;
            $data['position_type'] = $params && isset($params['position_type']) && !empty($params['position_type'])? intval($params['position_type']) : 3;
        }else{
            $info = db('device') -> where([['device_id','=',$pkId],['is_del','=',0]])-> find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '设备id错误']);die;}
            $storeVisit = $this->_checkStoreVisit($info['store_id']);
            $data = [
                'device_id' => $pkId,
                'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                'update_time'=> time(),
                'hls_url' => isset($params['hls_url']) ? $params['hls_url'] : $info['hls_url'],
                'rtmp_url' => isset($params['rtmp_url']) ? $params['rtmp_url'] : $info['rtmp_url'],
                'status'    => isset($params['status']) ? intval($params['status']) : $info['status'],
                'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
                'store_id' => isset($params['store_id']) && !empty($params['store_id'])? intval($params['store_id']) : $info['store_id'],
                'block_id' => isset($params['block_id']) && !empty($params['block_id'])? intval($params['block_id']) : $info['block_id'],
                'position_type' => isset($params['position_type']) && !empty($params['position_type'])? intval($params['position_type']) : $info['position_type'],

            ];
            if($params['store_id'] <> $info['store_id']){
                #TODO 判断设备是否在线，不在线不给改所属门店
                if($info['status'] == 0){
                    $this->_returnMsg(['code' => 1, 'msg' => '设备不在线，不给切换门店']);die;
                }
                $userInfo = db('store_member')->alias('SM')->join('user U','U.user_id=SM.user_id','left') -> where('SM.store_id','=',$params['store_id']) -> where('SM.is_del','=',0)->where('U.group_id','=',2)->find();
                $deviceApi = new \app\common\api\DeviceApi();

                $userIn = db('store_member')->alias('SM')->join('user U','U.user_id=SM.user_id','left') -> where('SM.store_id','=',$info['store_id']) -> where('SM.is_del','=',0)->where('U.group_id','=',2)->find();
                $result = $deviceApi->del($deviceCode,$userIn['user_id']);
                $res = $deviceApi->getDeviceInfo($deviceCode,$name,$userInfo['user_id']);
                $resul = $deviceApi -> openvideo($deviceCode);
            }
        }

        return $data;
    }

    function positionTypes()
    {
        return  [
            1 => lang('进店'),
            2 => lang('离店'),
            3 => lang('其它'),
        ];
    }

    public function getTypeList()
    {
        $list = $this->positionTypes();
        $this->_returnMsg(['code' => 0, 'msg' => '设备添加授权成功','data'=>['list'=>$list]]);die;
    }

    public function getEntity()
    {
        $params = $this -> postParams;
        $join = [
            ['store S', 'D.store_id = S.store_id', 'INNER'],
            ['store_block SB', 'D.block_id = SB.block_id', 'LEFT'],
        ];
        $where = [['D.status','=',1],['D.device_type','=','A2']];
        $data = db('device') -> alias('D') -> where($this->_getWhere($params))->where($where)-> field('D.*, S.name as sname, SB.name as bname') -> join($join) -> select();
        $temp = $tem = [];
        if(!empty($data)) {
            foreach ($data as $k => $v) {
                $v['label'] = $v['name'];
                $temp[$v['sname']][$v['bname']][] = $v;
            }
            $i = $j = 0;
            foreach ($temp as $key => $value){
               $tem[$i]['label'] = $key;
               foreach($value as $kk => $vv ){
                   $tem[$i]['children'][$j]['label'] = $kk;
                   $tem[$i]['children'][$j]['children'] = $vv;
                   $j++;
               }
                $i++;

            }
        }
//        pre($tem);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['list' => $tem]]);die;

    }
}