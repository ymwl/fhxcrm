<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmReminder extends TimeModel
{

    protected $name = "crm_reminder";

    protected $deleteTime = false;

    /**
     * 获取提醒状态列表
     * 0=待处理 1=已发送 2=已读
     */
    public function getStatusList()
    {
        return ['0' => '待处理', '1' => '已发送', '2' => '已读'];
    }

    /**
     * 获取提醒类型列表
     * 1=跟进提醒 2=合同到期 3=回款提醒 4=生日提醒
     */
    public function getTypeList()
    {
        return ['1' => '跟进提醒', '2' => '合同到期', '3' => '回款提醒', '4' => '生日提醒'];
    }
}