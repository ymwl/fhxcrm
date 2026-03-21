<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmSource extends TimeModel
{

    protected $name = "crm_source";

    protected $deleteTime = false;

    
    
    public function getStatusList()
    {
        return ['0'=>fy('disable'),'1'=>fy('enable')];
    }


}