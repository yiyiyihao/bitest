<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/2/21 0021
 * Time: 15:28
 */
namespace app\service\service;
/**
 * 后台授权树
 */
class Key
{
    /**
     * 获取用户组授权树
     */
    public function getKey()
    {
        $key = [

            'admin' => ['akey' => 'WhwTOCRu6A3sMrjrgojUnfhDx_jLOe57','skey' => 'iH_sdhD12ctZQKDg6OfFUQB_bOc3IAT_'],
            'developer' => ['akey' => 'WhwTOCRu6A3sMrjrgojUnfhDx_jLOe56','skey' => 'aH_sdhD12ctZQKDg6OfFUQB_bOc3IAT_'],
            //'other' => ['akey' => 'WhwTOCRu6A3sMrjrgojUnfhDx_jLOe55','skey' => 'bH_sdhD12ctZQKDg6OfFUQB_bOc3IAT_'],


        ];
        return $key;
    }

}
