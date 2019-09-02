<?php
namespace app\common\controller;
use think\Request;
use app\admin\controller\Upload;
//基本数据增删改查管理公共处理
class FormBase extends AdminBase
{
    var $model;
    var $modelName;
    var $where;
    var $infotempfile;
    var $indextempfile;
    
    var $showOther;
	public function __construct()
    {
    	parent::__construct();
    	$this->subMenu['info'] = [
    	    'name'         => lang($this->modelName.'_name'),
    	    'description'  => lang($this->modelName.'_desc'),
    	];
    	
    	$params = $this->request->param();
    	$other = isset($params['other']) && intval($params['other']) ? 1 : 0;
    	if (isset($params['other']) && $other) {
    	    $this->showOther = 1;
    	    $this->subMenu['menu'] = [
    	        [
    	            'name'  => lang($this->modelName.'_list'),
    	            'url'   => url('index', ['other' => $other]),
    	        ],
    	    ];
    	}else{
    	    $this->subMenu['menu'] = [
    	        [
    	            'name'  => lang($this->modelName.'_list'),
    	            'url'   => url('index'),
    	        ],
    	    ];
    	    $this->subMenu['add'] = [
    	        'name' => lang($this->modelName.'_add'),
    	        'url' => url("add"),
    	    ];
    	}
    	$this->assign('other', $other);
        $this->infotempfile = 'info';
        $this->indextempfile = '';
        
    }
    /**
     * 内容列表
     */
    public function index(){
        $field  = $this->_getField();
        $where  = $this->_getWhere();
        $alias  = $this->_getAlias();
        $join   = $this->_getJoin();
        $order  = $this->_getOrder();
        if($alias) $this->model->alias($alias);
        if($join) $this->model->join($join);
        if($field) $this->model->field($field);
        //取得内容列表
        $count  = $this->model->where($where)->count();
        
        if($alias) $this->model->alias($alias);
        if($join) $this->model->join($join);
        if($field) $this->model->field($field);
        $list   = $this->model->where($where)->order($order)->paginate(10,$count, ['query' => input('param.')]);
        // 获取分页显示
        $page   = $list->render();
        $list   = $list->toArray()['data'];
        $list = $this->_afterList($list);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page', $page);
    	return $this->fetch($this->indextempfile);
    }
    public function getAjaxList($where = [], $field = '')
    {
        $params = Request::instance()->param();
        $keyword = isset($params['word']) ? trim($params['word']) : '';
        $isPage = isset($params['isPage']) ? intval($params['isPage']) : 0;
        $currectPage   = Request::instance()->get('page') ? intval(Request::instance()->get('page')) : 1;
        $where = $where ? $where : ['is_del' => 0, 'status' => 1];
        if (!$where && $keyword) {
            $where['name'] = ['like', '%'.$keyword.'%'];
        }
        $count =  $this->model->where($where)->count();
        if (!$field) {
            $pk = $this->model->getPk();
            $field = $pk.' as id, name';
        }
        $list = $this->model->field($field)->where($where)->order('add_time DESC')->paginate(10, $count, ['page' => $currectPage, 'ajax' => TRUE]);
        $page = '';
        if ($list) {
            $page = $list->render();
            $list = $list->toArray()['data'];
        }
        $data = array(
            'data'  => $list,
            'page' => $page,
        );
        $this->ajaxJsonReturn($data);
    }
    /**
     * 新增内容
     */
    public function add() {
        $msg = lang($this->modelName.'_add');
        $params = Request::instance()->param();
        if ($params && isset($params['sid']) && $params['sid']) {
            $storeVisit = $this->_checkStoreVisit($params['sid']);
        }
        if(IS_POST){
            $data = $this->_getData();
            $pkId = $this->model->insertGetId($data);
            if($pkId){
                $this->_addAfter($pkId, $data);
                $msg .= lang('success');
                $routes = Request::instance()->route();
                $this->success($msg, url("index", $routes), TRUE);
            }else{
                $msg .= lang('fail');
                $this->error($msg);
            }
        }else{
            $routes = Request::instance()->route();
            $this->subMenu['menu'][] = [
                'name'  => $msg,
                'url'   => url('add', $routes),
            ];
            $this->_assignInfo();
            $this->_assignAdd();
            return $this->fetch($this->infotempfile);
        }
    }
    
    /**
     * 编辑内容
     */
    public function edit() {
        $params = Request::instance()->param();
        $routes = Request::instance()->route();
        $pkId = intval($params['id']);
        $msg = lang($this->modelName.'_edit');
        if($pkId){
            $this->subMenu['menu'][] = [
                'name'  => $msg,
                'url'   => url('edit', $routes),
            ];
            $info = $this->_assignInfo($pkId);
            if (!$info) {
                $this->error(lang('ERROR'));
            }
            if ($info && isset($info['store_id']) && $info['store_id']) {
                $storeVisit = $this->_checkStoreVisit($info['store_id']);
            }
            if(IS_POST){                
                $data = $this->_getData();
                $pk   =   $this->model->getPk();
                $where[$pk] = $pkId;
                $rs = $this->model->where($where)->update($data);                
                if($rs){
                    $this->_editAfter($pkId, $data);
                    $msg .= lang('success');
                    unset($routes['id']);
                    $this->success($msg, url("index", $routes), TRUE);
                }else{
                    $msg .= lang('fail');
                    $this->error($msg);
                }
            }else{
                return $this->fetch($this->infotempfile);
            }
        }else{
            $this->error(lang('param_error'));
        }
    }
    
    /**
     * 删除内容
     */
    public function del() {
        $params = Request::instance()->param();
        $pkId = intval($params['id']);
        if($pkId){
            $info = $this->_assignInfo($pkId);
            if ($info['store_id']) {
                $storeVisit = $this->_checkStoreVisit($info['store_id']);
            }
            $pk = $this->model->getPk();
            $result = $this->model->where(array($pk => $pkId))->update(array('is_del' => 1, 'update_time' => time()));
            if($result){
                $msg = lang($this->modelName.'_del').lang('success');
                $this->success($msg);
            }else{
                $this->error($this->model->getError());
            }
        }else{
            $this->error(lang('param_error'));
        }
    }
    
    public function avatar()
    {
//         $faceThumb = 'http://face.worthcloud.net/2000615460005515_20180901161438_0023.jpeg';
//         $existToken = [
//             'age' => 60,
//             'gender' => 1,
//         ];
//         return json(['status' => 1, 'thumb' => $faceThumb, 'face' => $existToken]);
        $upload = new Upload();
        Request::instance()->post(['thumb_type' => 'avatar_thumb']);
        $result = $upload->upload(TRUE);
        if (!$result) {
            return json(['status' => 0, 'info' => '图片错误']);
        }
        if (!$result['status']) {
            return json($result);
        }
        $faceThumb = isset($result['thumb']) ? $result['thumb'] : '';
        $faceImg = isset($result['file']) ? $result['file'] : '';
        if (!$faceImg) {
            return json(['status' => 0, 'info' => '头像不存在']);
        }
        $faceApi = new \app\common\api\BaseFaceApi();
        //接口图片检测
        $detectResult = $faceApi->_faceDetect($faceImg);
        if ($detectResult['errCode'] > 0) {
            return json(['status' => 0, 'info' => $detectResult['errMsg'], 'thumb' => $faceThumb]);
        }
        $faceCount = count($detectResult['faces']);
        if ($faceCount != 1) {
            return json(['status' => 0, 'info' => '图片内包含'.$faceCount.'张人脸信息', 'thumb' => $faceThumb]);
        }
        $face = $detectResult['faces'][0];
        $faceToken = trim($face['face_token']);//人脸唯一标识
        
        $attributes = $face['attributes'] ? $face['attributes'] : [];    //人脸属性特征
        $tags = strtolower($attributes['gender']['value']); //性别标签
        
        //同分组标签搜索用户匹配人脸信息
        $searchReturn = $faceApi->_faceSearch($faceImg, $tags, 1);
        $searchFaceId = isset($searchReturn['searchFaceId']) ? trim($searchReturn['searchFaceId']) : '';
        if (!$searchFaceId) {
            //不同分组标签搜索用户匹配人脸信息
            $searchReturn = $faceApi->_faceSearch($faceImg, $tags, 0);
            $searchFaceId = isset($searchReturn['searchFaceId']) ? trim($searchReturn['searchFaceId']) : '';
        }
        //搜索候选者的置信度大于等于65的personId
        $personId = $searchReturn && isset($searchReturn['personId']) ? trim($searchReturn['personId']): '';
        //搜索候选者的置信度大于等于65的置信度
        $searchConfidence = $searchReturn && isset($searchReturn['searchConfidence']) ? trim($searchReturn['searchConfidence']): '';
        if ($searchFaceId) {
            //对检索结果进行一对一对比
            $compareResult = $faceApi->_faceCompare($faceImg, $faceToken, $attributes, $searchFaceId, $searchConfidence);
            if ($compareResult['errCode'] > 0) {
                return json(['status' => 0, 'info' => $compareResult['errMsg'], 'thumb' => $faceThumb]);
            }
            $searchFaceId = isset($compareResult['searchFaceId']) ? trim($compareResult['searchFaceId']) : '';
            $compareJsonArray = isset($compareResult['compare']) ? $compareResult['compare'] : '';
            $compareTokenInfo = $compareJsonArray && isset($compareJsonArray['compare_token']) ? $compareJsonArray['compare_token'] : [];
        }
        if ($searchFaceId) {
            $existToken = db('face_token')->field('face_id, fuser_id, img_url, age, gender')->where(['face_id' => $searchFaceId])->find();
            if ($existToken) {
                //判断当前用户是否已是当前门店会员
                $memberId = db('store_member')->alias('SM')->join('store S', 'SM.store_id = S.store_id')->where(['fuser_id' => $existToken['fuser_id'], 'SM.store_id' => $this->storeId, 'SM.is_del' => 0, 'S.is_del' => 0])->value('SM.member_id');
                $existToken['member_id'] = $memberId ? $memberId : 0;
                //判断用户是否已经创建
                $user = db('user')->field('user_id, phone, realname, nickname, is_admin, group_id, age, gender')->where(['fuser_id' => $existToken['fuser_id'], 'is_del' => 0])->find();
                $existToken['user'] = $user ? $user : [];
                $existToken['age'] = $user ? $user['age'] : $existToken['age'];
                $existToken['gender'] = $user ? $user['gender'] : $existToken['gender'];
            }else{
                $existToken = [];
            }
        }else{
            $genderId = $faceApi->_getDataId('gender', $attributes['gender']['value']);   //性别ID
            $age = $attributes['age']['value'];             //年龄数据
            $existToken = [
                'age' => $age,
                'gender' => $genderId,
            ];
        }
        return json(['status' => 1, 'thumb' => $faceThumb, 'face' => $existToken]);
    }

    /**
     *
     * @description
     * @param int $sid
     * @param bool $storeSuperVisit
     * @param bool $clerkVisit
     * @return array|bool|int
     */
    function _checkStoreVisit($sid = 0, $storeSuperVisit = TRUE, $clerkVisit = TRUE)
    {
        $storeId = $sid ? $sid : $this->storeId;
        if ($storeId) {
            if ($storeId && in_array($this->adminUser['group_id'], [STORE_SUPER_ADMIN, STORE_MANAGER])) {
                if ($this->adminUser['group_id'] == STORE_SUPER_ADMIN) {
                    $childs = db('store')->where(['is_del' => 0, 'status' => 1, 'parent_id' => $this->storeId])->column('store_id');
                    if ($storeSuperVisit && $this->storeId == $sid) {
                        $childs[] = $this->storeId;
                    }
                    if ($sid && !in_array($sid, $childs)) {
                        $this->error(lang('NO ACCESS'));
                    }
                    if (!in_array($this->storeId, $childs)) {
                        $childs[] = $this->storeId;
                    }
                    return $childs;
                }else{
                    if ($this->storeIds && !in_array($storeId, $this->storeIds)) {
                        $this->error(lang('NO ACCESS'));
                    }
                    return $this->storeIds;
                }
            }elseif ($this->adminUser['group_id'] == SYSTEM_SUPER_ADMIN){
                return TRUE;
            }elseif ($this->adminUser['group_id'] == STORE_CLERK){
                if ($sid && $sid != $this->storeId) {
                    $this->error(lang('NO ACCESS'));
                }
                if (!$clerkVisit) {
                    $this->error(lang('NO ACCESS'));
                }
                return $storeId;
            }else{
                $this->error(lang('NO ACCESS'));
            }
        }else{
            return FALSE;
        }
    }
    
    //以下为私有方法    
    function _afterList($list)
    {
        return $list;
    }
    
    /**
     * 取得查询字段
     */
    function _getField(){
        return;
    }
    
    /**
     * 取得查询条件
     */
    function _getWhere(){
        $where = $this->where;
        $where['is_del'] = 0;
        return $where;
    }
    /**
     * 取得查询条件
     */
    function _getAlias(){
        return;
    }
    /**
     * 取得查询条件
     */
    function _getJoin(){
        return;
    }
    /**
     * 取得查询条件
     */
    function _getOrder(){
        return 'sort_order ASC, add_time DESC';
    }
    
    /**
     * 获取提交数据
     */
    function _getData(){
        $params = Request::instance()->param();
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        $data = input('post.');
        if (!$pkId) {
            $data['add_time'] = time();
            $data['store_id'] = $this->storeId;
        }
        $data['update_time'] = time();
//         $data['is_admin'] = 1;
//         $data['user_id']  = $this->isLogin();
        return $data;
    }
    
    /**
     * 赋值新增时基础参数
     */
    function _assignAdd(){
        unset($this->subMenu['add']);
        $this->assign("name",lang($this->modelName."_add"));
        $this->assign('info', []);
    }
    
    /**
     * 取得并赋值info内容
     */
    function _assignInfo($pkId = 0){
        if(!$pkId){
            $params = Request::instance()->param();
            $pkId = isset($params['id']) ? intval($params['id']) : null;
        }
        unset($this->subMenu['add']);
        $this->assign("name",lang($this->modelName."_edit"));
        if($pkId){
            $pk = $this->model->getPk();
            $info = $this->model->where(array($pk => $pkId))->find();
            $this->assign("info",$info);
            return $info;
        }else{
            return [];
        }
    }
    function _addAfter($pkId = 0, $data = [])
    {
        return FALSE;
    }
    function _editAfter($pkId = 0, $data = [])
    {
        return FALSE;
    }
}
