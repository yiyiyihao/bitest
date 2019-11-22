<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据 
//         Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
//         Gateway::sendToAll("$client_id login\r\n");

    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($clientId, $message)
   {
       // 客户端传递的是json数据
       $messageData = json_decode($message, true);
       if(!$messageData)
       {
           return ;
       }
       // 根据类型执行不同的业务
       switch($messageData['type'])
       {
           case 'notice':   //公告推送
               break;
           case 'pong':     //心跳监测
               break;
           case 'login':    //链接登录
               $uid = $messageData['id'];
               // 将当前链接与uid绑定
               Gateway::bindUid($clientId, $uid);

               // 判断是否有factoryRoom 厂商下级管理用户
               if(isset($messageData['factoryRoom']))
               {
                   $factoryRoom = $messageData['factoryRoom'];
                   //如果有房间号,加入到房间
                   Gateway::joinGroup($clientId, $factoryRoom);
               }
               // 判断是否有storeType 1厂商 2渠道商 3零售商/零售商 4服务商
               if(isset($messageData['storeType']))
               {
                   $storeType = $messageData['storeType'];
                   //如果有房间号,加入到房间
                   Gateway::joinGroup($clientId, $storeType);
               }
               // 判断是否有storeRoom 角色小群组 Example: 渠道管理员及渠道各角色管理用户
               if(isset($messageData['storeRoom']))
               {
                   $storeRoom = $messageData['storeRoom'];
                   //如果有房间号,加入到房间
                   Gateway::joinGroup($clientId, $storeRoom);
               }
               $returnMessage = [
                   'type'       =>  $messageData['type'],
                   'uid'        =>  $uid,
                   'clientId'   =>  $clientId,
                   'time'       =>  date('Y-m-d H:i:s')
               ];
               //给当前用户发送登录回执
               //W#Gateway::sendToCurrentClient(json_encode($returnMessage));
               break;
           case 'worker':   //工单推送
               break;
           case 'order':    //订单推送
               break;
           case 'message':  //消息推送
               break;
       }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       // 向所有人发送 
       //GateWay::sendToAll("$client_id logout\r\n");
   }
}
