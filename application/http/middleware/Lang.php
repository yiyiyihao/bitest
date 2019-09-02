<?php

namespace app\http\middleware;

class Lang
{
    public function handle($request, \Closure $next)
    {
        $lang = input('lang');
        if($lang == 'en-us'){
            \think\facade\Lang::range('en-us');
            $file = dirname(dirname(__FILE__)).'/lang/en-us.php';
            \think\facade\Lang::load($file);
        }else{
            $file = dirname(dirname(__FILE__)).'/lang/zh-cn.php';
            \think\facade\Lang::load($file);
        }

        return $next($request);
    }
}
