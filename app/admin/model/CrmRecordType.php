<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmRecordType extends TimeModel
{

    protected $name = "crm_record_type";

    protected $deleteTime = false;

    
    
    public function getStatusList()
    {
        return ['0'=>fy('disable'),'1'=>fy('enable'),];
    }


}