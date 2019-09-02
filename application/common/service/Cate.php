<?php
/**
 * 后台分类树
 */
namespace app\common\service;
class Cate
{
    //原始的分类数据
    private $rawList = array();
    //格式化后的分类
    private $formatList = array();
    //格式化的字符
    private $icon = array('│','├','└');
    //字段映射，分类id，上级分类parent_id,分类名称title,格式化后分类名称fulltitle
    private $field = array();
     
    /**
     * 构造函数
     **/
    public function __construct($field=array())
    {
        $this->field['cate_id']=isset($field['0'])?$field['0']:'cate_id';
        $this->field['parent_id']=isset($field['1'])?$field['1']:'parent_id';
        $this->field['name']=isset($field['2'])?$field['2']:'name';
        $this->field['cname']=isset($field['3'])?$field['3']:'cname';
    }
    
    /**
     * 取得全部系统分类树
     */
    public function getAllCategoryTree($type = false,$recomend = 0){
        $cateList = $this->_getAllCategory($type,$recomend);
        $cateListTree = $this->getTree($cateList);
        return $cateListTree;
    }
    /**
     * 取得商品全部分类树
     */
    public function getAllGoodsCateTree($cateList){
        $cateListTree = $this->getTree($cateList);
        return $cateListTree;
    }
    
    public function getChild($parent_id,$data=array())
    {
        $childs=array();
        if(empty($data))
        {
            $data=$this->rawList;
        }
        foreach($data as $Category)
        {
            if($Category[$this->field['parent_id']]==$parent_id)
                $childs[]=$Category;
        }
        return $childs;
    }
    
    public function getTree($data, $id=0, $pkId = 'cate_id')
    {
        //数据为空，则返回
        if(empty($data)) return false;
        $this->formatList = array();
        $this->rawList = $data;
        $this->_searchList($id, false, $pkId);
        return $this->formatList;
    }
    //获取当前分类的路径
    public function getPath($data,$id){
        $this->rawList=$data;
        while(1){
            $id=$this->_getparent_id($id);
            if($id==0){
                break;
            }
        }
        return array_reverse($this->formatList);
    }
    
    private function _searchList($id=0, $space="", $pkId = 'cate_id')
    {
        //下级分类的数组
        $childs=$this->getChild($id);
        //如果没下级分类，结束递归
        if(!($n=count($childs)))
            return;
        $cnt=1;
        //循环所有的下级分类
        for($i=0;$i<$n;$i++)
        {
            $pre="";
            $pad="";
            if($n==$cnt)
            {
                $pre=$this->icon[2];
            }
            else
            {
                $pre=$this->icon[1];
                $pad=$space?$this->icon[0]:"";
            }
            $childs[$i][$this->field['cname']]=($space?$space.$pre:"").$childs[$i][$this->field['name']];
            $this->formatList[]=$childs[$i];
            //递归下一级分类
            $filed = isset($this->field[$pkId]) ? $this->field[$pkId]: $pkId;
            $this->_searchList($childs[$i][$filed],$space.$pad."&nbsp;&nbsp;");
            $cnt++;
        }
    }
    
    //通过当前id获取parent_id
    private function _getparent_id($id)
    {
        foreach($this->rawList as $key=>$value){
    
            if($this->rawList[$key][$this->field['cate_id']]==$id)
            {
                $this->formatList[]=$this->rawList[$key];
                return $this->rawList[$key][$this->field['parent_id']];
            }
        }
        return 0;
    
    }
    /**
     * 取得全部系统分类
     */
    private function _getAllCategory($type = false)
    {
        $cateMod = db('category');
        if($type){
            $where['type'] = $type;
        }
        $categoryList = $cateMod->where($where)->order("sort_order, cate_id, add_time")->select();
        return $categoryList;
    }
}
