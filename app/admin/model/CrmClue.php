<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmClue extends TimeModel
{

    protected $name = "crm_clue";

    protected $deleteTime = false;

    /**
     * 线索状态列表
     * @return string[]
     */
    public function getStatusList()
    {
        return ['0' => '待跟进', '1' => '跟进中', '2' => '已转化', '3' => '无效'];
    }
}
