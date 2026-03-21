<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmHangye extends TimeModel
{

    protected $name = "crm_hangye";

    protected $deleteTime = false;

    
    
    public function getStatusList()
    {
        return ['0'=>fy('disable'),'1'=>fy('enable')];
    }


}