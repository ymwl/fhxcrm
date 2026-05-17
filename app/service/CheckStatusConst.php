<?php
/**
 * 审核状态常量定义
 * 
 * 使用示例：
 *   CheckStatusConst::APPROVED     // 审核通过 (3)
 *   CheckStatusConst::getText(-1)  // "审核未通过"
 *   CheckStatusConst::getList()    // 完整状态列表
 */

namespace app\service;

class CheckStatusConst
{
    // ========== 审核状态 ==========
    const REJECTED  = -1;      // 审核未通过
    const PENDING   = 0;       // 待审核
    const DRAFT     = 1;       // 草稿
    const REVIEWING = 2;       // 审核中
    const APPROVED  = 3;       // 审核通过

    /**
     * 获取审核状态映射表
     * @return array
     */
    public static function getList()
    {
        return [
            self::REJECTED  => '审核未通过',
            self::PENDING   => '待审核',
            self::DRAFT     => '草稿',
            self::REVIEWING => '审核中',
            self::APPROVED  => '审核通过',
        ];
    }

    /**
     * 根据状态值获取状态文本
     * @param int $status 状态值
     * @return string 状态文本
     */
    public static function getText($status)
    {
        $list = self::getList();
        return $list[$status] ?? '未知状态';
    }
}
