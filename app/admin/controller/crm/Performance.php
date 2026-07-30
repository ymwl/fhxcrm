<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;
use app\admin\model\CrmAchievement;

use think\App;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class Performance extends AdminController
{
    /**
     * 月份字段映射
     */
    private $monthFields = [
        1 => 'january', 2 => 'february', 3 => 'march',
        4 => 'april', 5 => 'may', 6 => 'june',
        7 => 'july', 8 => 'august', 9 => 'september',
        10 => 'october', 11 => 'november', 12 => 'december',
    ];

    /**
     * 业绩方式选项
     */
    private $configOptions = [
        1 => '合同金额',
        2 => '回款金额',
        3 => '订单金额',
    ];

    public function analytics()
    {
        if ($this->request->isAjax()) {

            $get = $this->request->get('', null, null);
            $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
            $filters = json_decode($filters, true);
            $month = empty($filters['month']) ? date('Y-m') : $filters['month'];
            $uninx_time = strtotime($month);
            $startTimestamp = $uninx_time;
            $endTimestamp = strtotime(date('Y-m-01 23:59:59', $uninx_time) . " +1 month -1 day");
            $curYear = date('Y', $uninx_time);
            $curMonth = intval(date('m', $uninx_time));
            $curMonthField = $this->monthFields[$curMonth] ?? 'january';

            $prefix = config('database.connections.mysql.prefix');
            $where = '';
            if (!empty($filters['username'])) {
                $where .= " AND `username` LIKE '%{$filters['username']}%'";
            }

            // 主查询：员工 + 订单统计 + 三种业绩目标（按config分类）
            $userlist = Db::query("
                SELECT 
                    a.admin_id, a.username, a.realname, a.ticheng,
                    COALESCE(o.order_money, 0) AS order_money,
                    COALESCE(o.order_count, 0) AS order_count,
                    COALESCE(ach_order.order_target, 0) AS order_target,
                    COALESCE(ach_contract.contract_target, 0) AS contract_target,
                    COALESCE(ach_receivables.receivables_target, 0) AS receivables_target
                FROM {$prefix}admin a
                LEFT JOIN (
                    SELECT SUM(`money`) AS order_money, COUNT(`id`) AS order_count, pr_user
                    FROM `{$prefix}crm_order`
                    WHERE `status` = 1 AND create_time BETWEEN '{$startTimestamp}' AND '{$endTimestamp}'
                    GROUP BY `pr_user`
                ) o ON a.username = o.pr_user
                LEFT JOIN (
                    SELECT admin_id, `{$curMonthField}` AS order_target
                    FROM {$prefix}crm_achievement WHERE year='{$curYear}' AND config=3
                ) ach_order ON ach_order.admin_id = a.admin_id
                LEFT JOIN (
                    SELECT admin_id, `{$curMonthField}` AS contract_target
                    FROM {$prefix}crm_achievement WHERE year='{$curYear}' AND config=1
                ) ach_contract ON ach_contract.admin_id = a.admin_id
                LEFT JOIN (
                    SELECT admin_id, `{$curMonthField}` AS receivables_target
                    FROM {$prefix}crm_achievement WHERE year='{$curYear}' AND config=2
                ) ach_receivables ON ach_receivables.admin_id = a.admin_id
                WHERE a.is_open=1 {$where}
                ORDER BY order_money DESC
            ");

            $adminIds = array_column($userlist, 'admin_id');
            $adminStr = !empty($adminIds) ? implode(',', $adminIds) : '0';

            // 合同签约统计（金额+数量，仅已审核 check_status=3）
            $ht_lst = Db::query("SELECT SUM(money) AS contract_money, COUNT(id) AS contract_count, owner_admin_id FROM {$prefix}crm_contract WHERE check_status=3 AND sign_time BETWEEN '{$startTimestamp}' AND '{$endTimestamp}' AND owner_admin_id IN ({$adminStr}) GROUP BY `owner_admin_id`");
            $ht_map = [];
            if ($ht_lst) {
                foreach ($ht_lst as $row) {
                    $ht_map[$row['owner_admin_id']] = $row;
                }
            }

            // 回款统计（金额+数量，仅已审核 check_status=3）
            $hk_lst = Db::query("SELECT SUM(money) AS receivables_money, COUNT(id) AS receivables_count, owner_admin_id FROM {$prefix}crm_contract_receivables WHERE check_status=3 AND return_time BETWEEN '{$startTimestamp}' AND '{$endTimestamp}' AND owner_admin_id IN ({$adminStr}) GROUP BY `owner_admin_id`");
            $hk_map = [];
            if ($hk_lst) {
                foreach ($hk_lst as $row) {
                    $hk_map[$row['owner_admin_id']] = $row;
                }
            }

            // 合并数据并计算完成率
            $userlist = array_map(function ($item) use ($ht_map, $hk_map) {
                // 合同数据
                $item['contract_money'] = isset($ht_map[$item['admin_id']]) ? floatval($ht_map[$item['admin_id']]['contract_money']) : 0;
                $item['contract_count'] = isset($ht_map[$item['admin_id']]) ? intval($ht_map[$item['admin_id']]['contract_count']) : 0;
                // 回款数据
                $item['receivables_money'] = isset($hk_map[$item['admin_id']]) ? floatval($hk_map[$item['admin_id']]['receivables_money']) : 0;
                $item['receivables_count'] = isset($hk_map[$item['admin_id']]) ? intval($hk_map[$item['admin_id']]['receivables_count']) : 0;

                // 计算完成率（目标为0时返回-1表示"未设置"）
                $item['order_target'] = floatval($item['order_target']);
                $item['contract_target'] = floatval($item['contract_target']);
                $item['receivables_target'] = floatval($item['receivables_target']);
                $item['order_money'] = floatval($item['order_money']);
                $item['order_count'] = intval($item['order_count']);

                $item['order_rate'] = $item['order_target'] > 0
                    ? round($item['order_money'] / $item['order_target'] * 100, 2)
                    : -1;
                $item['contract_rate'] = $item['contract_target'] > 0
                    ? round($item['contract_money'] / $item['contract_target'] * 100, 2)
                    : -1;
                $item['receivables_rate'] = $item['receivables_target'] > 0
                    ? round($item['receivables_money'] / $item['receivables_target'] * 100, 2)
                    : -1;

                return $item;
            }, $userlist);

            // 排序支持（白名单验证）
            $allowSortFields = [
                'username', 'order_target', 'order_rate', 'order_money', 'order_count',
                'contract_target', 'contract_rate', 'contract_money', 'contract_count',
                'receivables_target', 'receivables_rate', 'receivables_money', 'receivables_count',
            ];
            $sortField = $this->request->get('field', 'order_money');
            $sortOrder = strtolower($this->request->get('order', 'desc'));
            if (in_array($sortField, $allowSortFields)) {
                $sortFlags = ($sortOrder === 'asc') ? SORT_ASC : SORT_DESC;
                $sortColumn = array_column($userlist, $sortField);
                array_multisort($sortColumn, $sortFlags, $userlist);
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => count($userlist),
                'data'  => $userlist,
                'cur_date' => date('Y-m', $uninx_time)
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 业绩设置列表（每种业绩方式独立行）
     */
    public function achievement()
    {
        if ($this->request->isAjax()) {
            $get = $this->request->get('', null, null);
            $filters = isset($get['filter']) && !empty($get['filter']) ? json_decode($get['filter'], true) : [];
            $year = !empty($filters['year']) ? $filters['year'] : date('Y');

            $query = CrmAchievement::where('year', $year);

            // 按员工姓名搜索
            if (!empty($filters['admin_name'])) {
                $adminName = $filters['admin_name'];
                $adminIds = Db::name('admin')
                    ->where('username', 'like', '%' . $adminName . '%')
                    ->whereOr('realname', 'like', '%' . $adminName . '%')
                    ->column('admin_id');
                if (!empty($adminIds)) {
                    $query->whereIn('admin_id', $adminIds);
                } else {
                    $query->where('admin_id', 0);
                }
            }
            // 按范围筛选
            $scope=$this->request->get('scope', 0,'intval');
            if (!empty($scope)) {
                $query->where('config', $scope);
            }

            $list = $query->with(['admin'])
                ->order('admin_id', 'asc')
                ->order('config', 'asc')
                ->select()
                ->toArray();

            // 格式化config显示文本
            foreach ($list as &$row) {
                $configVal = intval($row['config']);
                $row['config_text'] = CrmAchievement::$configLabels[$configVal] ?? '未知';
            }
            unset($row);

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => count($list),
                'data'  => $list,
            ];
            return json($data);
        }

        View::assign('configOptions', $this->configOptions);
        return $this->fetch();
    }

    /**
     * 批量业绩设置（每种业绩方式独立设置目标）
     */
    public function batchAchievement()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();

            if (empty($post['admin_ids'])) {
                $this->error('请选择员工');
            }
            if (empty($post['year']) || !is_numeric($post['year']) || $post['year'] < 2000) {
                $this->error('请选择正确的年份');
            }

            $adminIds = is_array($post['admin_ids']) ? $post['admin_ids'] : explode(',', $post['admin_ids']);
            $year = $post['year'];
            $successCount = 0;

            // 遍历每种业绩方式，独立创建或更新记录
            foreach ($this->configOptions as $configVal => $configName) {
                // 检查该方式是否被勾选
                if (empty($post['config_' . $configVal])) {
                    continue;
                }

                // 构建该方式的月度目标数据
                $monthData = [];
                $yeartarget = 0;
                foreach ($this->monthFields as $num => $field) {
                    $key = $field . '_' . $configVal;
                    $val = floatval($post[$key] ?? 0);
                    $monthData[$field] = $val;
                    $yeartarget += $val;
                }

                $ytKey = 'yeartarget_' . $configVal;
                if (!empty($post[$ytKey])) {
                    $yeartarget = floatval($post[$ytKey]);
                }
                $monthData['yeartarget'] = $yeartarget;

                foreach ($adminIds as $adminId) {
                    $adminId = intval($adminId);
                    if ($adminId <= 0) continue;

                    $exists = CrmAchievement::where('admin_id', $adminId)
                        ->where('year', $year)
                        ->where('config', $configVal)
                        ->find();

                    if ($exists) {
                        $exists->save(array_merge($monthData, [
                            'update_time' => time(),
                        ]));
                    } else {
                        CrmAchievement::create(array_merge($monthData, [
                            'admin_id' => $adminId,
                            'year' => $year,
                            'config' => $configVal,
                            'create_time' => time(),
                            'update_time' => time(),
                        ]));
                    }
                    $successCount++;
                }
            }

            if ($successCount == 0) {
                $this->error('请至少勾选一种业绩方式');
            }
            $this->success('成功设置 ' . $successCount . ' 条业绩目标');
        }

        View::assign('configOptions', $this->configOptions);
        View::assign('currentYear', date('Y'));
        return View::fetch('batch_achievement');
    }

    /**
     * 编辑单条业绩记录（单种业绩方式）
     */
    public function achievementEdit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $row = CrmAchievement::with(['admin'])->find($id);
        if (empty($row)) {
            $this->error('数据不存在');
        }

        if ($this->request->isPost()) {
            $post = $this->request->post();

            if (empty($post['year']) || !is_numeric($post['year']) || $post['year'] < 2000) {
                $this->error('请选择正确的年份');
            }

            $monthData = [];
            $yeartarget = 0;
            foreach ($this->monthFields as $num => $field) {
                $val = floatval($post[$field] ?? 0);
                $monthData[$field] = $val;
                $yeartarget += $val;
            }

            if (!empty($post['yeartarget'])) {
                $yeartarget = floatval($post['yeartarget']);
            }
            $monthData['yeartarget'] = $yeartarget;
            $monthData['update_time'] = time();

            $row->save($monthData);
            $this->success('保存成功');
        }

        $configVal = intval($row['config']);
        View::assign('configOptions', $this->configOptions);
        View::assign('configName', CrmAchievement::$configLabels[$configVal] ?? '未知');
        View::assign('row', $row);
        return View::fetch('achievement_edit');
    }

    /**
     * 删除业绩记录
     */
    public function achievementDelete()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('参数错误');
        }

        $row = CrmAchievement::find($id);
        if (empty($row)) {
            $this->error('数据不存在');
        }

        if ($row->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

}
