<?php

namespace app\common\model;
use think\Model;

class Content extends Model
{
	protected $fields;

	//自定义初始化
    protected function initialize()
    {
        parent::initialize();
        //TODO:自定义的初始化
    }
    
    /**
     * 统计符合条件的数据数量
     * @param string $model 资源扩展模型
     * @param unknown $where 统计条件
     * @param string $backend 是否是后台
     */
    public function getCount($model = MODEL_ARTICLE,$where,$backend = false){
        if(!$backend){
            $where['status'] = 1;
        }
        $Mod = db('content_'.$model);
        $count = $Mod->alias('A')
               ->join("__CONTENT__ C","C.content_id = A.content_id")
               ->where($where)->count();// 查询满足要求的总记录数
        return $count;
    }
    
    /**
     * 获取符合条件的数据列表
     * @param string $model
     * @param unknown $where
     * @param unknown $Page
     * @param string $backend
     * @return unknown
     */
    public function getList($model = MODEL_ARTICLE,$where,$count,$backend = false) {
        $perPage = 8;
        if(!$backend){
            $where['C.status'] = 1;
        }
        $Mod = db('content_'.$model);
        $list = $Mod->alias('A')->field("A.content_id,C.title,C.status,C.sort_order")
        ->join("__CONTENT__ C","C.content_id = A.content_id")
//         ->join("__CATEGORY__ G","C.cate_id = G.cate_id")
//         ->join("__USER__ U","C.user_id = U.user_id","LEFT")
        ->where($where)->order('C.sort_order,C.add_time desc,A.content_id desc')->paginate($perPage,$count);
        return $list;
    }


    
}