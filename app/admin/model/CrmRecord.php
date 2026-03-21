<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\Db;

class CrmRecord extends TimeModel
{

    protected $name = "crm_record";

    protected $deleteTime = false;


    /**
     * 跟进趋势Echart
     * @return bool
     */
    public function getRecordEchartData($startDate,$endDate,$admin_ids)
    {

        // 生成查询的开始和结束时间，默认取30日
        !is_numeric($startDate) && $starttime = strtotime($startDate);
        !is_numeric($endDate) && $endtime = strtotime($endDate);
        $isnotrangeDate = empty($starttime) && empty($endtime);

        $nearly = '30';
        if ($isnotrangeDate) {
            $endtime = time();
            $nearly -= 1;
            $starttime = strtotime("-{$nearly} day");  // 最近30天日期
        } elseif ($starttime > $endtime) {
            $this->error = '起始时间要小于终止时间';
            return false;
        }
        list($format,$column)=\tools\hs::format_lx_time($starttime,$endtime);
        $where = [];
        if ($admin_ids&&is_numeric($admin_ids)){
            $where[] = ['admin_id', '=', $admin_ids];

        }elseif($admin_ids&&is_array($admin_ids)){
            $where[] = ['admin_id', 'in', $admin_ids];

        }
        //跟进次数
        $lists = $this->where($where)->where('create_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(create_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();

        $listsd = $this->where($where)->where('create_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(distinct customer_id) AS nums, DATE_FORMAT(FROM_UNIXTIME(create_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();


    /*    $tempsql = $this->field('max(id) id,types,max(create_time) create_time,types_id')->where($where)->where('create_time', 'between time', [$starttime, $endtime])->group('types_id')->buildSql();
        $listsd = Db::query("SELECT COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') AS add_date FROM ($tempsql) as r GROUP BY `add_date`");*/





        $c_count = $d_count = array_fill_keys($column, 0);

        foreach ($lists as $k => $v) {
            $c_count[$v['add_date']] = $v['nums'];//
        }

        foreach ($listsd as $k => $v) {
            $d_count[$v['add_date']] = $v['nums'];
        }

        $result = [
            'date' => array_keys($c_count),
            'd_count' => array_values($d_count),
            'c_count' => array_values($c_count),
        ];

        $data = [
            'date' => $result['date'],
            'data' => [
                "跟进客户" => $result['d_count'],
                "跟进次数" => $result['c_count'],
            ],
        ];
        return $data;

    }


}