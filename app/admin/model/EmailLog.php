<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class EmailLog extends TimeModel
{

    protected $name = "email_log";

    protected $deleteTime = false;

    public function getStatusList()
    {
        return [
            'success'=>'发送成功',
            'fail'=>'发送失败'
        ];
    }
    

}