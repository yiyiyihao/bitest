<?php
namespace app\common\api;
use \Request;

/**
 * Face++人脸识别接口
 * @author xiaojun
 */
class FaceApi extends BaseFaceApi
{
    var $config;    //Api接口配置
    var $error;
    public function __construct(){
        parent::__construct();
        $server = Request::server();
        if ($server['HTTP_HOST'] == 'bi.api.worthcloud.net') {
            $this->config = [
                'api_key'       => 'WhwTOCRu6A3sMrjrgojUnfhDx_jLOe57',  //调用API的API Key
                'api_secret'    => 'iH_sdhD12ctZQKDg6OfFUQB_bOc3IAT_'   //调用API的API Secret
            ];
        }else{
            $this->config = [
                'api_key'       => 'p-835sg08Sfv0t_jrT3jbhJhnMDnFCM-',  //调用API的API Key-local
                'api_secret'    => 'Kz6_9pVt_rmfv-aZB3Q9RxqGhOE6xMNE'   //调用API的API Secret
            ];
        }
    }
    
    /**
     * 
     * @param string $displayName 人脸集合的名字，最长256个字符，不能包括字符^@,&=*'"
     * @param string $outerId 账号下全局唯一的 FaceSet 自定义标识，可以用来管理 FaceSet 对象。最长255个字符，不能包括字符^@,&=*'"
     * @param string $tags   FaceSet自定义标签组成的字符串，用来对 FaceSet 分组。最长255个字符，多个 tag 用逗号分隔，每个 tag 不能包括字符^@,&=*'"
     * @param string $faceTokens 人脸标识 face_token，可以是一个或者多个，用逗号分隔。最多不超过5个 face_token
     * @param string $userData 自定义用户信息，不大于16 KB，不能包括字符^@,&=*'"
     * @param string $forceMerge 在传入 outer_id 的情况下，如果 outer_id 已经存在，是否将 face_token 加入已经存在的 FaceSet 中 0：不将 face_tokens 加入已存在的 FaceSet 中，直接返回 FACESET_EXIST 错误1：将 face_tokens 加入已存在的 FaceSet 中 默认值为0
     * @return array
     */
    public function faceSetCreate($displayName = '', $outerId = '', $tags = '', $faceTokens = '', $userData = '', $forceMerge = 0)
    {
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/create';
        if ($displayName) {
            $params['display_name'] = $displayName;
        }
        if ($outerId) {
            $params['outer_id'] = $outerId;
        }
        if ($tags) {
            $params['tags'] = $tags;
        }
        if ($faceTokens) {
            $faceTokens = explode(',', $faceTokens);
            $faceTokens = array_filter(array_unique(array_trim($faceTokens)));
            if($faceTokens){
                $params['face_tokens'] = implode(',', $faceTokens);
            }
        }
        if ($userData) {
            $params['user_data'] = $userData;
        }
        if ($outerId && $forceMerge) {
            $params['force_merge'] = $forceMerge;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    /**
     * 获取一个 FaceSet 的所有信息，包括此 FaceSet 的 faceset_token, outer_id, display_name 的信息，以及此 FaceSet 中存放的 face_token 数量与列表。(单次查询最多返回 100 个 face_token)
     * @param string $faceSetToken
     * @param number $outerId
     * @param number $start 一个数字 n，表示开始返回的 face_token 在本 FaceSet 中的序号， n 是 [1,10000] 间的一个整数。 通过传入数字 n，可以控制本 API 从第 n 个 face_token 开始返回。返回的 face_token 按照创建时间排序，每次返回 100 个 face_token。默认值为 1。
     * @return boolean
     */
    public function faceSetDetail($faceSetToken = '', $outerId = 0, $start = 0)
    {
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/getdetail';
        if ($faceSetToken){
            $params['faceset_token'] = $faceSetToken;
        }elseif ($outerId){
            $params['outer_id'] = $outerId;
        }else{
            $this->error = '参数错误:faceset_token和outer_id不能同时为空';
            return FALSE;
        }
        if ($start) {
            $params['start'] = $start;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    /**
     * 删除一个人脸集合。
     * @param string $faceSetToken
     * @param number $outerId
     * @param number $checkEmpty 删除时是否检查FaceSet中是否存在face_token，默认值为1 0：不检查 1：检查  如果设置为1，当FaceSet中存在face_token则不能删除
     */
    public function faceSetDelete($faceSetToken = '', $outerId = 0, $checkEmpty = 1)
    {
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/delete';
        if ($faceSetToken){
            $params['faceset_token'] = $faceSetToken;
        }elseif ($outerId){
            $params['outer_id'] = $outerId;
        }else{
            $this->error = '参数错误:faceset_token和outer_id不能同时为空';
            return FALSE;
        }
        $params['check_empty'] = $checkEmpty;
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    /**
     * 获取某一 API Key 下的 FaceSet 列表及其 faceset_token、outer_id、display_name 和 tags 等信息。(单次查询最多返回 100 个 FaceSet。如需获取全量数据，需要配合使用 start 和 next 参数。请尽快修改调整您的程序。)
     * @param string $tags
     * @param number $start
     */
    public function faceSetList($tags = '', $start = 0)
    {
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/getfacesets';
        if ($tags){
            $params['tags'] = $tags;
        }
        if($start){
            $params['start'] = $start;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    
    /**
     * 更新一个人脸集合的属性
     * @param string $faceSetToken
     * @param number $outerId
     * @param number $newOuterId
     * @param string $display_name
     * @param string $userData
     * @param string $tags
     */
    public function faceSetUpdate($faceSetToken = '', $outerId = 0, $newOuterId = 0, $displayName = '', $userData = '', $tags = ''){
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/update';
        $params = $this->config;
        if ($faceSetToken){
            $params['faceset_token'] = $faceSetToken;
        }elseif ($outerId){
            $params['outer_id'] = $outerId;
        }else{
            $this->error = '参数错误:faceset_token和outer_id不能同时为空';
            return FALSE;
        }
        if(!$newOuterId && !$displayName && !$userData && !$tags){
            $this->error = '参数错误:new_outer_id，display_name，user_data，user_data不能同时为空';
            return FALSE;
        }
        if ($newOuterId){
            $params['new_outer_id'] = $newOuterId;
        }
        if ($displayName){
            $params['display_name'] = $displayName;
        }
        if ($userData){
            $params['user_data'] = $userData;
        }
        if ($tags){
            $params['tags'] = $tags;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }

    /**
     * 为一个已经创建的 FaceSet 添加人脸标识 face_token。一个 FaceSet 最多存储1,000个 face_token。(注意：2017年8月16日后，一个 FaceSet 能够存储的 face_token 数量将从 1000 提升至 10000)
     * @param string $facesetToken
     * @param number $outerId
     * @param string $faceTokens
     */
    public function faceSetAddFace($faceSetToken = '', $outerId = 0, $faceTokens = '')
    {
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/addface';
        if ($faceSetToken){
            $where = ['faceset_token' => $faceSetToken];
            $params['faceset_token'] = $faceSetToken;
        }elseif ($outerId){
            $where = ['faceset_id' => $outerId];
            $params['outer_id'] = $outerId;
        }else{
            $this->error = '参数错误:faceset_token和outer_id不能同时为空';
            return FALSE;
        }
        if(!$faceTokens){
            $this->error = '参数错误:face_token不能空,多个用英文逗号分隔';
            return FALSE;
        }
        $faceTokens = explode(',', $faceTokens);
        $faceTokens = array_filter(array_unique(array_trim($faceTokens)));
        if($faceTokens){
            $params['face_tokens'] = implode(',', $faceTokens);
        }
        $where['is_del'] = 0;
        //判断faceSet对应face_token是否超过10000个
        $faceSet = db('faceset')->where($where)->find();
        if(!$faceSet){
            $this->error('faceSet不存在');
            return FALSE;
        }
        if($faceSet['face_count'] >= 10000){
            $this->error('数据异常:一个FaceSet最多存储10000个 face_token');
            return FALSE;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    
    /**
     * 移除一个FaceSet中的某些或者全部face_token
     * @param string $faceSetToken
     * @param int $outerId
     * @param string $faceTokens (最多不能超过1,000个face_token)
     */
    public function faceSetRemoveFace($faceSetToken = '', $outerId = 0, $faceTokens = '', $removeAll = false){
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/removeface';
        if ($faceSetToken){
            $params['faceset_token'] = $faceSetToken;
        }elseif ($outerId){
            $params['outer_id'] = $outerId;
        }else{
            $this->error = '参数错误:faceset_token和outer_id不能同时为空';
            return FALSE;
        }
        if(!$faceTokens && !$removeAll){
            $this->error = '参数错误:face_token不能空,多个用英文逗号分隔';
            return FALSE;
        }
        $faceTokens = explode(',', $faceTokens);
        $faceTokens = array_filter(array_unique(array_trim($faceTokens)));
        if($faceTokens){
            $params['face_tokens'] = implode(',', $faceTokens);
        }
        if ($removeAll) {
            $params['face_tokens'] = 'RemoveAllFaceTokens';
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    /**
     * 在一个已有的 FaceSet 中找出与目标人脸最相似的一张或多张人脸，返回置信度和不同误识率下的阈值。支持传入图片或 face_token 进行人脸搜索。使用图片进行搜索时会选取图片中检测到人脸尺寸最大的一个人脸。
     * @param string $searchFace  图片或 face_token
     * @param string $facesetToken 用来搜索的 FaceSet 的标识
     * @param string $outerId 用户自定义的 FaceSet 标识
     * @return array
     */
    public function searchApi($searchFace = '', $faceSetToken = '', $outerId = '', $returnResultCount = 1, $faceRectangle = '')
    {
        if(!$searchFace){
            $this->error = '参数错误:传入的图片或face_token为空';
            return FALSE;
        }
        if(!$faceSetToken && !$outerId){
            $this->error = '参数错误:faceset_token和outer_id不能同时为空';
            return FALSE;
        }
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/search';
        $path = pathinfo($searchFace);
        if (isset($path['extension'])) {
            $params['image_url'] = $searchFace;
        }else{
            $params['face_token'] = $searchFace;
        }
        if ($faceSetToken) {
            $params['faceset_token'] = $faceSetToken;
        }elseif($outerId){
            $params['outer_id'] = $outerId;
        }
        if ($returnResultCount > 1) {
            $params['return_result_count'] = $returnResultCount;
        }
        if (isset($path['extension']) && $faceRectangle) {
            $params['face_rectangle'] = $faceRectangle;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    /**
     * 将两个人脸进行比对，来判断是否为同一个人，(返回比对结果置信度和不同误识率下的阈值。支持传入图片或 face_token 进行比对。使用图片时会自动选取图片中检测到人脸尺寸最大的一个人脸。)
     * @param string $faceToken1
     * @param string $faceToken2
     * @return boolean|boolean|mixed|string
     */
    public function compareApi($faceToken1 = '', $faceToken2 = '')
    {
        if(!$faceToken1 || !$faceToken2){
            $this->error = '参数错误:传入的face_token为空';
            return FALSE;
        }
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/compare';
        $params['face_token1'] = trim($faceToken1);
        $params['face_token2'] = trim($faceToken2);
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    
    
    /**
     * 传入在 Detect API 检测出的人脸标识 face_token，分析得出人脸关键点，人脸属性信息
     * @param string $faceTokens 一个字符串，由一个或多个人脸标识组成，用逗号分隔。最多支持 5 个 face_token。
     * @param nintumber $returnLandmark
     * @param string $returnAttributes
     * @return array
     */
    public function analyzeApi($faceTokens = '', $returnLandmark = 0, $returnAttributes = 'gender,age,smiling,headpose,facequality,blur,eyestatus,emotion,ethnicity,beauty,mouthstatus,eyegaze,skinstatus')
    {
        if (!$faceTokens) {
            return FALSE;
        }
        if(!$returnLandmark && !$returnAttributes){
            return FALSE;
        }
        $params = $this->config;
        $faceTokens = explode(',', $faceTokens);
        $faceTokens = array_filter(array_unique(array_trim($faceTokens)));
        $nums = count($faceTokens);
        if($nums > 5){//最多支持 5 个 face_token。
            return FALSE;
        }
        $params['face_tokens'] = implode(',', $faceTokens);
        if ($returnLandmark && in_array($returnLandmark, [1, 2])) {
            $params['return_landmark'] = $returnLandmark;
        }
        if ($returnAttributes) {
            $params['return_attributes'] = $returnAttributes;
        }
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/face/analyze';
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
    /**
     * 传入图片进行人脸检测和人脸分析接口(可以检测图片内的所有人脸，对于每个检测出的人脸，会给出其唯一标识 face_token)
     * @param string $imageUrl
     * @param int $returnLandmark
     * @param string $returnAttributes
     * @return array
     */
    public function detectApi($imageUrl = '', $returnLandmark = 0, $returnAttributes = 'gender,age,smiling,headpose,facequality,blur,eyestatus,emotion,ethnicity,beauty,mouthstatus,eyegaze,skinstatus')
    {
        if(!$imageUrl){
            return FALSE;
        }
        $params = $this->config;
        $apiUrl = 'https://api-cn.faceplusplus.com/facepp/v3/detect';
        $params['image_url'] = $imageUrl;
        if ($returnLandmark && in_array($returnLandmark, [1, 2])) {
            $params['return_landmark'] = $returnLandmark;
        }
        if ($returnAttributes) {
            $params['return_attributes'] = $returnAttributes;
        }
        $result = curl_post_https($apiUrl, $params);
        return $result;
    }
}