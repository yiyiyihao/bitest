<?php
namespace app\common\service;
use app\common\service\Gateway;

//消息推送底层控制器
class PushBase
{
    public function __construct(){
        
    }
    /**
     * 绑定用户
     */
    public function bind(){
        // 假设用户已经登录，用户uid和群组id在session中
        $uid      = $_SESSION['uid'];
        $group_id = $_SESSION['group'];
        $client_id = '';
        // client_id与uid绑定
        Gateway::bindUid($client_id, $uid);
        // 加入某个群组（可调用多次加入多个群组）
        Gateway::joinGroup($client_id, $group_id);
    }
    
    /**
     * 发送消息给指定用户
     * @param $uid
     * @param $message
     */
    public function sendToUid($uid, $message){
        // 向任意uid的网站页面发送数据
        Gateway::sendToUid($uid, $message);
    }
    
    /**
     * 发送消息给群组内的所有人
     */
    public function sendToGroup($group, $message){
//        Gateway::sendToGroup($group, $message);
        Gateway::sendToAll($message);
//        Gateway::sendToUid('uid1', $message);
    }
    
    /**
     * 发送消息
     */
    public function sendMessage(){        
        // 向任意uid的网站页面发送数据
        //Gateway::sendToUid($uid, $message);
        // 向任意群组的网站页面发送数据
        //Gateway::sendToGroup($group, $message);
    }
}