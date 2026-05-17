<?php
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\admin\service\ReminderService;

/**
 * 提醒检查定时任务
 * 建议配置到系统 crontab，每小时执行一次：
 * 0 * * * * cd /项目目录 && php think reminder >> runtime/log/reminder.log 2>&1
 */
class Reminder extends Command
{
    protected function configure()
    {
        $this->setName('reminder')
            ->setDescription('Check and generate CRM reminders');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new ReminderService();
        $now = date('Y-m-d H:i:s');

        $output->writeln("[{$now}] 开始执行提醒检查...");

        // 1. 检查跟进提醒（提前1天）
        $followUpReminders = $service->checkFollowUpReminders(1);
        $output->writeln("跟进提醒：生成 " . count($followUpReminders) . " 条");
        foreach ($followUpReminders as $item) {
            $output->writeln("  - {$item['customer_name']} ({$item['admin_name']})");
        }

        // 2. 检查合同到期提醒（提前7天、3天、1天）
        foreach ([7, 3, 1] as $days) {
            $contractReminders = $service->checkContractExpireReminders($days);
            $output->writeln("合同到期提醒({$days}天)：生成 " . count($contractReminders) . " 条");
            foreach ($contractReminders as $item) {
                $output->writeln("  - {$item['contract_name']} ({$item['customer_name']})");
            }
        }
        // 3. 检查回款提醒（根据每个计划的 remind_days 设置）
        $receivablesReminders = $service->checkReceivablesReminders();


        $output->writeln("回款提醒：生成 " . count($receivablesReminders) . " 条");


        foreach ($receivablesReminders as $item) {


            $output->writeln("  - {$item['contract_name']} ({$item['customer_name']})");


        }
        // 4. 检查生日提醒（提前1天）

        $birthdayReminders = $service->checkBirthdayReminders(1);

        $output->writeln("生日提醒：生成 " . count($birthdayReminders) . " 条");

        foreach ($birthdayReminders as $item) {

            $output->writeln("  - {$item['customer_name']} ({$item['admin_name']})");

        }

        // 5. 清理过期提醒（保留30天）
        $cleaned = $service->cleanExpiredReminders(30);
        $output->writeln("清理过期提醒：删除 {$cleaned} 条");

        $output->writeln("[{$now}] 提醒检查完成。");
        return 0;
    }
}
