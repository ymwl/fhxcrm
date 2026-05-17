<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmReminder extends TimeModel
{

    protected $name = "crm_reminder";

    protected $deleteTime = false;

    
    
    public function getStatusList()
    {
        return ['0'=>fy('disable'),'1'=>fy('enable')];
    }


}