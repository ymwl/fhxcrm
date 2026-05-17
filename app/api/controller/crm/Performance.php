<?php


namespace app\api\controller\crm;

use app\api\controller\Authority;

use think\App;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
class Performance extends Authority
{


    public function analytics(){

        $get = $this->request->get('', null, null);
        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $filters = json_decode($filters, true);
        $month=empty($filters['month'])?date('Y-m'):$filters['month'];
        $uninx_time=strtotime( $month);
        $startTimestamp = $uninx_time;
        $endTimestamp = strtotime(date('Y-m-01 23:59:59', $uninx_time)." +1 month -1 day");



        //月度排名（名）、月目标（元）、已成交（元）、完成率（%）、已成交（单）、提成点（%），
        //管理员添加业绩设置权限。
        $prefix=config('database.connections.mysql.prefix');
        $where='';
        if(!empty($filters['username'])){
            $where.=" AND `username` LIKE '%{$filters['username']}%'";
        }

        $userlist=Db::query("SELECT o.*,a.admin_id,a.username,COALESCE(a.mubiao,0) mubiao,a.ticheng FROM {$prefix}admin a LEFT JOIN (SELECT SUM(`money`) AS money_month,COUNT( `id` ) AS number_month,SUM( `freight` ) AS freight_month,pr_user FROM `{$prefix}crm_client_order` WHERE `status` = 1 AND create_time between '{$startTimestamp}' AND '{$endTimestamp}' GROUP BY `pr_user` ORDER BY money_month DESC) o ON a.username=o.pr_user WHERE a.is_open=1 {$where} ORDER BY o.money_month DESC");

        /* 1. 一行代码：直接拿到 admin_id 列（返回索引数组） */
        $adminIds = array_column($userlist, 'admin_id');
        /* 2. 如果需要字符串 */
        $adminStr = implode(',', $adminIds);

        //合同签约金额
        $ht_money_month_lst=Db::query("SELECT sum(money) as ht_money_month,owner_admin_id  from {$prefix}crm_contract where check_status=3 and sign_time between '{$startTimestamp}' AND '{$endTimestamp}' AND owner_admin_id in ({$adminStr})  GROUP BY `owner_admin_id`");
        if($ht_money_month_lst){
            $ht_money_month_lst=array_column($ht_money_month_lst, 'ht_money_month', 'owner_admin_id');
        }


//合同回款金额
        $hk_money_month_lst=Db::query("SELECT sum(money) as hk_money_month,owner_admin_id  from {$prefix}crm_contract_receivables where check_status=3 and return_time between '{$startTimestamp}' AND '{$endTimestamp}' AND owner_admin_id in ({$adminStr}) GROUP BY `owner_admin_id`");
        if($hk_money_month_lst){
            $hk_money_month_lst=array_column($hk_money_month_lst, 'hk_money_month', 'owner_admin_id');
        }

        $userlist=array_map(function($item) use($ht_money_month_lst,$hk_money_month_lst){
            $item['ht_money_month']=isset($ht_money_month_lst[$item['admin_id']])?$ht_money_month_lst[$item['admin_id']]:0;
            $item['hk_money_month']=isset($hk_money_month_lst[$item['admin_id']])?$hk_money_month_lst[$item['admin_id']]:0;
            return $item;
        },$userlist);


        $data = [
            'code'  => 1,
            'msg'   => '',
            'data'  => ['rows'=>$userlist, 'count'=>count($userlist)],
            'cur_date'=>date('Y-m',$uninx_time)
        ];
        return json($data);
    }

}