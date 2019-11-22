<?php
/**
 * Created by huangyihao.
 * User: Administrator
 * Date: 2019/5/29 0029
 * Time: 17:07
 */

namespace app\api\controller\v1\staffer;

use app\api\controller\Api;
use app\service\service\Dataset;
use think\Request;

class WorkLog extends Api
{
    protected $noAuth = ['workLog', 'stafferWorkLog'];

    public function workLog()
    {

        $params = $this->postParams;
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $size = !empty($params['size']) ? intval($params['size']) : 20;
        $date = !empty($params['date']) ? trim($params['date']) : date('Y-m-d');
        $storeId = isset($params['store_id']) ? intval($params['store_id']) : 0;

        if ($storeId == '0') {
            $this->_returnMsg(['code' => 1, 'msg' => '门店id缺失']);
            die;
        }

        $where = [
            ['SM.store_id', '=', $storeId],
            ['U.is_del', '=', 0],
            ['U.is_admin', '>', 0],
        ];
        $join = [
            ['work_log WL', "WL.fuser_id = U.fuser_id and WL.store_id ='{$storeId}' and WL.date = '{$date}'", 'LEFT'],
            ['store_member SM', 'SM.user_id = U.user_id', 'LEFT'],
        ];
        $field = 'U.fuser_id,U.realname,WL.first_time,WL.first_img,WL.last_time,WL.last_img,WL.work_time';

        $count = db('user')->alias('U')->where($where)->join($join)->count();
        $list = db('user')->alias('U')->where($where)->join($join)->field($field)->limit(($page - 1) * $size, $size)->select();
        //调用第三方接口获取法定节假日，自己获取常规休息日（如本公司每月最后一个周六上班）
        #todo 返回一个字段，标记是休息日
        //查数据库获取自定义特殊日子
        $datelist = db('wcalendar')->alias('WC')->where('WC.is_del', '=', 0)->join('wrule WR', 'WR.wrule_id=WC.wrule_id', 'LEFT')->select();
        $datelist = empty($datelist) ? [] : $datelist;
        foreach ($datelist as $key => $value) {
            if (trim($value['date']) == $date) {
                $work_start_time = $value['work_start_time'];
                $work_out_time = $value['work_out_time'];
            }
        }

        //组装返回打开记录列表
        $data = [];
        $list = empty($list) ? [] : $list;
        foreach ($list as $k => $v) {
            $data[$k]['date'] = $date;
            $data[$k]['fuser_id'] = isset($v['fuser_id']) ? $v['fuser_id'] : '';
            $data[$k]['staffer_name'] = isset($v['realname']) ? $v['realname'] : '';
            $data[$k]['first_time'] = isset($v['first_time']) ? date('Y-m-d H:i:s', $v['first_time']) : '0';
            $data[$k]['first_img'] = isset($v['first_img']) ? $v['first_img'] : '';
            $data[$k]['last_time'] = isset($v['last_time']) ? date('Y-m-d H:i:s', $v['last_time']) : '0';
            $data[$k]['last_img'] = isset($v['last_img']) ? $v['last_img'] : '';
            $data[$k]['work_time'] = timediff(intval($v['work_time']));
            $data[$k]['work_start_time'] = isset($work_start_time) ? $work_start_time : $date . ' ' . '09:00:00';
            $data[$k]['work_out_time'] = isset($work_out_time) ? $work_out_time : $date . ' ' . '18:00:00';

        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => ['count' => $count, 'page' => $page, 'list' => $data]]);
        die;
    }

    public function stafferWorkLog()
    {
        $params = $this->postParams;
        $fuser_id = isset($params['fuser_id']) ? intval($params['fuser_id']) : 0;
        $end_date = isset($params['end_date']) ? trim($params['end_date']) : date('Y-m-d');
        $start_date = isset($params['start_date']) ? trim($params['start_date']) : date('Y-m-d', strtotime($end_date) - 3600 * 24 * 7);
        $days = (strtotime($end_date) - strtotime($start_date)) / 3600 / 24 + 1;
        $dataService = new Dataset();
        $dateList = $dataService->getDayList($days, 'day', 'Y-m-d', $end_date);
        $dateList = array_reverse($dateList);
        $storeId = isset($params['store_id']) ? intval($params['store_id']) : 0;

        if ($storeId == '0' || $fuser_id == '0') {
            $this->_returnMsg(['code' => 1, 'msg' => '门店id或者fuser_id缺失']);
            die;
        }


        $where = [
            ['U.fuser_id', '=', $fuser_id],
        ];
        $field = 'WL.date,U.fuser_id,U.realname,WL.first_time,WL.first_img,WL.last_time,WL.last_img,WL.work_time';

        $dateList = empty($dateList) ? [] : $dateList;
        foreach ($dateList as $k => $v) {
            $join = [
                ['work_log WL', "U.fuser_id=WL.fuser_id and WL.date = '{$v}' and WL.store_id ='{$storeId}'", 'LEFT']
            ];
            $list[$k] = db('user')->alias('U')->where($where)->join($join)->field($field)->find();
            $list[$k]['date'] = $v;
        }

        //调用第三方接口获取法定节假日，自己获取常规休息日（如本公司每月最后一个周六上班）
        #todo 返回一个字段，标记是休息日
        //查数据库获取自定义特殊日子
        $wcalendar = db('wcalendar')->alias('WC')->where('WC.is_del', '=', 0)->join('wrule WR', 'WR.wrule_id=WC.wrule_id', 'LEFT')->select();
        //组装返回打开记录列表
        $data = [];
        $list = empty($list) ? [] : $list;
        foreach ($list as $k => $v) {
            $wcalendar = empty($wcalendar) ? [] : $wcalendar;
            $work_start_time = null;
            $work_out_time = null;
            foreach ($wcalendar as $key => $value) {
                if (trim($value['date']) == $v['date']) {
                    $work_start_time = $value['work_start_time'];
                    $work_out_time = $value['work_out_time'];
                }
            }
            $data[$k]['date'] = $v['date'];
            $data[$k]['fuser_id'] = isset($v['fuser_id']) ? $v['fuser_id'] : '';
            $data[$k]['staffer_name'] = isset($v['realname']) ? $v['realname'] : '';
            $data[$k]['first_time'] = isset($v['first_time']) ? date('Y-m-d H:i:s', $v['first_time']) : '0';
            $data[$k]['first_img'] = isset($v['first_img']) ? $v['first_img'] : '';
            $data[$k]['last_time'] = isset($v['last_time']) ? date('Y-m-d H:i:s', $v['last_time']) : '0';
            $data[$k]['last_img'] = isset($v['last_img']) ? $v['last_img'] : '';
            $data[$k]['work_time'] = timediff(intval($v['work_time']));
            $data[$k]['work_start_time'] = isset($work_start_time) ? $work_start_time : $v['date'] . ' ' . '09:00:00';
            $data[$k]['work_out_time'] = isset($work_out_time) ? $work_out_time : $v['date'] . ' ' . '18:00:00';

        }

        $this->_returnMsg(['code' => 0, 'msg' => '成功', 'data' => $data]);
        die;

    }
}