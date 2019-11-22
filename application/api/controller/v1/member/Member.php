<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/29 0029
 * Time: 10:22
 */

namespace app\api\controller\v1\member;

use app\api\controller\Api;
use think\Request;

class Member extends Api
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    public function memberList()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;

        $count = db('store_member') -> alias('SM') -> where($this->_getWhere($params))-> join($this ->_getJoin($params)) -> count();
        $data = db('store_member') -> alias('SM') -> where($this->_getWhere($params)) -> field($this->_getField($params)) -> join($this ->_getJoin($params)) ->order('U.group_id ASC, U.sort_order ASC, U.add_time DESC') -> limit(($page-1)*$size,$size) -> select();
        if(!empty($data)) {
            foreach ($data as $key => $value) {
                $where = [
                    ['ML.member_id','=',$value['member_id']],
                    ['ML.is_del','=',0],
                    ['L.is_del','=',0],
                ];
                $labels = db('member_label') ->alias('ML') -> join('label L','L.label_id=ML.label_id','left') -> where($where) -> select();
                $lab=[];
                if(!empty($labels)){
                    foreach($labels as $k=>$v){
                        $lab[$k]['label_name'] = $v['name'];
                        $lab[$k]['label_id'] = $v['label_id'];
                    }
                }
                $total_visit_counts = db('day_visit')->where('store_id', '=', $value['store_id'])->where('fuser_id','=',$value['fuser_id']) -> order('add_time desc') -> field('total_visit_counts')-> find();
//                $data[$key]['labels_id'] = !empty($labe) ? $labe : [];
                $data[$key]['labels'] = !empty($lab) ? $lab : [];
                $data[$key]['total_visit_counts'] = isset($total_visit_counts['total_visit_counts'])?$total_visit_counts['total_visit_counts']:0;
            }
        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $data]]);die;
    }

    /**
     * 会员注册
     */
    public function register()
    {

    }

    public function edit()
    {
        $params = $this->postParams;
        $storeId = isset($params['sid']) ? $params['sid'] : 1;
        $memberId = isset($params['id']) ? $params['id'] : 0;
        $gradeId = isset($params['grade_id']) ? intval($params['grade_id']) : 0;
        $realName = isset($params['realname']) ? trim($params['realname']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        $age = isset($params['age']) ? trim($params['age']) : 0;
        $gender = isset($params['gender']) ? trim($params['gender']) : 0;
        $labels = isset($params['labels']) ? explode(',',$params['labels']) : [];
        if ($labels) {
            $array = array_unique(array_filter($labels));
            if ($array && count($labels) != count($array)) {
                $this->_returnMsg(['code' => 1, 'msg' => '标签重复']);die;
            }
        }

        if(!$memberId){
            $this->_returnMsg(['code' => 1, 'msg' => '会员id参数缺失']);die;
        }
        $info = db('store_member') -> where([['member_id','=',$memberId],['is_del','=',0]]) -> find();

        $user = db('user')->where(['user_id' => $info['user_id'], 'is_del' => 0, 'is_admin' => 0])->find();
        if (!$user) {
            $this->_returnMsg(['code' => 1, 'msg' => '账号不存在']);die;
        }
        $userService = new \app\common\service\User();
        $result = $userService->_checkFormat(['phone'=> $phone]);
        if ($result === false) {
            //$this->error($userService->error);
            $this->_returnMsg(['code' => 1, 'msg' => $userService->error]);die;
        }
        $data = [
            'update_time'=> time(),
            'status'    => isset($params['status']) ? intval($params['status']): $info['status'],
        ];
        if(!$gradeId){
            $data['grade_id'] = $info['grade_id'];
        }else{
            $gradeList = $this->_getGradeList($storeId);
//            if(empty($gradeList)){
//                $this->_returnMsg(['code' => 1, 'msg' => '门店id下无会员等级']);die;
//            }
//            if (!$gradeList[$gradeId]) {
//                //$this->error('会员等级错误');
//                $this->_returnMsg(['code' => 1, 'msg' => '会员等级错误']);die;
//            }
            $data['grade_id'] = $gradeId;
        }
        $update = [
            'realname' => !empty($realName) ? $realName : $user['realname'],
            'phone' => !empty($phone) ? $phone : $user['phone'],
            'age' => !empty($age) ? $age : $user['age'],
            'gender' => !empty($gender) ? $gender : $user['gender'],
        ];

        $resu = db('member_label') -> where('member_id','=',$memberId) -> update(['is_del'=>1]);
        foreach($labels as $v){

            $resul = db('member_label') -> insert(['member_id'=>$memberId,'label_id'=>$v]);
            $re = db('label') -> where('label_id','=',$v) -> setInc('quote',1);
        }
        $res = db('user') -> where('user_id','=',$info['user_id']) -> update($update);
        $memberId = db('store_member')->where(['member_id' => $info['member_id']])->update($data);
        if($memberId === false){
            //$this->error('会员编辑失败'.$this->model->error);
            $this->_returnMsg(['code' => 1, 'msg' => '会员等级错误']);die;
        }else{
            //$this->success('编辑会员成功', url('index', $url));
            $this->_returnMsg(['code' => 0, 'msg' => '编辑会员成功']);die;
        }

    }

    private function _getGradeList($storeId)
    {
        $where = [
            ['is_del' ,'=' , 0],
            //'store_id' => ['IN' , [0, $storeId]],
            ['store_id', 'IN' , [0, $storeId]],
        ];

        $gradeList = db('user_grade')->where($where)->order('sort_order ASC, add_time DESC')->column('grade_id, name');
        return $gradeList;
    }

    function _getField($params){
        return 'U.*, SM.*, UG.name, S.name as sname';
    }
    function _getJoin($params)
    {
        $join = [
            ['user U', 'SM.user_id = U.user_id', 'LEFT'],
            ['store S', 'SM.store_id = S.store_id', 'LEFT'],
        ];

        $join[] = ['user_grade UG', 'UG.grade_id = SM.grade_id', 'LEFT'];

        return $join;
    }
    function _getWhere($params,$tag = 0)
    {
        $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
        $storeId = cache($authorization)['admin_user']['store_id'];
        $storeIds = cache($authorization)['admin_user']['store_ids'];
        $store_id = isset($params['sid']) ? intval($params['sid']) : 0;
        if($tag){
            $where = [
                ['U.user_id','<>', 1],
                ['U.is_del' ,'=',0],
                ['SM.is_del','=',0],
            ];
        }else{
            $where = [
                ['U.is_admin','=', 0],
                ['SM.is_admin','=', 0],
                ['U.user_id','<>', 1],
                ['U.is_del' ,'=',0],
                ['SM.is_del','=',0],
            ];
        }


        $store = db('store')->where(['store_id' => $store_id, 'is_del' => 0])->find();
        if ($store_id) {
            $where[] = ['SM.store_id','=',isset($store['store_id'])?$store['store_id']:-1];
        }else{
            $where[] = ['SM.store_id','IN',$storeIds];
        }


        $name = isset($params['name']) ? trim($params['name']) : '';
        if($name){
            $where[] = ['U.realname|U.nickname|U.phone|U.username','like','%'.$name.'%'];
        }

        return $where;
    }

    public function info()
    {
        $params = $this->postParams;
        $member_id = isset($params['member_id']) ? intval($params['member_id']) :0;
        if(!$member_id){
            $this->_returnMsg(['code' => 1, 'msg' => '参数缺失']);die;
        }
        $is_have = db('store_member') -> where('member_id','=',$member_id) -> find();
        if(!$is_have){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' =>  []]);die;
        }
        $data = db('store_member') -> alias('SM') -> where('SM.member_id','=',$member_id) -> field($this->_getField($params))-> join($this ->_getJoin($params)) -> find();

            $where = [
                ['ML.member_id','=',$data['member_id']],
                ['ML.is_del','=',0],
                ['L.is_del','=',0],
            ];
            $labels = db('member_label') ->alias('ML') -> join('label L','L.label_id=ML.label_id','left') -> where($where) -> select();
            $lab=[];
            if(!empty($labels)){
                foreach($labels as $k=>$v){
                    $lab[$k]['label_name'] = $v['name'];
                    $lab[$k]['label_id'] = $v['label_id'];
                }
            }
            $data['labels'] = $lab;


        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' =>  $data]);die;

    }


    /**
     * created by huangyihao
     * @description []会员详情
     */
    public function detail(){
        $params = $this->postParams;
        $fuid = isset($params['fuser_id']) ? intval($params['fuser_id']) :0;
        $date = isset($params['d']) ? trim($params['d']) : date('Y-m-d');
        $storeId = isset($params['sid']) ? trim($params['sid']) : 0;
        $username = isset($params['name']) ? trim($params['name']) : 0;
        $sname = isset($params['sname']) ? trim($params['sname']) : 0;
        if(!$storeId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数缺失']);die;
        }
        $dataService = new \app\service\service\Dataset();
//        $dataService->initialize($storeId);
        //获取用户当前的访问数据
        $personInfo  = $dataService->getPersonInfo($fuid,$date,$storeId);

        if (!$personInfo['fuser']) {
            $this->_returnMsg(['code' => 1, 'msg' => '个体用户不存在或已被合并']);die;
        }
        $personInfo['fuser']['name'] = $username;
        $personInfo['fuser']['sname'] = $sname;
        $this->_returnMsg(['code' => 0, 'msg' => 'success','data'=>$personInfo]);die;
    }

    /**
     * created by huangyihao
     * @description []删除会员
     */
    public function del()
    {
        $params = $this -> postParams;
        $member_id = isset($params['member_id']) ? intval($params['member_id']) : 0;
        if(!$member_id){
            $this->_returnMsg(['code' => 1, 'msg' => '参数member_id缺失']);die;
        }
        $info = db('store_member') -> where([['member_id','=',$member_id],['is_del','=',0],['group_id','=',0]]) -> find();
        if(!$info){
            $this->_returnMsg(['code' => 1, 'msg' => '会员不存在，或者不是会员']);die;
        }
        $result = db('store_member') -> where([['member_id','=',$member_id],['is_del','=',0],['group_id','=',0]]) ->  update(['is_del'=>1,'update_time' => time()]);
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $res = db('user')->where(['user_id' => $info['user_id']])->update(['is_del'=>1, 'update_time' => time()]);
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data'=>['member_id'=>$member_id]]);die;
    }

    /**
     * created by huangyihao
     * @description []根据标签获取用户
     */
    public function userByLabel()
    {
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $label_id = !empty($params['label_id']) ? trim($params['label_id']) : 0;
        if (!$label_id) {
            $this->_returnMsg(['code' => 1, 'msg' => '标签ID不能为空']);die;
        }
        //标签id获取member_id集合
//        $where1 = [['label_id','=',$label_id],['is_del','=',0]];
//        $ret = db('member_label') -> where($where1) ->column('member_id');
/*        $count = db('store_member')-> alias('SM') -> where($this->_getWhere($params,1))->join($this ->_getJoin($params))-> count();
        $o = db('store_member');
        $data = $o-> alias('SM') -> where($this->_getWhere($params,1))-> field("U.*, SM.*, UG.name, S.name as sname ,0 as top") -> join($this ->_getJoin($params))->order('U.group_id ASC, U.sort_order ASC, U.add_time DESC') -> limit(($page-1)*$size,$size) -> select();*/
//pre($o->getLastSql());
        $alias = 'DV';
        $join = [
            ['face_user FU', 'DV.fuser_id = FU.fuser_id', 'INNER'],
            ['store_member SM', 'SM.fuser_id = DV.fuser_id AND SM.is_del = 0', 'LEFT'],
            ['store S', 'DV.store_id = S.store_id', 'LEFT'],
            ['member_label ML', 'DV.fuser_id = ML.fuser_id', 'LEFT'],
        ];
        $where = [
            ['DV.is_del' ,'=', 0],
            ['DV.store_id' ,'IN', $this->userInfo['store_ids']],
        ];
        $list = [];
        $field = 'DV.visit_id, FU.fuser_id,SM.member_id, FU.avatar, FU.age, FU.gender,FU.add_time, S.name as sname, ML.fuser_id as top';
        $order = 'FU.fuser_id DESC';
        $count = db('day_visit')->alias($alias)->join($join)->where($where)->group('DV.fuser_id')->count();
        if ($count > 0) {
            $list = db('day_visit')->field($field)->alias($alias)->join($join)->where($where)->group('DV.fuser_id')->limit(($page - 1) * $size, $size)->order('ML.fuser_id desc')->select();
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $list]]);die;

    }

    //关联,取消关联
    public function related()
    {
        $params = $this -> postParams;
        $memberId = $params['member_id'] ?? '';
        $fuserId = $params['fuser_id'] ?? '';
        $labelId = $params['label_id'] ??  '';
        $isRelated = $params['is_related'] ?? 1; //1关联，0取消关联

        if(!$fuserId || !$labelId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        if($isRelated == 1){
            $resul = db('member_label') -> insert(['fuser_id'=>$fuserId,'label_id'=>$labelId]);
            if(!$resul){
                $this->_returnMsg(['code' => 1, 'msg' => '关联失败']);die;
            }
            $re = db('label') -> where('label_id','=',$labelId) -> setInc('quote',1);
        }elseif($isRelated == 0){
            $result = db('member_label')->where(['fuser_id'=>$fuserId,'label_id'=>$labelId])->delete();
            if(!$result){
                $this->_returnMsg(['code' => 1, 'msg' => '取消关联失败']);die;
            }
            $re = db('label') -> where('label_id','=',$labelId) -> setDec('quote',1);
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
    }


}