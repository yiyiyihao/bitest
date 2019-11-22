<?php
namespace app\common\api;

use ai\face\recognition\Client;
/**
 * 人脸识别接口
 * @author xiaojun
 */
class BaseFaceApi
{
    var $config;    //Api接口配置
    var $emotions;  //情绪列表
    var $headposes; //人脸姿势分析列表
    var $ethnicitys;//人种分析列表
    var $genders;   //性别
    var $ageLvels;  //年龄等级
    var $error;
    public function __construct(){
        $this->emotions = [
            0 => [
                'code' => 'other',
                'name' => lang('其它'),
            ],
            1 => [
                'code' => 'anger',
                'name' => lang('愤怒'),
            ],
            2 => [
                'code' => 'disgust',
                'name' => lang('厌恶'),
            ],
            3 => [
                'code' => 'fear',
                'name' => lang('恐惧'),
            ],
            4 => [
                'code' => 'happiness',
                'name' => lang('高兴'),
            ],
            5 => [
                'code' => 'neutral',
                'name' => lang('平静'),
            ],
            6 => [
                'code' => 'sadness',
                'name' => lang('伤心'),
            ],
            7 => [
                'code' => 'surprise',
                'name' => lang('惊讶'),
            ],
        ];
        $this->headposes = [
            1 => [
                'code' => 'pitch_angle',
                'name' => lang('抬头'),
            ],
            2 => [
                'code' => 'roll_angle',
                'name' => '旋转',
            ],
            3 => [
                'code' => 'yaw_angle',
                'name' => lang('摇头'),
            ],
        ];
        $this->ethnicitys = [
            1 => [
                'code' => 'asian',
                'name' => lang('亚洲人'),
            ],
            2 => [
                'code' => 'white',
                'name' => lang('白人'),
            ],
            3 => [
                'code' => 'black',
                'name' => lang('黑人'),
            ],
        ];
        $this->genders = [
            1 => [
                'code' => 'male',
                'name' => lang('男士'),
                'count' => 0,
            ],
            2 => [
                'code' => 'female',
                'name' => lang('女士'),
                'count' => 0,
            ],
        ];
        $this->ageLvels = [
            1 => [
                'level' => 1,
                'name' => '0-10'.lang('岁'),
                'tag' => ['1'=>lang('萌娃'),'2'=>lang('萌娃')],
                'min' => 0,
                'max' => 10,
                'count' => 0,
            ],
            2 => [
                'level' => 2,
                'name' => '10-20'.lang('岁'),
                'tag' => ['1'=>lang('正太'),'2'=>lang('萝莉')],
                'min' => 10,
                'max' => 20,
                'count' => 0,
            ],
            3 => [
                'level' => 3,
                'name' => '20-30'.lang('岁'),
                'tag' => ['1'=>lang('鲜肉'),'2'=>lang('仙女')],
                'min' => 20,
                'max' => 30,
                'count' => 0,
            ],
            4 => [
                'level' => 4,
                'name' => '30-40'.lang('岁'),
                'tag' => ['1'=>lang('型男'),'2'=>lang('女神')],
                'min' => 30,
                'max' => 40,
                'count' => 0,
            ],
            5 => [
                'level' => 5,
                'name' => '40-50'.lang('岁'),
                'tag' => ['1'=>lang('萌叔'),'2'=>lang('御姐')],
                'min' => 40,
                'max' => 50,
                'count' => 0,
            ],
            6 => [
                'level' => 6,
                'name' => '50-60'.lang('岁'),
                'tag' => ['1'=>lang('大叔'),'2'=>lang('娇娘')],
                'min' => 50,
                'max' => 60,
                'count' => 0,
            ],
            7 => [
                'level' => 7,
                'name' => lang('60岁以上'),
                'tag' => ['1'=>lang('伯公'),'2'=>lang('媪妪')],
                'min' => 60,
                'max' => 0,
                'count' => 0,
            ],
        ];
    }
    /**
     * 图片识别
     * @param string $faceImg   识别的目标图片
     * @param string $deviceCode上报图片的设备串码
     * @param int $storeId      门店ID
     * @param array $params     其它参数
     * @param string $apiType   接口类型
     * @return array
     */
    public function faceRecognition($faceImg, $deviceCode, $storeId, $params = [], $apiType = 'all')
    {
        $captureTime    = isset($params['capture_time']) ? trim($params['capture_time']) : time();
        $faceX          = isset($params['face_x']) ? trim($params['face_x']) : '';
        $faceY          = isset($params['face_y']) ? trim($params['face_y']) : '';
        $imgPixel       = isset($params['img_pixel']) ? trim($params['img_pixel']) : '';
        $blockId        = isset($params['block_id']) ? intval($params['block_id']) : 0;
        $deviceId       = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $positionType   = isset($params['position_type']) ? intval($params['position_type']) : 0;

//         $userId         = isset($params['user_id']) ? trim($params['user_id']) : 0;
        $isAdmin        = isset($params['is_admin']) ? trim($params['is_admin']) : 0;
        $searchFaceId   = isset($params['search_face_id']) ? trim($params['search_face_id']) : '';
        $searchFuserId  = isset($params['search_fuser_id']) ? intval($params['search_fuser_id']) : 0;
        //解析人脸图片
        if(isset(config('faceRecognition')['detect']) && config('faceRecognition')['detect'] == 'baidu'){
            $apiType = 'baidu';
            $client = new \app\common\api\BaiduAipFace();
            // 如果有可选参数
            $options = array();
            $options["face_field"] = "age,beauty,expression,face_shape,gender,glasses,landmark,race,quality,eye_status,emotion,face_type,quality,face_probability";

            // 带参数调用人脸检测
            $detectResultBaidu = $client->detect($faceImg, 'URL', $options);
            if ($detectResultBaidu['error_code'] != 0) {
                $detectResultBaidu['code'] = 1;
                return $detectResultBaidu;
            }
            $confidence = db('face_confidence')->where('store_id',$storeId)->value('confidence');
            $confidence = $confidence ?? 80;
            if(($detectResultBaidu['result']['face_list']['0']['face_probability'] * 100 + (1-$detectResultBaidu['result']['face_list']['0']['quality']['blur']) * 100)/2 <= $confidence){
                return ['code' => 1, 'errMsg' => '图片质量不行', 'face_img' => $faceImg];
            }
            if (isset($detectResultBaidu['result'])) {
                $faces['0']['attributes']['gender']['value'] = $detectResultBaidu['result']['face_list']['0']['gender']['type'];
                $faces['0']['attributes']['age']['value'] = $detectResultBaidu['result']['face_list']['0']['age'];
                $faces['0']['attributes']['emotion'] = [$detectResultBaidu['result']['face_list']['0']['emotion']['type'] => $detectResultBaidu['result']['face_list']['0']['emotion']['probability']];
                $faces['0']['attributes']['headpose'] = $detectResultBaidu['result']['face_list']['0']['angle'];
                $faces['0']['attributes']['facequality']['value'] = (1-$detectResultBaidu['result']['face_list']['0']['quality']['blur']) * 100;
                $faces['0']['attributes']['facequality']['threshold'] = 50;
                $faces['0']['attributes']['ethnicity']['value'] = $detectResultBaidu['result']['face_list']['0']['race']['type'];
                $faces['0']['face_rectangle'] = $detectResultBaidu['result']['face_list']['0']['angle'];
                $faces['0']['face_token'] = $detectResultBaidu['result']['face_list']['0']['face_token'];
            }
        }else {
            $detectResult = $this->_faceDetect($faceImg);
            if ($detectResult['code']) {
                return $detectResult;
            }
            $faces = $detectResult['faces'];
            //如果all，那么再用baidu解析人脸图片，获取更多的参数。用百度解析得的年龄，性别替换face++解析出来的年龄性别。
            if (isset(config('faceRecognition')['detect']) && config('faceRecognition')['detect'] == 'all') {

                $client = new \app\common\api\BaiduAipFace();
                // 如果有可选参数
                $options = array();
                $options["face_field"] = "age,beauty,expression,face_shape,gender,glasses,landmark,race,quality,eye_status,emotion,face_type,quality";

                // 带参数调用人脸检测
                $detectResultBaidu = $client->detect($faceImg, 'URL', $options);
                if ($detectResultBaidu['error_code'] == 0) {
                    if (isset($faces['0']['attributes'])) {
                        $faces['0']['attributes']['gender']['value'] = $detectResultBaidu['result']['face_list']['0']['gender']['type'];
                        $faces['0']['attributes']['age']['value'] = $detectResultBaidu['result']['face_list']['0']['age'];
                    }
                }
            }
        }

        $deviceFaceModel = db('device_face');
        $faceTokenModel = db('face_token');
        if ($deviceCode) {//设备上传图片
            $imgData = [
                'store_id'      => $storeId,
                'block_id'      => $blockId,
                'device_id'     => $deviceId,
                'device_code'   => $deviceCode,
                'position_type' => $positionType,
                'img_url'       => $faceImg,
                'faces'         => $faces ? json_encode($faces) : '',
                'capture_time'  => $captureTime,
                'image_id'      => isset($detectResult['image_id']) ? trim($detectResult['image_id']) : '',
                'img_x'         => $faceX,
                'img_y'         => $faceY,
                'img_pixel'     => $imgPixel,
                'api_type'      => $apiType,
                'add_time'      => time(),
                'update_time'   => time(),
            ];
            $dfaceId = $deviceFaceModel->insertGetId($imgData);
            if (!$dfaceId) {
                return ['code' => 1, 'errMsg' => '图片处理失败', 'face_img' => $faceImg];
            }
        }else{//后台上传图片
            $faceCount = count($faces);
            if ($faceCount != 1) {
                return ['code' => 1, 'errMsg' => '图片内包含'.$faceCount.'张人脸信息', 'face_img' => $faceImg];
            }
            $dfaceId = 0;
        }
        $facePlusApi = new \app\common\api\FaceApi();
        $tencentFaceApi = new  \app\common\api\TencentFaceApi();

        $face = $faces[0];
        $faceToken = trim($face['face_token']);//人脸唯一标识
        $attributes = $face['attributes'] ? $face['attributes'] : [];    //人脸属性特征

        $genderId = $this->_getDataId('gender', $attributes['gender']['value']);   //性别ID
        $age = $attributes['age']['value'];             //年龄数据
        $ageLevel = $this->_getAgeData($age, 'level');  //年龄等级
        $ethnicity = strtolower($attributes['ethnicity']['value']);
        $ethnicity = ($ethnicity === 'india' || $ethnicity === 'arabs' || $ethnicity === 'yellow') ? 'asian' : $ethnicity;//人种信息
        $ethnicityId = isset($attributes['ethnicity']) ? $this->_getDataId('ethnicity', $ethnicity) : 0;
        $tags = strtolower($attributes['gender']['value']); //性别标签

        if (!$deviceCode) {
            $exist = $faceTokenModel->where(['store_id' => $storeId, 'img_url' => $faceImg])->find();
            if ($exist) {
                return ['code' => 0, 'face' => $face, 'fuser_id' => $exist['fuser_id'], 'face_img' => $faceImg];
            }
        }
        //将face_token加入faceset分组内
//        $addReturn = $facePlusApi->_facePlusAdd($faceToken, $tags);
//        if ($addReturn['code'] > 0) {
//            return $addReturn;
//        }
        $searchJsonArray = $compareJsonArray  = $searchFaceQuality = $compareTokenInfo = $searchResult = [];
        $searchScore = 0;
        if (!$searchFaceId) {
            if ($isAdmin) {
                $personId = '';
            }elseif(isset(config('faceRecognition')['search']) && config('faceRecognition')['search'] == 'baidu'){
                //配置了百度云来存储人脸库
                $client = new \app\common\api\BaiduAipFace();
//                $groupIdList = $tags;
                $groupIdList = 'male,female';
                $searchReturn = $client->search($faceImg, 'URL', $groupIdList);
                $searchScore = isset($searchReturn['result']['user_list']) ? trim($searchReturn['result']['user_list']['0']['score']) : 0;
                $searchFaceId = isset($searchReturn['result']['face_token']) ? trim($searchReturn['result']['face_token']) : '';
                $searchResult = isset($searchReturn['result']) ? $searchReturn['result'] : [];
//                if ($searchScore < 80) {
////                    $groupIdList = ($tags == 'male' ? 'female':'male');
//                    $groupIdList = 'male,female';
//                    $searchReturn = $client->search($faceImg, 'URL', $groupIdList);
//                    $searchScore = isset($searchReturn['result']['user_list']) ? trim($searchReturn['result']['user_list']['0']['score']) : 0;
//                    $searchFaceId = isset($searchReturn['result']['face_token']) ? trim($searchReturn['result']['face_token']) : '';
//                }
                if (isset($searchReturn['result']) && $searchReturn['result']) {
                    $searchResult = array_merge($searchJsonArray, $searchReturn['result']);
                }

                $personId = $searchReturn && isset($searchReturn['result']['user_list']) ? trim($searchReturn['result']['user_list']['0']['user_id']): '';

                $compareJsonArray = isset($searchReturn['result']) ? $searchReturn['result'] : '';
                $compareTokenInfo = $compareJsonArray;
                if (!$searchJsonArray) {
                    $searchJsonArray = $searchResult;
                }
            }elseif(isset(config('faceRecognition')['search']) && config('faceRecognition')['search'] == 'haibo'){
                $apiType = 'haibo';
                $client = new Client(config('haibo'));
                $searchReturn = $client->driver('tencent-cloud')->search($faceImg, ['male','female']);
                $searchReturn = json_decode(json_encode($searchReturn),true);
//                $groupIdList = 'male,female';
//                $searchReturn = $client->search($faceImg, 'URL', $groupIdList);
                $searchScore = isset($searchReturn['Results']['0']) ? trim($searchReturn['Results']['0']['Candidates'][0]['Score']) : 0;
                $searchFaceId = isset($searchReturn['Results']['0']) ? trim($searchReturn['Results']['0']['Candidates'][0]['FaceId'])  : '';
                $searchResult = isset($searchReturn) ? $searchReturn : [];

                if (isset($searchReturn['Results']) && $searchReturn['Results']) {
                    $searchResult = array_merge($searchJsonArray, $searchReturn['Results']);
                }

                $personId = $searchReturn && isset($searchReturn['Results']['0']) ? trim($searchReturn['Results']['0']['Candidates'][0]['PersonId']): '';

                $compareJsonArray = isset($searchReturn['Results']) ? $searchReturn['Results'] : '';
                $compareTokenInfo = $compareJsonArray;
                if (!$searchJsonArray) {
                    $searchJsonArray = $searchResult;
                }

            }else{
                //同分组标签搜索用户匹配人脸信息
                $smark =2;
                $searchReturn = $this->_faceSearch($faceImg, $tags, 1);
                $searchFaceId = isset($searchReturn['searchFaceId']) ? trim($searchReturn['searchFaceId']) : '';
                $searchJsonArray = isset($searchReturn['candidates']) ? $searchReturn['candidates'] : [];
                $searchResult = isset($searchReturn['result']) ? $searchReturn['result'] : [];
                if (!$searchFaceId) {
                    $smark = 1;
                    //不同分组标签搜索用户匹配人脸信息
                    $searchReturn = $this->_faceSearch($faceImg, $tags, 0);
                    $searchFaceId = isset($searchReturn['searchFaceId']) ? trim($searchReturn['searchFaceId']) : '';
                    $tempList = isset($searchReturn['candidates']) ? $searchReturn['candidates'] : [];
                    if ($tempList) {
                        $searchJsonArray = $searchJsonArray ? array_merge($searchJsonArray, $tempList) : $tempList;
                    }
                    if (isset($searchReturn['result']) && $searchReturn['result']) {
                        $searchResult = array_merge($searchJsonArray, $searchReturn['result']);
                    }
                }
                //搜索候选者的置信度大于等于65的personId
                $personId = $searchReturn && isset($searchReturn['personId']) ? trim($searchReturn['personId']): '';
                //搜索候选者的置信度大于等于65的置信度
                $searchConfidence = $searchReturn && isset($searchReturn['searchConfidence']) ? trim($searchReturn['searchConfidence']): '';
                if ($searchFaceId) {
                    //对检索结果进行一对一对比
                    $compareResult = $this->_faceCompare($faceImg, $faceToken, $attributes, $searchFaceId, $searchConfidence);
                    if ($compareResult['code'] > 0) {
                        return $compareResult;
                    }

                    $a = isset($compareResult['searchFaceId']) ? trim($compareResult['searchFaceId']) : '';

                    //在所在分组找到相似度65以上，但是匹配不到人，再走其他分组找并匹配。
                    if ($smark==2 && empty($a)) {
                        //不同分组标签搜索用户匹配人脸信息
                        $searchReturn = $this->_faceSearch($faceImg, $tags, 0);
                        $searchFaceId = isset($searchReturn['searchFaceId']) ? trim($searchReturn['searchFaceId']) : '';
                        $tempList = isset($searchReturn['candidates']) ? $searchReturn['candidates'] : [];
                        if ($tempList) {
                            $searchJsonArray = $searchJsonArray ? array_merge($searchJsonArray, $tempList) : $tempList;
                        }
                        if (isset($searchReturn['result']) && $searchReturn['result']) {
                            $searchResult = array_merge($searchJsonArray, $searchReturn['result']);
                        }
                        //搜索候选者的置信度大于等于65的personId
                        $personId = $searchReturn && isset($searchReturn['personId']) ? trim($searchReturn['personId']): '';
                        //搜索候选者的置信度大于等于65的置信度
                        $searchConfidence = $searchReturn && isset($searchReturn['searchConfidence']) ? trim($searchReturn['searchConfidence']): '';
                        if($searchFaceId){
                            //对检索结果进行一对一对比
                            $compareResult = $this->_faceCompare($faceImg, $faceToken, $attributes, $searchFaceId, $searchConfidence);
                            if ($compareResult['code'] > 0) {
                                return $compareResult;
                            }
                        }

                    }

                    $searchFaceId = isset($compareResult['searchFaceId']) ? trim($compareResult['searchFaceId']) : '';
                    $compareJsonArray = isset($compareResult['compare']) ? $compareResult['compare'] : '';
                    $compareTokenInfo = $compareJsonArray && isset($compareJsonArray['compare_token']) ? $compareJsonArray['compare_token'] : [];
                }
                if (!$searchJsonArray) {
                    $searchJsonArray = $searchResult;
                }
            }
        }elseif ($searchFaceId && $searchFuserId){
            $personId = 'person_'.$searchFuserId;
            $compareTokenInfo = $faceTokenModel->where(['face_id' => $searchFaceId])->find();
        }else{
            return ['code' => 1, 'errMsg' => '参数错误', 'face_img' => $faceImg];
        }
        //获取分组信息
        if(isset(config('faceRecognition')['search']) && config('faceRecognition')['search'] == 'baidu'){
            $faceset = $this->_facesetGet($tags,'baidu',10000000); //personcount理论上没有限制，给1000万人
        }else{
            $faceset = $this->_facesetGet($tags);
        }

        if ($faceset['code'] > 0) {
            return $faceset;
        }
        $addFlag = TRUE;
        $addPersonReturn = $this->_faceAddPerson($faceImg, $faceToken, $attributes, $storeId, $faceset, $personId, $searchFaceId, $compareTokenInfo,$searchScore,$apiType);
        if ($addPersonReturn['code'] > 0) {
            return $addPersonReturn;
        }
        $faceId = $addPersonReturn && isset($addPersonReturn['face_id']) ? trim($addPersonReturn['face_id']) : '';
        $addFlag = $addPersonReturn && isset($addPersonReturn['add_flag']) ? trim($addPersonReturn['add_flag']) : 0;
        $fuserId = $addPersonReturn && isset($addPersonReturn['fuserId']) ? trim($addPersonReturn['fuserId']) : 0;
        $addJsonArray = $addPersonReturn && isset($addPersonReturn['add_result']) ? $addPersonReturn['add_result'] : [];
        //判断用户图片信息是否存在
        //创建face_token表
        $tokenData = [
            'store_id'      => $storeId,
            'block_id'      => $blockId,
            'device_id'     => $deviceId,
            'device_code'   => $deviceCode,
            'position_type' => $positionType,
            'dface_id'      => $dfaceId,
            'img_url'       => $faceImg,
            'fuser_id'      => $fuserId,
            'faceset_id'    => $faceset['faceset_id'],
            'face_token'    => $faceToken,  //人脸的标识(Face++)
            'face_id'       => $faceId,     //人脸的标识(腾讯)
            'gender'        => $genderId,
            'age'           => $age,
            'tags'          => $tags,
            'ethnicity'     => $ethnicityId,
            'emotion'       => $attributes['emotion'] ? $this->_getDataId('emotion', face_get_max($attributes['emotion'])) : 0,
            'headpose'      => $attributes['headpose'] ? $this->_getDataId('headpose', face_get_max($attributes['headpose'])) : 0,
            'fquality_value'     => $attributes['facequality']['value'],//人脸质量判断结果,value：值为人脸的质量判断的分数，threshold：表示人脸质量基本合格的一个阈值，超过该阈值的人脸适合用于人脸比对。
            'fquality_threshold' => $attributes['facequality']['threshold'],//人脸质量判断结果,value：值为人脸的质量判断的分数，threshold：表示人脸质量基本合格的一个阈值，超过该阈值的人脸适合用于人脸比对。
            'face_rectangle'=> $face['face_rectangle'] ? json_encode($face['face_rectangle']) : '',
            'add_time'      => time(),
            'update_time'   => time(),
            'capture_time'  => $captureTime,
            'img_x'         => $faceX,
            'img_y'         => $faceY,
            'attributes'    => $attributes ? json_encode($attributes) : '',
            'is_del'        => $addFlag ? 0 : 1,
            'api_type'      => $apiType,
            'search_result' => isset($searchJsonArray) && $searchJsonArray ? json_encode($searchJsonArray) : '',    //搜索结果
            'compare_result'=> isset($compareJsonArray) && $compareJsonArray ? json_encode($compareJsonArray) : '', //1对1 对比结果
            'add_result'    => isset($addJsonArray) && $addJsonArray? json_encode($addJsonArray) : '',              //图片对应人脸数据添加到个体
        ];
        $tokenId = $faceTokenModel->insertGetId($tokenData);
        if ($tokenId){
            return ['code' => 0, 'face' => $face, 'fuser_id' => $fuserId, 'face_img' => $faceImg];
        }else{
            return ['code' => 1, 'errMsg' => '系统繁忙，请稍后重试'];
        }
    }

    /**
     * Face++接口:将face_token添加至faceSet (避免face_token失效)
     * @param string $faceToken 人脸face_token唯一标识
     * @param int $storeId      门店ID
     * @param string $tags      faceset标签(目前按性别区分:male female)
     * @param string $apiType   接口类型(固定为faceplus)
     * @param int $maxFaceCount faceSet内最大face_token保存数量
     * @return array
     */
    public function _facePlusAdd($faceToken, $tags, $apiType = 'faceplus', $maxFaceCount = 1000)
    {
        $facePlusApi = new \app\common\api\FaceApi();
        //判断当前face_token对应的faceSet是否存在
        $where = [
            'is_del'    => 0,
            'tags'      => $tags,
            'api_type'  => $apiType,
        ];
        $faceSetInfo = db('faceset')->where($where)->where('face_count', '<', $maxFaceCount)->order('add_time DESC')->find();
        if (!$faceSetInfo) {
            //创建faceSet表
            $where['display_name'] = $tags;
            $where['add_time'] = $where['update_time'] = time();
            $faceSetId = db('faceset')->insertGetId($where);
            if(!$faceSetId){
                return ['code' => 1, 'errMsg' => '系统繁忙,请稍后再试'];
            }
            $setTag = $tags.'_'.$faceSetId;
            //接口创建一个人脸的集合 FaceSet
            $createResult = $facePlusApi->faceSetCreate($setTag, $faceSetId, $setTag, $faceToken, $tags);
            if (!is_array($createResult)) {
                return ['code' => 1, 'errMsg' => 'faceSet创建失败'];
            }
            if (isset($createResult['error_message']) && $createResult['error_message'] == 'FACESET_EXIST') {
                //获取组信息
                $faceSetResult = $facePlusApi->faceSetDetail(false, $faceSetId);
                if (isset($faceSetResult['faceset_token'])) {
                    $faceSetToken = $faceSetResult['faceset_token'];
                    $faceCount = $faceSetResult['face_count'];
                }else{
                    $createParams = ['display_name' => $setTag, 'out_id' => $faceSetId, 'tags' => $setTag, 'face_token' => $faceToken, 'user_data' => $setTag];
                    $detailParams = ['out_id' => $faceSetId];
                    return ['code' => 1, 'errMsg' => 'faceSet信息获取错误:'.$faceSetResult['error_message'], 'createParams' => $createParams, 'detailParams' => $detailParams];
                }
            }elseif ($createResult && !isset($createResult['faceset_token'])) {
                db('faceset')->where(['faceset_id' => $faceSetId])->delete();
                return ['code' => 1, 'errMsg' => 'faceSet创建错误:'.$createResult['error_message']];
            }else{
                $faceSetToken = isset($createResult['faceset_token']) ? $createResult['faceset_token'] : '';
                $faceCount = isset($createResult['face_count']) ? $createResult['face_count'] : 0;
            }
            db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'faceset_token' => $faceSetToken, 'face_count' => $faceCount]);
        }else{
            //将face_token添加到当前 FaceSet
            $faceSetId = $faceSetInfo['faceset_id'];
            $faceSetToken = $faceSetInfo['faceset_token'];
            $addResult = $facePlusApi->faceSetAddFace($faceSetToken, $faceSetId, $faceToken);
            if (!is_array($addResult)) {
                return ['code' => 1, 'errMsg' => 'face_token添加至faceSet失败'];
            }
            if ($addResult && isset($addResult['error_message'])) {
                $addParams = ['faceset_token' => $faceSetToken, 'outer_id' => $faceSetId, 'face_token' => $faceToken];
                return ['code' => 1, 'errMsg' => 'face_token添加至faceSet错误:'.$addResult['error_message'], 'addParams' => $addParams];
            }
            $faceCount = isset($addResult['face_count']) ? intval($addResult['face_count']) : 0;
            $result = db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'face_count' => $faceCount]);
        }
        return ['code' => 0];
    }
    /**
     * 腾讯云接口:将图片内的face_id添加至个体 并创建face_user表数据
     * @param string $faceImg           人脸图片
     * @param string $faceToken         Face++接口解析的face_token
     * @param array[] $attributes       Face++接口解析的人脸属性(年龄\性别等)
     * @param int $storeId              门店ID
     * @param array $faceSet            个体分组信息[腾讯云分组]
     * @param string $personId          个体ID('user'+fuser_id)
     * @param string $searchFaceId      检索匹配的腾讯云face_id
     * @param array $compareTokenInfo   检索匹配的数据库人脸信息
     * @param string $apiType           接口类型
     * @return array|number[]|string[]|unknown[]|number[]|string[]|unknown[]|mixed[]|number[]|string[]|unknown[]|number[]|boolean[]|unknown[]|string[]|mixed[]|array[]
     */
    public function _faceAddPerson($faceImg, $faceToken, $attributes, $storeId, $faceSet, $personId, $searchFaceId, $compareTokenInfo, $searchScore = 0, $apiType = 'all')
    {
        $tencentFaceApi = new  \app\common\api\TencentFaceApi();

        $faceSetId = $faceSet['faceset_id'];
        $faceSetToken = $faceSet['faceset_token'];

        $genderId = $this->_getDataId('gender', $attributes['gender']['value']);   //性别ID
        $age = $attributes['age']['value'];             //年龄数据
        $ageLevel = $this->_getAgeData($age, 'level');  //年龄等级
        $ethnicity = strtolower($attributes['ethnicity']['value']);
        $ethnicity = ($ethnicity === 'india' || $ethnicity = 'yellow' || $ethnicity = 'arabs') ? 'asian' : $ethnicity;//人种信息
        $ethnicityId = isset($attributes['ethnicity']) ? $this->_getDataId('ethnicity', $ethnicity) : 0;
        $tags = strtolower($attributes['gender']['value']); //性别标签

        $addJsonArray  = $createJsonArray= [];
        $addFlag = TRUE;

        if($apiType == 'baidu') {
            //存在￥searchScore，说明使用百度云人脸库
            if($searchScore < 80){
                //创建face_user表
                $data = [
                    'gender'        => $genderId,
                    'age'           => $age,
                    'age_level'     => $ageLevel,
                    'ethnicity'     => $ethnicityId,
                    'face_token'    => $faceToken,
                    'avatar'        => $faceImg,
                    'fquality_value'     => $attributes['facequality']['value'],
                    'fquality_threshold' => $attributes['facequality']['threshold'],
                    'tags'          => $tags,
                    'add_time'      => time(),
                    'update_time'   => time(),
                    'faceset_id'    => $faceSetId,
                    'api_type'      => 'baidu',
                    'store_id'      => $storeId,
                ];
                $fuserId = db('face_user')->insertGetId($data);
                $personId = 'person_'.$fuserId;
            }
            $groupId = $tags;
            $client = new \app\common\api\BaiduAipFace();
            $fuserExist = db('face_user')->where([['fuser_id','=', substr($personId,7)], ['is_del','=',0]])->find();
            $lab = true;
            if($fuserExist['token_count'] >= 10){
                $lab = false;
                $temp = db('face_token') -> where('fuser_id','=',substr($personId,7))->where('is_del','=',0)->order('fquality_value asc') -> find();
                $addReturn = $client->faceDelete($personId, $groupId, $temp['face_token']);
                $temp['is_del'] = 1;
                db('face_token') -> update($temp);
            }
            $addReturn = $client->addUser($faceImg, 'URL', $groupId, $personId);
            if ($addReturn['error_code'] > 0) {
                $addReturn['code'] = 1;
                return $addReturn;
            }
            $faceId         = isset($addReturn) && $addReturn ?  $addReturn['result']['face_token']: '';
            $fuserId        = isset($addReturn) && $addReturn ?  substr($personId,7): 0;
            $addJsonArray   = isset($addReturn) && $addReturn ?  $addReturn['result']: [];
            if($lab){
                db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'face_count' => ['inc', 1]]);
                db('face_user')->where(['fuser_id' => $fuserId])->update(['update_time' => time(), 'token_count' => ['inc', 1]]);
            }
        }elseif($apiType == 'haibo'){
            $client = new Client(config('haibo'));
            //存在￥searchScore，说明使用百度云人脸库
            $lab = true;
            if($searchScore < 80){
                //创建face_user表
                $data = [
                    'gender'        => $genderId,
                    'age'           => $age,
                    'age_level'     => $ageLevel,
                    'ethnicity'     => $ethnicityId,
                    'face_token'    => $faceToken,
                    'avatar'        => $faceImg,
                    'fquality_value'     => $attributes['facequality']['value'],
                    'fquality_threshold' => $attributes['facequality']['threshold'],
                    'tags'          => $tags,
                    'add_time'      => time(),
                    'update_time'   => time(),
                    'faceset_id'    => $faceSetId,
                    'api_type'      => $apiType,
                ];
                $fuserId = db('face_user')->insertGetId($data);
                $personId = 'person_'.$fuserId;
                $addReturn = $client->driver('tencent-cloud')->addUser($faceImg, $personId, $tags, $personId);
                $addReturn = json_decode(json_encode($addReturn),true);
                $faceId = isset($addReturn) && $addReturn ?  $addReturn['FaceId']: '';

            }else{
                $groupId = $tags;
                $fuserExist = db('face_user')->where([['fuser_id','=', substr($personId,7)], ['is_del','=',0]])->find();
                if($fuserExist['token_count'] >= 5){
                    $lab = false;
                    $temp = db('face_token') -> where('fuser_id','=',substr($personId,7))->where('is_del','=',0)->order('fquality_value asc') -> find();
//                    $addReturn = $client->faceDelete($personId, $groupId, $temp['face_token']);
                    $addReturn = $client->driver('tencent-cloud')->deleteFace($personId,$searchFaceId);
                    $temp['is_del'] = 1;
                    @db('face_token') ->where('token_id','=',$temp['token_id'])-> update($temp);
                }
                $addReturn = $client->driver('tencent-cloud')->addFace($personId, $faceImg);
                $addReturn = json_decode(json_encode($addReturn),true);
                $faceId = isset($addReturn) && $addReturn ?  $addReturn['SucFaceIds']['0']: '';
            }

//            if ($addReturn['error_code'] > 0) {
//                $addReturn['code'] = 1;
//                return $addReturn;
//            }
            $fuserId        = isset($addReturn) && $addReturn ?  substr($personId,7): 0;
            $addJsonArray   = isset($addReturn) && $addReturn ?  $addReturn: [];
            if($lab){
                db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'face_count' => ['inc', 1]]);
                db('face_user')->where(['fuser_id' => $fuserId])->update(['update_time' => time(), 'token_count' => ['inc', 1]]);
            }
        } elseif ($personId && $searchFaceId && $compareTokenInfo) {
            $searchFaceQuality = $compareTokenInfo ? ($compareTokenInfo['fquality_value'] - $compareTokenInfo['fquality_threshold']) : 0;
            $addReturn = $this->_personAddFace($faceImg, $personId, 0, $faceToken, $searchFaceId, $attributes, $searchFaceQuality);
            if ($addReturn['code'] > 0) {
                return $addReturn;
            }
            $faceId         = isset($addReturn) && $addReturn ?  $addReturn['face_id']: '';
            $addFlag        = isset($addReturn) && $addReturn ?  $addReturn['flag']: $addFlag;
            $fuserId        = isset($addReturn) && $addReturn ?  $addReturn['fuser_id']: 0;
            $addJsonArray   = isset($addReturn) && $addReturn ?  $addReturn['add_result']: [];
        }else{
            $searchFaceQuality = $compareTokenInfo ? ($compareTokenInfo['fquality_value'] - $compareTokenInfo['fquality_threshold']) : 0;
            //创建face_user表
            $data = [
                'gender'        => $genderId,
                'age'           => $age,
                'age_level'     => $ageLevel,
                'ethnicity'     => $ethnicityId,
                'face_token'    => $faceToken,
                'avatar'        => $faceImg,
                'fquality_value'     => $attributes['facequality']['value'],
                'fquality_threshold' => $attributes['facequality']['threshold'],
                'tags'          => $tags,
                'add_time'      => time(),
                'update_time'   => time(),
                'faceset_id'    => $faceSetId,
                'api_type'      => $apiType,
                'store_id'      => $storeId,
            ];
            $fuserId = db('face_user')->insertGetId($data);
            $personId = 'person_'.$fuserId;
            //创建个体
            $createPersonResult = $tencentFaceApi->facePersonCreate($faceImg, [$faceSetToken], $personId, $faceToken);
            if (!is_array($createPersonResult)) {
                return ['code' => 1, 'errMsg' => '创建个体失败', 'face_img' => $faceImg];
            }
            $createJsonArray = isset($createPersonResult) && $createPersonResult ?  $createPersonResult: [];
            if ($createPersonResult['code'] == '-1302') {
                //创建的个人在腾讯云已存在,通过接口查询个人信息
                $personResult = $tencentFaceApi->getFacePersonInfo($personId);
                if (!is_array($personResult)) {
                    return['code' => 1, 'errMsg' => '个体信息查询错误', 'face_img' => $faceImg];
                }
                if ($personResult['code'] != 0){
                    return ['code' => 1, 'errMsg' => '个体信息查询错误:'.$personResult['message'], 'code' => $personResult['code'], 'face_img' => $faceImg];
                }
                $personId = $personResult && isset($personResult['data']['person_id']) ? $personResult['data']['person_id'] : '';
                $faceIds = $personResult && isset($personResult['data']['face_ids']) ? $personResult['data']['face_ids'] : [];
                //判断当前face_id是否已存在个人token内
                if (!isset($searchFaceId) || !$searchFaceId || !in_array($searchFaceId, $faceIds)) {//huangyihao
                    $faceCount = $faceIds ? count($faceIds) :0;
                    //不存在则将当前token加入个人信息
                    $addReturn = $this->_personAddFace($faceImg, $personId, $faceCount, $faceToken, $searchFaceId, $attributes, $searchFaceQuality);
                    if ($addReturn['code'] > 0) {
                        return $addReturn;
                    }
                    $faceId = $addReturn['face_id'];
                    if ($faceId) {
                        db('face_user')->where(['fuser_id' => $fuserId])->update(['update_time' => time(), 'face_token' => $faceToken]);
                        db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'face_count' => ['inc', 1]]);//huangyihao
                    }
                }
            }elseif ($createPersonResult['code'] != 0){
                db('face_user')->where(['fuser_id' => $fuserId])->update(['update_time' => time(), 'is_del' => 1]);
                if ($createPersonResult['code'] == -1101) {
                    return ['code' => 1, 'errMsg' => '腾讯云人脸检测失败', 'code' => $createPersonResult['code'], 'face_img' => $faceImg];
                }
                return ['code' => 1, 'errMsg' => '个体创建错误:'.$createPersonResult['message'], 'code' => $createPersonResult['code'], 'face_img' => $faceImg];
            }else{
                $faceId = isset($createPersonResult['data']['face_id']) ? $createPersonResult['data']['face_id'] : '';
                $sucFace = isset($createPersonResult['data']['suc_face']) ? $createPersonResult['data']['suc_face'] : 0;
                if ($sucFace) {
                    db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'face_count' => ['inc', 1]]);
                    db('face_user')->where(['fuser_id' => $fuserId])->update(['update_time' => time(), 'token_count' => ['inc', $sucFace]]);
                }
            }
        }

        if (!$faceId) {
            //解析图片
            $detectImgResult = $tencentFaceApi->detectApi($faceImg);
            if (!is_array($detectImgResult)) {
                return['code' => 1, 'errMsg' => '图片解析失败', 'face_img' => $faceImg];
            }
            if ($detectImgResult['code'] != 0){
                if ($detectImgResult['code'] == '-1101') {
                    return ['code' => 1, 'errMsg' => '腾讯云人脸检测失败', 'code' => $detectImgResult['code'], 'face_img' => $faceImg];
                }
                return ['code' => 1, 'errMsg' => '图片解析失败:'.$detectImgResult['message'], 'code' => $detectImgResult['code'], 'face_img' => $faceImg];
            }
            $detectImgData = $detectImgResult && isset($detectImgResult['data']) ? $detectImgResult['data'] : [];
            $faces = $detectImgData && isset($detectImgData['face']) ? $detectImgData['face'] : [];
            $faceId = $faces && isset($faces[0]) ? trim($faces[0]['face_id']) : '';   //人脸唯一标识
        }
        $returnJson['create'] = $createJsonArray;
        $returnJson['add'] = $addJsonArray;
        return ['code' => 0, 'face_id' => $faceId, 'add_flag' => $addFlag, 'fuserId' => $fuserId, 'add_result' => $returnJson];
    }
    /**
     * 腾讯云接口:将图片内的face_id添加至腾讯云个体
     * @param string $faceImg       人脸图片
     * @param string $personId      个体Id('person_'+fuser_id)
     * @param int $personFaceCount  个体内的人脸数量
     * @param string $faceToken     Face++接口解析的人脸face_token
     * @param string $searchFaceId  检索匹配的腾讯云face_id
     * @param array $attributes     Face++接口解析的人脸属性(年龄\性别等)
     * @param int $searchFaceQuality检索匹配人脸质量数据差
     * @return array
     */
    public function _personAddFace($faceImg = '', $personId = '', $personFaceCount = 0, $faceToken = '', $searchFaceId = '', $attributes = [], $searchFaceQuality = 0)
    {
        if(!$faceImg){
            return ['code' => 1, 'errMsg' => '参数不能为空', 'face_img' => $faceImg];
        }

        if (is_int($personId)) {
            $fuserId = $personId;
        }else{
            $person = explode('_', $personId);
            $fuserId = $person && isset($person[1]) ? intval($person[1]) : 0;
        }
        $tencentFaceApi = new  \app\common\api\TencentFaceApi();
        $flag = TRUE;
        $faceId = '';
        if ($attributes) {
            $genderId = $this->_getDataId('gender', $attributes['gender']['value']);   //性别ID
            $age = $attributes['age']['value'];     //年龄数据
            $ageLevel = $this->_getAgeData($age, 'level'); //年龄等级
            $ethnicity = strtolower($attributes['ethnicity']['value']);
            $ethnicity = $ethnicity === 'india' ? 'asian' : $ethnicity;
            $ethnicityId = isset($attributes['ethnicity']) ? $this->_getDataId('ethnicity', $ethnicity) : 0;
        }
        $faceQuality = $attributes && isset($attributes['facequality']) ? $attributes['facequality'] : [];
        $thisFaceQuality = isset($faceQuality['value']) ? ($faceQuality['value'] - $faceQuality['threshold']) : 0;

        if ($faceToken) {
            $tokenInfo = db('face_token')->where(['face_token' => $faceToken])->find();
        }
        if ($fuserId) {
            $fuserExist = db('face_user')->where(['fuser_id' => $fuserId, 'is_del' => 0])->find();
            $userData = [];
            if ($fuserExist) {
                $userFaceQuality = $fuserExist['fquality_value'] - $fuserExist['fquality_threshold'];
                if ($faceToken && $faceQuality && $thisFaceQuality > $userFaceQuality) {
                    $userData['avatar']   = $faceImg;
                    $userData['age']      = $age;
                    $userData['gender']   = $genderId;
                    $userData['age_level'] = $this->_getAgeData($age, 'level');  //年龄等级
                    $userData['ethnicity']= $ethnicityId;
                    $userData['fquality_value'] = $faceQuality['value'];
                    $userData['fquality_threshold'] = $faceQuality['threshold'];
                }
            }
            if ($personFaceCount && $personFaceCount != $fuserExist['token_count']) {
                $userData['update_time'] = time();
                $userData['token_count'] = $personFaceCount;
            }
            if ($userData) {
                db('face_user')->where(['fuser_id' => $fuserId])->update($userData);
            }
            if (!$personFaceCount) {
                $personFaceCount = isset($fuserExist['token_count']) ? $fuserExist['token_count'] : 0;
            }
            $maxFaceCount = 18;//接口20个，为了控制高并发，设置为18
            if ($personFaceCount >= $maxFaceCount) {
                $flag = FALSE;
                //对比人脸清晰度，当前人脸清晰度高于搜索人脸清晰度则删除搜索的人脸重新添加新的人脸(数据库逻辑删除 接口删除)
                if ($faceToken && $tokenInfo && $tokenInfo['face_id'] && $thisFaceQuality && $searchFaceId && $thisFaceQuality > $searchFaceQuality) {
                    //接口删除原有人脸
                    $deletePersonResult = $tencentFaceApi->facePersonDelFace([$tokenInfo['face_id']], $personId);
                    if ($deletePersonResult && isset($deletePersonResult['code']) && $deletePersonResult['code'] = 0) {
                        $flag = TRUE;
                    }
                }
            }
        }
        $addResult = [];
        if ($flag) {
            if ($personFaceCount < 1 || ($personFaceCount >= 1 && $searchFaceId && $personId && $faceQuality['value'] >= 10)) {
                //增加人脸
                $addResult = $tencentFaceApi->facePersonAddFace($faceImg, $personId, $faceToken);
                if (!is_array($addResult)) {
                    return ['code' => 1, 'errMsg' => 'face添加至个体失败'.$addResult];
                }
                if ($addResult['code'] == '-1312' || (isset($addResult['data']['ret_codes'][0]) && $addResult['data']['ret_codes'][0] == '-1312')) {
                    $flag = FALSE;
                    //对个体添加了相似度为99%及以上的人脸,添加失败不处理
                }elseif ($addResult['code'] != 0){
                    return ['code' => 1, 'errMsg' => 'face添加至个体错误:'.$addResult['message'],'code' => $addResult['code']];
                }
                $faceId = isset($addResult['data']['face_ids']) ? $addResult['data']['face_ids'][0] : '';
            }else{
                $flag = FALSE;
            }
        }
        $update = [];
        if ($flag) {
            $update['update_time'] = time();
            $update['token_count'] = ['inc', 1];
        }
        if ($update) {
            db('face_user')->where(['fuser_id' => $fuserId])->update($update);
        }
        return [
            'code'   => 0,
            'flag'      => $flag,
            'fuser_id'  => $fuserId,
            'face_id'   => $faceId,
            'add_result'=> isset($addResult) ? $addResult : '',
        ];
    }
    /**
     * 获取用户的腾讯云分组信息
     * @param int $storeId      门店ID
     * @param string $tags      分组标签信息(目前按性别作为分组标签)
     * @param string $apiType   接口类型(固定为tencent)
     * @param int $maxPersonCount 腾讯云分组内最大的个体数量
     * @return array
     */
    public function _facesetGet($tags, $apiType = 'tencent', $maxPersonCount = 20000)
    {
        //判断当前face_token对应的faceSet是否存在
        $where = [
            'is_del'    => 0,
            'tags'      => $tags,
            'api_type'  => $apiType,
        ];
        $faceSetInfo = db('faceset')->where($where)->where('face_count', '<', $maxPersonCount)->order('add_time DESC')->find();
        if (!$faceSetInfo) {
            //创建faceSet表
            $where['display_name'] = $tags;
            $where['add_time'] = $where['update_time'] = time();
            $faceSetId = db('faceset')->insertGetId($where);
            if(!$faceSetId){
                return ['code' => 1, 'errMsg' => 'faceSet创建失败'];
            }
            $faceSetToken = $tags.'_'.$faceSetId;
            db('faceset')->where(['faceset_id' => $faceSetId])->update(['update_time' => time(), 'faceset_token' => $faceSetToken]);
        }else{
            //将face_token添加到当前 FaceSet
            $faceSetId = $faceSetInfo['faceset_id'];
            $faceSetToken = $faceSetInfo['faceset_token'];
        }
        return ['code' => 0, 'faceset_id' => $faceSetId, 'faceset_token' => $faceSetToken];
    }
    /**
     * Face++接口: 人脸一对一对比
     * @param string $faceImg   人脸图片
     * @param string $faceToken Face++解析的人脸face_token
     * @param array $attributes Face++解析的人脸属性信息
     * @param string $searchFaceId      腾讯云检索后匹配的face_id
     * @param string $searchConfidence  腾讯云检索后匹配的face_id对应的置信度
     * @param string $apiType   接口类型
     * @return array
     */
    public function _faceCompare($faceImg, $faceToken, $attributes, $searchFaceId, $searchConfidence, $apiType = 'all')
    {
        $faceTokenModel = db('face_token');
        $facePlusApi = new \app\common\api\FaceApi();
        $compareParams['token'] = [
            'face_img'      => $faceImg,
            'face_token'    => $faceToken,
            'search_face_id'=> $searchFaceId,
            'search_face_confidence' => $searchConfidence,
        ];
        $compareToken = $faceTokenModel->where(['face_id' => $searchFaceId, 'api_type' => $apiType])->find();
        if ($compareToken) {
            unset($compareToken['face_rectangle'], $compareToken['attributes'], $compareToken['search_result'], $compareToken['compare_result'], $compareToken['add_result']);
        }
        $compareParams['compare_token'] = $compareToken ? $compareToken : [];
        if ($searchFaceId) {
            if ($searchConfidence < 90) {
                if ($compareToken) {
                    $searchFaceQuality = $compareToken['fquality_value'] - $compareToken['fquality_threshold'];
                    //Face++实现人脸一对一对比
                    $compareResult = $facePlusApi->compareApi(trim($faceToken), trim($compareToken['face_token']));
                    if (!is_array($compareResult)) {
                        return ['code' => 1, 'errMsg' => '一对一 compare错误', 'params' => ['face_token1' => $faceToken, 'face_token2' => $compareToken['face_token']]];
                    }
                    if ($compareResult && isset($compareResult['error_message'])) {
                        return ['code' => 1, 'errMsg' => '一对一 compare错误:'.$compareResult['error_message'], 'params' => ['face_token1' => $faceToken, 'face_token2' => $compareToken['face_token']]];
                    }
                    $thresholds = isset($compareResult['thresholds']) ? $compareResult['thresholds'] : [];//一组用于参考的置信度阈值(1e-3：误识率为千分之一的置信度阈值； 1e-4：误识率为万分之一的置信度阈值；1e-5：误识率为十万分之一的置信度阈值；)
                    $faceConfidence = $compareResult && isset($compareResult['confidence']) ? $compareResult['confidence'] : 0;//比对结果置信度，范围 [0,100]，小数点后3位有效数字，数字越大表示两个人脸越可能是同一个人。
                    //判断结果置信度是否某个范围内(如果置信值低于“千分之一”阈值则不建议认为是同一个人；如果置信值超过“十万分之一”阈值，则是同一个人的几率非常高。)
//                    $threshold = ($attributes['facequality']['value'] >= 10 && $compareToken['fquality_value'] >=10) ? $thresholds['1e-5']: $thresholds['1e-4'];
                    $threshold = $thresholds['1e-4'];//huangyihao
                    $compareParams['compare_result'] = $compareResult;
                    if ($faceConfidence < $threshold) {
                        $searchFaceId = FALSE;
                    }
                }else{
                    $searchFaceId = FALSE;
                }
            }
        }else{
            $searchFaceId = FALSE;
        }
        return ['code' => 0, 'searchFaceId' => $searchFaceId, 'compare' => $compareParams];
    }
    /**
     * 腾讯云接口:人脸检索(分组检索)
     * @param string $faceImg   人脸图片
     * @param string $tags      分组标签
     * @param int $eq           查询匹配条件(1为等于当前标签 0为不等于当前标签)
     * @param string $apiType   接口类型
     * @param int $maxPersonCount腾讯云分组最大个体数量
     * @return array
     */
    public function _faceSearch($faceImg, $tags, $eq = 1, $apiType = 'tencent', $maxPersonCount = 20000)
    {
        $tencentFaceApi = new  \app\common\api\TencentFaceApi();
        $searchFaceId = $personId = $_searchFaceId = $searchConfidence = $searchResult = '';
        //判断当前用户对应分组是否存在
        $where = [
            'is_del'    => 0,
            'api_type'  => $apiType
        ];
        if ($eq) {
            $where['tags'] = $tags;
        }else{
            $where['tags'] = ['<>', $tags];
        }
        $faceSetList = db('faceset')->where($where)->where('face_count', '<=', $maxPersonCount)->select();
        $searchResult = [];
        if ($faceSetList) {
            $faceSetSize = count($faceSetList);
            if ($faceSetSize > 1) {
                $groupIds = [];
                foreach ($faceSetList as $k => $v) {
                    $groupIds[] = $v['faceset_token'];
                }
                $searchResult = $tencentFaceApi->searchApi(false, $groupIds, $faceImg);
            }else{
                $searchResult = $tencentFaceApi->searchApi($faceSetList[0]['faceset_token'], false, $faceImg);
            }
            if (!is_array($searchResult)) {
                return ['code' => 1, 'errMsg' => '图片检索错误', 'face_img' => $faceImg];
            }
            if ($searchResult['code'] != 0){
                return ['code' => 1, 'errMsg' => '图片检索错误:'.$searchResult['message'], 'code' => $searchResult['code'], 'face_img' => $faceImg];
            }
            $searchData = $searchResult && isset($searchResult['data']) ? $searchResult['data'] : [];
            $candidates = $searchData && isset($searchData['candidates']) ? $searchData['candidates'] : [];
            if ($candidates) {
                $firstFace = $candidates ? $candidates[0]: [];
                $searchConfidence = isset($firstFace['confidence']) && $firstFace['confidence'] ? $firstFace['confidence'] : 0;
                $_searchFaceId = isset($firstFace['face_id']) && $firstFace['face_id'] ? $firstFace['face_id'] : '';
                if ($searchConfidence >= 65) {//候选者的置信度大于等于65则认为是同一个人
                    $personId = $firstFace['person_id'];
                    $searchFaceId = $firstFace['face_id'];
                }
            }else{
                return ['code' => 1, 'errMsg' => '未检索到图片', 'face_img' => $faceImg];
            }
        }else{
            $searchResult[] = ['error' => 'no faseset', 'map' => $where];
        }
        return [
            'searchFaceId'      => $searchFaceId,
            'personId'          => $personId,
            'searchConfidence'  => $searchConfidence,
            'candidates'        => isset($candidates) ? $candidates : [],
            'result'            => isset($searchResult) ? $searchResult : [],
        ];
    }
    /**
     * Face++接口:人脸解析
     * @param string $faceImg 需要解析的图片地址
     * @return number[]|string[]|number[]|string[]|unknown[]|number[]|string[]|array[]|mixed[]
     */
    public function _faceDetect($faceImg)
    {
        if (!$faceImg) {
            return ['code' => 1, 'errMsg' => '图片不能为空'];
        }
        $facePlusApi = new \app\common\api\FaceApi();
        //用Face++接口解析图片
        $detectResult = $facePlusApi->detectApi($faceImg);
        if (!is_array($detectResult)) {
            return ['code' => 1, 'errMsg' => '接口解析图片异常', 'face_img' => $faceImg];
        }
        if ($detectResult && isset($detectResult['error_message'])) {
            return ['code' => 1, 'errMsg' => '图片解析错误:'.$detectResult['error_message'], 'face_img' => $faceImg];
        }
        if (!isset($detectResult['faces'])) {
            return ['code' => 1, 'errMsg' => '图片内无人脸信息(未检测到人脸,请重新选择一张图片，并确保人脸正对画面)', 'face_img' => $faceImg];
        }
        $faces = $detectResult['faces'] ? $detectResult['faces'] : [];
        if (!$faces) {
            return ['code' => 1, 'errMsg' => '您选择的图片内无人脸信息', 'face_img' => $faceImg];
        }
        $imageId = isset($detectResult['image_id']) ? trim($detectResult['image_id']) : '';
        return ['code' => 0, 'faces' => $faces, 'image_id' => $imageId];
    }

    /**
     * 门店每日抓拍用户记录
     * @param int $storeId      门店ID
     * @param int $fuserId      会员ID
     * @param int $captureTime  抓拍时间戳
     * @param int $positionType 设备类型:1进店 2离店 3其它
     * @param string $avatar    头像
     * @param int $age          年龄
     * @param int $ageLevel     年龄等级
     * @param int $genderId     性别
     * @return array()
     */
    public function _dayVisit($storeId = 0, $fuserId = 0, $captureTime = 0, $positionType = 3, $avatar = '', $age = 0, $ageLevel = 0, $genderId = 0, $faceToken = '', $faceQuality = [], $emotion = 0)
    {
        if (!$storeId || !$fuserId) {
            return FALSE;
        }

        $date = $captureTime ? date('Y-m-d', $captureTime): date('Y-m-d');
        //判断当日是否存在用户到访信息
        $where = [
            'store_id' => $storeId,
            'fuser_id' => $fuserId,
            'capture_date' => $date,
            'is_del' => 0,
        ];
        $data = [
            'recent_time' => $captureTime, //最近抓拍时间
        ];
        $visitExist = db('day_visit')->where($where)->find();
        $personStep = $customeStep = $stayTimeValue = 0;
        $stayJson = $visitExist && $visitExist['stay_json'] ? json_decode($visitExist['stay_json'], TRUE): [];
        if ($stayJson) {
            $stayJson = array_order($stayJson, 'in_time', 'desc');
        }
        //         //判断当前门店是否同时存在进店和离店设备
        $deviceCount = db('device')->group('position_type')->where(['store_id' => $storeId, 'is_del' => 0, 'status' => 1, 'position_type' => [1,2]])->count();
        if ($deviceCount != 2) {
            //不同时具备两个统计设备
            if (!$visitExist || !$visitExist['first_in_time']) {
                $data['first_in_time'] = $captureTime;  //第一次到店时间
                $data['recent_in_time'] = $captureTime; //最近一次到访时间
            }
            $timeThreshold = 60;//60秒
            if ($visitExist) {
                if (($captureTime - $visitExist['recent_time']) > $timeThreshold) {
                    $data['recent_in_time'] = $captureTime; //最近一次到访时间
                    $data['total_visit_counts'] = ['inc', 1];
                    $data['visit_counts'] = ['inc', 1];
                    $personStep = 1;
                }else{
                    $stayTimeValue = ($captureTime - $visitExist['recent_time']);
                    $stayTimeValue = $stayTimeValue > 0 ? $stayTimeValue : 0;
                    $data['stay_times'] = ['inc', $stayTimeValue];//计算当前用户停留总时长
                }
            }
        }else{
            if ($positionType == 1) {//进店
                if (!$visitExist || !$visitExist['first_in_time']) {
                    $data['first_in_time'] = $captureTime;//第一次到店时间
                }
                $data['recent_in_time'] = $captureTime; //最近一次到访时间
            }elseif ($positionType == 2){//离店
                $data['last_out_time'] = $captureTime;  //最后一次离店时间
            }
            if ($positionType == 1) {//进店
                $stayJson[]['in_time'] = $captureTime;
            }elseif ($positionType == 2){//离店
                foreach ($stayJson as $k1 => $v1) {
                    if (!$v1['in_time']) {
                        $stayJson[]['out_time'] = $captureTime;
                    }elseif (isset($v1['in_time']) && $v1['in_time'] && !isset($v1['out_time']) && $captureTime >= $v1['in_time']) {
                        $stayJson[$k1]['out_time'] = $captureTime;
                        $stayTimeValue = $captureTime - $v1['in_time'];
                        break;
                    }else{
                        continue;
                    }
                }
            }
            //计算用户到访次数
            if ($positionType == 1 && $visitExist['last_out_time'] && $captureTime > ($visitExist['last_out_time'] + 1*60)) {
                $data['total_visit_counts'] = ['inc', 1];
                $data['visit_counts'] = ['inc', 1];
                $personStep = 1;
            }
            if ($positionType == 2 && $stayTimeValue) {
                $data['stay_times'] = ['inc', $stayTimeValue];//计算当前用户停留总时长
            }
        }
        $data['stay_json'] = $stayJson ? json_encode($stayJson) : '';
        $timeJson = $visitExist && $visitExist['time_json'] ? json_decode($visitExist['time_json'], TRUE): [];
        $timeJson[$positionType][] = $captureTime;
        $data['time_json'] = $timeJson ? json_encode($timeJson) : '';

        if ($visitExist) {
            if ($faceToken && isset($faceQuality) && $faceQuality) {
                $thisQuantity = $faceQuality['value'] - $faceQuality['threshold'];
                $searchQuality = $visitExist['facequality_value'] - $visitExist['facequality_threshold'];
                $data['avatar'] = $avatar;
                if ($thisQuantity > $searchQuality) {
                    $data['age'] = $age;
                    $data['age_level'] = $ageLevel;
                    $data['gender'] = $genderId;
                    $data['face_token'] = $faceToken;
                    $data['emotion'] = $emotion;
                    $data['facequality_value'] = $faceQuality['value'];
                    $data['facequality_threshold'] = $faceQuality['threshold'];
                }
            }
            $visitId = intval($visitExist['visit_id']);
            db('day_visit')->where(['visit_id' => $visitId])->update($data);
            $userType = $visitExist['user_type'];
        }else{
            $data['stay_times'] = $stayTimeValue;//计算当前用户停留总时长
            #TODO 只有进店计算到店用户总量/到店人次总量
            //             if ($positionType == 1) {
            //                 $customeStep = $personStep = 1;
            //             }
            $customeStep = $personStep = 1;
            $lastWhere = [
                'store_id' => $storeId,
                'fuser_id' => $fuserId,
            ];
            $startTime = strtotime($date.' 00:00:00');//当日开始时间戳

            $lastExist = db('day_visit')->where($lastWhere)->where('capture_time', '<', $startTime)->order('capture_time DESC')->find();
            $data['total_visit_counts'] = isset($lastExist['total_visit_counts']) ? ($lastExist['total_visit_counts'] + $personStep) : $personStep;     //历史到访次数
            $data['total_visit_days'] = isset($lastExist['total_visit_days']) ? ($lastExist['total_visit_days'] + $personStep) : $personStep;           //历史到访天数

            //判断当前用户是否是员工/管理员
            $map = [
                'SM.is_del'     => 0,
                'U.fuser_id'    => $fuserId,
                'S.store_id'    => $storeId,
                'S.is_del'      => 0,
                'SM.group_id'   => ['>', 0],
            ];
            $manager = db('store_member')->alias('SM')->join([['store S', 'SM.store_id = S.store_id', 'INNER'], ['user U', 'SM.user_id = U.user_id', 'INNER']])->where($map)->find();
            if($manager){
                $userType = 3;//员工
            }else{
                //判断当前用户是新客户还是老客户
                $userType = $lastExist ? 2 : 1;//1新客户 2老客户
            }
            $data['face_token'] = $faceToken ? $faceToken : '';
            $data['avatar']     = $avatar ? $avatar : '';
            $data['facequality_value'] = $faceQuality['value'] ? $faceQuality['value'] : '';
            $data['facequality_threshold'] = $faceQuality['threshold'] ? $faceQuality['threshold'] : '';
            $data['age']        = $age;
            $data['age_level']  = $ageLevel;
            $data['emotion']    = $emotion;
            $data['visit_counts'] = $personStep;
            $data['gender']     = $genderId;
            $data['capture_time'] = $captureTime;
            $data['add_time']   = time();
            $data['user_type']  = $userType;
            $data = array_merge($data, $where);
            $visitId = db('day_visit')->insertGetId($data);
        }
        return [
            'user_type' => $userType,
            'custome_step' => $customeStep,
            'person_step' => $personStep,
            'stay_time_value' => $stayTimeValue,
        ];
    }
    /**
     * 设备抓拍记录整理入库
     * @param int $storeId      门店ID
     * @param int $blockId      区域ID
     * @param int $deviceId     设备ID
     * @param int $fuserId      抓拍人脸ID
     * @param int $captureTime  设备抓拍时间戳
     * @param int $age          年龄
     * @param int $ageLevel     年龄等级
     * @param int $genderId     性别
     * @param int $userType     用户类型(1新顾客 2老顾客 3员工)
     * @param float $imgx       图片X坐标
     * @param float $imgy       图片Y坐标
     */
    public function _dayCapture($storeId = 0, $blockId = 0, $deviceId = 0, $fuserId = 0, $captureTime = 0, $age = 0, $ageLevel = 0, $genderId = 1, $ethnicity = 1, $userType = 1, $imgx = 0, $imgy = 0)
    {
        //判断用户当日在当前设备是否存在抓拍记录
        if ($storeId && $deviceId && $blockId) {
            $date = $captureTime ? date('Y-m-d', $captureTime): date('Y-m-d');
            $where = [
                'store_id' => $storeId,
                'block_id' => $blockId,
                'device_id' => $deviceId,
                'fuser_id' => $fuserId,
                'capture_date' => $date,
                'is_del' => 0,
            ];
            $data = [];
            $captureExist = db('day_capture')->where($where)->find();
            $timeJson = $captureExist && $captureExist['time_json'] ? json_decode($captureExist['time_json'], TRUE): [];
            $timeJson[] = $captureTime;
            $data['time_json'] = $timeJson ? json_encode($timeJson) : '';
            if ($captureExist) {
                $data['recent_time'] = $captureTime;
                $captureId = intval($captureExist['capture_id']);
                $data['capture_counts'] = $captureExist['capture_counts'] + 1;//计算抓拍到用户次数
                db('day_capture')->where(['capture_id' => $captureId])->update($data);
            }else{
                $data['capture_time'] = $data['recent_time'] = $captureTime;
                $data['age'] = $age;
                $data['age_level'] = $ageLevel;
                $data['gender'] = $genderId;
                $data['ethnicity'] = $ethnicity;
                $data['add_time'] = time();
                $data['user_type'] = $userType;
                $data = array_merge($data, $where);
                $captureId = db('day_capture')->insertGetId($data);
            }
        }else{
            return false;
        }
    }
    /**
     * 门店每日统计记录入库
     * @param int $storeId          门店ID
     * @param int $blockId          区域ID
     * @param int $deviceId         设备ID
     * @param int $captureTime      抓拍时间戳
     * @param int $customerStep     累加顾客到店人数
     * @param int $personStep       累加顾客到店次数
     * @param int $stayTimesValue   顾客停留时长
     * @param int $ageLevel         年龄等级
     * @param int $gender           性别
     * @param int $userType         用户类型(1新顾客 2老顾客 3员工)
     */
    public function _dayTotal($storeId = 0, $blockId = 0, $deviceId = 0, $captureTime = 0, $customerStep = 0, $personStep = 0, $stayTimesValue = 0, $ageLevel = 0, $gender = 1, $userType = 1, $ethnicityId = 1)
    {
        //判断用户当日在当前门店是否存在统计记录
        if ($storeId) {
            $date = $captureTime ? date('Y-m-d', $captureTime): date('Y-m-d');
            $where = [
                'store_id' => $storeId,
                'capture_date' => $date,
            ];
            $totalExist = db('day_total')->where($where)->find();
            $data = $ageArray = $genderArray = $typeArray = $blockArray = $deviceArray = [];
            $data['update_time'] = time();
            if ($totalExist) {
                if ($userType == 3) {
                    $clearkTotal = $totalExist['cleark_total'] + $customerStep;
                }else{
                    //到店顾客总量
                    $customerTotal = $totalExist['customer_total'] + $customerStep;
                    //到店人次总量
                    $personTotal = $totalExist['person_total'] + $personStep;
                    //停留时长
                    $stayTimes = $totalExist['stay_times'] + $stayTimesValue;

                    //年龄对应数量处理
                    $ageArray = $totalExist['age_json'] ? json_decode($totalExist['age_json'], TRUE): [];
                    //性别对应数量处理
                    $genderArray = $totalExist['gender_json'] ? json_decode($totalExist['gender_json'], TRUE): [];
                    //用户类别对应数量处理
                    $typeArray = $totalExist && $totalExist['user_type_json'] ? json_decode($totalExist['user_type_json'], TRUE): [];
                    //区域客流(人次)统计处理
                    $blockArray = $totalExist['block_json'] ? json_decode($totalExist['block_json'], TRUE): [];
                    //设备客流(人次)统计处理
                    $deviceArray = $totalExist['device_json'] ? json_decode($totalExist['device_json'], TRUE): [];

                    if ($customerStep) {
                        $ageArray[$ageLevel] = isset($ageArray[$ageLevel]) ? $ageArray[$ageLevel] + 1 : 1;
                        $genderArray[$gender] = isset($genderArray[$gender]) ? $genderArray[$gender] + 1 : 1;
                        $typeArray[$userType] = isset($typeArray[$userType]) ? $typeArray[$userType] + 1 : 1;
                    }
                    $blockArray[$blockId] = isset($blockArray[$blockId]) ? $blockArray[$blockId] + 1 : 1;
                    $deviceArray[$deviceId] = isset($deviceArray[$deviceId]) ? $deviceArray[$deviceId] + 1 : 1;
                }
            }else{
                if ($userType == 3) {
                    $clearkTotal = $customerStep;
                }else{
                    //到店顾客总量
                    $customerTotal = $customerStep;
                    //到店人次总量
                    $personTotal = $personStep;
                    //停留时长
                    $stayTimes = $stayTimesValue;

                    $ageArray[$ageLevel] = 1;
                    $genderArray[$gender] = 1;
                    $typeArray[$userType] = 1;
                    $blockArray[$blockId] = 1;
                    $deviceArray[$deviceId] = 1;
                }
                $data['add_time'] = time();
                $data['capture_time'] = $captureTime;
            }
            if ($userType == 3) {
                $data['cleark_total'] = $clearkTotal;
            }else{
                $data['customer_total'] = $customerTotal;
                $data['person_total'] = $personTotal;
                $data['stay_times'] = $stayTimes;
                $data['age_json'] = $ageArray ? json_encode($ageArray) : '';
                $data['gender_json'] = $genderArray ? json_encode($genderArray) : '';
                $data['user_type_json'] = $typeArray ? json_encode($typeArray) : '';
                $data['block_json'] = $blockArray ? json_encode($blockArray) : '';
                $data['device_json'] = $deviceArray ? json_encode($deviceArray) : '';
            }
            if ($totalExist) {
                $totalId = intval($totalExist['total_id']);
                db('day_total')->where(['total_id' => $totalId])->update($data);
            }else{
                $data = $data + $where;
                $totalId = db('day_total')->insertGetId($data);
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取属性ID
     */
    public function _getDataId($type = 'emotion', $code){
        if(!$code){
            return FALSE;
        }
        $code = strtolower($code);
        switch ($type) {
            case 'emotion':
                $list = $this->emotions;
                break;
            case 'headpose':
                $list = $this->headposes;
                break;
            case 'ethnicity':
                $list = $this->ethnicitys;
                break;
            case 'gender':
                $list = $this->genders;
                break;
            default:
                return FALSE;
                break;
        }
        foreach ($list as $key => $value) {
            if (strtolower($value['code']) == $code) {
                return $key;
            }
        }
        return FALSE;
    }
    /**
     * 获取属性信息
     */
    public function _getDataDetail($type = 'emotion', $id, $field = 'code'){
        if(!$id){
            return FALSE;
        }
        switch ($type) {
            case 'emotion':
                $list = $this->emotions;
                break;
            case 'headpose':
                $list = $this->headposes;
                break;
            case 'ethnicity':
                $list = $this->ethnicitys;
                break;
            case 'gender':
                $list = $this->genders;
                break;
            default:
                return FALSE;
                break;
        }
        return isset($list[$id][$field]) ? strtolower($list[$id][$field]) : FALSE;
    }
    /**
     * 获取年龄信息
     */
    public function _getAgeData($age = false, $code = '')
    {
        $age = $age ? intval($age) : 0;
        if (!$code) {
            return FALSE;
        }
        foreach ($this->ageLvels as $key => $value) {
            $min = $value['min'];
            $max = $value['max'];
            if ($age >= $min && ($age < $max || !$max)) {
                if ($code == 'range') {
                    return [
                        'min' => $value['min'],
                        'max' => $value['max'],
                    ];
                }
                return $value[$code];
            }
        }
        return FALSE;
    }

    /**
     * created by huangyihao
     * @description 员工到店记录
     * @param int $storeId
     * @param int $fuserId
     * @param int $captureTime
     * @param string $avatar
     * @param string $remarks
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function _workLog($storeId = 0, $fuserId = 0, $captureTime = 0, $avatar = '', $remarks = '')
    {
        //判断是否是员工
        $result = db('user') -> where('fuser_id','=',$fuserId)->where('is_admin','>',0)->where('is_del','=',0)->find();
        if(!$result){
            return false;
        }
        $date = $captureTime ? date('Y-m-d', $captureTime) : date('Y-m-d');
        //判断当日是否存在用户到访信息
        $where = [
            'store_id' => $storeId,
            'fuser_id' => $fuserId,
            'date' => $date,
        ];

        $workExist = db('work_log')->where($where)->find();
        if ($workExist) {
            $data = [
                'last_time' => $captureTime,
                'last_img' => $avatar,
                'work_time' => $captureTime - $workExist['first_time'],
                'update_time' => time(),
                'remarks' => $workExist['remarks'] . '----' . $remarks
            ];
            $res = db('work_log')->where($where)->update($data);
        } else {
            $data = [
                'date' => $date,
                'store_id' => $storeId,
                'fuser_id' => $fuserId,
                'first_time' => $captureTime,
                'first_img' => $avatar,
                'last_time' => $captureTime,
                'last_img' => $avatar,
                'work_time' => '0',
                'remarks' => $remarks,
                'add_time' => time(),
                'update_time' => time(),
            ];
            $res = db('work_log')->insertGetId($data);
        }

        if (!$res) {
            return false;
        }
        return true;
    }
}
