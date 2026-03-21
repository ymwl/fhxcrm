<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class SystemConfigGroup extends TimeModel
{

    protected $name = "system_config_group";

    protected $deleteTime = false;

    public function getStatusList()
    {
        return ['0'=>'禁用','1'=>'启用',];
    }

    
    

}