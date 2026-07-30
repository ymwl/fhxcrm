<?php

namespace app\admin\model;

use app\common\model\TimeModel;

/**
 * 员工业绩目标模型
 * 每个员工每年每种业绩方式各一条记录
 */
class CrmAchievement extends TimeModel
{
    protected $name = 'crm_achievement';

    /**
     * 业绩方式映射
     */
    public static $configLabels = [
        1 => '合同金额',
        2 => '回款金额',
        3 => '订单金额',
    ];

    /**
     * 关联员工（admin表）
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id')
            ->field('admin_id,username,realname');
    }

    /**
     * 获取员工业绩目标（按年月和业绩方式）
     * @param int $adminId 员工ID
     * @param string $year 年份
     * @param int $config 业绩方式 1合同 2回款 3订单，0表示所有方式求和
     * @param int $month 月份(1-12)，0表示全年目标
     * @return float
     */
    public static function getMonthTarget($adminId, $year, $config = 0, $month = 0)
    {
        $monthFields = [
            0 => 'yeartarget',
            1 => 'january', 2 => 'february', 3 => 'march',
            4 => 'april', 5 => 'may', 6 => 'june',
            7 => 'july', 8 => 'august', 9 => 'september',
            10 => 'october', 11 => 'november', 12 => 'december',
        ];
        $field = $monthFields[$month] ?? 'yeartarget';

        $query = self::where('admin_id', $adminId)->where('year', $year);

        if ($config > 0) {
            $query->where('config', $config);
            $val = $query->value($field);
            return $val ? (float)$val : 0;
        }

        // config=0 时求和所有业绩方式
        $val = $query->sum($field);
        return $val ? (float)$val : 0;
    }
}
