<?php
//技术支持微信：zrwx978
namespace app\index\controller;

use app\BaseController;
use app\service\CheckStatusConst;
use think\facade\Db;

class Cron extends BaseController
{
    /**
     * 计划任务入口 - 提醒检查 + 合同到期处理
     * 
     * 访问方式：
     * 1. 宝塔计划任务 → 访问URL → http://域名/index.php/cron/index?token=your_secure_token_here_2026
     * 2. 在线cron服务 → https://cron-job.org 等
     * 
     * 安全机制：需要 token 验证
     */
    public function index()
    {
        // ========== 安全配置 上线后建议修改==========
        $cronToken = config('app.cron_token', 'your_secure_token_here_2026');
        
        // Token验证
        $inputToken = $this->request->param('token', '');
        if (empty($inputToken) || $inputToken !== $cronToken) {
            return json(['code' => 1, 'msg' => 'Forbidden: Invalid token', 'time' => date('Y-m-d H:i:s')]);
        }

        ignore_user_abort(true);
        ini_set("memory_limit", '-1');
        ini_set('max_execution_time', '0');

        // 实例化提醒服务
        $service = new \app\admin\service\ReminderService();

        $now = date('Y-m-d H:i:s');
        $log = [];
        $log[] = "[{$now}] 开始执行提醒检查...";

        // 1. 检查跟进提醒
        $followUpReminders = $service->checkFollowUpReminders(1);
        $log[] = "跟进提醒：生成 " . count($followUpReminders) . " 条";

        // 2. 检查合同到期提醒（7天内到期的合同，动态更新剩余天数）
        $contractReminders = $service->checkContractExpireReminders();
        $newCount = count(array_filter($contractReminders, function($r) { return empty($r['updated']); }));
        $updatedCount = count(array_filter($contractReminders, function($r) { return !empty($r['updated']); }));
        $log[] = "合同到期提醒：新增 {$newCount} 条，更新 {$updatedCount} 条";

        // 3. 检查回款提醒
        $receivablesReminders = $service->checkReceivablesReminders();
        $log[] = "回款提醒：生成 " . count($receivablesReminders) . " 条";

        // 4. 检查生日提醒
        $birthdayReminders = $service->checkBirthdayReminders(1);
        $log[] = "生日提醒：生成 " . count($birthdayReminders) . " 条";

        // 5. 清理过期提醒
        $cleaned = $service->cleanExpiredReminders(180);
        $log[] = "清理过期提醒：删除 {$cleaned} 条";

        $log[] = "[{$now}] 提醒检查完成。";

        // 6. 合同到期自动处理
        // contract_status: 0进行中 1已完成 -1已作废
        // check_status: -1审核未通过 0待审核 1草稿 2审核中 3审核通过
        $today = strtotime(date('Y-m-d'));

        // 6.1 审核通过的合同 → 设置为已完成
        $completedCount = Db::name('crm_contract')
            ->where('end_time', '>', 0)
            ->where('end_time', '<', $today)
            ->where('contract_status', 0)
            ->where('check_status', CheckStatusConst::APPROVED)
            ->update(['contract_status' => 1, 'update_time' => time()]);
        $log[] = "合同到期-审核通过→已完成：更新 {$completedCount} 条";

        // 6.2 非审核通过的合同（到期后）→ 设置为已作废
        $voidedCount = Db::name('crm_contract')
            ->where('end_time', '>', 0)
            ->where('end_time', '<', $today)
            ->where('contract_status', 0)
            ->where('check_status', '<>', CheckStatusConst::APPROVED)
            ->update(['contract_status' => -1, 'update_time' => time()]);
        $log[] = "合同到期-非审核通过→已作废：更新 {$voidedCount} 条";

        return json([
            'code' => 0,
            'msg' => 'success',
            'time' => $now,
            'data' => [
                'follow_up' => count($followUpReminders),
                'contract_new' => $newCount,
                'contract_updated' => $updatedCount,
                'receivables' => count($receivablesReminders),
                'birthday' => count($birthdayReminders),
                'cleaned' => $cleaned,
                'contract_completed' => $completedCount,
                'contract_voided' => $voidedCount,
            ],
            'log' => $log
        ]);
    }


}
