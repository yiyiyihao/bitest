<?php
namespace app\common\service;
class User
{
    var $error;
    var $userModel;
    public function __construct(){

    }
    public function assetChange($userId = '', $field = 'balance', $num = 0, $action = '', $msg = '', $extra = [])
    {
        if (!$userId) {
            $this->error = '参数错误';
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user){
            $this->error = '用户不存在或已删除';
            return FALSE;
        }
        if (!isset($user[$field])) {
            $this->error = '数据库字段不存在';
            return FALSE;
        }
        $fields = ['balance'];
        if(!in_array($field, $fields)) {
            $this->error = '参数错误';
            return FALSE;
        }
        $where = ['user_id' => $userId];
        if ($num > 0) {
            $result = db('user')->where($where)->setInc($field, $num);
        }else{
            $fieldValue = db('user')->where($where)->value($field);
            $absNum = abs($num);
            if ($absNum > $fieldValue) {
                $result = db('user')->where(['user_id' => $userId])->setField($field, 0);
            }else{
                $result = db('user')->where(['user_id' => $userId])->setDec($field, $absNum);
            }
        }
        if($result === FALSE) {
            $this->error = '系统错误';
            return FALSE;
        }
        $_user = db('user')->where(['user_id' => $userId])->find();
        $detail = [$field => $_user[$field]];
        $data = [
            'user_id'       => $userId,
            'asset_type'    => $field,
            'action_type'   => $action,
            'num'           => $num,
            'msg'           => $msg,
            'detail'        => $detail ? json_encode($detail) : '',
            'extra'         => $extra ? json_encode($extra) : '',
            'add_time'      => time(),
        ];
        $logId = db('user_asset_log')->insertGetId($data);
        return TRUE;
    }
    
    /**
     * 获取用户平台openid
     * @return 产生的随机字符串
     */
    public function _getUserOpenid()
    {
        $openid = get_nonce_str(30);
        $exist = db('user_data')->where(['openid' => $openid])->find();
        if ($exist) {
            return $this->_getUserOpenid();
        }else{
            return $openid;
        }
    }
    /**
     * 获取会员卡号
     * @return 产生的随机字符串
     */
    public function _getUserCardNo($length = 12)
    {
        $cardNo = get_nonce_str($length, 2);
        $exist = db('store_member')->where(['card_no' => $cardNo])->find();
        if ($exist) {
            return $this->_getUserCardNo($length);
        }else{
            return $cardNo;
        }
    }
    
    /**
     * 用户注册
     * @param string $username  登录用户名
     * @param string $password  登录密码
     * @param array $extra      其它用户信息
     * @return boolean|number
     */
    public function register($username = '', $password = '', $extra = [],$tag=0,$yz_pwd=0)
    {
        if (!$username) {
            $this->error = '登录用户名不能为空';
            return FALSE;
        }
        if ($password !== FALSE && !$password && !$yz_pwd) {
            $this->error = '登录密码不能为空';
            return FALSE;
        }
        $phone = isset($extra['phone']) ? trim($extra['phone']) : '';
        $email = isset($extra['email']) ? trim($extra['email']) : '';
        $extra['username'] = $username;
        $extra['password'] = $password;
        if(!$tag){
            $result = $this->_checkFormat($extra);
            if ($result === FALSE) {
                return FALSE;
            }
            $password = isset($password) ? $this->_passwordEncryption($password) : '';
        }

        //检查登录用户名是否存在
        $exist = $this->_checkUsername($username);
        if ($exist) {
            $this->error = '登录用户名已经存在';
            return FALSE;
        }
        $nickname = isset($extra['nickname']) ? trim($extra['nickname']) : (isset($extra['realname']) ? trim($extra['realname']) : '');
        $groupId = isset($extra['group_id']) ? intval($extra['group_id']) : 0;
        if ($groupId && in_array($groupId, [1, 2, 3])) {
            $isAdmin = 1;
        }elseif ($groupId && $groupId == 4){
            $isAdmin = 2;
        }else{
            $isAdmin = 0;
        }
        $data = [
            'username'  => $username,
            'password'  => $password,
            'nickname'  => $nickname,
            'realname'  => isset($extra['realname']) ? trim($extra['realname']) : '',
            'avatar'    => isset($extra['avatar']) ? trim($extra['avatar']) : '',
            'phone'     => $phone,
            'email'     => $email,
            'age'       => isset($extra['age']) ? intval($extra['age']) : 0,
            'gender'    => isset($extra['gender']) ? intval($extra['gender']) : 0,
            'group_id'  => $groupId,
            'is_admin'  => $isAdmin,
            'add_time'  => time(),
            'update_time'=> time(),
            'fuser_id'  => isset($extra['fuser_id']) ? intval($extra['fuser_id']) : 0,
        ];
        $userId = db('user')->insertGetId($data);
        if ($userId === false) {
            $this->error = '系统出错';
            return FALSE;
        }
        return $userId;
    }
    public function update($userId = 0, $password = '', $extra = [],$tag=0)
    {
        if (!$userId) {
            $this->error = '参数错误';
            return FALSE;
        }
        $user = db('user')->where(['user_id' => $userId])->find();
        if (!$user) {
            $this->error = '用户不存在';
            return FALSE;
        }
        $phone = isset($extra['phone']) ? trim($extra['phone']) : '';
        $email = isset($extra['email']) ? trim($extra['email']) : '';
        $extra['password'] = $password;
        if(!$tag){
            if (isset($extra['username'])) {
                unset($extra['username']);
            }
            $result = $this->_checkFormat($extra);
        }else{
            $result = true;
        }


        if ($result === FALSE) {
            return FALSE;
        }
        $data = [
            'username'  => isset($extra['username']) ? trim($extra['username']) : $user['username'],
            'nickname'  => isset($extra['nickname']) ? trim($extra['nickname']) : $user['nickname'],
            'realname'  => isset($extra['realname']) ? trim($extra['realname']) : $user['realname'],
            'avatar'    => isset($extra['avatar']) ? trim($extra['avatar']) : $user['avatar'],
            'phone'     => isset($extra['phone']) ? trim($extra['phone']) : $user['phone'],
            'email'     => isset($extra['email']) ? trim($extra['email']) : $user['email'],
            'age'       => isset($extra['age']) ? trim($extra['age']) : $user['age'],
            'gender'    => isset($extra['gender']) ? intval($extra['gender']) : $user['gender'],
            'group_id'  => isset($extra['group_id']) ? intval($extra['group_id']) : $user['group_id'],
            'is_admin'  => isset($extra['is_admin']) ? intval($extra['is_admin']) : $user['is_admin'],
            'update_time'=> time(),
            'fuser_id'  => isset($extra['fuser_id']) ? intval($extra['fuser_id']) : $user['fuser_id'],
            'status'    => isset($extra['status']) ? intval($extra['status']) : $user['status'],
        ];
        if (isset($extra['password']) && $extra['password']) {
            $data['password'] = $this->_passwordEncryption($password);
        }
        $result = db('user')->where(['user_id' => $userId])->update($data);
        if ($result === false) {
            $this->error = '系统出错';
            return FALSE;
        }
        return $userId;
    }
    /**
     * 检查登录用户名是否存在
     * @param string $username
     * @return number
     */
    public function _checkUsername($username = '')
    {
        $exist = db('user')->where(['username' => $username, 'is_del' => 0])->find();
        return $exist ? 1: 0;
    }
    /**
     * 密码加密
     * @param string $password
     * @return string
     */
    public function _passwordEncryption($password = '')
    {
        if (!$password) {
            $this->error = '密码不能为空';
        }
        return md5($password);
    }
    public function _checkFormat($extra = [])
    {
        $username = isset($extra['username']) ? trim($extra['username']) : '';
        $password = isset($extra['password']) ? trim($extra['password']) : '';
        $phone = isset($extra['phone']) ? trim($extra['phone']) : '';
        $email = isset($extra['email']) ? trim($extra['email']) : '';
        if ($phone == $username) {
            $exist = db('user')->where(['username' => $phone, 'is_del' => 0])->find();
            if ($exist) {
                $this->error = '手机号已存在';
                return FALSE;
            }
            $pattern = '/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/';
            if ($phone && !preg_match($pattern, $phone)) {
                $this->error = '手机号格式错误';
                return FALSE;
            }
        }
        //检查用户名格式
        $pattern = '/^[\w]{5,16}$/';
        if ($username) {
            $uncheckName = $extra && isset($extra['uncheck_name']) ? $extra['uncheck_name'] : 0;
            if (!$uncheckName && !preg_match($pattern, $username)) {
                $this->error = '登录用户名格式:5-16位字符长度,只能由英文数字下划线组成';
                return FALSE;
            }
            //检查登录用户名是否存在
            $exist = $this->_checkUsername($username);
            if ($exist) {
                $this->error = '登录用户名或手机号已经存在';
                return FALSE;
            }
        }
        //检查密码格式
        $pattern = '/^[a-zA-Z][\w@]{5,19}$/';
        if ($password && !preg_match($pattern, $password)) {
            $this->error = '登录密码格式:以字母开头，长度在6~20之间，只能包含字母、数字和下划线和@';
            return FALSE;
        }
        $pattern = '/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/';
        if ($phone && !preg_match($pattern, $phone)) {
            $this->error = '手机号格式错误';
            return FALSE;
        }
        $pattern = '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/';
        if ($email && !preg_match($pattern, $email)) {
            $this->error = 'email格式错误';
            return FALSE;
        }
        return TRUE;
    }
}