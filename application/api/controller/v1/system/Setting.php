<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/11/4 0004
 * Time: 18:37
 */
namespace app\api\controller\v1\system;

use app\api\controller\Api;
use think\Request;
use think\Db;

//设置数据接口
class Setting extends Api
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //获取系统设置的数据
    public function get()
    {
        $params = $this -> postParams;
        $storeId = $params['store_id'] ?? 0;
        if(!$storeId){
            $this->_returnMsg(['code' => 1, 'msg' => lang('门店id缺失')]);die;
        }
        $result1 = db('stay_time')->where('store_id',$storeId)->find();
        $result2 = db('year_label')->where('store_id',$storeId)->select();
        $result3 = db('face_confidence')->where('store_id',$storeId)->find();
        $temp = [];
        if($result2){
            foreach($result2 as $k => $v){
                $temp[$k]['min'] = $v['min'];
                $temp[$k]['type'] = $v['type'];
                $temp[$k]['max'] = $v['max'];
                $temp[$k]['label_name'] = $v['label_name'];
            }
        }
        $minute = $result1['min'] ?? '';
        $confidence = $result3['confidence'] ?? 80;
        $data = ['minute'=>$minute,'label'=>$temp,'confidence'=>$confidence];
        $this->_returnMsg(['code' => 0, 'msg' => lang('成功'),'data'=>$data]);die;
    }

    //系统设置数据
    public function set()
    {
        $params = $this -> postParams;
        $storeId = $params['store_id'] ?? 0;
        if(!$storeId){
            $this->_returnMsg(['code' => 1, 'msg' => lang('门店id缺失')]);die;
        }
        $minute = $params['minute'] ?? 0;
        if($minute <= 0){
            $this->_returnMsg(['code' => 1, 'msg' => lang('时间必须大于0')]);die;
        }
        $type = $params['type'] ?? 1;
        $min = $params['min'] ?? [];
        $max = $params['max'] ?? [];
        $gender = $params['gender'] ?? 0;
        if($min <= 0 || $max <=0){
            $this->_returnMsg(['code' => 1, 'msg' => lang('年龄必须大于0')]);die;
        }
        if($min > $max){
            $temp = $min;
            $min = $max;
            $max = $temp;
        }
        $labelName = $params['label_name'] ?? [];
        if(!$labelName){
            $this->_returnMsg(['code' => 1, 'msg' => lang('年龄标签不能为空')]);die;
        }
        $confidence = $params['confidence'] ?? 0;
        if($confidence <= 0){
            $this->_returnMsg(['code' => 1, 'msg' => lang('置信度不能小于0')]);die;
        }

        Db::startTrans();
        try {
            $this->set_stay_time($storeId,$minute);
            $this->set_year_label($storeId,$labelName,$min,$max,$gender,$type);
            $this->set_confidence($storeId,$confidence);
            // 提交事务
            Db::commit();
            $this->_returnMsg(['code' => 0, 'msg' => lang('成功')]);die;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->_returnMsg(['code'=> 1,'msg'=>$e->getMessage()]);
        }

    }


    //设置客户停留时长
    private function set_stay_time($storeId,$minute)
    {
        $data = [
            'store_id'=>$storeId,
            'min'=>$minute,
            'update_time'=>time(),
            'create_time'=>time(),
        ];
        //写入数据库
        $result = db('stay_time')->insert($data,true);
        if(!$result){
            Db::rollback();
            $this->_returnMsg(['code' => 1, 'msg' => lang('停留时间添加失败')]);die;
        }
        return true;
    }

    //新增客户年龄标签
    private function set_year_label($storeId,$labelName,$min,$max,$gender,$type)
    {
        foreach($labelName as $k => $v){
            $data[] = [
                'store_id'=>$storeId,
                'label_name'=>$v,
                'min'=>$min[$k],
                'max'=>$max[$k],
                'type'=>$type,
                'gender'=>$gender,
                'update_time'=>time(),
                'create_time'=>time(),
            ];
        }

        //写入数据库
        $result = db('year_label') -> where('store_id',$storeId)->delete();
        $result = db('year_label')->insertAll($data);
        if(!$result){
            Db::rollback();
            $this->_returnMsg(['code' => 1, 'msg' => lang('停留时间添加失败')]);die;
        }
        return true;
    }

    //设置图片置信度
    private function set_confidence($storeId,$confidence)
    {
        $data = [
            'store_id'=>$storeId,
            'confidence'=>$confidence,
            'update_time'=>time(),
            'create_time'=>time(),
        ];
        //写入数据库
        $result = db('face_confidence')->insert($data,true);
        if(!$result){
            Db::rollback();
            $this->_returnMsg(['code' => 1, 'msg' => lang('置信度设置失败')]);die;
        }
        return true;
    }

    //返回图片的置信度
    public function get_confidence()
    {
        $params = $this -> postParams;
        $faceUrl = $params['face_url'] ?? '';
        if(!$faceUrl){
            $this->_returnMsg(['code' => 1, 'msg' => lang('人脸地址缺失')]);die;
        }

        $client = new \app\common\api\BaiduAipFace();
        // 如果有可选参数
        $options = array();
        $options["face_field"] = "quality,face_probability";

        // 带参数调用人脸检测
        $detectResultBaidu = $client->detect($faceUrl, 'URL', $options);
        if ($detectResultBaidu['error_code'] != 0) {
            $this->_returnMsg(['code' => 1, 'msg' => $detectResultBaidu['error_msg']]);die;
        }

        $probalility = ($detectResultBaidu['result']['face_list']['0']['face_probability'] * 100 + (1-$detectResultBaidu['result']['face_list']['0']['quality']['blur']) * 100)/2;
        $this->_returnMsg(['code' => 0, 'msg' => $detectResultBaidu['error_msg'],'data'=>['probalility'=>$probalility]]);die;

    }

    // 用户生命周期管理
    public function life_cycle()
    {
        $params = $this -> postParams;
        $label_id = $params['label_id'] ?? '';
        if(!$label_id){
            $this->_returnMsg(['code' => 1, 'msg' => lang('标签id为空')]);die;
        }
        $result = db('label') -> where('label_id',$label_id)->find();
        if(!$result){
            $this->_returnMsg(['code' => 1, 'msg' => lang('标签id不存在')]);die;
        }
        if($result['type'] < 3){
            $this->_returnMsg(['code' => 1, 'msg' => lang('非系统标签')]);die;
        }

        $markType = $params['mark_type'] ?? 0;
        $times = $params['times'] ?? 0;
        $stayTimes = $params['stay_times'] ?? 0;
        if($result['type'] < 6){
            if(!$markType || !$times || !$stayTimes){
                $this->_returnMsg(['code' => 1, 'msg' => lang('参数缺失')]);die;
            }
        }else{
            if(!$times){
                $this->_returnMsg(['code' => 1, 'msg' => lang('参数缺失')]);die;
            }
        }

        $data = [
            'mark_type' => $markType,
            'times' => $times,
            'stay_times' => $stayTimes,
            'update_time' => time(),
        ];
        $res = db('label')->where('label_id',$label_id)->update($data);
        if(!$res){
            $this->_returnMsg(['code' => 1, 'msg' => lang('编辑失败')]);die;
        }

        $this->_returnMsg(['code' => 0, 'msg' => 'success']);die;
    }

}
