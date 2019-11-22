<?php
namespace app\api\controller;

/**
 * 相机原图处理接口
 * @author xiaojun
 */
class Facefull extends ApiBase
{
    protected $imgFile;
    protected $imgPixel;
    protected $captureTime;
    protected $deviceTime;
    protected $deviceCode;
    
    public function __construct(){
        parent::__construct();
    }
    /**
     * 请求参数处理
     */
    protected function _checkPostParams()
    {
        parent::_checkPostParams();
        $this->imgFile = $this->postParams && isset($this->postParams['img_url']) ? trim($this->postParams['img_url']) : '';        //人脸图片
        $this->deviceCode = $this->postParams && isset($this->postParams['mac_id']) ? trim($this->postParams['mac_id']): '';        //设备串码
        $this->imgPixel = $this->postParams && isset($this->postParams['img_pixel']) ? trim($this->postParams['img_pixel']): 0;     //抓拍时间戳
        $this->angle = $this->postParams && isset($this->postParams['angle']) ? trim($this->postParams['angle']): 0;                //抓拍角度
        $this->captureTime = $this->postParams && isset($this->postParams['timestamp']) ? trim($this->postParams['timestamp']): 0;  //抓拍时间戳
        if (!$this->imgFile) {
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'img_url: 参数缺失']);
        }
        if (!$this->deviceCode) {
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'mac_id: 参数缺失']);
        }
        if (!$this->captureTime) {
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'timestamp: 参数缺失']);
        }
        if (strlen($this->captureTime) != 10) {
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'timestamp: 时间戳为10位有效数字']);
        }
        //判断时间戳格式是否正确
        if(strtotime(date('Y-m-d H:i:s',$this->captureTime)) != $this->captureTime) {
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'timestamp: 参数格式错误']);
        }
        $this->deviceTime = $this->captureTime - 8*3600;
        $this->captureTime = time();
    }
    public function index()
    {
        $deviceModel = db('device');
        //判断设备是否存在
        $deviceExist = $deviceModel->where(['device_code' => $this->deviceCode, 'is_del' => 0])->find();
        if (!$deviceExist) {
        }
        $storeId = $deviceExist ? intval($deviceExist['store_id']) : 0;         //设备授权门店ID
        $deviceId = $deviceExist ?  intval($deviceExist['device_id']) : 0;      //设备ID
        $blockId = $deviceExist ? intval($deviceExist['block_id']): 0;          //设备所属区域ID
        $positionType = $deviceExist ? intval($deviceExist['position_type']) :3;//1为进店 2为离店 3为店内其它
        $clerkId = 0;
        $deviceImgModel = db('device_img');
        
        //判断当前图片是否已经存在
        $imgData = [
            'store_id'      => $storeId,
            'block_id'      => $blockId,
            'device_id'     => $deviceId,
            'device_code'   => $this->deviceCode,
            'position_type' => $positionType,
            'img_url'       => $this->imgFile,
            'angle'         => $this->angle,
            'is_del'        => 0,
        ];
        $dimgExist = $deviceImgModel->where($imgData)->find();
        if($dimgExist){
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'img_url:图片+角度重复']);
        }
        $imgData['add_time'] = $imgData['update_time'] = time();
        $imgData['capture_time']= $this->captureTime;
        $imgData['img_pixel']= $this->imgPixel;
        $imgArr = $this->imgPixel ? explode('*', $this->imgPixel) : [];
        $imgData['image_width'] = isset($imgArr[0]) ? $imgArr[0] : '';
        $imgData['image_height'] = isset($imgArr[1]) ? $imgArr[1] : '';
        $dimgId = $deviceImgModel->insertGetId($imgData);
        if (!$dimgId) {
            $this->_returnMsg(['errCode' => 1, 'errMsg' => 'img_url:数据库处理失败']);
        }
        $this->_returnMsg(['errCode' => 0, 'msg' => 'img_url: 图片上报成功', 'img_url' => $this->imgFile]);
    }
    /**
     * 处理接口返回信息
     */
    protected function _returnMsg($data, $echo = TRUE){
        $result = parent::_returnMsg($data);
        $responseTime = $this->_getMillisecond() - $this->visitMicroTime;//响应时间(毫秒)
        $addData = [
            'type'          => 'facefull',
            'request_time'  => $this->requestTime,
            'capture_time'  => $this->deviceTime ? $this->deviceTime: 0,
            'return_time'   => time(),
            'capture_img'   => $this->imgFile ? $this->imgFile : '',
            'device_code'   => $this->deviceCode ? $this->deviceCode : '',
            'img_x'         => 0,
            'img_y'         => 0,
            'img_pixel'     => $this->imgPixel ? $this->imgPixel : '',
            'request_params'=> $this->postParams ? json_encode($this->postParams) : '',
            'return_params' => $result,
            'response_time' => $responseTime,
            'error'         => isset($data['errCode']) ? intval($data['errCode']) : 0,
        ];
        $apiLogId = db('apilog_device')->insertGetId($addData);
        exit();
    }
}    