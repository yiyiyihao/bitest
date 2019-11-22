<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/1/28 0028
 * Time: 18:04
 */
namespace app\api\controller\v1\person;

use app\api\controller\Api;
use think\Request;
use think\facade\Log;

//后台数据接口页
class Person extends Api
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function personlist()
    {
        $params = $this -> postParams;
        $page   = !empty($params['page']) ? intval($params['page']) : 1;
        $size   = !empty($params['size']) ? intval($params['size']) : 10;
        
        $count  = db('face_user') -> where($this->_getWhere($params)) -> count();
        $list   = db('face_user') -> where($this->_getWhere($params)) -> order('add_time ASC') -> limit(($page-1)*$size,$size) -> select();
        $list   = $this -> _afterList($list);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'info' => $list[1],'list' => $list[0]]]);die;
    }

    public function faces()
    {
        $params = $this -> postParams;
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if(!$pkId)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('face_user') -> where('fuser_id','=',$pkId) -> find();

        if (!$info || $info['is_del']) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $where[] = [
            'fuser_id' ,'=', $info['fuser_id'],
        ];

        $is_status = [['is_status',0],['is_status',1]]; //给是否在图片库搜索选项
        $page   = !empty($params['page']) ? intval($params['page']) : 1;
        $size   = !empty($params['size']) ? intval($params['size']) : 10;
        $count  = db('face_token') -> where($this->_getWhere($params,$where)) -> count();
        $list   = db('face_token') -> where($this->_getWhere($params,$where)) -> order('add_time ASC') -> limit(($page-1)*$size,$size) -> select();
        $list   = $this -> _afterList($list);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'tag'=>$is_status,'list' => $list[0]]]);die;

    }


    //选择合并用户列表
    public function choose()
    {

        $params = $this -> postParams;
        $fid = $params && isset($params['fid']) ? intval($params['fid']) : 0;
        if(!$fid)
        {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $info = db('face_user') -> where('fuser_id','=',$fid) -> find();

        if ($info['is_del'] > 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        $where = [
            ['fuser_id' , 'NEQ', $info['fuser_id']],
        ];
        $page   = !empty($params['page']) ? intval($params['page']) : 1;
        $size   = !empty($params['size']) ? intval($params['size']) : 10;
        $count  = db('face_user') -> where($this->_getWhere($params,$where)) -> count();
        $list   = db('face_user') -> where($this->_getWhere($params,$where)) -> order('add_time ASC') -> limit(($page-1)*$size,$size) -> select();
        $list   = $this -> _afterList($list);
        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count'=>$count,'page'=>$page ,'pinfo' =>$info,'info' => $list[1],'list' => $list[0]]]);die;

    }
    //用户合并去重操作
    public function distinct()
    {
        $params = $this -> postParams;
        $toid = isset($params['fromid']) ? intval($params['fromid']) : 0; //前端字段错了，fromid和toid调换
        $fromid = isset($params['toid']) ? intval($params['toid']) : 0;  //前端字段错了，fromid和toid调换
        $type = isset($params['type']) ? intval($params['type']) : 1;  //1人脸比较，2人脸合并
        if (!$fromid || !$toid || $fromid == $toid) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $from = db('face_user') -> where([['fuser_id','=',$fromid],['is_del','=',0]]) -> find();
        $to = db('face_user') -> where([['fuser_id','=',$toid],['is_del','=',0]]) -> find();

        if (!$from || !$to || $from['is_del'] || $to['is_del']) {
            $this->_returnMsg(['code' => 1, 'msg' => '请求参数错误']);die;
        }

        $total = 18;
        $fromCount  = $from['token_count']; //去重用户人脸数量
        $toCount    = $to['token_count'];   //合并目标个体的人脸数量

        if ($fromCount <= 0 || $toCount <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '个体用户数据异常']);die;
        }

        if(isset(config('faceRecognition')['search']) && config('faceRecognition')['search'] == 'baidu'){
            $client = new \app\common\api\BaiduAipFace();
            if($type == 1){
                $img = [
                    ['image'=>$from['face_token'],'image_type'=>'FACE_TOKEN'],
                    ['image'=>$to['face_token'],'image_type'=>'FACE_TOKEN'],
                ];
                $return = $client->match($img);
                if($return['error_code'] <> 0){
                    $this->_returnMsg(['code' => 0, 'msg' => '人脸对比失败']);die;
                }
                $this->_returnMsg(['code' => 0, 'msg' => 'ok', 'data' => $return['result']]);die;
            }

            $fromlist = db('face_token')->where(['fuser_id' => $fromid, 'is_del' => 0])->select();
            if ($fromlist && $fromCount > 0) {
                $fromPersonId = 'person_'.$fromid;
                $toPersonId = 'person_' . $toid;
                $toGroupId = $to["tags"];
                $fromGroupId = $from["tags"];


                if($toCount >= 16){
                    $temp = db('face_token') -> where('fuser_id','IN',[$fromid,$toid])->where('is_del','=',0)->order('fquality_value asc') ->limit($toCount)-> select();
                    foreach($temp as $k=>$v){
                        $addReturn = $client->faceDelete('person_'.$v['fuser_id'], $v['tags'], $v['face_token']);
                        $v['is_del'] = 1;
                        db('face_token') -> update($v);
                    }
                }


                //合并人脸
                $delReturn = $client -> deleteUser($fromGroupId, $fromPersonId);
                if ($delReturn['error_code'] > 0) {
                    $this->_returnMsg(['code' => 1, 'msg' => $delReturn['error_msg']]);die;
                }
                $temp = db('face_token') -> where('is_del','=',0)->where('fuser_id','=',$fromid)->select();

                //db('face_user')->where('is_del','=',0)->where('fuser_id','=',$toid)->inc('token_count',count($temp));
                db('face_user')->where(['fuser_id' => $toid])->update(['update_time' => time(), 'token_count' => ['inc', count($temp)]]);
                foreach ($temp as $key => $value) {
                    $imgUrl = trim($value['img_url']);
                    $addReturn = $client -> addUser($imgUrl, 'URL', $toGroupId, $toPersonId);
                    if ($addReturn['error_code'] > 0) {
                        $this->_returnMsg(['code' => 1, 'msg' => $addReturn['error_msg']]);die;
                    }

                }
                //修改本地数据库
                $result = db('face_user')->where(['fuser_id' => $fromid, 'is_del' => 0])->update(['is_del' => 1, 'update_time' => time()]);
//                     //将去重用户数据库存在的且接口不存在的人脸数据修改成为目标用户的不存在的人脸访问记录
//                     $result = db('face_token')->where(['fuser_id' => $fromid, 'is_del' => 1])->update(['fuser_id' => $toid, 'update_time' => time()]);
                //人脸数据库逻辑删除
                $result = db('face_token')->where(['fuser_id' => $fromid])->update(['fuser_id' => $toid, 'update_time' => time()]);
                //分组数据库个体减1
                $result = db('faceset')->where(['faceset_id' => $from['faceset_id'], 'is_del' => 0])->setDec('face_count', 1);

                //处理用户day_visit/day_total/day_capture数据记录
                $this->_handlingDuplicateData($from, $to);
                $this->_returnMsg(['code' => 0, 'msg' => '成功']);die;
            }else{
                $this->_returnMsg(['code' => 1, 'msg' => '无可合并的个体数据']);die;
            }
        }

        $msg = '';
        $distinctFlag =  FALSE;

        $faceApi = new \app\common\api\FaceApi();
        $result = $faceApi->compareApi($from['face_token'], $to['face_token']);
        if ($result) {
            $confidence = isset($result['confidence']) ? $result['confidence'] : '';
            $thresholds = isset($result['thresholds']) ? $result['thresholds'] : [];
            if ($confidence && $thresholds) {
                $le3 = isset($thresholds['1e-3']) ? $thresholds['1e-3'] : '';
                $le4 = isset($thresholds['1e-4']) ? $thresholds['1e-4'] : '';
                $le5 = isset($thresholds['1e-5']) ? $thresholds['1e-5'] : '';
                $distinctFlag = TRUE;
//                if ($confidence >= $le5) {//误识率为十万分之一的置信度阈值；
//                    $distinctFlag = TRUE;
//                    $msg = '可能性很高';
//                }elseif ($confidence >= $le4){//误识率为万分之一的置信度阈值；
////                         $distinctFlag = TRUE;
//                    $msg = '可能性高';
//                }elseif ($confidence >= $le3){//误识率为千分之一的置信度阈值
////                         $distinctFlag = TRUE;
//                    $msg = '可能性一般';
//                }else{
//                    $msg = '可能性低';
//                }
            }
        }

        if ($distinctFlag) {
            $this -> distinct_comfirm();
        }else{
            $info = [
                    'msg'=> $msg,
                    'from'=> $from,
                    'to'=> $to,
                    ];
            $this->_returnMsg(['code' => 1, 'msg' => '确定要合并？','data' => ['info' => $info]]);die;
        }
    }

    public function distinct_comfirm()
    {
        $params = $this -> postParams;
        $fromid = isset($params['fromid']) ? intval($params['fromid']) : 0;
        $toid = isset($params['toid']) ? intval($params['toid']) : 0;
        if (!$fromid || !$toid || $fromid == $toid) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $from = db('face_user') -> where('fuser_id','=',$fromid) -> find();
        $to = db('face_user') -> where('fuser_id','=',$toid) -> find();

        if (!$from || !$to || $from['is_del'] || $to['is_del']) {
            $this->_returnMsg(['code' => 1, 'msg' => '请求参数错误']);die;
        }

        $total = 18;
        $fromCount  = $from['token_count']; //去重用户人脸数量
        $toCount    = $to['token_count'];   //合并目标个体的人脸数量
        $lastCount  = $total - $toCount;    //合并目标个体剩余可添加人脸数量
        if ($fromCount <= 0 || $toCount <= 0) {
            $this->_returnMsg(['code' => 1, 'msg' => '个体用户数据异常']);die;
        }



        $faceCount = $toCount;
        //获取去重用户个体人脸列表
        $fromlist = db('face_token')->where(['fuser_id' => $fromid, 'is_del' => 0])->order('fquality_value DESC')->select();
        if ($fromlist && $fromCount > 0) {
            //获取合并目标个体人脸列表
            $field = 'token_id, fuser_id, img_url, face_token, face_id, fquality_value, fquality_threshold';
            if ($toCount > 1 && ($toCount >= $total || $fromCount > $lastCount)) {
                //合并目标个体大于1且(已达人脸最大值/去重用户人脸数量大于合并目标个体剩余可添加人脸数量)，则需要先删除部分人脸
                $tolist = db('face_token')->where(['fuser_id' => $toid, 'is_del' => 0])->field($field)->limit(0, $toCount)->order('fquality_value ASC')->select();
            }
            //1.当去重用户人脸等于1时，将仅有的一个人脸添加至目标个体(判断目标个体是否需要删除人脸，如果需要则删除fquality_value最低的人脸)
            //2.当去重用户人脸大于1时，判断去重用户人脸数量是否大于合并目标个体剩余可添加人脸数量(大于则表示需要对比去重用户和目标用户下人脸的质量参数，保留fquality_value较高的人脸至目标用户)
            $fromPersonId = 'person_'.$fromid;
            $toPersonId = 'person_'.$toid;
            $faceApi = new \app\common\api\TencentFaceApi();
            $client = new \ai\face\recognition\Client(config('haibo'));
            $deleteTokenIds = [];
            foreach ($fromlist as $key => $value) {
                $flag = FALSE;
                $imgUrl = trim($value['img_url']);
                if ($fromCount == 1) {
                    $flag = TRUE;
                }elseif ($fromCount >= $lastCount){
                    break;
                }else{
                    if ($toCount > 1 && isset($tolist) && $tolist && isset($tolist[$key]) && $tolist[$key]) {
                        //对比两个人脸fquality_value，保留质量较高的数据
                        if (($value['fquality_value'] - $value['fquality_threshold']) < ($tolist[$key]['fquality_value'] - $tolist[$key]['fquality_threshold'])) {
                            break;
                        }else{
                            $deletFaceId = $tolist[$key]['face_id'];
                            //删除目标用户当前人脸(接口删除)
//                                $apiResult = $faceApi->facePersonDelFace([$deletFace['face_id']], $toPersonId);
//                            $apiResult = $faceApi->facePersonDelFace($deletFaceId, $toPersonId);  //huangyihao
                            $apiResult = $client->driver('tencent-cloud')->deleteFace($toPersonId,$deletFaceId);
                            $apiResult = json_decode(json_encode($apiResult),true);
                            if (isset($apiResult['code']) && $apiResult['code'] != 0) {
                                //$this->error($apiResult['message']);
                                $this->_returnMsg(['code' => 1, 'msg' => $apiResult['message']]);die;
                            }else{
//                                $deleted = isset($apiResult['data']['deleted']) ? intval($apiResult['data']['deleted']) : 0;
//                                if ($deleted <= 0) {
//                                    //$this->error('接口:删除人脸操作异常');
//                                    $this->_returnMsg(['code' => 1, 'msg' => '接口:删除人脸操作异常']);die;
//                                }
                                $faceCount -- ;
                                $lastCount ++ ;
                                $deleteTokenIds[] = $tolist[$key]['token_id'];
                            }
                        }
                    }
                    $flag = TRUE;
                }
                if ($flag === TRUE) {
                    //将人脸添加至合并目标个体
//                    $apiResult = $faceApi->facePersonAddFace($imgUrl, $toPersonId, $value['face_token']);
                    $apiResult = $client->driver('tencent-cloud')->addFace($toPersonId, $imgUrl);
                    $apiResult = json_decode(json_encode($apiResult),true);
                    if (isset($apiResult['code']) && $apiResult['code'] != 0) {
                        $this->_returnMsg(['code' => 1, 'msg' => $apiResult['message']]);die;
                    }else{
                        $added = isset($apiResult['data']['added']) ? intval($apiResult['data']['added']) : 0;
                        $retCode = isset($apiResult['data']['ret_codes']) ? $apiResult['data']['ret_codes'][0] : '';
                        $faceIds = isset($apiResult['data']['face_ids']) ? $apiResult['data']['face_ids'] : [];
                        if ($added <= 0 && $retCode != -1312) {
                            //$this->error('接口报错:'.$retCode);
                            $this->_returnMsg(['code' => 1, 'msg' => '接口报错:'.$retCode]);die;
                        }
                        if (($added > 0 || $retCode != -1312) && $faceIds) {
                            $newFaceId = isset($faceIds[0]) ? $faceIds[0] : '';
                            if($newFaceId){
                                $addResult = $value['add_result'] ? json_decode($value['add_result'], 1) : [];
                                $addResult['distinct']['last'] = [
                                    'face_id' => $value['face_id'],
                                    'fuser_id' => $fromid,
                                ];
                                $addResult['distinct']['add'] = $apiResult;
                                $update = [
                                    'update_time' => time(),
                                    'face_id' => $newFaceId,
                                    'fuser_id' => $toid,
                                    'add_result' => json_encode($addResult)
                                ];
                                $result = db('face_token')->where(['token_id' => $value['token_id']])->update($update);
                                $lastCount --;
                                $faceCount ++;
                            }
                        }
                    }
                }
            }
            $fuserData = [];
            if (($from['fquality_value'] - $from['fquality_threshold']) > ($to['fquality_value'] - $to['fquality_threshold'])) {
                $fuserData = [
                    'gender' => $from['gender'],
                    'age' => $from['age'],
                    'age_level' => $from['age_level'],
                    'ethnicity' => $from['ethnicity'],
                    'face_token' => $from['face_token'],
                    'tags' => $from['tags'],
                    'avatar' => $from['avatar'],
                    'fquality_value' => $from['fquality_value'],
                    'fquality_threshold' => $from['fquality_threshold'],
                ];
            }
            if ($faceCount != $to['token_count']) {
                $fuserData['token_count'] = $faceCount;
            }
            if ($fuserData) {
                $fuserData['update_time'] = time();
                $result = db('face_user')->where(['fuser_id' => $toid])->update($fuserData);
            }
            if (isset($tolist) && $tolist && $deleteTokenIds) {
                //合并目标人脸删除(数据库逻辑删除)
                $result = db('face_token')->where(['token_id' => [$deleteTokenIds], 'fuser_id' => $toid, 'is_del' => 0])->update(['is_del' => 1, 'update_time' => time()]);
            }
            //删除个体
//            $apiResult = $faceApi->facePersonDelete($fromPersonId);
            $apiResult = $client->driver('tencent-cloud')->deleteUser($fromPersonId);
            $apiResult = json_decode(json_encode($apiResult),true);
            if (isset($apiResult['code']) && $apiResult['code'] != 0) {
                //$this->error($apiResult['message']);
                $this->_returnMsg(['code' => 1, 'msg' => $apiResult['message']]);die;
            }else{
                //接口删除
//                $deleted = isset($apiResult['data']['deleted']) ? intval($apiResult['data']['deleted']) : 0;
//                if ($deleted <= 0) {
//                    Log::write('==============================================');
//                    Log::write('接口删除个体异常=='.json_encode($apiResult));
//                    Log::write('==============================================');
//                }
                //个体数据库逻辑删除
                $result = db('face_user')->where(['fuser_id' => $fromid, 'is_del' => 0])->update(['is_del' => 1, 'update_time' => time()]);
//                     //将去重用户数据库存在的且接口不存在的人脸数据修改成为目标用户的不存在的人脸访问记录
//                     $result = db('face_token')->where(['fuser_id' => $fromid, 'is_del' => 1])->update(['fuser_id' => $toid, 'update_time' => time()]);
                //人脸数据库逻辑删除
                $result = db('face_token')->where(['fuser_id' => $fromid])->update(['fuser_id' => $toid, 'update_time' => time()]);
                //分组数据库个体减1
                $result = db('faceset')->where(['faceset_id' => $from['faceset_id'], 'is_del' => 0])->setDec('face_count', 1);
                //处理用户day_visit/day_total/day_capture数据记录
                $this->_handlingDuplicateData($from, $to);
                //$url = $fid ? url('person/index') : url('index/home');
                //$this->success('个体合并操作成功', $url);
                $this->_returnMsg(['code' => 0, 'msg' => '个体合并操作成功']);die;
            }
        }else{
            //$this->error('无可合并的个体数据');
            $this->_returnMsg(['code' => 1, 'msg' => '个体用户数据异常']);die;
        }
    }

    //从接口中移除人脸
    public function faceDel()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $info = db('face_token') ->where('token_id', $pkId)->find();
        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '非法的id']);die;
        }
        //判断接口是否超过一张基础人脸
        $fuserId = $info['fuser_id'];
        $faceIds = [$info['face_id']];
        $personId = 'person_'.$fuserId;
        $count =db('face_token')->where(['fuser_id' => $fuserId, 'is_del' => 0])->count();
        if ($count <= 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前个体只有一张人脸图片，不允许删除']);die;
        }else{
            $faceApi = new \app\common\api\TencentFaceApi();
            #TODO 接口获取个体人脸数量
//             $result = $faceApi->getFaceTokenList($personId);
//             if (isset($result['code']) && $result['code'] != 0) {
//                 $this->error($result['message']);
//             }else{
//                 $faceIds = isset($result['data']['face_ids']) ? $result['data']['face_ids'] : [];
//                 pre($faceIds);
//                 $count = count($faceIds);
//             }
        }
        if(isset(config('faceRecognition')['search']) && config('faceRecognition')['search'] == 'baidu') {
            $client = new \app\common\api\BaiduAipFace();
            $addReturn = $client->faceDelete($personId, $info['tags'], $info['face_token']);
            db('face_token')->where('token_id' , $pkId)->update(['is_del'=>1]);
            db('face_user') -> where('fuser_id',$fuserId)->setDec('token_count');
            $this->_returnMsg(['code' => 0, 'msg' => '人脸移除成功', 'data' => ['token_id' => $pkId]]);die;
        }
        //删除个体人脸
        $result = $faceApi->facePersonDelFace($faceIds, $personId);
        if (isset($result['code']) && $result['code'] != 0) {
            $this->_returnMsg(['code' => 1, 'msg' => $result['message']]);die;
        }else{
//            $delete = isset($result['data']['deleted']) ? $result['data']['deleted'] : 0;
//            if ($delete <= 0) {
//                //$this->_returnMsg(['code' => 1, 'msg' => '人脸不存在或已删除']);die;
//            }
            //物理删除
            if (isset($info['add_result']) && $info['add_result']) {
                $addResult = json_decode($info['add_result'], TRUE);
            }else{
                $addResult = [];
            }
            $addResult['add']['data']['error'] = '平台删除';
            $addResult['remove'] = $result;
            $result = db('face_token')->where(['token_id' => $info['token_id']])->update(['is_del' => 1, 'add_result' => json_encode($addResult)]);
            $result = db('face_user')->where(['fuser_id' => $fuserId])->setDec('token_count', 1);
            $this->_returnMsg(['code' => 0, 'msg' => '人脸移除成功', 'data' => ['token_id' => $pkId]]);die;
        }
    }
    //将当前人脸添加至图片库
    public function faceAdd()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $info = db('face_token') ->where(['token_id' => $pkId])->find();

        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }
        if (!$info['is_del']) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前人脸已经存在图片库，不能重复添加']);die;
        }
        //判断个体用户人脸是否达到最大值
        $fuserId = $info['fuser_id'];
        $fuser = db('face_user')->where(['fuser_id' => $fuserId])->find();
        if (!$fuser || $fuser['is_del']) {
            $this->_returnMsg(['code' => 1, 'msg' => '个体用户不存在']);die;
        }
        $total = 18;
        if ($fuser['token_count'] >= $total) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前个体对应人脸数量已达最大值,如需添加当前人脸请先移除其它人脸']);die;
        }

        $personId = 'person_'.$fuserId;
        $count = db('face_token')->where(['fuser_id' => $fuserId, 'is_del' => 0])->count();
        if ($count >= $total) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前个体人脸图片以满18张，先移除才可以添加']);die;
        }else{

            if(isset(config('faceRecognition')['search']) && config('faceRecognition')['search'] == 'baidu') {
                $client = new \app\common\api\BaiduAipFace();
                $addReturn = $client -> addUser($info['img_url'], 'URL', $info['tags'], $personId);
                if ($addReturn['error_code'] > 0) {
                    $this->_returnMsg(['code' => 1, 'msg' => $addReturn['error_msg']]);die;
                }
                db('face_token')->where('token_id' , $pkId)->update(['is_del'=>0]);
                db('face_user') -> where('fuser_id',$fuserId)->setInc('token_count');
                $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['token_id' => $pkId]]);die;
            }

            $faceApi = new \app\common\api\TencentFaceApi();
            $apiResult = $faceApi->facePersonAddFace($info['img_url'], $personId, $info['face_token']);
            if (isset($apiResult['code']) && $apiResult['code'] != 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '人脸添加失败：'.$apiResult['message']]);die;
            }else{
                $added = isset($apiResult['data']['added']) ? intval($apiResult['data']['added']) : 0;
                $retCode = isset($apiResult['data']['ret_codes']) ? $apiResult['data']['ret_codes'][0] : '';
                $faceIds = isset($apiResult['data']['face_ids']) ? $apiResult['data']['face_ids'] : [];
                if ($added <= 0) {
                    $this->_returnMsg(['code' => 1, 'msg' => '操作异常:'.$faceApi->_getErrMsg($retCode)]);die;
                }
                $newFaceId = isset($faceIds[0]) ? $faceIds[0] : '';
                if($newFaceId){
                    $addResult = $info['add_result'] ? json_decode($info['add_result'], 1) : [];
                    $addResult['add'] = $apiResult;
                    $update = [
                        'update_time'=> time(),
                        'is_del'    => 0,
                        'face_id'   => $newFaceId,
                        'add_result'=> json_encode($addResult),
                        'token_id' => $info['token_id']
                    ];
                    $result = db('face_token')->where(['token_id' => $info['token_id']])->update($update);
                    if ($result !== FALSE) {
                        $result = db('face_user')->where(['fuser_id' => $fuserId])->update([
                            'update_time' => time(),
                            'token_count' => ['inc', $added],
                        ]);
                    }
                }
            }
        }
        $this->_returnMsg(['code' => 0, 'msg' => '添加成功','data' => ['token_id' => $pkId]]);die;
    }
    function _afterList($list){
        $action     = $this->request->action();
        $faceApi    = new \app\common\api\BaseFaceApi();
        $genders    = $faceApi->genders;
        $ageLvels   = $faceApi->ageLvels;
        $ethnicitys = $faceApi->ethnicitys;

        $info = [
                'genders' => $genders,
                'ageLvels' => $ageLvels,
                'ethnicitys' => $ethnicitys,
                ];
        if ($list) {
            foreach ($list as $key => $value) {
                $error = '';
                $list[$key]['gender'] = $faceApi->_getDataDetail('gender', $value['gender'], 'name');
                $list[$key]['ethnicity'] = $faceApi->_getDataDetail('ethnicity', $value['ethnicity'], 'name');
                if (isset($value['emotion'])) {
                    $list[$key]['emotion'] = $faceApi->_getDataDetail('emotion', $value['emotion'], 'name');
                }
                if (isset($value['headpose'])) {
                    $list[$key]['headpose'] = $faceApi->_getDataDetail('headpose', $value['headpose'], 'name');
                }
                if (isset($value['add_result']) && $value['add_result']) {
                    $add = json_decode($value['add_result'], TRUE);
                    if (isset($add['add']['data']['error']) && $add['add']['data']['error']) {
                        $error = $add['add']['data']['error'];
                    }else if (isset($add['add']['code']) && $add['add']['code'] == 0 && $add['add']['data']['ret_codes'] != 0) {
                        $retCode = isset($add['add']['data']['ret_codes']) ? $add['add']['data']['ret_codes'][0]: 0;
                        if ($retCode == '-1312') {
                            $error = '对个体添加了相似度为99%及以上的人脸';
                        }elseif ($retCode == '-1309') {
                            $error = '人脸个数超过限制';
                        }elseif ($retCode == '-1305') {
                            $error = '人脸不存在';
                        }else{
                            $error = $add['add']['data']['ret_codes'];
                        }
                    }
                    if (!$error && isset($value['fquality_value']) && $value['fquality_value'] < 10) {
                        $error = '置信度低于10';
                    }
                    $list[$key]['error'] = $error;
                }
            }
        }
        return [$list,$info];
    }

    function _getWhere($params,$where = [])
    {

        $action = $this->request->action();
        if (!$where || strtolower($action) == 'choose') {
            $authorization = !empty(\think\facade\Request::header('authentication')) ? \think\facade\Request::header('authentication') : input('token');
            $storeIds = cache($authorization)['admin_user']['store_ids'] ?? [1,16,18];

            $where[] = ['is_del' , '=', 0];
            $where[] = ['store_id' , 'IN', $storeIds];
            if ($params) {
                $fuid = isset($params['fuid']) ? intval($params['fuid']) : '';
                if($fuid){
                    $fid = isset($params['fid']) ? $params['fid'] : (isset($params['id']) ? $params['id'] : 0);
                    if ($fid != $fuid || !$where) {
                        $where[] = ['fuser_id','=',$fuid];
                    }else{
                        $where[] = ['fuser_id','=',0];
                    }
                }
                $gender = isset($params['gender']) ? intval($params['gender']) : '';
                if($gender){
                    $where[] = ['gender','=',$gender];
                }
                $ageLevel = isset($params['age_level']) ? intval($params['age_level']) : '';
                if($ageLevel){
                    $where[] = ['age_level','=',$ageLevel];
                }
                $ethnicity = isset($params['ethnicity']) ? intval($params['ethnicity']) : '';
                if($ethnicity){
                    $where[] = ['ethnicity','=',$ethnicity];
                }
            }
        }elseif(strtolower($action) == 'faces'){
            $where[] = ['is_del','<>',10];
            if ($params) {
                $macId = isset($params['mac_id']) ? trim($params['mac_id']) : '';
                if($macId){
                    $where[] = ['device_code','like', '%'.$macId.'%'];
                }
                if(isset($params['is_status'])){
                    $isDel = isset($params['is_status']) ? intval($params['is_status']) : '';
                    $where[] = ['is_del','=',$isDel];
                }
            }
        }else{
            if ($params) {
                $macId = isset($params['mac_id']) ? trim($params['mac_id']) : '';
                if($macId){
                    $where[] = ['device_code','like', '%'.$macId.'%'];
                }
                if(isset($params['is_status'])){
                    $isDel = isset($params['is_status']) ? intval($params['is_status']) : '';
                    $where[] = ['is_del','=',$isDel];
                }
            }
        }
        return $where;
    }

    function _getData()
    {
        $data = parent::_getData();
        $name = trim($data['name']);
        if (!$name) {
            $this->error('分组名称不能为空');
        }
        $where = ['name' => $name, 'is_del' => 0, 'store_id' => $this->storeId];
        $params = Request::instance()->param();
        $pkId = $params && isset($params['id']) ? intval($params['id']) : 0;
        if($pkId){
            $where['dgroup_id'] = ['neq', $pkId];
        }
        $exist = $this->model->where($where)->find();
        if($exist){
            $this->error('当前分组名称已存在');
        }
        $data['name'] = $name;
        return $data;
    }
    /**
     * 处理去重操作对应用户统计数据
     * @param unknown $from
     * @param unknown $to
     */
    private function _handlingDuplicateData($from, $to)
    {
        $fromid = isset($from['fuser_id']) ? intval($from['fuser_id']) : 0;
        $toid = isset($to['fuser_id']) ? intval($to['fuser_id']) : 0;
        if (!$fromid || !$toid) {
            die('数据错误');
        }
        $visitModel = db('day_visit');
        $captureModel = db('day_capture');
        $totalModel = db('day_total');
        //获取去重用户每日访问记录
        $list = $visitModel->where(['fuser_id' => $fromid, 'is_del' => 0])->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $total = $totalModel->where(['store_id' => $value['store_id'], 'capture_date' => $value['capture_date']])->find();
                if ($total) {
                    $update = [
//                         'person_total' => ['dec', 1],
                    ];
                    $age = $total['age_json'] ? json_decode($total['age_json'], 1) : [];
                    if (isset($age[$value['age_level']]) && $age[$value['age_level']] > 0) {
                        $age[$value['age_level']] = $age[$value['age_level']] - 1;
                    }
                    $update['age_json'] = $age ? json_encode($age) : '';
                    
                    $gender = $total['gender_json'] ? json_decode($total['gender_json'], 1) : [];
                    if (isset($gender[$value['gender']]) && $gender[$value['gender']] > 0) {
                        $gender[$value['gender']] = $gender[$value['gender']] - 1;
                    }
                    $update['gender_json'] = $gender ? json_encode($gender) : '';
                    
                    $userType = $value['user_type'];
                    
                    $userTypeArray = $total['user_type_json'] ? json_decode($total['user_type_json'], 1) : [];
                    if (isset($userTypeArray[$userType]) && $userTypeArray[$userType] > 0) {
                        $userTypeArray[$userType] = $userTypeArray[$userType] - 1;
                    }
                    $update['user_type_json'] = $userTypeArray ? json_encode($userTypeArray) : '';
                    if ($userType != 3) {
                        $update['customer_total'] = ['dec', 1];
                    }else{
                        $update['cleark_total'] = ['dec', 1];
                    }
                    $update['total_id'] = $total['total_id'];
                    $totalModel->where(['total_id' => $total['total_id']])->update($update);
                }
            }
        }
        //处理去重用户day_visit/day_total/day_capture数据记录
        //$faceTokenList = db('face_token')->where(['fuser_id' => ['IN', [$fromid, $toid]]])->order('add_time ASC')->select();
        $faceTokenList = db('face_token')->where(['fuser_id' => $toid])->order('capture_time ASC')->select();
        if ($faceTokenList) {
            $result = $visitModel->where(['fuser_id' => [$fromid, $toid]])->update(['is_del' => 1]);
            $result = $captureModel->where(['fuser_id' => [$fromid, $toid]])->update(['is_del' => 1]);
            $faceApi = new \app\common\api\BaseFaceApi();
            foreach ($faceTokenList as $key => $value) {
                $ageLevel = 0;
                $faceQuality = [
                    'value' => $value['fquality_value'],
                    'threshold' => $value['fquality_threshold'],
                ];
                $storeId = $value['store_id'];
                $blockId = $value['block_id'];
                $deviceId = $value['device_id'];
                $fuserId = $toid;
                $captureTime = $value['capture_time'];
                $positionType = $value['position_type'];
                $imgFile = $value['img_url'];
                $age = $value['age'];
                $ageLevel = $faceApi->_getAgeData($age, 'level');  //年龄等级;
                $genderId = $value['gender'];
                $ethnicityId = $value['ethnicity'];
                $faceToken = $value['face_token'];
                $emotion = $value['emotion'];
                $faceX = $value['img_x'];
                $faceY = $value['img_y'];
                $day = date('Y-m-d', $captureTime);
                //当日用户到访信息
                $result = $faceApi->_dayVisit($storeId, $fuserId, $captureTime, $positionType, $imgFile, $age, $ageLevel, $genderId, $faceToken, $faceQuality, $emotion);
                $userType = $result && $result['user_type'] ? $result['user_type'] : 0;
                $customeStep = $result && $result['custome_step'] ? $result['custome_step'] : 0;
                $personStep = $result && $result['person_step'] ? $result['person_step'] : 0;
                $stayTimesValue = $result && $result['stay_time_value'] ? $result['stay_time_value'] : 0;
                //用户当日在当前设备抓拍记录
                $result = $faceApi->_dayCapture($storeId, $blockId, $deviceId, $fuserId, $captureTime, $age, $ageLevel, $genderId, $ethnicityId, $userType, trim($faceX), trim($faceY));
            }
            if ($list) {
                foreach ($list as $key => $value) {
                    $total = $totalModel->where(['store_id' => $value['store_id'], 'capture_date' => $value['capture_date']])->find();
                    $where = [
                        ['store_id','=', $value['store_id']],
                        ['capture_date','=', $value['capture_date']],
                    ];
                    $totalWhere_f = [['fuser_id' ,'=', $fromid], ['is_del' ,'=', 1]];
                    $totalWhere = $totalWhere_f + $where;
                    $totalVisitCounts_f = $visitModel->where($totalWhere)->order('add_time DESC')->limit(1)->value('visit_counts');

                    $totalWhere_t = [['fuser_id' ,'=', $toid], ['is_del' ,'=', 1]];
                    $totalWhere = $totalWhere_t + $where;
                    $totalVisitCounts_t = $visitModel->where($totalWhere)->order('add_time DESC')->limit(1)->value('visit_counts');
                    $totalVisitCounts = $totalVisitCounts_f + $totalVisitCounts_t;
//                     echo 'Total:';
//                     pre($totalVisitCounts, 1);
                    
                    $thisWhere = [['fuser_id' ,'=' ,$toid], ['is_del' ,'=' ,0]];
                    $thisWhere = $thisWhere + $where;
                    $thisVisitCounts =  $visitModel->where($thisWhere)->value('visit_counts');
//                     echo 'This:';
//                     pre($thisVisitCounts, 1);
                    
                    if ($totalVisitCounts > $thisVisitCounts) {
                        $step = $totalVisitCounts - $thisVisitCounts;
                        $setType = 'dec';
                    }elseif ($thisVisitCounts > $totalVisitCounts){
                        $step = $thisVisitCounts - $totalVisitCounts;
                        $setType = 'inc';
                    }
                    if (isset($step) && $step) {
//                         echo 'This:';
//                         pre(['person_total' => [$setType, $step]], 1);
//                        $totalModel->where(['total_id' => $total['total_id']])->update(['person_total' => [$setType, $step]]);
                    }
                }
            }
        }
    }

    //彻底参数
    public function faceTokenDel()
    {
        $params = $this -> postParams;
        $pkId = isset($params['id']) ? intval($params['id']) : null;

        if(!$pkId){
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
        }

        $info = db('face_token') ->where(['token_id' => $pkId])->find();
        if (!$info) {
            $this->_returnMsg(['code' => 1, 'msg' => '参数错误']);die;
            //$this->error('参数错误');
        }
        //判断接口是否超过一张基础人脸
        $fuserId = $info['fuser_id'];
        $faceIds = [$info['face_id']];
        $personId = 'person_'.$fuserId;
        $count = db('face_token')->where(['fuser_id' => $fuserId, 'is_del' => 0])->count();
        if ($count <= 1) {
            $this->_returnMsg(['code' => 1, 'msg' => '当前个体只有一张人脸图片，不允许删除']);die;
            //$this->error('当前个体只有一张人脸图片，不允许删除');
        }
        $faceApi = new \app\common\api\TencentFaceApi();
        //删除个体人脸
        $faceIds = [$info['face_id']];
        $result = $faceApi->facePersonDelFace($faceIds, $personId);
        if (isset($result['code']) && $result['code'] != 0) {
            //$this->error($result['message']);
            $this->_returnMsg(['code' => 1, 'msg' => $result['message']]);die;
        }else{
            $delete = isset($result['data']['deleted']) ? $result['data']['deleted'] : 0;
            if ($delete <= 0) {
                $this->_returnMsg(['code' => 1, 'msg' => '人脸不存在或已删除']);die;
            }
            //物理删除
            if (isset($info['add_result']) && $info['add_result']) {
                $addResult = json_decode($info['add_result'], TRUE);
            }else{
                $addResult = [];
            }
            $addResult['add']['data']['error'] = '人脸列表删除';
            $addResult['remove'] = $result;
            $result = db('face_token')->where(['token_id' => $info['token_id']])->update(['is_del' => 10, 'add_result' => json_encode($addResult)]);
            $result = db('face_user')->where(['fuser_id' => $fuserId])->setDec('token_count', 1);
            $this->_returnMsg(['code' => 0, 'msg' => '人脸移除成功', 'data' => ['token_id' => $pkId]]);die;
        }
    }
}
