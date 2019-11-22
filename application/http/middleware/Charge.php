<?php

namespace app\http\middleware;

use think\facade\Request;
use think\facade\Cache;
class Charge
{
    public function handle($request, \Closure $next)
    {
        #使用这个中间件的接口已经确定是收费接口了，需要做的是：查看门店有开通这个接口吗
        //接收门店id
        $storeId = input('store_id') ?? 0;
        if($storeId == 0){
            $data = file_get_contents('php://input');
            if ($data) {
                $tempData = json_decode($data, true);
                $storeId = $tempData['store_id'] ?? 0;
            }
        }

        //查询门店是否有权限访问这个收费接口
        $result = db('auth_rule')
            ->where([
                ['route','=','v1.help.help_cateList'],
                ['is_fees','=',1],
                ['is_del','=',0],
            ])->find();
        $auth_rule_id = isset($result['id']) ? $result['id'] : 0;

        $a = db('store_fees_rule');
        $return = $a
            ->where([
                ['auth_rule_id','=',$auth_rule_id],
                ['store_id','=',$storeId],
                ['expire_time','>',time()],
            ])
            ->whereOr(function ($query) use ($auth_rule_id,$storeId){
                $query->where([
                    ['auth_rule_id','=',$auth_rule_id],
                    ['store_id','=',$storeId],
                    ['is_forever','=',1],
                ]);
            })
            ->value('store_id');

        //判断是否有权限处理结果
        if($return === NULL){
            return json(['code'=>1,'msg'=>'您未开通此功能，请去开通']);
        }
        return $next($request);
    }
}
