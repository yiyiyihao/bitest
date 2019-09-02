<?php
namespace app\common\api;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\facade\Env;
/**
 * 七牛云接口
 * @author xiaojun
 */
class QiniuApi
{
    var $config;
    public function __construct()
    {
        $this->config = [
            'accessKey' => 'ViZmjaqh9B_Q27LK1YaML1_ENwjLYAO1RfQVuF5l',  //accessKey
            'secretKey' => '6GV7xKIYheriN9OmOuETcylrHxq0Pp1rwLXF_yte',  //secretKey
            'bucket'    => 'bestchang',                                 //上传的空间
            'domain'    => 'face.bi.worthcloud.net',                    //空间绑定的域名
            'thumb_config' => [
                'goods_thumb' => '?imageView2/1/w/400/h/400/q/75|imageslim',
                'avatar_thumb' => '?imageView2/1/w/200/h/200/q/75|imageslim',
            ],
        ];
    }
    
    public function uploadFileData($data, $qiniuName = '', $thumbType = '')
    {
        require_once Env::get('APP_PATH') . '/../vendor/Qiniu/autoload.php';
        // 构建鉴权对象
        $auth = new Auth($this->config['accessKey'], $this->config['secretKey']);
        $token = $auth->uploadToken($this->config['bucket']);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadManager = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        $result = $uploadManager->put($token, $qiniuName, $data);
        if ($result && isset($result['statusCode']) && $result['statusCode'] != 200) {
            $return = [
                'error' => 1,
                'msg'   => $result['error'],
                'data'  => '',
            ];
        }else{
            $body = isset($result['body']) && $result['body'] ? json_decode($result['body'], TRUE) : [];
            $filePath = 'http://'.$this->config['domain'].'/'.$body['key'];
            if (isset($this->config['thumb_config']) && $thumbType && $this->config['thumb_config'][$thumbType]) {
                $thumb = $filePath.$this->config['thumb_config'][$thumbType];
            }else{
                $thumb = '';
            }
            $return = [
                'error' => 0,
                'msg' => '上传完成',
                'domain' => $this->config['domain'],
                'file' => [
                    'hash'  => $body['hash'],
                    'key'   => $body['key'],
                    'path'  => $filePath,
                    'thumb' => $thumb,
                ],
            ];
        }
        return $return;
    }
    
    public function uploadFile($filePath = '', $qiniuName = '', $thumbType = '')
    {
        require_once Env::get('app_path') . '/../vendor/Qiniu/autoload.php';
        // 构建鉴权对象
        $auth = new Auth($this->config['accessKey'], $this->config['secretKey']);
        $token = $auth->uploadToken($this->config['bucket']);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadManager = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        $result = $uploadManager->putFile($token, $qiniuName, $filePath);
        if ($result && isset($result['statusCode']) && $result['statusCode'] != 200) {
            $return = [
                'error' => 1,
                'msg'   => $result['error'],
                'data'  => '',
            ];
        }else{
            $body = isset($result['body']) && $result['body'] ? json_decode($result['body'], TRUE) : [];
            $filePath = 'http://'.$this->config['domain'].'/'.$body['key'];
            if (isset($this->config['thumb_config']) && $thumbType && $this->config['thumb_config'][$thumbType]) {
                $thumb = $filePath.$this->config['thumb_config'][$thumbType];
            }else{
                $thumb = '';
            }
            $return = [
                'error' => 0,
                'msg' => '上传完成',
                'domain' => $this->config['domain'],
                'file' => [
                    'hash'  => $body['hash'],
                    'key'   => $body['key'],
                    'path'  => $filePath,
                    'thumb' => $thumb,
                ],
            ];
        }
        return $return;
    }
}