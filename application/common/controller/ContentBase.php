<?php
namespace app\common\controller;
use app\common\model\Content;
//内容管理公共处理
class ContentBase extends AdminBase
{
    var $modelName;
    var $model;
    var $contentMod;
	public function __construct()
    {
    	parent::__construct();
    	$this->subMenu['info'] = [
    	    'name'         => lang($this->modelName.'_name'),
    	    'description'  => lang($this->modelName.'_desc'),
    	];
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
    	$this->contentMod = new Content();
    }
    /**
     * 内容列表
     */
    public function index(){
        $where = [];//"C.is_del=0 AND C.verify=1";
        if(IS_GET){
            if(input('get.name')){
                $name = input('get.name');
                $this->assign('name',$name);
                $where['title'] = ['like',"%'.$name.'%"];
            }
        }
        //取得内容列表
        $count = $this->contentMod->getCount($this->modelName,$where,true);
        $list  = $this->contentMod->getList($this->modelName,$where,$count);
        // 获取分页显示
        $page   = $list->render();
        $list   = $list->toArray()['data'];
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page', $page);
        return $this->fetch();
    }
    
    /**
     * 新增内容
     */
    public function add() {
        if(IS_POST){
            $data = input("post.");
            $data['add_time'] = strtotime($data['add_time']);
            $data['is_admin'] = 1;
            $data['user_id']  = $this->isLogin();
            $contentId = $this->contentMod->saveData('add',$data,$this->model);
            if($contentId){
                $this->success(lang('main_add_success'),url("index"),TRUE);
            }else{
                $this->error(lang('main_add_error'));
            }
        }else{
            $this->assign('name',lang('main_add'));
            //获取内容分类树
            $categoryList = $this->_getAllCategoryTree($this->modelName);
            $this->assign("cateList",$categoryList);
            $this->display("info");
        }
    }
    
    /**
     * 编辑内容
     */
    public function edit() {
        $contentId = input('get.id','','intval');
        if($contentId){
            if(IS_POST){
                $data = input("post.");
                $data['add_time'] = strtotime($data['add_time']);
                $data['content_id'] = $contentId;
                $contentMod = model('Content');
                $rs = $contentMod->saveData('edit',$data,$this->model);
                if($rs){
                    $this->success(lang('main_edit_success'),url("index"),TRUE);
                }else{
                    $this->error(lang('main_edit_error'));
                }
            }else{
                $this->assign('name',lang('main_edit'));
                //获取内容分类树
                $categoryList = $this->_getAllCategoryTree($this->modelName);
                $this->assign("cateList",$categoryList);
                //取得内容详情
                $contentInfo = $this->contentMod->getInfo($contentId,$this->model);
                $this->assign("info",$contentInfo);
                $this->display("info");
            }
        }else{
            $this->error(lang('param_error'));
        }
    }
    
    /**
     * 删除内容
     */
    public function del() {
        $contentId = input('get.id','','intval');
        if($contentId){
            $data = array('is_del'=>1);
            $rs = $this->contentMod->where("content_id={$contentId}")->save($data);
            if($rs){
                $this->success(lang('main_del_success'));
            }else{
                $this->error($this->contentMod->getError());
            }
        }else{
            $this->error(lang('param_error'));
        }
    }
    
    //私有方法
    /**
     * 获取分类树
     */
    private function _getAllCategoryTree($type = '',$recomend = 0){
        $cateListTree = model('category')->categoryTree($type,$recomend);
        return $cateListTree;
    }
}
