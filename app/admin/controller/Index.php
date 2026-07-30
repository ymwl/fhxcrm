<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use think\App;
use think\facade\Cache;
use think\facade\Lang;
use think\facade\View;
use think\facade\Db;

class Index extends AdminController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

    }
    public function index(){
        $this->app->view->engine()->layout(false);
        return $this->fetch();
    }
    public function main(){
//        增加自动清理日志功能
        $auto_clear_logs=config('app.auto_clear_logs');
        if($auto_clear_logs>0){
            $logs_time=time()-$auto_clear_logs*86400;
            Db::name('admin_log')->where('create_time','<',$logs_time)->delete();
            Db::name('login_log')->where('createtime','<',$logs_time)->delete();
        }
        //1客户，2公海
        $cluesCount = Db::name('crm_clue')->where(['to_customer_id'=> 0])->count();
        $clientCount = Db::name('crm_customer')->where(['status'=> 1])->count();
        $liberumCount = Db::name('crm_customer')->where(['status'=> 2])->count();
        // 区别管理员和业务员

        View::assign('cluesCount', $cluesCount);
        View::assign('clientCount', $clientCount);
        View::assign('liberumCount', $liberumCount);


        //获取我的线索数
        $cluesCount_week = Db::name('crm_clue')->where(['to_customer_id'=> 0,'owner_admin_id'=>$this->admin['admin_id']])->count();
        //获取本月转客户数据 ->whereTime('to_kh_time', 'month')
        $clientCount_month = Db::name('crm_customer')->cache('pr_user_customerCount'.$this->admin['username'],60)->where(['status'=> 1,'pr_user'=>$this->admin['username']])->count();
        //获取今年公海数据 ->whereTime('to_gh_time', 'year')
        $liberumCount_year = Db::name('crm_customer')->where(['status'=> 2])->count();
        //成交数 TODO

        //月度排名（名）、月目标（元）、已成交（元）、完成率（%）、已成交（单）、提成点（%）， ->order('money_month DESC') ['a.`admin_id`','<>', 1],
        //管理员添加业绩设置权限。

        $prefix=getDataBaseConfig('prefix');

// 获取当月开始时间戳
        $startTimestamp = strtotime(date('Y-m-01 00:00:00'));
//// 获取当月结束时间戳
        $endTimestamp = strtotime(date('Y-m-t 23:59:59'));


        $userlist=Db::query("SELECT o.*,a.admin_id,a.username,a.ticheng FROM {$prefix}admin a LEFT JOIN (SELECT SUM(`money`) AS money_month,COUNT( `id` ) AS number_month,SUM( `freight` ) AS freight_month,pr_user FROM `{$prefix}crm_order` WHERE `status` = 1 AND create_time between '{$startTimestamp}' AND '$endTimestamp' GROUP BY `pr_user` ORDER BY money_month DESC LIMIT 5) o ON a.username=o.pr_user WHERE a.is_open=1 ORDER BY o.money_month DESC LIMIT 5");
        /* 1. 一行代码：直接拿到 admin_id 列（返回索引数组） */
        $adminIds = array_column($userlist, 'admin_id');
        /* 2. 如果需要字符串 */
        $adminStr = implode(',', $adminIds);

        // 月目标数据从 crm_achievement 表获取（config=1合同金额, config=2回款金额, config=3订单金额）
        $monthField = strtolower(date('F', $startTimestamp));
        $currentYear = date('Y', $startTimestamp);
        $order_mubiao_list = [];
        $contract_mubiao_list = [];
        $receivables_mubiao_list = [];
        if ($adminStr) {
            $mubiao_result = Db::name('crm_achievement')
                ->where('year', $currentYear)
                ->where('admin_id', 'in', $adminIds)
                ->field('admin_id, config, SUM(`' . $monthField . '`) as mubiao')
                ->group('admin_id, config')
                ->select()
                ->toArray();
            foreach ($mubiao_result as $mb_row) {
                if ($mb_row['config'] == 3) {
                    $order_mubiao_list[$mb_row['admin_id']] = $mb_row['mubiao'];
                } elseif ($mb_row['config'] == 1) {
                    $contract_mubiao_list[$mb_row['admin_id']] = $mb_row['mubiao'];
                } elseif ($mb_row['config'] == 2) {
                    $receivables_mubiao_list[$mb_row['admin_id']] = $mb_row['mubiao'];
                }
            }
        }
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

        $userlist=array_map(function($item) use($ht_money_month_lst,$hk_money_month_lst,$order_mubiao_list,$contract_mubiao_list,$receivables_mubiao_list){
            $item['ht_money_month']=isset($ht_money_month_lst[$item['admin_id']])?$ht_money_month_lst[$item['admin_id']]:0;
            $item['hk_money_month']=isset($hk_money_month_lst[$item['admin_id']])?$hk_money_month_lst[$item['admin_id']]:0;
            // 订单目标与完成率
            $item['order_mubiao']=isset($order_mubiao_list[$item['admin_id']])?(float)$order_mubiao_list[$item['admin_id']]:0;
            $item['order_wanchenglv']=($item['order_mubiao']>0 && !empty($item['money_month']))?round($item['money_month']/$item['order_mubiao']*100,2).'%':'';
            // 合同目标与完成率
            $item['ht_mubiao']=isset($contract_mubiao_list[$item['admin_id']])?(float)$contract_mubiao_list[$item['admin_id']]:0;
            $item['ht_wanchenglv']=($item['ht_mubiao']>0 && !empty($item['ht_money_month']))?round($item['ht_money_month']/$item['ht_mubiao']*100,2).'%':'';
            // 回款目标与完成率
            $item['hk_mubiao']=isset($receivables_mubiao_list[$item['admin_id']])?(float)$receivables_mubiao_list[$item['admin_id']]:0;
            $item['hk_wanchenglv']=($item['hk_mubiao']>0 && !empty($item['hk_money_month']))?round($item['hk_money_month']/$item['hk_mubiao']*100,2).'%':'';
            $item['money_month']=!empty($item['money_month'])?$item['money_month']:'';
            return $item;
        },$userlist);

        // 计算三个维度的排名（按金额降序）
        $order_sorted = $ht_sorted = $hk_sorted = $userlist;
        usort($order_sorted, function($a, $b) { return (($b['money_month']?:0) <=> ($a['money_month']?:0)); });
        usort($ht_sorted, function($a, $b) { return (($b['ht_money_month']?:0) <=> ($a['ht_money_month']?:0)); });
        usort($hk_sorted, function($a, $b) { return (($b['hk_money_month']?:0) <=> ($a['hk_money_month']?:0)); });
        $order_rank_map = $ht_rank_map = $hk_rank_map = [];
        foreach ($order_sorted as $i => $v) $order_rank_map[$v['admin_id']] = $i + 1;
        foreach ($ht_sorted as $i => $v) $ht_rank_map[$v['admin_id']] = $i + 1;
        foreach ($hk_sorted as $i => $v) $hk_rank_map[$v['admin_id']] = $i + 1;
        foreach ($userlist as &$v) {
            $v['order_rank'] = $order_rank_map[$v['admin_id']] ?? '-';
            $v['ht_rank'] = $ht_rank_map[$v['admin_id']] ?? '-';
            $v['hk_rank'] = $hk_rank_map[$v['admin_id']] ?? '-';
        }
        unset($v);
//        foreach ($userlist as $key => $value) {
            /*$wheretoday = [];
            $wheretoday['pr_user'] = $value['username'];
            $wheretoday['status'] = '审核通过';
            $monthOrder = Db::name('crm_order')->field('SUM(o.`money`) AS money_month,COUNT(o.`id`) AS number_month,SUM(o.`freight`) AS freight_month')
                            ->where($wheretoday)
                            ->whereTime('o.create_time','month')
                            ->find();*/

        /*    $value['money_month'] = $monthOrder['money_month']?$monthOrder['money_month']:0;
            if ($value['mubiao']>0) {
                $value['wanchenglv'] = round($monthOrder['money_month']/$value['mubiao']*100,2);
            }else{
                $value['wanchenglv']=0;
            }
            $value['number_month']=$monthOrder['number_month'];
            $value['freight_month']=$monthOrder['freight_month']?$monthOrder['freight_month']:0;
            $userlist[$key] = $value;*/
//        }

        // 数组排序
//        array_multisort(array_column($userlist,'money_month'),SORT_DESC,$userlist);
        View::assign('userlist', $userlist);

        //本人跟进动态
        //最近跟进动态
        $result = Db::name('crm_customer')
            ->alias('c')
            ->join('crm_record r','r.customer_id = c.id')
            ->join('admin a','r.admin_id = a.admin_id')
            ->field('c.id,a.username,a.avatar,c.name,r.content,r.create_time')
            ->order('r.id desc')
            ->where(['c.pr_user'=> $this->admin['username']])
            ->limit(10)->select();
        View::assign('result', $result);


      	$strTimeToString = "000111222334455556666667";
        $strWenhou = array('夜深了，','凌晨了，','早上好！','上午好！','中午好！','下午好！','晚上好！','夜深了，');
        //echo $strWenhou[(int)$strTimeToString[(int)date('G',time())]];
        View::assign('wenhou', '尊敬的管理员'. $strWenhou[(int)$strTimeToString[(int)date('G',time())]]);



        View::assign('cluesCount_week', $cluesCount_week);
        View::assign('clientCount_month', $clientCount_month);
        View::assign('liberumCount_year', $liberumCount_year);
        // 获取待办事项
        //今日已跟进客户*个，未跟进*个，跟进率*%
        //last_up_time

        $wheretoday = [];
        $wheretoday[] = ['pr_user','=',$this->admin['username']];
        //$wheretoday['status'] = 1;
        //$wheretoday['issuccess'] = -1;
        $today_followed_count = Db::name('crm_customer')->where($wheretoday)->whereTime('last_up_time','today')->count();//今日已经跟进个数

//        not followed

        View::assign('today_followed_count', $today_followed_count);

        // 提醒列表
//        $today_tiixng = Db::name('crm_customer')->where($wheretoday)->whereTime('next_time','today')->select();
//        没有跟进的列表
        $today_tiixng = Db::name('crm_customer')->field('`id`,`pr_user`,`name`,`next_time`')->where($wheretoday)->whereNotNull('next_time')->where('next_time','<>',0)->whereTime('next_time','<=','tomorrow -1second')->order('next_time asc')->limit(10)->select()->toArray();
//        待跟进数
        $not_followed_count=Db::name('crm_customer')->where($wheretoday)->whereNotNull('next_time')->where('next_time','<>',0)->whereTime('next_time','<=','tomorrow -1second')->count();
        View::assign('not_followed_count',$not_followed_count);
        $all_count=($today_followed_count+$not_followed_count);
        if ($all_count > 0) {
            $genjinlv = ($today_followed_count/$all_count)*100;
        }else{
            $genjinlv = 0;
        }
        View::assign('genjinlv', round($genjinlv,2));
        View::assign('today_tiixng', $today_tiixng);

        // ========== 8项待办事项统计 ==========
        $admin_id = $this->admin['admin_id'];
        $group_id = $this->admin['group_id'];

        // 待跟进时间窗口：系统设置 daigenjin（提前N天提醒），默认到明天
        if (isset($this->system['daigenjin']) && is_numeric($this->system['daigenjin'])) {
            $nextTimeEnd = strtotime("+{$this->system['daigenjin']} day 00:00:00");
        } else {
            $nextTimeEnd = strtotime('tomorrow');
        }

        // 1. 待跟线索（与 crm.clue/index scope=10 条件一致）
        $pending_clue_count = Db::name('crm_clue')
            ->where([
                ['to_customer_id', '=', 0],
                ['next_time', '>', 0],
                ['next_time', '<', $nextTimeEnd],
                ['owner_admin_id', '=', $admin_id]
            ])->count();
        View::assign('pending_clue_count', $pending_clue_count);

        // 2. 待跟客户（复用已有的 $not_followed_count）
        View::assign('pending_customer_count', $not_followed_count);

        // 3. 待跟商机（与 crm.business/index scope=10 条件一致）
        $pending_business_count = Db::name('crm_business')
            ->where([
                ['next_time', '>', 0],
                ['next_time', '<', strtotime('tomorrow')],
                ['owner_admin_id', '=', $admin_id]
            ])->count();
        View::assign('pending_business_count', $pending_business_count);

        // 4. 即将到期合同（与 crm.contract/index scope=expiring 条件一致）
        $expiring_contract_count = Db::name('crm_contract')
            ->where([
                ['check_status', '=', 3],
                ['renewal_id', '=', 0],
                ['end_time', 'between', [strtotime('today'), strtotime('+1 month')]],
                ['owner_admin_id', '=', $admin_id]
            ])->count();
        View::assign('expiring_contract_count', $expiring_contract_count);

        // 5. 待回款（与 crm.contract_receivables_plan/index scope=pending_payment 条件一致）
        $now = time();
        $pending_receivables_count = Db::name('crm_contract_receivables_plan')
            ->where([
                ['status', 'in', [0, 1, 3]],
                ['owner_admin_id', '=', $admin_id],
                ['', 'exp', Db::raw("(plan_date - IFNULL(remind_days, 0) * 86400 <= {$now} OR plan_date < {$now})")]
            ])->count();
        View::assign('pending_receivables_count', $pending_receivables_count);

        // 6. 待审合同（按当前用户审批权限统计，管理员组 group_id=1 默认拥有全部权限）
        $audit_contract_count = Db::name('audit_management')
            ->where('is_finish', 0)
            ->where('title', '合同审核')
            ->where(function ($query) use ($group_id, $admin_id) {
                if ($group_id != 1) {
                    $query->whereRaw('(FIND_IN_SET(:group_id, auditor_group_ids) OR FIND_IN_SET(:admin_id, auditor_admin_ids))', [
                        'group_id' => $group_id,
                        'admin_id' => $admin_id,
                    ]);
                }
            })->count();
        View::assign('audit_contract_count', $audit_contract_count);

        // 7. 待审回款
        $audit_receivables_count = Db::name('audit_management')
            ->where('is_finish', 0)
            ->where('title', '回款审核')
            ->where(function ($query) use ($group_id, $admin_id) {
                if ($group_id != 1) {
                    $query->whereRaw('(FIND_IN_SET(:group_id, auditor_group_ids) OR FIND_IN_SET(:admin_id, auditor_admin_ids))', [
                        'group_id' => $group_id,
                        'admin_id' => $admin_id,
                    ]);
                }
            })->count();
        View::assign('audit_receivables_count', $audit_receivables_count);

        // 8. 待审订单
        $audit_order_count = Db::name('audit_management')
            ->where('is_finish', 0)
            ->where('title', 'Order review')
            ->where(function ($query) use ($group_id, $admin_id) {
                if ($group_id != 1) {
                    $query->whereRaw('(FIND_IN_SET(:group_id, auditor_group_ids) OR FIND_IN_SET(:admin_id, auditor_admin_ids))', [
                        'group_id' => $group_id,
                        'admin_id' => $admin_id,
                    ]);
                }
            })->count();
        View::assign('audit_order_count', $audit_order_count);

        // 审批类待办跳转URL（JSON参数含花括号，不能在模板标签内生成）
        View::assign('audit_contract_url', myurl('process.audit/index', ['scope' => 'audit', 'filter' => '{"title":"合同审核"}', 'op' => '{"title":"="}']));
        View::assign('audit_receivables_url', myurl('process.audit/index', ['scope' => 'audit', 'filter' => '{"title":"回款审核"}', 'op' => '{"title":"="}']));
        View::assign('audit_order_url', myurl('process.audit/index', ['scope' => 'audit', 'filter' => '{"title":"Order review"}', 'op' => '{"title":"="}']));

        // 当日已跟进商机数
        $today_business_record_count = Db::name('crm_business_record')
            ->where('create_admin_id', '=', $admin_id)
            ->whereTime('create_time', 'today')
            ->count('DISTINCT business_id');
        View::assign('today_business_record_count', $today_business_record_count);

        // 待跟商机列表
        $today_business_tiixng = Db::name('crm_business')
            ->field('`id`,`owner_username`,`customer_id`,`name`,`next_time`')
            ->where('is_end', '=', 0)
            ->where('owner_admin_id', '=', $admin_id)
            ->whereNotNull('next_time')
            ->where('next_time', '<>', 0)
            ->whereTime('next_time', '<=', 'tomorrow -1second')
            ->order('next_time ASC')
            ->limit(10)
            ->select()
            ->toArray();
        View::assign('today_business_tiixng', $today_business_tiixng);

        return $this->fetch();
    }


    public function clear(){
        Cache::clear();
        $this->success(lang('Clear cache successful'));

    }

    /**
     * 生成手机版扫码登录二维码内容
     * token 使用安全随机数生成，缓存有效期5分钟，被扫码换取登录凭证后立即失效
     */
    public function scanLoginToken(){
        // 64位十六进制安全随机 token，防止被猜测碰撞
        $token = bin2hex(random_bytes(32));
        // 使用跨应用共享缓存通道，保证 api 应用能读取到该 token
        Cache::store('share')->set('scan_login_' . $token, $this->admin['admin_id'], 300);
        $qrUrl = rtrim($this->request->domain(), '/') . '/fhxcrm/#/pages/login/index?token=' . $token;
        $this->success('生成成功', '', ['qr_url' => $qrUrl, 'expire' => 300]);
    }
    private function _deleteDir($R)
    {
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            if ($item != '.' and $item != '..') {
                if (is_dir($R . '/' . $item)) {
                    $this->_deleteDir($R . '/' . $item);
                } else {
                    if (!unlink($R . '/' . $item))
                        die('error!');
                }
            }
        }
        closedir($handle);
        return rmdir($R);
    }

    //退出登陆
    public function logout(){
        session(null);
        session('referer',$_SERVER["HTTP_REFERER"]);
        $this->redirect(myurl('login/index'));
    }


    /**
     * 切换语言
     */
    public function language()
    {
        $lang = input('get.lang');
        if (!in_array($lang, config('lang.allow_lang_list'))) {
            $lang = 'zh-cn';
        }
        cookie(config('lang.cookie_var'), $lang);
        Cache::clear();
        $this->success(lang('The change succeeded'));

    }
}
