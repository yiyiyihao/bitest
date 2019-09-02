<?php
namespace app\api\controller;
use think\Facade\Request;

/**
 * 定时器执行接口
 * @author xiaojun
 */
class Timer extends ApiBase
{
    var $method;
    public function __construct($return = FALSE){
        if (!$return) {
            parent::__construct();
            $this->method = Request::instance()->action();
        }
    }
    protected function _checkPostParams()
    {
        $this->requestTime = time();
        $this->visitMicroTime = $this->_getMillisecond();//会员访问时间(精确到毫秒)
    }
    public function prize_old()
    {
        $time = time();
        $config = cache('win_data');
        $totalNum = isset($config['total_num']) ? $config['total_num']: 0;  //总中奖数量
        $realNum = isset($config['real_num']) ? $config['real_num']: 0;     //真实中奖用户数量
        if ($totalNum <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '未配置总中奖用户数量']);
        }
        $roundNum = $totalNum > $realNum ? $totalNum - $realNum : 0;    //需要随机手机号的虚拟用户数量
        //当日开始时间戳
        $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
        $validStartDay = '2018-10-23';
        $validEndDay = '2018-10-26';
        
        //活动开始时间戳
        $validStartTime = strtotime($validStartDay);
        //活动结束时间戳
        $validEndTime = strtotime($validEndDay);
        
        $startHour = 8;
        $endHour = 17;
        /**
         *  1. 活动时间：10月23日-26日，每天8:00-17:00
         2. 活动细则：每个整点抽奖，通过BI系统抽出5位前一小时到店的幸运会员，获取精美礼品！
         3. 中奖条件：当天到店的客户，在小程序上认证成为会员；
         4. 领奖流程：会员收到中奖短信，凭短信到我们展区前台领取礼品（礼品待定）
         */
        
        $msg = '当前时间:'.date('Y-m-d H:i:s').' 不在抽奖时间('.$validStartDay.' ~ '.$validEndDay.'，每天'.$startHour.':00~'.$endHour.':00)范围内';
        if ($time <= $validStartTime || $time > $validEndTime) {
            $this->_returnMsg(['code' => 1, 'msg' => $msg]);
        }
        //判断当前时间是否在整点执行范围内(整点-整点+10)
        $startTime = $dayBeginTime + $startHour*3600;
        $endTime = $dayBeginTime + $endHour*3600;
        $winModel = db('win_log');

        if ($time <= $startTime || $time > $endTime) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前时间:'.date('Y-m-d H:i:s').' 不在整点范围内']);
        }
        $timestamp = mktime(date("H"), 0, 0, date("m"), date("d"), date("y"));//整点时间戳
        $flag = $timestamp <= $time && $time <= $timestamp + 10;
        $flag = $timestamp <= $time && $time <= $timestamp + 1*60*50;
        if ($flag) {
            //获取所有已中奖的用户手机号
            $winphones = $winModel->column('phone');
            //判断真实中奖用户数量
            $list = [];
            if ($realNum > 0) {
                $lastTime = mktime(date("H")-1, 0, 0, date("m"), date("d"), date("y"));//整点时间戳
                $list = $this->getValidUsers($lastTime, $timestamp, $realNum);
                if (!$list) {
                    $list = [
                        [
                            'user_id' => 3,
                            'nickname' => '小君',
                            'phone' => '13760170785',
                        ]
                    ];
                }
                $realCount = $list ? count($list) : 0;
                if ($realCount < $realNum) {
                    $roundNum = $totalNum - $realCount;
                }
            }
            if ($roundNum > 0) {
                //获取随机生成手机号数量(虚拟用户)
                for ($i = 0; $i < $roundNum; $i++) {
                    $phone = $this->_getRandPhone();
                    $name = $this->_generateName();
                    $list[] = [
                        'nickname'  => $name,
                        'phone'     => $phone,
                    ];
                }
            }
            $phones = $phones1 = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    $phone = trim($value['phone']);
                    $nickname = isset($value['nickname']) ? trim($value['nickname']): '';
                    if ($value && $phone) {
                        $userId = isset($value['user_id']) ? intval($value['user_id']) : 0;
                        if ($userId) {
                            $nickname = isset($value['realname']) ? trim($value['realname']): $nickname;
                        }
                        //记录中奖结果(数据库插入)
                        $insert = [
                            'fuser_id'  => isset($value['fuser_id']) ? intval($value['fuser_id']) : 0,
                            'avatar'    => isset($value['avatar']) ? trim($value['avatar']) : '',
                            'user_id'   => $userId,
                            'nickname'  => $nickname,
                            'phone'     => $phone,
                            'add_time'  => time()
                        ];
                        $winId = $winModel->insertGetId($insert);
                        if ($winId === false) {
                            $this->_returnMsg(['code' => 1, 'msg' => '数据库执行异常']);
                        }
                        if ($userId) {
                            //真实用户发送短信+小程序通知
                            $this->_sendNotify('all', $value, $winId);
                            $phones[] = $phone;
                        }else{
                            $phones1[] = $phone;
                        }
                    }
                }
            }
            $phoneStr = $phones ? array_implode($phones) : ' - ';
            $phone1Str = $phones1 ? array_implode($phones1) : ' - ';
            $this->_returnMsg(['code' => 0, 'msg' => '中奖用户手机号:'.$phoneStr.' , 未发送通知手机号：'.$phone1Str]);
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => $msg]);
        }
    }
    public function prize()
    {
        $time = time();
        $config = cache('win_data');
        $totalNum = isset($config['total_num']) ? $config['total_num']: 0;  //总中奖数量
        $realNum = isset($config['real_num']) ? $config['real_num']: 0;     //真实中奖用户数量
        if ($totalNum <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '未配置总中奖用户数量']);
        }
        $roundNum = $totalNum > $realNum ? $totalNum - $realNum : 0;    //需要随机手机号的虚拟用户数量
        //当日开始时间戳
        $dayBeginTime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
        $validStartDay = '2018-10-23';
        $validStartDay = '2018-10-22';
        $validEndDay = '2018-10-26';
        
        //活动开始时间戳
        $validStartTime = strtotime($validStartDay);
        //活动结束时间戳
        $validEndTime = strtotime($validEndDay);
        
        $startHour = 18;
        
        $msg = '当前时间:'.date('Y-m-d H:i:s').' 不在抽奖时间('.$validStartDay.' ~ '.$validEndDay.'，每天'.$startHour.':00)范围内';
        if ($time <= $validStartTime || $time > $validEndTime) {
            $this->_returnMsg(['code' => 1, 'msg' => $msg]);
        }
        /**
         *  1. 活动时间：10月23日-26日，每天8:00-17:00
         2. 活动细则：每个整点抽奖，通过BI系统抽出5位前一小时到店的幸运会员，获取精美礼品！
         3. 中奖条件：当天到店的客户，在小程序上认证成为会员；
         4. 领奖流程：会员收到中奖短信，凭短信到我们展区前台领取礼品（礼品待定）
         */
//         $validStartDay = '2018-10-23';
//         $validStartDay = '2018-10-18';
//         $validEndDay = '2018-10-26';
//         $validStartTime = strtotime($validStartDay);
//         $validEndTime = strtotime($validEndDay);
//         $msg = '当前时间:'.date('Y-m-d H:i:s').' 不在抽奖时间('.$validStartDay.'-'.$validEndDay.'，每天8:00-17:00)范围内';
//         if ($time <= $validStartTime || $time > $validEndTime) {
//             $this->_returnMsg(['code' => 1, 'msg' => $msg]);
//         }
//         //判断当前时间是否在整点执行范围内(整点-整点+30)
//         $startTime = $dayBeginTime + 8*3600;
//         $endTime = $dayBeginTime + 17*3600;
        
               
//         #TODO DELETE
// //         $endTime = $dayBeginTime + 18*3600;
//         if ($time <= $startTime || $time > $endTime) {
//             $this->_returnMsg(['code' => 1, 'msg' => '当前时间:'.date('Y-m-d H:i:s').' 不在整点范围内']);
//         }
//         $timestamp = mktime(date("H"), 0, 0, date("m"), date("d"), date("y"));//整点时间戳
//         $flag = $timestamp <= $time && $time <= $timestamp + 30;
        
        /**
         * 1. 展会期间，我们每天会在参观值得看展区的客户中抽取5位幸运客户，赠送超值礼品卡！
         2. 一等奖 1名 100元礼品卡
                             二等奖 2名 50元礼品卡
                             三等奖 3名 30元礼品卡
         3.  奖品由系统发放至“会员卡-卡券”栏目，礼品卡可用于换购商城商品，详情请参考相关卡券说明。
         */
        $winModel = db('win_log');
        $timestamp = mktime(date("H"), 0, 0, date("m"), date("d"), date("y"));//整点时间戳
        //判断今日是否已经完成抽奖
        $exist = $winModel->where(['add_time' => ['>=', $dayBeginTime]])->find();
        if ($exist) {
            $this->_returnMsg(['code' => 1, 'msg' => '今日已完成抽奖~~']);
        }
        $flag = $startHour == date("H") && $timestamp <= $time && $time <= $timestamp + 5;        //18点的执行范围(整点5秒范围内执行)
        #TODO DELETE
//         $startHour = 14;
//         $flag = $startHour == date("H") && $timestamp <= $time && $time <= $timestamp + 1*60*60;        //18点的执行范围(整点5秒范围内执行)
        if ($flag) {
            //获取所有已中奖的用户手机号
            $winphones = $winModel->column('phone');
            //判断真实中奖用户数量
            $list = [];
            if ($realNum > 0) {
                $lastTime = mktime(date("H")-1, 0, 0, date("m"), date("d"), date("y"));//整点时间戳
                $list = $this->getValidUsers($lastTime, $timestamp, $realNum);
                $realCount = $list ? count($list) : 0;
                if ($realCount < $realNum) {
                    $roundNum = $totalNum - $realCount;
                }
            }
            if ($roundNum > 0) {
                //获取随机生成手机号数量(虚拟用户)
                for ($i = 0; $i < $roundNum; $i++) {
                    $phone = $this->_getRandPhone();
                    $name = $this->_generateName();
                    $list[] = [
                        'nickname'  => $name,
                        'phone'     => $phone,
                    ];
                }
            }
            $phones = $phones1 = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    $phone = trim($value['phone']);
                    $nickname = isset($value['nickname']) ? trim($value['nickname']): '';
                    if ($value && $phone) {
                        $userId = isset($value['user_id']) ? intval($value['user_id']) : 0;
                        if ($userId) {
                            $nickname = isset($value['realname']) ? trim($value['realname']): $nickname;
                        }
                        //记录中奖结果(数据库插入)
                        $insert = [
                            'fuser_id'  => isset($value['fuser_id']) ? intval($value['fuser_id']) : 0, 
                            'avatar'    => isset($value['avatar']) ? trim($value['avatar']) : '', 
                            'user_id'   => $userId,
                            'nickname'  => $nickname, 
                            'phone'     => $phone, 
                            'add_time'  => time()
                        ];
                        $winId = $winModel->insertGetId($insert);
                        if ($winId === false) {
                            $this->_returnMsg(['code' => 1, 'msg' => '数据库执行异常']);
                        }
                        if ($userId) {
                            //真实用户发送短信+小程序通知
                            $this->_sendNotify('all', $value, $winId);
                            $phones[] = $phone;
                        }else{
                            $phones1[] = $phone;
                        }
                    }
                }
            }
            $phoneStr = $phones ? array_implode($phones) : ' - ';
            $phone1Str = $phones1 ? array_implode($phones1) : ' - ';
            $this->_returnMsg(['code' => 0, 'msg' => '中奖用户手机号:'.$phoneStr.' , 未发送通知手机号：'.$phone1Str]);
        }else{
            $this->_returnMsg(['code' => 1, 'msg' => $msg]);
        }
    }
    private function _sendNotify($type = 'all', $user = [], $winId = 0)
    {
        $phone = $touser = $username = $formId = '';
        
        $userId     = isset($user['user_id']) ? intval($user['user_id']) : 0;
        $phone      = isset($user['phone']) ? trim($user['phone']) : '';
        $realname   = isset($user['realname']) ? trim($user['realname']) : (isset($user['nickname']) ? trim($user['nickname']) : '');
        
        $field = 'FT.token_id, U.phone, U.realname, U.nickname, FT.face_token, FT.fuser_id, U.user_id, FU.avatar';
        
        if (in_array($type, ['all', 'wechat_applet']) && $userId) {
            $map = ['user_id' => $userId, 'third_type' => 'wechat_applet', 'is_del' => 0];
            $uData = db('user_data')->where($map)->find();
            $udataId    = $uData ? $uData['udata_id'] : 0;
            $touser     = $uData ? trim($uData['third_openid']) : '';
            $formData   = $uData ? $uData['form_data'] : '';
            if ($uData && $formData) {
                $form = json_decode($formData, 1);
                $addTime = isset($form['time']) ? trim($form['time']) : 0;
                if ($addTime + 7*24*60*60-1 > time()) {
                    //formid:允许开发者向用户在7天内推送有限条数的模板消息（1次提交表单可下发1条，多次提交下发条数独立，相互不影响）
                    $formId = isset($form['formid']) ? trim($form['formid']) : '';
                }
            }
            $username = $realname ? $realname : ($uData['nickname'] ? $uData['nickname'] : '尊敬的用户');
        }
        $prizeName  = '精美礼品一份';
        $validTime  = '当天内';
        $address    = '前台';
        $notifyModel = db('log_notify');
        $tplType = 'winning_notice';
        $extra['win_id'] = $winId;
        switch ($type) {
            case 'all':             //全部
            case 'wechat_applet':   //小程序通知
                if ($touser && $formId) {
                    $templateId = 'vz21TTIZUi5_dmOi0dc66S0IrnJ-Z2u6Yf0ZivvAt-Y';
                    $post = [
                        'touser'        => $touser,
                        'template_id'   => $templateId,
                        'page'          => '/pages/user/activity',
                        'form_id'       => $formId,
                        'data' => [
                            'keyword1' => [
                                'value' => $username,
                            ],
                            'keyword2' => [
                                'value' => $prizeName,
                            ],
                            'keyword3' => [
                                'value' => $validTime,
                            ],
                            'keyword4' => [
                                'value' => date('Y-m-d'),
                            ],
                        ],
                    ];
                    $result = $this->_sendWechatAppletNotify($post, $tplType, $extra);
                    if ($result && isset($udataId) && $udataId) {
                        db('user_data')->where(['udata_id' => $udataId])->update(['form_data' => '']);
                    }
                }
                if($type != 'all'){
                    break;
                }
            case 'all': //全部
            case 'sms': //短信通知
                if ($phone) {
                    $params = [
                        'prize'     => $prizeName,
                        'time'      => $validTime,
                        'address'   => $address,
                    ];
                    $this->_sendSmsNotify($phone, $params, $tplType, $extra);
                }
            break;
            default:
                return FALSE;
            break;
        }
        return TRUE;
    }
    public function  test()
    {
        for ($i = 0; $i < 50; $i++) {
            echo $this->_generateName(). '<br>';
        }
        die();
    }
    
    public function getValidUsers($lastTime, $timestamp, $realNum = 0)
    {
        //1.获取已经中奖的用户手机号
        $winModel = db('win_log');
        $winphones = $winModel->column('phone');
        //获取已注册的用户且在上一小时内抓拍到的用户
        $where = [
            ['FT.fuser_id'   ,'>', 0],                                    //有人脸信息的用户
//             ['FT.capture_time' ,'between', [$lastTime, $timestamp]],   //上一小时内抓拍到的用户
            ['FT.add_time'   ,'between', [$lastTime, $timestamp]],        //上一小时内抓拍到的用户
            ['U.user_id'     ,'>', 0],                                    //已注册的用户
            ['U.phone'       ,'NEQ', ''],                                 //注册手机号不为空的用户
            ['FT.device_code','NEQ', ''],
        ];
        if ($winphones) {
//             $where['U.phone'] = ['NOT IN', $winphones]; //未中奖的用户
        }
        $tokenModel = db('face_token');
        $join = [
//             ['face_user FU', 'FU.fuser_id = FT.fuser_id', 'INNER'],
//             ['user U', 'FT.fuser_id = U.fuser_id', 'INNER'],
            ['face_user FU', 'FU.fuser_id = FT.fuser_id', 'LEFT'],
            ['user U', 'FT.fuser_id = U.fuser_id', 'LEFT'],
        ];
        $field = 'FT.token_id, U.phone, U.realname, U.nickname, FT.face_token, FT.fuser_id, U.user_id, FU.avatar';
        if ($realNum > 0) {
            $list = $tokenModel->alias('FT')->join($join)->orderRaw('RAND()')->limit(0, $realNum)->where($where)->group('FT.fuser_id')->column($field);
        }else{
            $list = $tokenModel->alias('FT')->join($join)->where($where)->group('FT.fuser_id')->column($field);
        }
        return $list;
    }
    
    /**
     * 随机生成符合条件的手机号
     * @return string
     */
    private function _getRandPhone()
    {
        //匹配手机号的正则表达式 #^(13[0-9]|14[47]|15[0-35-9]|17[6-8]|18[0-9])([0-9]{8})$#
        $arr = array(
            130,131,132,133,134,135,136,137,138,139,
            144,147,
            150,151,152,153,155,156,157,158,159,
            176,177,178,
            180,181,182,183,184,185,186,187,188,189,
        );
        $phone = $arr[array_rand($arr)].mt_rand(1000,9999).mt_rand(1000,9999);
        //判断生成手机号是否存在于奖品库中
        $exist = db('win_log')->where(['phone' => $phone])->find();
        if ($exist) {
            return $this->_getRandPhone();
        }
        //判断生成的手机号是否存在用户注册手机号中
        $exist = db('user')->where(['phone' => $phone])->find();
        if ($exist) {
            return $this->_getRandPhone();
        }
        return $phone;
    }
    //随机生成用户名
    private function _generateName(){
        $arrXing = $this->getXingList();
        $arrXing = ['任'];
//         pre($arrXing);
        $numbXing = count($arrXing);
        $arrMing = $this->getMingList();
        $numbMing =  count($arrMing);
        
        $Xing = $arrXing[mt_rand(0, $numbXing-1)];
        $count1 = mb_strlen($Xing);
        if ($count1 == 2) {
            $index = 1;
        }else{
            $index = rand(1, 2);
        }
        $Ming = '';
        for ($i = 0; $i < $index; $i++) {
            $Ming .= $arrMing[mt_rand(0, $numbMing-1)];
        }
        $name = $Xing.$Ming;
        
        //判断生成的用户名是否存在于奖品库中
        $exist = db('win_log')->where(['nickname' => $name])->find();
        if ($exist) {
            return $this->_generateName();
        }
        //判断生成的用户名是否存在用户注册用户名中
        $exist = db('user')->where(['realname' => $name])->whereOr(['nickname' => $name])->find();
        if ($exist) {
            return $this->_generateName();
        }
        return $name;
        
    }
    //获取姓氏
    private function getXingList(){
        $arrXing = array('赵','钱','孙','李','周','吴','郑','王','冯','陈','褚','卫','蒋','沈','韩','杨','朱','秦','尤','许','何','吕','施','张','孔','曹','严','华','金','魏','陶','姜','戚','谢','邹',
            '喻','柏','水','窦','章','云','苏','潘','葛','奚','范','彭','郎','鲁','韦','昌','马','苗','凤','花','方','任','袁','柳','鲍','史','唐','费','薛','雷','贺','倪','汤','滕','殷','罗',
            '毕','郝','安','常','傅','卞','齐','元','顾','孟','平','黄','穆','萧','尹','姚','邵','湛','汪','祁','毛','狄','米','伏','成','戴','谈','宋','庞','熊','纪','舒','屈','项','祝',
            '董','梁','杜','阮','蓝','闵','季','贾','路','娄','江','童','颜','郭','梅','盛','林','钟','徐','邱','骆','高','夏','蔡','田','樊','胡','凌','霍','虞','万','支','柯','管','卢','莫',
            '柯','房','缪','应','宗','丁','宣','邓','单','杭','洪','包','诸','左','石','崔','吉','龚','程','邢','裴','陆','荣','翁','荀','于','惠','甄','曲','封','伊',
            '宁','仇','甘','武','符','刘','景','詹','龙','叶','幸','司','黎','溥','印','怀','蒲','邰','从','索','赖','卓','池','乔','胥','闻','莘','党','翟','谭','贡','劳','姬','申',
            '冉','宰','雍','桑','寿','通','燕','浦','尚','农','温','别','庄','晏','柴','瞿','阎','连','习','容','向','古','易','廖','庾','终','步','都','耿','满','弘','匡','文',
            '寇','东','欧','利','师','巩','聂','关','荆','司马','上官','欧阳','诸葛','东方',
            '宇文','长孙','慕容','司徒');
        return $arrXing;
        
    }
    //获取名字
    private function getMingList(){
        $arrMing = array('伟','刚','勇','毅','俊','峰','强','军','平','保','东','文','辉','力','明','永','健','世','广','志','义','兴','良','海','山','仁','波','宁','贵','福','生','龙','元','全'
            ,'国','胜','学','祥','才','发','武','新','利','清','飞','彬','富','顺','信','子','杰','涛','昌','成','康','星','光','天','达','安','岩','中','茂','进','林','有','坚','和','彪','博','诚'
            ,'先','敬','震','振','壮','会','思','群','豪','心','邦','承','乐','绍','功','松','善','厚','庆','磊','民','友','裕','河','哲','江','超','浩','亮','政','谦','亨','奇','固','之','轮','翰'
            ,'朗','伯','宏','言','若','鸣','朋','斌','梁','栋','维','启','克','伦','翔','旭','鹏','泽','晨','辰','士','以','建','家','致','树','炎','德','行','时','泰','盛','雄','琛','钧','冠','策'
            ,'腾','楠','榕','风','航','弘','秀','娟','英','华','慧','巧','美','娜','静','淑','惠','珠','翠','雅','芝','玉','萍','红','娥','玲','芬','芳','燕','彩','春','菊','兰','凤','洁','梅','琳'
            ,'素','云','莲','真','环','雪','荣','爱','妹','霞','香','月','莺','媛','艳','瑞','凡','佳','嘉','琼','勤','珍','贞','莉','桂','娣','叶','璧','璐','娅','琦','晶','妍','茜','秋','珊','莎'
            ,'锦','黛','青','倩','婷','姣','婉','娴','瑾','颖','露','瑶','怡','婵','雁','蓓','纨','仪','荷','丹','蓉','眉','君','琴','蕊','薇','菁','梦','岚','苑','婕','馨','瑗','琰','韵','融','园'
            ,'艺','咏','卿','聪','澜','纯','毓','悦','昭','冰','爽','琬','茗','羽','希','欣','飘','育','滢','馥','筠','柔','竹','霭','凝','晓','欢','霄','枫','芸','菲','寒','伊','亚','宜','可','姬'
            ,'舒','影','荔','枝','丽','阳','妮','宝','贝','初','程','梵','罡','恒','鸿','桦','骅','剑','娇','纪','宽','苛','灵','玛','媚','琪','晴','容','睿','烁','堂','唯','威','韦','雯','苇','萱'
            ,'阅','彦','宇','雨','洋','忠','宗','曼','紫','逸','贤','蝶','菡','绿','蓝','儿','翠','烟');
        return $arrMing;
    }
    /**
     * 处理接口返回信息
     */
    protected function _returnMsg($data, $echo = TRUE){
        $result = parent::_returnMsg($data);
        $responseTime = $this->_getMillisecond() - $this->visitMicroTime;//响应时间(毫秒)
        $addData = [
            'request_time'  => $this->requestTime,
            'return_time'   => time(),
            'method'        => $this->method ? $this->method : '',
            'return_params' => $result,
            'response_time' => $responseTime,
            'error'         => isset($data['code']) ? intval($data['code']) : 0,
        ];
        $apiLogId = db('apilog_timer')->insertGetId($addData);
        exit();
    }
}    