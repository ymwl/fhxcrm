<?php
namespace app\admin\service;

use think\facade\Db;
use think\facade\Log;

/**
 * 提醒服务层
 * 处理跟进提醒、合同到期提醒等
 */
class ReminderService
{
    /**
     * 待办提醒类型
     */
    const TYPE_FOLLOW_UP = 1;        // 跟进提醒
    const TYPE_CONTRACT_EXPIRE = 2;  // 合同到期提醒
    const TYPE_RECEIVABLES = 3;      // 回款提醒
    const TYPE_BIRTHDAY = 4;         // 生日提醒

    /**
     * 提醒状态
     */
    const STATUS_PENDING = 0;   // 待提醒
    const STATUS_SENT = 1;      // 已发送
    const STATUS_READ = 2;      // 已读

    /**
     * 检查并生成跟进提醒
     * 查找 next_time 在未来 N 天内且未提醒的记录
     * @param int $days 提前天数
     * @return array
     */
    public function checkFollowUpReminders($days = 1)
    {
        $reminders = [];
        $deadline = strtotime("+{$days} day 00:00:00");
        $now = time();

        // 查找需要提醒的客户
        $customers = Db::name('crm_customer')
            ->where('status', 1)
            ->where('next_time', '>', 0)
            ->where('next_time', '<=', $deadline)
            ->where('next_time', '>=', $now)
            ->field('id, name, pr_user, owner_admin_id, next_time')
            ->select()
            ->toArray();

        foreach ($customers as $customer) {
            // 检查是否已存在未读的提醒
            $exists = Db::name('crm_reminder')
                ->where('type', self::TYPE_FOLLOW_UP)
                ->where('related_id', $customer['id'])
                ->where('status', self::STATUS_PENDING)
                ->find();

            if (!$exists) {
                $reminderId = Db::name('crm_reminder')->insertGetId([
                    'type' => self::TYPE_FOLLOW_UP,
                    'related_id' => $customer['id'],
                    'related_type' => 'customer',
                    'title' => "客户跟进提醒：{$customer['name']}",
                    'content' => "客户 {$customer['name']} 的跟进时间即将到来，请及时跟进。",
                    'admin_id' => $customer['owner_admin_id'],
                    'admin_name' => $customer['pr_user'],
                    'remind_time' => $customer['next_time'],
                    'status' => self::STATUS_PENDING,
                    'create_time' => time(),
                ]);

                $reminders[] = [
                    'id' => $reminderId,
                    'customer_name' => $customer['name'],
                    'admin_name' => $customer['pr_user'],
                    'remind_time' => date('Y-m-d H:i', $customer['next_time']),
                ];
            }
        }

        return $reminders;
    }

    /**
     * 检查并生成合同到期提醒
     * @param int $daysBefore 提前天数
     * @return array
     */
    public function checkContractExpireReminders($daysBefore = 7)
    {
        $reminders = [];
        $deadline = strtotime("+{$daysBefore} day 23:59:59");
        $now = strtotime('today');

        // 查找即将到期的合同
        $contracts = Db::name('crm_contract')
            ->alias('c')
            ->join('crm_customer cu', 'c.customer_id = cu.id', 'left')
            ->where('c.check_status', 3)  // 已审批通过
            ->where('c.end_time', '>=', $now)
            ->where('c.end_time', '<=', $deadline)
            ->field('c.id, c.name, c.end_time, c.owner_admin_id, cu.pr_user, cu.name as customer_name')
            ->select()
            ->toArray();

        foreach ($contracts as $contract) {
            // 检查是否已存在未读的提醒
            $exists = Db::name('crm_reminder')
                ->where('type', self::TYPE_CONTRACT_EXPIRE)
                ->where('related_id', $contract['id'])
                ->where('status', self::STATUS_PENDING)
                ->find();

            if (!$exists) {
                $daysLeft = ceil(($contract['end_time'] - time()) / 86400);
                $reminderId = Db::name('crm_reminder')->insertGetId([
                    'type' => self::TYPE_CONTRACT_EXPIRE,
                    'related_id' => $contract['id'],
                    'related_type' => 'contract',
                    'title' => "合同到期提醒：{$contract['name']}",
                    'content' => "客户 {$contract['customer_name']} 的合同《{$contract['name']}》将在 {$daysLeft} 天后到期，请及时处理续签事宜。",
                    'admin_id' => $contract['owner_admin_id'],
                    'admin_name' => Db::name('admin')->cache('admin_id_'.$contract['owner_admin_id'],3600)->where(['admin_id'=>$contract['owner_admin_id']])->value('username'),
                    'remind_time' => $contract['end_time'],
                    'status' => self::STATUS_PENDING,
                    'create_time' => time(),
                ]);

                $reminders[] = [
                    'id' => $reminderId,
                    'contract_name' => $contract['name'],
                    'customer_name' => $contract['customer_name'],
                    'admin_name' => $contract['pr_user'],
                    'days_left' => $daysLeft,
                ];
            }
        }

        return $reminders;
    }

    /**
     * 获取用户的提醒列表
     * @param int $adminId 管理员ID
     * @param int $status 状态：0待提醒 1已发送 2已读
     * @param int $limit 数量限制
     * @return array
     */
    public function getUserReminders($adminId, $status = null, $limit = 20)
    {
        $query = Db::name('crm_reminder')
            ->where('admin_id', $adminId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $list = $query->order('status ASC, create_time DESC')
            ->limit($limit)
            ->select()
            ->toArray();

        return $list;
    }

    /**
     * 获取用户待处理提醒数量
     * @param int $adminId 管理员ID
     * @return int
     */
    public function getPendingCount($adminId)
    {
        return Db::name('crm_reminder')
            ->where('admin_id', $adminId)
            ->where('status', self::STATUS_PENDING)
            ->count();
    }

    /**
     * 标记提醒为已读
     * @param int $reminderId 提醒ID
     * @param int $adminId 管理员ID（用于权限验证）
     * @return bool
     */
    public function markAsRead($reminderId, $adminId)
    {
        $reminder = Db::name('crm_reminder')
            ->where('id', $reminderId)
            ->where('admin_id', $adminId)
            ->find();

        if (!$reminder) {
            return false;
        }

        Db::name('crm_reminder')
            ->where('id', $reminderId)
            ->update([
                'status' => self::STATUS_READ,
                'read_time' => time(),
                'update_time' => time(),
            ]);

        return true;
    }

    /**
     * 标记所有提醒为已读
     * @param int $adminId 管理员ID
     * @return int 更新的数量
     */
    public function markAllAsRead($adminId)
    {
        return Db::name('crm_reminder')
            ->where('admin_id', $adminId)
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status' => self::STATUS_READ,
                'read_time' => time(),
                'update_time' => time(),
            ]);
    }

    /**
     * 删除过期提醒
     * @param int $days 保留天数
     * @return int 删除数量
     */
    public function cleanExpiredReminders($days = 30)
    {
        $deadline = strtotime("-{$days} day");

        return Db::name('crm_reminder')
            ->where('status', self::STATUS_READ)
            ->where('read_time', '<', $deadline)
            ->delete();
    }

    /**
     * 创建自定义提醒
     * @param array $data 提醒数据
     * @return int 提醒ID
     */
    public function createReminder($data)
    {
        $defaultData = [
            'status' => self::STATUS_PENDING,
            'create_time' => time(),
        ];

        $insertData = array_merge($defaultData, $data);
        return Db::name('crm_reminder')->insertGetId($insertData);
    }

    /**
     * 检查并生成回款提醒
     * 根据每个回款计划的 remind_days（提前提醒天数）动态计算提醒时间
     * @return array
     */
    public function checkReceivablesReminders()
    {
        $reminders = [];
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow') - 1;

        // 查找需要提醒的回款计划（未回款或部分回款状态）
        $plans = Db::name('crm_contract_receivables_plan')
            ->alias('p')
            ->join('crm_customer c', 'p.customer_id = c.id', 'left')
            ->join('crm_contract ct', 'p.contract_id = ct.id', 'left')
            ->join('admin a', 'p.owner_admin_id = a.admin_id', 'left')
            ->where('p.status', 'in', [0, 2])  // 未回款或部分回款
            ->field('p.id, p.plan_no, p.plan_money, p.plan_date, p.remind_days, p.owner_admin_id, p.customer_id, p.contract_id, 
                     c.name as customer_name, ct.name as contract_name, a.username as admin_name')
            ->select()
            ->toArray();

        foreach ($plans as $plan) {
            // 获取该计划的提前提醒天数，默认为1天
            $remindDays = !empty($plan['remind_days']) ? intval($plan['remind_days']) : 1;

            // 计算提醒触发日期（计划日期前 remind_days 天）
            $remindDate = strtotime("-{$remindDays} day", $plan['plan_date']);

            // 检查今天是否在提醒日期范围内（提醒当天）
            if ($today <= $remindDate && $remindDate <= $tomorrow) {
                // 检查是否已存在未读的提醒
                $exists = Db::name('crm_reminder')
                    ->where('type', self::TYPE_RECEIVABLES)
                    ->where('related_id', $plan['id'])
                    ->where('status', self::STATUS_PENDING)
                    ->find();

                if (!$exists) {
                    $planDate = date('Y-m-d', $plan['plan_date']);
                    $reminderId = Db::name('crm_reminder')->insertGetId([
                        'type' => self::TYPE_RECEIVABLES,
                        'related_id' => $plan['id'],
                        'related_type' => 'receivables_plan',
                        'title' => "回款提醒：{$plan['plan_no']}",
                        'content' => "客户 {$plan['customer_name']} 的合同《{$plan['contract_name']}》有回款计划，计划金额：{$plan['plan_money']} 元，计划日期：{$planDate}，请及时跟进。",
                        'admin_id' => $plan['owner_admin_id'],
                        'admin_name' => $plan['admin_name'],
                        'remind_time' => $plan['plan_date'],
                        'status' => self::STATUS_PENDING,
                        'create_time' => time(),
                    ]);

                    $reminders[] = [
                        'id' => $reminderId,
                        'plan_no' => $plan['plan_no'],
                        'contract_name' => $plan['contract_name'],
                        'customer_name' => $plan['customer_name'],
                        'admin_name' => $plan['admin_name'],
                        'plan_money' => $plan['plan_money'],
                        'plan_date' => $planDate,
                        'remind_days' => $remindDays,
                    ];
                }
            }
        }

        return $reminders;
    }

    /**
     * 检查并生成生日提醒
     * 查找联系人 birthday 在未来 N 天内且未提醒的记录
     * @param int $days 提前天数
     * @return array
     */
    public function checkBirthdayReminders($days = 1)
    {
        $reminders = [];
        $now = time();
        $targetDate = strtotime("+{$days} day");
        
        // 获取目标日期的月和日
        $targetMonth = date('n', $targetDate);
        $targetDay = date('j', $targetDate);

        // 查找需要提醒的联系人（根据生日的月和日匹配）
        $contacts = Db::name('crm_customer_contacts')
            ->alias('cc')
            ->join('crm_customer c', 'cc.customer_id = c.id', 'left')
            ->join('admin a', 'c.owner_admin_id = a.admin_id', 'left')
            ->where('cc.birthday', '>', 0)  // 确保生日已设置
            ->where(function ($query) use ($targetMonth, $targetDay) {
                // 使用FROM_UNIXTIME提取月日进行匹配
                $query->whereRaw("FROM_UNIXTIME(cc.birthday, '%c') = ?", [$targetMonth])
                      ->whereRaw("FROM_UNIXTIME(cc.birthday, '%e') = ?", [$targetDay]);
            })
            ->field('cc.id, cc.contact as contact_name, cc.birthday, cc.customer_id, 
                     c.name as customer_name, c.owner_admin_id, a.username as admin_name')
            ->select()
            ->toArray();

        foreach ($contacts as $contact) {
            // 检查是否已存在未读的提醒（当年已提醒过则跳过）
            $currentYear = date('Y');
            $yearStart = strtotime("{$currentYear}-01-01");
            $yearEnd = strtotime("{$currentYear}-12-31 23:59:59");
            
            $exists = Db::name('crm_reminder')
                ->where('type', self::TYPE_BIRTHDAY)
                ->where('related_id', $contact['id'])
                ->where('create_time', '>=', $yearStart)
                ->where('create_time', '<=', $yearEnd)
                ->where('status', 'in', [self::STATUS_PENDING, self::STATUS_READ])
                ->find();

            if (!$exists) {
                $birthdayDate = date('Y-m-d', $contact['birthday']);
                $age = date('Y') - date('Y', $contact['birthday']);
                $reminderId = Db::name('crm_reminder')->insertGetId([
                    'type' => self::TYPE_BIRTHDAY,
                    'related_id' => $contact['id'],
                    'related_type' => 'contact',
                    'title' => "生日提醒：{$contact['contact_name']}",
                    'content' => "客户 {$contact['customer_name']} 的联系人 {$contact['contact_name']} 即将过生日（{$birthdayDate}），今年 {$age} 岁，记得送上祝福哦！",
                    'admin_id' => $contact['owner_admin_id'],
                    'admin_name' => $contact['admin_name'],
                    'remind_time' => strtotime(date('Y-m-d')),  // 当天提醒
                    'status' => self::STATUS_PENDING,
                    'create_time' => time(),
                ]);

                $reminders[] = [
                    'id' => $reminderId,
                    'customer_name' => $contact['customer_name'],
                    'contact_name' => $contact['contact_name'],
                    'admin_name' => $contact['admin_name'],
                    'birthday' => $birthdayDate,
                    'age' => $age,
                ];
            }
        }

        return $reminders;
    }
}
