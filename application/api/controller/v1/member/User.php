<?php
namespace app\api\controller\v1\member;
use app\api\controller\Api;
use think\Request;

class User extends Api
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    /**
     * 全部用户列表
     */
    public function list()
    {
        $params = $this -> postParams;
        $page   = !empty($params['page']) ? intval($params['page']) : 1;
        $size   = !empty($params['size']) ? intval($params['size']) : 10;
        $labelId   = !empty($params['label_id']) ? intval($params['label_id']) : 0;

        $join1 = [];
        $where_label = [];
        if($labelId){
            $res = db('label')->where([
                ['label_id','=',$labelId],
                ['is_del','=',0]
            ])->find();
            if(!$res){
                $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>0,'page'=>$page,'list' => []]]);die;
            }
            if($res['type'] > 2){
                //系统标签
                if($res['type'] == 3){//新客户标签
                    if($res['mark_type'] == 1){//访问天数统计
                        $where_label = [
                            ['DV.total_visit_days','<',$res['times']],
                        ];
                    }else{//访问次数统计
                        $where_label = [
                            ['DV.total_visit_counts','>=',$res['times']],
                        ];
                    }
                }elseif ($res['type'] == 4 || $res['type'] == 5){//活跃客户标签//忠诚客户标签
                    if($res['mark_type'] == 1){//访问天数统计
                        $where_label = [
                            ['DV.total_visit_days','>=',$res['times']],
                        ];
                    }else{//访问次数统计
                        $where_label = [
                            ['DV.total_visit_counts','>=',$res['times']],
                        ];
                    }
                }else{//流失客户标签
                    $where_label = [
                        ['DV.recent_time','<',time()-$res['times'] * 86400], //周天代替天
                    ];
                }



            }else{
                //自定义标签
                $join1 = [['member_label ML','ML.fuser_id=DV.fuser_id','LEFT']];
                $where_label = [
                    ['ML.label_id','=',$labelId],
                ];
            }

        }
        $alias = 'DV';
        $join = [
            ['face_user FU', 'DV.fuser_id = FU.fuser_id', 'INNER'],
            ['store_member SM', 'SM.fuser_id = DV.fuser_id AND SM.is_del = 0', 'LEFT'],
            ['user_group UG', 'UG.ugroup_id = SM.group_id', 'LEFT'],
        ];
        $where = [
            ['DV.is_del' ,'=', 0],
            ['DV.store_id' ,'IN', $this->userInfo['store_ids']],
        ];
        $list = [];
        $field = 'DV.visit_id, DV.store_id, FU.fuser_id, FU.avatar, FU.age, FU.gender, SM.member_id, SM.is_admin, UG.name as group_name, FU.add_time';
        $order = 'FU.fuser_id DESC';
        $count = db('day_visit')->field($field)->alias($alias)->join(array_merge($join,$join1))->where(array_merge($where,$where_label))->group('DV.fuser_id')->count();
        if ($count > 0) {
            $list = db('day_visit')->field($field)->alias($alias)->join(array_merge($join,$join1))->where(array_merge($where,$where_label))->group('DV.fuser_id')->limit(($page-1)*$size,$size)->order($order)->select();
            if ($list) {
                foreach ($list as $key => $value) {
                    if (empty($value['member_id'])) {
                        $list[$key]['user_type'] = '访客';
                        $list[$key]['type'] = 0;
                    }elseif ($value['is_admin'] === 0){
                        $list[$key]['user_type'] = '会员';
                        $list[$key]['type'] = 1;
                    }else{
//                         $list[$key]['user_type'] = $value['group_name'];
                        $list[$key]['user_type'] = '员工';
                        $list[$key]['type'] = 2;
                    }
                    $list[$key]['member_id'] = intval($list[$key]['member_id']);
                    $list[$key]['is_admin'] = intval($list[$key]['is_admin']);
                    unset($list[$key]['group_name']);
                }
            }
        }
        $data['page'] = $page;
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page,'list' => $list]]);die;
    }
    /**
     * 用户详情
     */
    public function detail($return1 = FALSE)
    {
        $result = $this->_verifyInfo();
        $info = $result['info'];
        $user = $result['user'];
        $info['is_admin'] = $user['is_admin'];
        if ($user) {
            $info['nickname'] = $user['nickname'];
            $info['realname'] = $user['realname'];
            $info['phone'] = $user['phone'];
            if ($user['is_admin'] === 0){
                $info['user_type'] = lang('会员');
            }else{
                $info['user_type'] = $user['group_name'];
            }
            $return = db('member_label')->alias('ML')->join('label L','ML.label_id=L.label_id','left')->where([
                ['ML.is_del','=',0],
                ['L.is_del','=',0],
                ['ML.member_id','=',$user['member_id']],
            ])->field('L.name')->select();
            $info['tag'] = [];
            if($return){
                foreach($return as $k =>$v){
                    $info['tag'][] = $v['name'];
                }
            }

        }else{
            $info['nickname'] = $info['realname'] = $info['phone'] = $info['card_no'] = '';
            $info['user_type'] = lang('访客');
            $info['tag'] = [];
        }
        if ($return1) {
            return ['info'=>$info,'user'=>$user];
        }
        unset($info['is_admin']);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => $info]);die;
    }
    
    public function getStores()
    {
//        $storeIds = $this->userInfo['store_ids'] ? $this->userInfo['store_ids'] : [$this->userInfo['store_id']];
        $request = new \think\Request;
        $storeInfoModel = new \app\api\controller\v1\Login($request);
        $storeIds = $storeInfoModel->getUserStores($this->userInfo)['store_ids'];
        $where = [
            ['store_id', 'IN', $storeIds],
            ['is_del', '=', 0],
            ['status', '=', 1],
        ];
        $stores = db('store')->where($where)->field('store_id, name')->select();
        $this->_returnMsg(['code' => 0, 'msg' => '用户删除成功','data'=> $stores]);die;
    }
    public function staffset(Request $request)
    {
        $staffer = new \app\api\controller\v1\staffer\Staffer($request);
        return $staffer->staffAdd();
    }
    public function del()
    {
        $result = $this->_verifyInfo();
        $user = $result['user'];
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => '不能重复删除']);die;
        }
        $isAdmin = $user['is_admin'];
        $data = [
            'is_admin' => 0, 
            'group_id' => 0,
            'update_time' => time(),
        ];
        if ($user['user_id']) {
            $result = db('User')->where(['user_id' => $user['user_id']])->update($data);
            if ($result === FALSE) {
                $this->_returnMsg(['code' => 1, 'msg' => '系统错误']);die;
            }
        }
        $data['is_del'] = 1;
        $result = db('store_member')->where(['member_id' => $user['member_id']])->update($data);
        if ($result === FALSE) {
            $this->_returnMsg(['code' => 1, 'msg' => '系统错误']);die;
        }
        $data['is_del'] = 0;
        $this->_returnMsg(['code' => 0, 'msg' => '用户删除成功','data'=>['member_id'=>$user['member_id']]]);die;
    }
    private function _verifyInfo()
    {
        $fuserId = isset($this->postParams['fuser_id']) ? intval($this->postParams['fuser_id']) : 0;
        if (!$fuserId) {
            $this->_returnMsg(['code' => 1, 'msg' => '缺少参数']);die;
        }
        $field = 'fuser_id, avatar, age,age_level, gender, ethnicity';
        $info = db('face_user')->where(['fuser_id' => $fuserId])->field($field)->find();
        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '用户不存在,请返回重试']);die;
        }
        $api = new \app\common\api\BaseFaceApi();
        $info['gender_id'] = $info['gender'];
        $info['gender'] = $api->_getDataDetail('gender', $info['gender'], 'name');
        $info['ethnicity'] = $api->_getDataDetail('ethnicity', $info['ethnicity'], 'name');
        $join = [
            ['user U', 'U.user_id = SM.user_id AND U.is_del = 0', 'LEFT'],
            ['user_group UG', 'UG.ugroup_id = SM.group_id', 'LEFT'],
            ['store S', 'S.store_id = SM.store_id', 'LEFT'],
        ];
        $where = [
            'SM.fuser_id' => $fuserId,
            'SM.store_id' => $this->userInfo['store_id'],
        ];
        $field = 'SM.member_id, U.user_id, SM.is_admin, U.nickname, U.realname, U.phone, UG.name as group_name, S.name as store_name';
        $user = db('store_member')->alias('SM')->field($field)->join($join)->where($where)->find();
        return [
            'info' => $info,
            'user' => $user,
        ];
    }
}    