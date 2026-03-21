<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class OpenApps extends TimeModel
{

    protected $name = "open_apps";

    protected $deleteTime = false;

    
    
    public function getStatusList()
    {
        return ['0'=>'禁用','1'=>'启用',];
    }


}