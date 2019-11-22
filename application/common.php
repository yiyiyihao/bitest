<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function get_service_order_status($service = array()) {
    if (!$service){
        return FALSE;
    }
    if ($service['status'] == 2 || (isset($service['service_sku_id']) && $service['status'] == 1)){
        $status = '已撤销';
    }else{
        if ($service['apply_status'] == -1) {
            $status = '未通过';
        }else{
            if ($service['return_status'] == 2 || (isset($service['service_sku_id']) && $service['return_status'] == 1)) {
                $status = '已退款';
            }elseif ($service['return_status'] == 1){
                $status = '部分退款';
            }elseif ($service['apply_status'] == 0){
                $status = '待审核';
            }else{
                $status = '已通过';
            }
        }
    }
    return $status;
}
function array_implode($array = [])
{
    $array = $array ? array_filter($array) : [];
    $array = $array ? array_unique($array) : [];
    $implode = $array ? implode(',', $array) : '';
    return $implode;
}
function array_explode($str = '')
{
    $array = $str ? explode(',', $str) : '';
    $array = $array ? array_filter($array) : [];
    $array = $array ? array_unique($array) : [];
    return $array;
}

/**
 *
 * 拼接签名字符串
 * @param array $urlObj
 *
 * @return 返回已经拼接好的字符串
 */
function to_url_params($urlObj)
{
    $buff = "";
    foreach ($urlObj as $k => $v)
    {
        if($k != "sign"){
            $buff .= $k . "=" . $v . "&";
        }
    }
    $buff = trim($buff, "&");
    return $buff;
}
/*
 *xml to array
 */
function xml_to_array($xml)
{
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}
/*
 *array to xml
 */
function array_to_xml($arr)
{
    $xml = "<xml>";
    foreach ($arr as $key=>$val)
    {
        $xml.="<".$key.">".$val."</".$key.">";
    }
    $xml.="</xml>";
    return $xml;
}
/**
 *
 * 产生随机字符串，不长于32位
 * @param int $length
 * @return 产生的随机字符串
 */
function get_nonce_str($length = 32, $type = 1)
{
    if ($type == 1) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }else{
        $chars = "0123456789";
    }
    
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {
        $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}
function object_array($array) {
    if(is_object($array)) {
        $array = (array)$array;
    } if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}  
/**
 * 获取订单状态
 * @param  $order    : 订单信息
 * @return [string]
 */
function get_order_status($order = array()) {
    $arr = array();
    switch ($order['status']) {
        case 2: // 已取消
            $arr['now'] = 'cancel';
            $arr['wait'] = 'cancel';
            $arr['status_text'] = ch_order_status($arr['wait']);
            break;
        case 3: // 已回收
            $arr['now'] = 'recycle';
            $arr['wait'] = 'recycle';
            $arr['status_text'] = ch_order_status($arr['wait']);
            break;
        case 4: // 前台用户已删除
            $arr['now'] = 'delete';
            break;
        default:    // 正常状态
            if (!isset($order['pay_type'])) {
                $order['pay_type'] = 1;
            }
            if ($order['pay_type'] == 1 && $order['pay_status'] == 0) {
                $arr['now'] = 'create'; // 创建订单
                $arr['wait'] = ($order['pay_type'] == 1) ? 'load_pay' : 'load_delivery';
            }elseif ($order['pay_type'] == 1 && $order['pay_status'] == 1 && $order['delivery_status'] == 0) {
                $arr['now'] = 'pay';    // 已支付
                $arr['wait'] = 'load_delivery';
            }elseif ($order['delivery_status'] == 1 && $order['finish_status'] == 0) {
                $arr['now'] = 'part_delivery';   // 部分发货
                $arr['wait'] = 'part_delivery';   
            }elseif ($order['delivery_status'] == 2 && $order['finish_status'] == 0) {
                $arr['now'] = 'all_delivery';   // 已发货
                $arr['wait'] = 'load_finish';
            }elseif ($order['delivery_status'] != 0 && $order['finish_status'] == 1) {
                $arr['now'] = 'part_finish';   // 部分完成
                $arr['wait'] = 'part_delivery';
            }elseif ($order['delivery_status'] == 2 && $order['finish_status'] == 2) {
                $arr['now'] = 'all_finish';   // 已完成
                $arr['wait'] = 'all_finish';
            }
            $arr['status_text'] = ch_order_status($arr['wait']);
            break;
    }
    return $arr;
}

/**
 * 获取状态中文信息
 * @param  string $ident 标识
 * @return [string]
 */
function ch_order_status($ident) {
    $arr = array(
        'cancel'        => '已取消',
        'recycle'       => '已回收',
        'delete'        => '已删除',
        'create'        => '创建订单',
        'load_pay'      => '待付款',
        'load_delivery' => '待发货',
        'pay'           => '已付款',
        'part_delivery' => '部分发货',
        'all_delivery'  => '已发货',
        'load_finish'   => '待收货',
        'part_finish'   => '部分完成',
        'all_finish'    => '已完成',
        'receive'       => '已收货',
        
        // 前台时间轴
        'time_cancel'   => '取消订单',
        'time_recycle'  => '回收订单',
        'time_create'   => '提交订单',
        'time_pay'      => '确认付款',
        'time_delivery' => '商品发货',
        'time_finish'   => '确认收货',
    );
    return isset($arr[$ident]) ? $arr[$ident] : '';
}


/**
 * 二维数组排序
 * @param array $array 排序的数组
 * @param string $key 排序主键
 * @param string $type 排序类型 asc|desc
 * @param bool $reset 是否返回原始主键
 * @return array
 */
function array_order($array, $key, $type = 'asc', $reset = false)
{
    if (empty($array) || !is_array($array)) {
        return $array;
    }
    foreach ($array as $k => $v) {
        $keysvalue[$k] = $v[$key];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    $i = 0;
    foreach ($keysvalue as $k => $v) {
        $i++;
        if ($reset) {
            $new_array[$k] = $array[$k];
        } else {
            $new_array[$i] = $array[$k];
        }
    }
    return $new_array;
}

function array_trim($array)
{
    foreach ($array as $key => &$value) {
        $value = trim($value);
    }
    return $array;
}

/**
 * 系统断点调试方法
 */
function pre($array, $undie = 0)
{
    echo '<pre>';
    print_r($array);
    echo '</pre>';
    if($undie){
        return ;
    }
    exit();
}

/**
 * 推送给客户端
 */
function sendToClient($message){
    // 建立socket连接到内部推送端口
    $client = stream_socket_client(config('setting.workerman_server'), $errno, $errmsg, 1);
    fwrite($client, $message."\n");
}

/**
 * 格式化性别
 */
 function gender_text($gender){
     switch ($gender){
         case 1:
             return '男士';
             break;
         case 2:
             return '女士';
             break;
         default:
             return '未知';
             break;
     }
 }
/**
* 格式化门店类型
*/
function storeType($type){
 switch ($type){
     case 1:
         return '实体门店';
         break;
     case 2:
         return '虚拟门店';
         break;
     default:
         return '商场';
         break;
 }
}
 

/**
 * 格式化时间戳
 * @param int $timediff 时间戳
 * @param number $return_type 返回数据类型 1：带单位字符串 2：返回分钟数 3： 数组
 */
function timediff($timediff, $return_type = 1)
{
    //计算天数
    $days = trim(intval($timediff/86400));
    //计算小时数
    $remain = $timediff%86400;
    $hours = trim(intval($remain/3600));
    //计算分钟数
    $remain = $remain%3600;
    $mins = trim(intval($remain/60));
    //计算秒数
    $secs = trim($remain%60);
    $return = '';
    if ($return_type == 1) {
        if($days){
            $return = $days.'天';
        }
        if($hours){
            $return .= $return ? ''.$hours.'时' : $hours.'时';
        }
        if($mins){
            $return .= $return ? ''.$mins.'分' : $mins.'分';
        }
        if($secs){
            $return .= $return ? ''.$secs.'秒' : $secs.'秒';
        }
    }elseif($return_type == 2){//返回分钟数
        $return = round($timediff%86400/60, 2);
    }else{
        $return = ["day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs];
    }
    return $return;
}
/**
 * curl函数
 * @url :请求的url
 * @post_data : 请求数组
 **/
function curl_post_https($url, $post_data){
    if (empty($url)){
        return false;
    }
    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 0);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 信任任何证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 检查证书中是否设置域名（为0也可以，就是连域名存在与否都不验证了）
    //设置post数据
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
    $data = curl_exec($curl);
    $error = '';
    if($data === false){
        $error = curl_error($curl);
        echo 'Curl error: ' . $error;
    }
    //关闭URL请求
    curl_close($curl);
    $json = json_decode($data,true);
    if (empty($json)){
        return $data = $data ? $data : $url.':'.$error;
    }
    //显示获得的数据
    return $json;
}
/**
 * curl函数
 * @url :请求的url
 * @post_data : 请求数组
 **/
function curl_post($url, $post_data,$headers = []){
    if (empty($url)){
        return false;
    }
    $post_data = json_encode($post_data);
    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    //设置headers
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    //设置post数据
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);



    //执行命令
    $data = curl_exec($curl);
    $error = '';
    if($data === false){
        $error = curl_error($curl);
        echo 'Curl error: ' . $error;
    }
    $result=curl_getinfo($curl);
//    pre($result, 1);
//    pre($data);
//    //关闭URL请求
    curl_close($curl);
    $json = json_decode($data,true);
    if (empty($json)){
        return $data = $data ? $data : $url.':'.$error;
    }
    return $json;
}

//参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
function curl_request($url,$post='',$headers=[],$cookie='', $returnCookie=0,$delete = ''){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
    //设置headers
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }else if($delete){
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    if($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if($returnCookie){
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie']  = substr($matches[1][0], 1);
        $info['content'] = $body;
        return $info;
    }else{
        return $data;
    }
}

//获取最大的表情
function face_get_max($arr){
    $max = 0;
    $v = 0;
    foreach ($arr as $key => $val){
        if ($val > $v){
            $max = $key;
            $v = $val;
        }
    }
    return $max;
}

/**
 * 使用”.“号从嵌套数组中获取值，如果数组key中包含“.”号则不适用
 * @param $array
 * @param $key
 * @param null $default
 * @return mixed
 * @author hybo
 */
function array_get($array, $key, $default = null)
{
    if (is_null($key)) {
        return $array;
    }

    if (isset($array[$key])) {
        return $array[$key];
    }

    foreach (explode('.', $key) as $segment) {
        if (! is_array($array) || ! array_key_exists($segment, $array)) {
            return value($default);
        }

        $array = $array[$segment];
    }
    return $array;
}


function _checkStoreVisit($sid = 0, $storeSuperVisit = TRUE, $clerkVisit = TRUE)
{
    $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
    $storeId = cache($authorization)['admin_user']['store_id'];
    $storeIds = cache($authorization)['admin_user']['store_ids'];
    $groupId = cache($authorization)['admin_user']['group_id'];

    $storeId = $sid ? $sid : $storeId;
    if ($storeId) {
        if ($storeId && in_array($groupId, [STORE_SUPER_ADMIN, STORE_MANAGER])) {
            if ($groupId == STORE_SUPER_ADMIN) {
                $childs = db('store')->where(['is_del' => 0, 'status' => 1, 'parent_id' => $storeId])->column('store_id');
                if ($storeSuperVisit && $storeId == $sid) {
                    $childs[] = $storeId;
                }
                if ($sid && !in_array($sid, $childs)) {
                    $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                    //$this->error(lang('NO ACCESS'));
                }
                if (!in_array($storeId, $childs)) {
                    $childs[] = $storeId;
                }
                return $childs;
            }else{
                if ($storeIds && !in_array($storeId, $storeIds)) {
                    $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                    //$this->error(lang('NO ACCESS'));
                }
                return $storeIds;
            }
        }elseif ($groupId == SYSTEM_SUPER_ADMIN || $groupId == EXPERIENCER){
            return TRUE;
        }elseif ($groupId == STORE_CLERK){
            if ($sid && $sid != $storeId) {
                $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                //$this->error(lang('NO ACCESS'));
            }
            if (!$clerkVisit) {
                $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
                //$this->error(lang('NO ACCESS'));
            }
            return $storeId;
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => 'NO ACCESS']);die;
            //$this->error(lang('NO ACCESS'));
        }
    }else{
        return FALSE;
    }
}

function get_menu_tree($data = [], $p_id = 0, $level = 0)
{
    $tree = [];
    if ($data && is_array($data)) {
        foreach ($data as $v) {
            if ($v['p_menu_index'] == $p_id) {
                if($p_id == 0){
                    $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                        'title' => lang($v['title']),
                        'index' => $v['menu_index'],
//                    'menu' => $v['title'],
                        'menuItemList' => get_menu_tree($data, $v['menu_index'], $level + 1),
                    ];
                }else{
                    $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                        'name' => lang($v['title']),
                        'index' => $v['menu_index'],
                        'icon' => $v['icon'],
//                    'submenu' => $v['title'],
                        'path' => $v['path'],
                    ];
                }

            }
        }
    }
    return $tree;
}


/**
 * 二极分类树 getTree($categories)
 * @param array $data
 * @param int $parent_id
 * @param int $level
 * @return array
 */
function get_tree($data = [], $p_id = 0, $level = 0)
{
    $tree = [];
    if ($data && is_array($data)) {
        foreach ($data as $v) {
            if ($v['p_id'] == $p_id) {
                if($p_id == 0){
                    $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                        'name' => $v['title'],
                        'value' => $v['route'],
//                    'menu' => $v['title'],
                        'list' => $this->get_tree($data, $v['id'], $level + 1),
                    ];
                }else{
                    $tree[] = [
//                    'id' => $v['id'],
//                    'level' => $level,
//                    'title' => $v['title'],
//                    'p_id' => $v['p_id'],
                        'name' => $v['title'],
                        'value' => $v['route'],
//                    'submenu' => $v['title'],
                        'type' => 'single',
                    ];
                }

            }
        }
    }
    return $tree;
}