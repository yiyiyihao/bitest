<?php
/**
 * 权限认证类
 */
namespace app\service\service;

class Auth{

    //默认配置
    protected $_config = array(
        'AUTH_ON'           => true,                // 认证开关
    );

    public function __construct() {
        if (config('AUTH_CONFIG')) {
            $this->_config = array_merge($this->_config, config('auth'));
        }
    }

    /**
     * 检查权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param purview  string|array     认证用户的组权限
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($name, $purview, $type=1, $relation='or') {
        if (!$this->_config['AUTH_ON'])
            return true;
        //获取用户需要验证的所有有效规则列表
        $authList = $purview;

        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }

        if (is_array($name)) {
//             $module     = strtolower($name['module']);
            $controller = strtolower($name['controller']);
            $action     = strtolower($name['action']);
            $name       = [];
//             $name[]     = $module.'_*';
            $name[]     = $controller.'_*';
            $name[]     = $controller.'_'.$action;
        }
        $default = [
            'member_profile',
            'apilog_index',
        ];
        $authList = array_merge($default, $authList);
        $list = array(); //保存验证通过的规则名
        if ($authList) {
            foreach ( $authList as $auth ) {
                $auth = strtolower($auth);
                if (in_array($auth , $name)){
                    $list[] = $auth ;
                }
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        return false;
    }
}
