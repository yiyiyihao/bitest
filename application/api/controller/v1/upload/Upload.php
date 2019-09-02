<?php
namespace app\api\controller\v1\upload;
use app\api\controller\Api;

class Upload extends Api
{

    public function qiniuUploadData($data, $prex, $thumbType = '', $fileSize = 0)
    {
        $qiniuApi = new \app\common\api\QiniuApi();
        $name = date('YmdHis').get_nonce_str(8).'.png';
        $name = ($prex ? $prex : $this->prex).$name;
        $result = $qiniuApi->uploadFileData($data, $name, $thumbType);
        if (isset($result['error']) && $result['error'] > 0) {
            $this->error($result['msg']);
        }
        if ($result['error'] == 1) {
            $return = [
                'status' => 0,
                'info' => $result['msg'],
            ];
        }else{
            return ['status'=>1,'info'=>$result['file']];
        }

    }


    public function uploadImageSource($verifyUser = TRUE)
    {

        $image = isset($this->postParams['image-data']) ? trim($this->postParams['image-data']) : '';

        if (!$image) {

            $this->_returnMsg(['code' => 1, 'msg' => '图片数据不能为空']);die;
        //    $this->_returnMsg(['code' => 1, 'msg' => '图片数据不能为空']);die;
        }
        $type = isset($this->postParams['type']) ? trim($this->postParams['type']) : '';
        $type = 'goods_thumb';
        if (!$type || !in_array($type, ['idcard', 'store_profile', 'order_service','goods_thumb'])) {
            $this->_returnMsg(['code' => 1, 'msg' => '图片类型错误']);die;
        //    $this->_returnMsg(['errCode' => 1, 'errMsg' => '图片类型错误']);
        }

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/',$image, $res)) {
            $image = base64_decode(str_replace($res[1],'', $image));
        }
        $fileSize = strlen($image);
        //图片上传到七牛
        $result = $this->qiniuUploadData($image, 'api_'.$type.'_', $type, $fileSize);
        if (!$result || !$result['status']) {
            $this->_returnMsg(['code' => 1, 'msg' => $result['info']]);die;
        //    $this->_returnMsg(['errCode' => 1, 'errMsg' => $result['info']]);
        }
        unset($result['status']);
        $this->_returnMsg(['code' => 0, 'msg' => '图片上传成功','data'=> ['file'=>$result]]);die;
        //$this->_returnMsg(['msg' => '图片上传成功', 'file' => $result]);
    }
}