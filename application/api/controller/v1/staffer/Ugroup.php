<?php
namespace app\api\controller\v1\staffer;

use app\api\controller\Api;
use think\Request;

class Ugroup extends Api
{
    public $noAuth = ['purview_list','purviewlist'];
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    private function _checkPurview()
    {
        if ($this->userInfo['user_id'] !== 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '无操作权限']);die;
        }
    }


    public function grouplist()
    {
        $this->_checkPurview();
        $params = $this -> postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 10;
        $count = db('user_group') -> where($this->_getWhere($params)) -> count();
        $list = db('user_group') -> where($this->_getWhere($params))-> limit(($page-1)*$size,$size)-> select();

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'list' => $list]]);die;
    }


    public function add()
    {
        $this->_checkPurview();
        $data = $this -> _getData();
        $pkId = db('user_group')->insertGetId($data);
        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '添加失败']);die;
        }
        //授权
        $params = $this -> postParams;
        $grouppurview = (isset($params['grouppurview']) && !empty($params['grouppurview']))?json_encode($params['grouppurview']):''; //huangyihao
        $purview['menu_json'] = $grouppurview;

        $rs = db('user_group')->where([['ugroup_id','=',$pkId]])->update($purview);

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['group_id' => $pkId]]);die;
    }

    public function edit()
    {
        $this->_checkPurview();
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '分组id缺失']);die;
        }
        $data = $this -> _getData();
        $res = db('user_group')->update($data);

        if(!$res){
            $this->_returnMsg(['code' => 1, 'msg' => '修改失败']);die;
        }

        //授权
        $params = $this -> postParams;
        $grouppurview = (isset($params['grouppurview']) && !empty($params['grouppurview']))?json_encode($params['grouppurview']):''; //huangyihao
        $purview['menu_json'] = $grouppurview;

        $grouppur = isset($params['grouppurview']) && !empty($params['grouppurview']) ? $params['grouppurview'] : [];
        //组装返回导航菜单
        $obj = new \app\service\service\Purview();
        $purviewvList = $obj->getGroupPurview();
        $data['menu'] = $data['sub_menu'] = [];
        foreach($grouppur as $kk => $vv){
            foreach($purviewvList as $k1 => $v1){
                if(substr($v1['value'],0,strpos($v1['value'], '_'))==substr($vv,0,strpos($vv, '_')))
                {
                    $index = isset($v1['menu']['index']) ? $v1['menu']['index'] :'';
                    if(!$index){
                        continue;
                    }
                    $data['menu'][$index] = $v1['menu'];
//                    if($v1['value'] != (isset($temp1) ? $temp1 :[])){
//                        $temp = [];
//                    }
//                    $temp1 = $v1['value'];
                    foreach($v1['list'] as $k2 => $v2){
                        if($v2['value'] == $vv){
                            if(!empty($v2['sub_menu'])){
                                $data['sub_menu'][$v2['sub_menu']['index']] = $v2['sub_menu'];
                            }


                        }
                    }
                }
            }
        }
        ksort($data['sub_menu']);
        foreach($data['menu'] as $k3=>$v3){
            foreach($data['sub_menu'] as $k4 => $v4){
                if($v3['index']==substr($v4['index'],0,strpos($v4['index'], '-'))){
                    $data['menu'][$k3]['menuItemList'][] = $v4;
                };
            }
        }
        $templ = [];
        ksort($data['menu']);
        foreach($data['menu'] as $k =>$v){
            if (!isset($v['menuItemList']) || !$v['menuItemList']) {
                continue;
            }
            $templ[] = $v;
        }
        $purview['menu'] = json_encode($templ);


        $rs = db('user_group')->where([['ugroup_id','=',$pkId]])->update($purview);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['ugroup_id' => $data['ugroup_id']]]);die;

    }

    public function del(){
        $this->_checkPurview();
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if (!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }elseif($pkId <= USER) {
            //$this->error('系统分组，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '系统分组，不允许删除']);die;
        }
        //$info = parent::_assignInfo($pkId);
        //判断当前分组下是否存在用户
        $device = db('user')->where(['group_id' => $pkId, 'is_del' => 0])->find();
        if ($device) {
            //this->error('分组下存在用户，不允许删除');
            $this->_returnMsg(['code' => 1, 'msg' => '分组下存在用户，不允许删除']);die;
        }
        $result = db('user_group') -> where('ugroup_id','=',$pkId) -> update(['is_del'=>1]);
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => '删除失败']);die;
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['ugroup_id' => $pkId]]);die;
    }


    public function info(){
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('user_group') -> where([['ugroup_id','=',$pkId],['is_del','=',0]]) -> find();
        $info['menu_json'] = isset($info['menu_json']) && !empty($info['menu_json']) ? $info['menu_json'] : "[]";
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['info' => $info]]);die;
    }

    //权限列表
    public function purviewlist()
    {
        //获取菜单
        $userInfo = $this->userInfo;

        $data = db('auth_rule') -> where([
            ['menu_status','=',1],
            ['is_fees','=',0],
            ['is_del','=',0],
        ])->select();
        $tree = [];
        if(!empty($data)){
            $tree = $this->get_menu_tree($data);
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $tree]);die;

        if($userInfo['group_id'] == 1){

        }else{
            $groupInfo = db("user_group")->where(['ugroup_id' => $userInfo['group_id']])->find();

            $menuData = json_decode($groupInfo['menu'],true);
            if(!empty($menuData)){
                foreach($menuData as $k => $v){
                    $menuData[$k]['title'] = lang($v['title']);
                    if(is_array($v['menuItemList'])){
                        foreach($v['menuItemList'] as $key => $value){
                            $menuData[$k]['menuItemList'][$key]['name'] = lang($value['name']);
                        }
                    }
                }
            }
            $menu = $menuData;
        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功','data' => $menu]);die;
    }

    //purviewlist1
    public function purview_list()
    {
        $where = [
            ['is_del','=',0],
            ['is_fees','=',0],
        ];
        $data = db('auth_rule') ->where($where) ->select();
        $tree = [];
        if(!empty($data)){
            $tree = $this->get_tree($data);
        }
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['purviewvList' => $tree]]);die;
    }
    
    /**
     * 用户组授权
     */
    public function purview()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $obj = new \app\service\service\Purview();
        $purviewvList = $obj->getGroupPurview();


        $grouppur = isset($params['grouppurview']) && !empty($params['grouppurview']) ? $params['grouppurview'] : [];
        //组装返回导航菜单
        $data['menu'] = $data['sub_menu'] = [];
        foreach($grouppur as $kk => $vv){
            foreach($purviewvList as $k1 => $v1){
                if(substr($v1['value'],0,strpos($v1['value'], '_'))==substr($vv,0,strpos($vv, '_')))
                {
                    $index = isset($v1['menu']['index']) ? $v1['menu']['index'] :'';
                    if(!$index){
                        continue;
                    }
                    $data['menu'][$index] = $v1['menu'];
//                    if($v1['value'] != (isset($temp1) ? $temp1 :[])){
//                        $temp = [];
//                    }
//                    $temp1 = $v1['value'];
                    foreach($v1['list'] as $k2 => $v2){
                        if($v2['value'] == $vv){
                            if(!empty($v2['sub_menu'])){
                                $data['sub_menu'][$v2['sub_menu']['index']] = $v2['sub_menu'];
                            }


                        }
                    }
                }
            }
        }
        $grouppurview = (isset($params['grouppurview']) && !empty($params['grouppurview']))?json_encode($params['grouppurview']):''; //huangyihao
        $data['menu_json'] = $grouppurview;

        foreach($data['menu'] as $k3=>$v3){
            foreach($data['sub_menu'] as $k4 => $v4){
                if($v3['index']==substr($v4['index'],0,strpos($v4['index'], '-'))){
                    $data['menu'][$k3]['menuItemList'][] = $v4;
                };
            }
        }
        $templ = [];
        foreach($data['menu'] as $k =>$v){
            $templ[] = $v;
        }
        $data['menu'] = json_encode($templ);
        unset($data['sub_menu']);
        $rs = db('user_group')->where([['ugroup_id','=',$pkId],['is_del','=',0]])->update($data);
        if($rs){
            $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['ugroup_id' => $pkId]]);die;

        }else{
            $this->_returnMsg(['code' => 1, 'msg' => '授权失败']);die;
        }


    }
    
    function _getWhere($params){
        $where[] = ['is_del','=',0];

        if ($params) {
            $name = isset($params['name']) ? trim($params['name']) : '';
            if($name){
                $where[] = ['name','like','%'.$name.'%'];
            }
        }
        return $where;
    }
    function _getData()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : null;

        $name = $params && isset($params['name']) ? trim($params['name']) : '';
        if (!$name && !$pkId) {
            //$this->error('分组名称不能为空');
            $this->_returnMsg(['code' => 1, 'msg' => '分组名称不能为空']);die;
        }
        $where = [['name','=',$name], ['is_del','=',0]];

        if($pkId){
            $where[] = ['ugroup_id','neq', $pkId];
        }
        $exist = db('user_group')->where($where)->find();
        if($exist){
            //$this->error('当前分组名称已存在');
            $this->_returnMsg(['code' => 1, 'msg' => '当前分组名称已存在']);die;
        }

        //组装数据返回

        if (!$pkId) {
            $data['name'] = $name;
            $data['store_id'] = 1;
            $data['ugroup_id'] = $pkId;
            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['status'] = $params && isset($params['status']) ? intval($params['status']) : 1;
            $data['sort_order'] = $params && isset($params['sort_order']) ? intval($params['sort_order']) : 255;
            $data['menu_json'] = '';
        }else{
            $info = db('user_group') -> where([['ugroup_id','=',$pkId],['is_del','=',0]])-> find();
            if(!$info){$this->_returnMsg(['code' => 1, 'msg' => '分组id错误']);die;}
            $data = [
                    'ugroup_id' => $pkId,
                    'name'  => isset($params['name']) ? trim($params['name']) : $info['name'],
                    'update_time'=> time(),
                    'status'    => isset($params['status']) ? intval($params['status']) : $info['status'],
                    'sort_order'    => isset($params['sort_order']) ? intval($params['sort_order']) : $info['sort_order'],
                    ];
        }
        return $data;
    }

    /**
     * 二极分类树 getTree($categories)
     * @param array $data
     * @param int $parent_id
     * @param int $level
     * @return array
     */
    public function get_tree($data = [], $p_id = 0, $level = 0)
    {
        $tree = [];
        if ($data && is_array($data)) {
            foreach ($data as $v) {
                if ($v['p_id'] == $p_id) {
                    if($p_id == 0){
                        $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                            'name' => $v['title'],
                            'value' => $v['route'],
//                    'menu' => $v['title'],
                            'list' => $this->get_tree($data, $v['id'], $level + 1),
                        ];
                    }else{
                        $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                            'name' => $v['title'],
                            'value' => $v['route'],
//                    'submenu' => $v['title'],
                            'type' => 'single',
                        ];
                    }

                }
            }
        }
        return $tree;
    }

    public function get_menu_tree($data = [], $p_id = 0, $level = 0)
    {
        $tree = [];
        if ($data && is_array($data)) {
            foreach ($data as $v) {
                if ($v['p_menu_index'] == $p_id) {
                    if($p_id == 0){
                        $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                            'title' => $v['title'],
                            'index' => $v['menu_index'],
//                    'menu' => $v['title'],
                            'menuItemList' => $this->get_menu_tree($data, $v['menu_index'], $level + 1),
                        ];
                    }else{
                        $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                            'name' => $v['title'],
                            'index' => $v['menu_index'],
                            'icon' => $v['icon'],
//                    'submenu' => $v['title'],
                            'path' => $v['path'],
                        ];
                    }

                }
            }
        }
        return $tree;
    }


}
