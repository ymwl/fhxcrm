<?php
/**
 * 客户相关静态方法
 * 
 */

namespace app\service;

use think\facade\Db;

class CrmClueService
{
    /**
     * 获取客户领取次数信息（静态方法）
     * @param array $system 系统配置
     * @param int $adminId 管理员ID
     * @return array
     */
    public static function getGrabCount($system, $adminId)
    {
        $cycleList = \app\admin\model\System::getCycleList();
        if (isset($system['clue_limit_counts']) && $system['clue_limit_counts'] > 0 && isset($cycleList[$system['clue_limit_condition']])) {
            if ($system['clue_limit_condition'] == 'day') {
                $starttime = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
            } elseif ($system['clue_limit_condition'] == 'week') {
                $starttime = mktime(0, 0, 0, (int)date('n'), date('d') - date('w') + 1, (int)date('Y'));
            } else {
                $starttime = mktime(0, 0, 0, (int)date('m'), 1, (int)date('Y'));
            }

            $crmGrabCount = Db::name('crm_clue_grab')->where("`createtime` >= {$starttime} and `admin_id`={$adminId}")->sum('nums');
            if ($crmGrabCount >= $system['clue_limit_counts']) {
                return ['code' => false, 'msg' => fy('The number of times customers can be claimed has been used up')];
            } else {
                $shy = $system['clue_limit_counts'] - $crmGrabCount;
                return ['code' => true, 'msg' => $cycleList[$system['clue_limit_condition']] . ' ' . fy('remaining number of times can be claimed') . '：' . $shy, 'count' => $shy];
            }
        } else {
            return ['code' => true, 'msg' => ''];
        }
    }
}
