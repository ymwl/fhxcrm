<?php

namespace app\admin\model;

use app\common\model\TimeModel;

/**
 * 呼叫任务模型
 */
class CallTask extends TimeModel
{
    protected $name = 'addon_call_task';

    /**
     * 呼叫类型：外拨（电脑端下发任务或手机端手动拨出）
     */
    const CALL_TYPE_OUT = 1;

    /**
     * 呼叫类型：来电（接听/未接挂断）
     */
    const CALL_TYPE_IN = 2;

    /**
     * 任务状态：待执行
     */
    const STATUS_PENDING = 0;

    /**
     * 任务状态：已执行
     */
    const STATUS_DONE = 1;

    /**
     * 任务状态：已取消
     */
    const STATUS_CANCELED = 2;

    /**
     * 任务状态：失败
     */
    const STATUS_FAILED = 3;

    /**
     * 任务状态：超时
     */
    const STATUS_TIMEOUT = 4;

    /**
     * 通话结果：未知
     */
    const CALL_RESULT_UNKNOWN = 0;

    /**
     * 通话结果：已接通
     */
    const CALL_RESULT_CONNECTED = 1;

    /**
     * 通话结果：无人接听
     */
    const CALL_RESULT_NO_ANSWER = 2;

    /**
     * 通话结果：对方忙线
     */
    const CALL_RESULT_BUSY = 3;

    /**
     * 通话结果：被拒接
     */
    const CALL_RESULT_REJECTED = 4;

    /**
     * 通话结果：拨号失败/网络异常
     */
    const CALL_RESULT_NETWORK_ERROR = 5;

    /**
     * 呼叫类型映射（外拨/来电）
     */
    public static function getCallTypeMap(): array
    {
        return [
            self::CALL_TYPE_OUT => '外拨',
            self::CALL_TYPE_IN  => '来电',
        ];
    }

    /**
     * 通话结果映射（细分通话结局，供后台列表与统计展示）
     */
    public static function getCallResultMap(): array
    {
        return [
            self::CALL_RESULT_UNKNOWN       => '未知',
            self::CALL_RESULT_CONNECTED     => '已接通',
            self::CALL_RESULT_NO_ANSWER     => '无人接听',
            self::CALL_RESULT_BUSY          => '对方忙线',
            self::CALL_RESULT_REJECTED      => '被拒接',
            self::CALL_RESULT_NETWORK_ERROR => '网络异常/拨号失败',
        ];
    }

    /**
     * 任务状态映射
     */
    public static function getStatusMap(): array
    {
        return [
            self::STATUS_PENDING  => '待执行',
            self::STATUS_DONE     => '已执行',
            self::STATUS_CANCELED => '已取消',
            self::STATUS_FAILED   => '失败',
            self::STATUS_TIMEOUT  => '超时',
        ];
    }

    /**
     * 关联客户
     */
    public function customer()
    {
        return $this->hasOne(CrmCustomer::class, 'id', 'customer_id');
    }

    /**
     * 关联联系人
     */
    public function contacts()
    {
        return $this->hasOne(CrmCustomerContacts::class, 'id', 'contacts_id');
    }
}
