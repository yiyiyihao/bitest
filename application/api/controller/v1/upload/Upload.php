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

    public function qiniuUpload($filePath, $name, $fileSize, $thumbType = '')
    {
        $qiniuApi = new \app\common\api\QiniuApi();
        $result = $qiniuApi->uploadFile($filePath, 'cloud_'.$name, $thumbType);
        if ($result['error'] == 1) {
            $return = [
                'status' => 0,
                'info' => $result['msg'],
            ];
        }else{
            $filePath = $result['file']['path'];
            $fileModel = db('file');
            //判断数据库是否存在当前文件
            $exist = $fileModel->where(['qiniu_hash' => $result['file']['hash'], 'qiniu_domain' => $result['domain']])->find();
            if (!$exist) {
                //将对应文件保存到数据库
                $data = [
                    'qiniu_hash'    => $result['file']['hash'],
                    'qiniu_key'     => $result['file']['key'],
                    'qiniu_domain'  => $result['domain'],
                    'file_path'     => $filePath,
                    'file_size'     => $fileSize,
                    'add_time'      => time(),
                    'update_time'   => time(),
                ];
                $fileId = $fileModel->insertGetId($data);
            }else{
                $fileId = $exist['file_id'];
            }
            $thumb = isset($result['file']['thumb']) && $result['file']['thumb'] ? $result['file']['thumb'] : $filePath;
            $return = [
                'status'    => 1,
                'thumb'     => $thumb,
                'thumbMid'  => $thumb,
                'file'      => $filePath,
            ];
        }
        return $return;
    }
}