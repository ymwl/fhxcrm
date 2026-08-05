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




        // 根据当前小时生成问候语
        $hour = (int) date('G');
        $greeting = match (true) {
            $hour <= 2  => '夜深了，注意休息',
            $hour <= 5  => '凌晨了，早点休息',
            $hour <= 8  => '早上好',
            $hour <= 10 => '上午好',
            $hour <= 12 => '中午好',
            $hour <= 17 => '下午好',
            $hour <= 22 => '晚上好',
            default     => '夜深了，注意休息',
        };
        $realname = $this->admin['realname'] ?? $this->admin['username'];
        View::assign('wenhou', '尊敬的' . $realname . '，' . $greeting . '！');



        View::assign('cluesCount_week', $cluesCount_week);
        View::assign('clientCount_month', $clientCount_month);
        View::assign('liberumCount_year', $liberumCount_year);
        // 获取待办事项
        //今日已跟进客户*个，未跟进*个，跟进率*%
        //last_up_time

        // ========== 今日跟进统计（与列表页 scope=10/11 口径一致：按 owner_admin_id 过滤） ==========
        $admin_id = $this->admin['admin_id'];
        $wheretoday = [];
        $wheretoday[] = ['owner_admin_id','=',$admin_id];
        // 待跟进时间窗口：系统设置 daigenjin（提前N天提醒），默认到明天
        if (isset($this->system['daigenjin']) && is_numeric($this->system['daigenjin'])) {
            $nextTimeEnd = strtotime("+{$this->system['daigenjin']} day 00:00:00");
        } else {
            $nextTimeEnd = strtotime('tomorrow');
        }

        // 客户：今日已跟进（与 crm.customer/index scope=11 条件一致）
        $today_followed_count = Db::name('crm_customer')->where($wheretoday)->whereTime('last_up_time','today')->count();//今日已经跟进个数
        View::assign('today_followed_count', $today_followed_count);


        $not_followed_count=Db::name('crm_customer')->where($wheretoday)->whereNotNull('next_time')->where('next_time','<>',0)->where('next_time','<',$nextTimeEnd)->count();
        View::assign('not_followed_count',$not_followed_count);

        // 客户：跟进率
        $all_count=($today_followed_count+$not_followed_count);
        if ($all_count > 0) {
            $genjinlv = ($today_followed_count/$all_count)*100;
        }else{
            $genjinlv = 0;
        }
        View::assign('genjinlv', round($genjinlv,2));

        // 线索：今日已跟进（与 crm.clue/index scope=11 条件一致）
        $today_followed_clue_count = Db::name('crm_clue')
            ->where([
                ['last_up_time', '>=', strtotime('today')],
                ['last_up_time', '<', strtotime('tomorrow')],
                ['owner_admin_id', '=', $admin_id],
                ['to_customer_id', '=', 0],
                ['status', '<>', 2],
            ])->count();
        View::assign('today_followed_clue_count', $today_followed_clue_count);

        // 商机：今日已跟进（与 crm.business/index scope=11 条件一致）
        $today_followed_business_count = Db::name('crm_business')
            ->where([
                ['last_up_time', '>=', strtotime('today')],
                ['last_up_time', '<', strtotime('tomorrow')],
                ['owner_admin_id', '=', $admin_id],
            ])->count();
        View::assign('today_followed_business_count', $today_followed_business_count);

        // ========== 8项待办事项统计 ==========
        $group_id = $this->admin['group_id'];

        // 1. 待跟线索（与 crm.clue/index scope=10 条件一致）
        $pending_clue_count = Db::name('crm_clue')
            ->where([
                ['to_customer_id', '=', 0],
                ['status', '<>', 2],
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

        // 线索：跟进率（今日已跟进 / (今日已跟进+待跟进)）
        $clue_all_count = $today_followed_clue_count + $pending_clue_count;
        $clue_genjinlv = $clue_all_count > 0 ? round($today_followed_clue_count / $clue_all_count * 100, 2) : 0;
        View::assign('clue_genjinlv', $clue_genjinlv);

        // 商机：跟进率
        $business_all_count = $today_followed_business_count + $pending_business_count;
        $business_genjinlv = $business_all_count > 0 ? round($today_followed_business_count / $business_all_count * 100, 2) : 0;
        View::assign('business_genjinlv', $business_genjinlv);

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

        // 待办事项总数字角标（8项之和，与头部导航待办角标 backlog 口径一致）
        // 注：pending_customer_count 与 not_followed_count 同值（见上方复用），故直接引用后者求和
        View::assign('todo_total_count', $pending_clue_count + $not_followed_count + $pending_business_count + $expiring_contract_count + $pending_receivables_count + $audit_contract_count + $audit_receivables_count + $audit_order_count);

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



        // ========== 首页图表数据统计 ==========
        // 1. 客户量趋势：近30天客户增量(to_kh_time)与客户成交量(success_time)
        $endtime = time();
        $starttime = strtotime('-29 day');
        $fmtResult = \tools\Hs::format_lx_time($starttime, $endtime);
        $format = $fmtResult[0];
        $column = $fmtResult[1];
        $c_count = $d_count = array_fill_keys($column, 0);
        $c_lists = Db::name('crm_customer')->where('to_kh_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(to_kh_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        foreach ($c_lists as $v) {
            $c_count[$v['add_date']] = $v['nums'];
        }
        $d_lists = Db::name('crm_customer')->where('success_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(success_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        foreach ($d_lists as $v) {
            $d_count[$v['add_date']] = $v['nums'];
        }

        // 2. 跟进方式占比（独立卡片：默认当前年·全年 + 线索跟进，与卡片默认下拉选项、月份留空口径一致）
        $recordTypeData = $this->getRecordTypeData(date('Y') . '-01-01 - ' . date('Y') . '-12-31', 1);

        // ========== 业绩概况（首页面板初始数据：默认当前年·当前月·合同金额） ==========
        $achievement = $this->getAchievementData(1, (int)date('Y'), (int)date('n'));
        View::assign('achievement', $achievement);
        $this->assignconfig('achievement', $achievement);

        $this->assignconfig('erchart', [
            'main_customer' => [
                'date' => array_keys($c_count),
                'data' => [
                    '客户增量' => array_values($c_count),
                    '客户成交量' => array_values($d_count),
                ],
            ],
            'main_clue_customer' => $this->getClueEchartData(),
            'main_record_type' => $recordTypeData,
            'trend' => $this->getTrendEchartData(),
        ]);

        return $this->fetch();
    }

    /**
     * 业绩概况接口（首页面板 AJAX 调用）
     * 参数：row_config 业绩方式(1合同金额 2回款金额 3订单金额)，row_year 年份，row_month 月份(0=全年)
     */
    public function achievement()
    {
        if (!$this->request->isAjax()) {
            $this->error(lang('Invalid request'));
        }
        $config = (int)input('row_config', 1);
        $year = (int)input('row_year', date('Y'));
        $month = (int)input('row_month', 0);
        if (!in_array($config, [1, 2, 3])) {
            $this->error(lang('参数有误'));
        }
        if ($year < 2000 || $year > 2100) {
            $this->error(lang('年份有误'));
        }
        $this->success('success', '', $this->getAchievementData($config, $year, $month));
    }

    /**
     * 合同|回款|订单 金额与数量趋势接口（首页面板 AJAX 调用）
     * 参数：date_range 日期范围（形如 "2026-07-01 - 2026-07-31"，不传默认最近30天）
     */
    public function achievementEchart()
    {
        if (!$this->request->isAjax()) {
            $this->error(lang('Invalid request'));
        }
        $dateRange = $this->request->param('date_range', '');
        $this->success('success', '', [
            'trend' => $this->getTrendEchartData($dateRange),
        ]);
    }

    /**
     * 客户量趋势接口（首页面板 AJAX 调用）
     * 参数：date_range 日期范围（形如 "2026-07-01 - 2026-07-31"，不传默认最近30天）
     */
    public function customerEchart()
    {
        if (!$this->request->isAjax()) {
            $this->error(lang('Invalid request'));
        }
        $dateRange = $this->request->param('date_range', '');
        $this->success('success', '', [
            'main_customer' => $this->getCustomerEchartData($dateRange),
            'main_record_type' => $this->getRecordTypeData($dateRange),
        ]);
    }

    /**
     * 线索量趋势接口（首页面板 AJAX 调用）
     * 参数：date_range 日期范围（形如 "2026-07-01 - 2026-07-31"，不传默认最近30天）
     */
    public function clueEchart()
    {
        if (!$this->request->isAjax()) {
            $this->error(lang('Invalid request'));
        }
        $dateRange = $this->request->param('date_range', '');
        $this->success('success', '', [
            'main_clue_customer' => $this->getClueEchartData($dateRange),
        ]);
    }

    /**
     * 跟进方式占比统计（按时间段过滤跟进记录）
     * @param string $dateRange 日期范围（"开始 - 结束"，空则默认最近30天）
     * @param int    $source    跟进来源 1线索跟进(crm_clue_record) 2客户跟进(crm_record) 3商机跟进(crm_business_record)
     * @return array [{name,value},...] 仅含有所选时间段内产生记录的跟进方式
     */
    private function getRecordTypeData($dateRange = '', $source = 2)
    {
        // 时间范围：解析 date_range；非法或为空时默认最近30天（含今天）
        $starttime = $endtime = null;
        if ($dateRange !== '' && strpos($dateRange, ' - ') !== false) {
            list($startDate, $endDate) = explode(' - ', $dateRange, 2);
            $starttime = strtotime($startDate);
            $endtime = strtotime($endDate);
            if ($starttime && $endtime) {
                if ($starttime > $endtime) {
                    list($starttime, $endtime) = [$endtime, $starttime];
                }
                // 结束日期不带时分秒时补到当天 23:59:59，避免漏掉当天数据
                if (date('H:i:s', $endtime) == '00:00:00') {
                    $endtime += 86399;
                }
            } else {
                $starttime = $endtime = null;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $endtime = time();
            $starttime = strtotime('-29 day');
        }
        // 来源表选择（默认客户跟进，保持 customerEchart 接口原有口径不变）
        $recordTable = 'crm_record';
        if ((int)$source === 1) {
            $recordTable = 'crm_clue_record';
        } elseif ((int)$source === 3) {
            $recordTable = 'crm_business_record';
        }
        $recordTypeData = [];
        $recordTypeList = Db::name('crm_record_type')->field('id,name')->where('status', '=', 1)->order('sort ASC')->select();
        if ($recordTypeList) {
            foreach ($recordTypeList as $v) {
                $nums = Db::name($recordTable)->where('create_time', 'between time', [$starttime, $endtime])
                    ->where('record_type', $v['name'])
                    ->count();
                if ($nums > 0) {
                    $recordTypeData[] = ['name' => $v['name'], 'value' => $nums];
                }
            }
        }
        return $recordTypeData;
    }

    /**
     * 跟进方式占比接口（首页面板 AJAX 调用）
     * 参数：row_source 跟进来源(1线索跟进 2客户跟进 3商机跟进)，date_range 日期范围（空则默认最近30天）
     */
    public function recordTypeEchart()
    {
        if (!$this->request->isAjax()) {
            $this->error(lang('Invalid request'));
        }
        $source = (int)input('row_source', 2);
        if (!in_array($source, [1, 2, 3], true)) {
            $this->error(lang('参数有误'));
        }
        $dateRange = $this->request->param('date_range', '');
        $this->success('success', '', [
            'main_record_type' => $this->getRecordTypeData($dateRange, $source),
        ]);
    }

    /**
     * 客户量趋势统计（客户增量 to_kh_time + 客户成交量 success_time）
     * @param string $dateRange 日期范围（"开始 - 结束"，空则默认最近30天）
     * @return array {date:[], data:{客户增量,客户成交量}}
     */
    private function getCustomerEchartData($dateRange = '')
    {
        // 时间范围：解析 date_range；非法或为空时默认最近30天（含今天）
        $starttime = $endtime = null;
        if ($dateRange !== '' && strpos($dateRange, ' - ') !== false) {
            list($startDate, $endDate) = explode(' - ', $dateRange, 2);
            $starttime = strtotime($startDate);
            $endtime = strtotime($endDate);
            if ($starttime && $endtime) {
                if ($starttime > $endtime) {
                    list($starttime, $endtime) = [$endtime, $starttime];
                }
                // 结束日期不带时分秒时补到当天 23:59:59，避免漏掉当天数据
                if (date('H:i:s', $endtime) == '00:00:00') {
                    $endtime += 86399;
                }
            } else {
                $starttime = $endtime = null;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $endtime = time();
            $starttime = strtotime('-29 day');
        }
        $totalseconds = $endtime - $starttime;
        // 时间跨度决定分组粒度：>60天按月，>1天按天，否则按小时
        if ($totalseconds > 86400 * 30 * 2) {
            $format = '%Y-%m';
        } elseif ($totalseconds > 86400) {
            $format = '%Y-%m-%d';
        } else {
            $format = '%H:00';
        }

        // 生成连续时间轴（保证无数据的日期也出现在图中）
        $column = [];
        if ($totalseconds > 86400 * 30 * 2) {
            $mStart = mktime(0, 0, 0, (int)date('n', $starttime), 1, (int)date('Y', $starttime));
            $mEnd = mktime(0, 0, 0, (int)date('n', $endtime), 1, (int)date('Y', $endtime));
            for ($t = $mStart; $t <= $mEnd; $t = strtotime('+1 month', $t)) {
                $column[] = date('Y-m', $t);
            }
        } elseif ($totalseconds > 86400) {
            for ($t = $starttime; $t <= $endtime; $t += 86400) {
                $column[] = date('Y-m-d', $t);
            }
        } else {
            for ($t = $starttime; $t <= $endtime; $t += 3600) {
                $column[] = date('H:00', $t);
            }
        }

        $c_count = $d_count = array_fill_keys($column, 0);
        // 客户增量：按转客户时间(to_kh_time)
        $c_lists = Db::name('crm_customer')->where('to_kh_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(to_kh_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        foreach ($c_lists as $v) {
            $c_count[$v['add_date']] = (int)$v['nums'];
        }
        // 客户成交量：按成交时间(success_time)
        $d_lists = Db::name('crm_customer')->where('success_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(success_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        foreach ($d_lists as $v) {
            $d_count[$v['add_date']] = (int)$v['nums'];
        }

        return [
            'date' => array_keys($c_count),
            'data' => [
                '客户增量' => array_values($c_count),
                '客户成交量' => array_values($d_count),
            ],
        ];
    }

    /**
     * 线索量趋势统计（线索增量 create_time + 线索转化量 to_customer_time）
     * @param string $dateRange 日期范围（"开始 - 结束"，空则默认最近30天）
     * @return array {date:[], data:{线索增量,线索转化量}}
     */
    private function getClueEchartData($dateRange = '')
    {
        // 时间范围：解析 date_range；非法或为空时默认最近30天（含今天）
        $starttime = $endtime = null;
        if ($dateRange !== '' && strpos($dateRange, ' - ') !== false) {
            list($startDate, $endDate) = explode(' - ', $dateRange, 2);
            $starttime = strtotime($startDate);
            $endtime = strtotime($endDate);
            if ($starttime && $endtime) {
                if ($starttime > $endtime) {
                    list($starttime, $endtime) = [$endtime, $starttime];
                }
                // 结束日期不带时分秒时补到当天 23:59:59，避免漏掉当天数据
                if (date('H:i:s', $endtime) == '00:00:00') {
                    $endtime += 86399;
                }
            } else {
                $starttime = $endtime = null;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $endtime = time();
            $starttime = strtotime('-29 day');
        }
        $totalseconds = $endtime - $starttime;
        // 时间跨度决定分组粒度：>60天按月，>1天按天，否则按小时
        if ($totalseconds > 86400 * 30 * 2) {
            $format = '%Y-%m';
        } elseif ($totalseconds > 86400) {
            $format = '%Y-%m-%d';
        } else {
            $format = '%H:00';
        }

        // 生成连续时间轴（保证无数据的日期也出现在图中）
        $column = [];
        if ($totalseconds > 86400 * 30 * 2) {
            $mStart = mktime(0, 0, 0, (int)date('n', $starttime), 1, (int)date('Y', $starttime));
            $mEnd = mktime(0, 0, 0, (int)date('n', $endtime), 1, (int)date('Y', $endtime));
            for ($t = $mStart; $t <= $mEnd; $t = strtotime('+1 month', $t)) {
                $column[] = date('Y-m', $t);
            }
        } elseif ($totalseconds > 86400) {
            for ($t = $starttime; $t <= $endtime; $t += 86400) {
                $column[] = date('Y-m-d', $t);
            }
        } else {
            for ($t = $starttime; $t <= $endtime; $t += 3600) {
                $column[] = date('H:00', $t);
            }
        }

        $c_count = $d_count = array_fill_keys($column, 0);
        // 线索增量：按创建时间(create_time)
        $c_lists = Db::name('crm_clue')->where('create_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(create_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        foreach ($c_lists as $v) {
            $c_count[$v['add_date']] = (int)$v['nums'];
        }
        // 线索转化量：按转客户时间(to_customer_time)
        $d_lists = Db::name('crm_clue')->where('to_customer_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(to_customer_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        foreach ($d_lists as $v) {
            $d_count[$v['add_date']] = (int)$v['nums'];
        }

        return [
            'date' => array_keys($c_count),
            'data' => [
                '线索增量' => array_values($c_count),
                '线索转化量' => array_values($d_count),
            ],
        ];
    }

    /**
     * 合同|回款|订单 金额与数量趋势统计（仅统计当前登录人自己的数据）
     * @param string $dateRange 日期范围（"开始 - 结束"，空则默认最近30天）
     * @return array {date:[], data:{合同数量,合同金额,回款金额,回款数量,订单数量,订单金额}}
     */
    private function getTrendEchartData($dateRange = '')
    {
        $admin_id = $this->admin['admin_id'];
        // 时间范围：解析 date_range；非法或为空时默认最近30天（含今天）
        $starttime = $endtime = null;
        if ($dateRange !== '' && strpos($dateRange, ' - ') !== false) {
            list($startDate, $endDate) = explode(' - ', $dateRange, 2);
            $starttime = strtotime($startDate);
            $endtime = strtotime($endDate);
            if ($starttime && $endtime) {
                if ($starttime > $endtime) {
                    list($starttime, $endtime) = [$endtime, $starttime];
                }
                // 结束日期不带时分秒时补到当天 23:59:59，避免漏掉当天数据
                if (date('H:i:s', $endtime) == '00:00:00') {
                    $endtime += 86399;
                }
            } else {
                $starttime = $endtime = null;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $endtime = time();
            $starttime = strtotime('-29 day');
        }
        $totalseconds = $endtime - $starttime;
        // 时间跨度决定分组粒度：>60天按月，>1天按天，否则按小时
        if ($totalseconds > 86400 * 30 * 2) {
            $format = '%Y-%m';
        } elseif ($totalseconds > 86400) {
            $format = '%Y-%m-%d';
        } else {
            $format = '%H:00';
        }

        // 合同：审核通过(check_status=3)，按签约时间(sign_time)
        $contractList = Db::name('crm_contract')
            ->where('check_status', 3)
            ->where('owner_admin_id', $admin_id)
            ->where('sign_time', 'between', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, SUM(money) AS money, DATE_FORMAT(FROM_UNIXTIME(sign_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        // 回款：审核通过(check_status=3)，按回款时间(return_time)
        $receivablesList = Db::name('crm_contract_receivables')
            ->where('check_status', 3)
            ->where('owner_admin_id', $admin_id)
            ->where('return_time', 'between', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, SUM(money) AS money, DATE_FORMAT(FROM_UNIXTIME(return_time), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();
        // 订单：审核通过(status=1)，按下单时间(xiadanriqi)
        $orderList = Db::name('crm_order')
            ->where('status', 1)
            ->where('owner_admin_id', $admin_id)
            ->where('xiadanriqi', 'between', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, SUM(money) AS money, DATE_FORMAT(FROM_UNIXTIME(xiadanriqi), "' . $format . '") AS add_date')
            ->group('add_date')
            ->select();

        // 生成连续时间轴（保证无数据的日期也出现在图中）
        $column = [];
        if ($totalseconds > 86400 * 30 * 2) {
            $mStart = mktime(0, 0, 0, (int)date('n', $starttime), 1, (int)date('Y', $starttime));
            $mEnd = mktime(0, 0, 0, (int)date('n', $endtime), 1, (int)date('Y', $endtime));
            for ($t = $mStart; $t <= $mEnd; $t = strtotime('+1 month', $t)) {
                $column[] = date('Y-m', $t);
            }
        } elseif ($totalseconds > 86400) {
            for ($t = $starttime; $t <= $endtime; $t += 86400) {
                $column[] = date('Y-m-d', $t);
            }
        } else {
            for ($t = $starttime; $t <= $endtime; $t += 3600) {
                $column[] = date('H:00', $t);
            }
        }

        $c_count = $c_money = $r_count = $r_money = $o_count = $o_money = array_fill_keys($column, 0);
        foreach ($contractList as $v) {
            $c_count[$v['add_date']] = (int)$v['nums'];
            $c_money[$v['add_date']] = (float)$v['money'];
        }
        foreach ($receivablesList as $v) {
            $r_count[$v['add_date']] = (int)$v['nums'];
            $r_money[$v['add_date']] = (float)$v['money'];
        }
        foreach ($orderList as $v) {
            $o_count[$v['add_date']] = (int)$v['nums'];
            $o_money[$v['add_date']] = (float)$v['money'];
        }

        return [
            'date' => array_keys($c_count),
            'data' => [
                '合同数量' => array_values($c_count),
                '合同金额' => array_values($c_money),
                '回款金额' => array_values($r_money),
                '回款数量' => array_values($r_count),
                '订单数量' => array_values($o_count),
                '订单金额' => array_values($o_money),
            ],
        ];
    }

    /**
     * 业绩概况统计
     * @param int $config 业绩方式 1合同金额 2回款金额 3订单金额
     * @param int $year   年份
     * @param int $month  月份 0=全年
     * @return array
     */
    private function getAchievementData($config, $year, $month)
    {
        $config_arr = [1 => '合同金额', 2 => '回款金额', 3 => '订单金额'];
        $month_arr = [0 => 'yeartarget', 1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april', 5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august', 9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'];
        list($start_time, $end_time) = $this->mFristAndLast($year, $month);
        $admin_id = $this->admin['admin_id'];

        // 业绩目标（month=0 取全年目标 yeartarget，否则取对应月份字段）
        $yeartarget = Db::name('crm_achievement')
            ->where('admin_id', $admin_id)
            ->where('config', $config)
            ->where('year', $year)
            ->value($month_arr[$month]);

        // 合同金额：审核通过(check_status=3)，按签约日期(sign_time)
        $contract_money = Db::name('crm_contract')
            ->where('check_status', 3)
            ->where('owner_admin_id', $admin_id)
            ->where('sign_time', 'between', [$start_time, $end_time])
            ->sum('money');

        // 回款金额：审核通过(check_status=3)，按回款日期(return_time)
        $receivables_money = Db::name('crm_contract_receivables')
            ->where('check_status', 3)
            ->where('owner_admin_id', $admin_id)
            ->where('return_time', 'between', [$start_time, $end_time])
            ->sum('money');

        // 订单金额：审核通过(status=1)，按下单日期(xiadanriqi)
        $order_money = Db::name('crm_order')
            ->where('status', 1)
            ->where('owner_admin_id', $admin_id)
            ->where('xiadanriqi', 'between', [$start_time, $end_time])
            ->sum('money');

        $money_arr = [1 => $contract_money, 2 => $receivables_money, 3 => $order_money];
        $complete_percent = $yeartarget > 0 ? round($money_arr[$config] / $yeartarget * 100, 2) : 0;

        return [
            'name' => $config_arr[$config],
            'year' => $year,
            'month' => $month,
            'yeartarget' => $yeartarget ?: 0,
            'contract_money' => $contract_money ?: 0,
            'receivables_money' => $receivables_money ?: 0,
            'order_money' => $order_money ?: 0,
            'complete_percent' => $complete_percent,
        ];
    }

    /**
     * 获取某年某月的时间范围
     * @param int $year  年份
     * @param int $month 月份 0=全年
     * @return array [开始时间戳, 结束时间戳]
     */
    private function mFristAndLast($year, $month)
    {
        if ($month > 0) {
            $start_time = mktime(0, 0, 0, $month, 1, $year);
            $end_time = mktime(23, 59, 59, $month, date('t', $start_time), $year);
        } else {
            $start_time = mktime(0, 0, 0, 1, 1, $year);
            $end_time = mktime(23, 59, 59, 12, 31, $year);
        }
        return [$start_time, $end_time];
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
