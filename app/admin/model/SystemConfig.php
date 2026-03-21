<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class SystemConfig extends TimeModel
{

    protected $name = "system_config";

    protected $deleteTime = false;

    public function group()
    {
        return $this->belongsTo('app\admin\model\SystemConfigGroup', 'identification', 'identification');
    }

    
    

}