<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class Emailtpl extends TimeModel
{

    protected $name = "emailtpl";

    protected $deleteTime = false;

    
    
    public function getStatusList()
    {
        return ['0'=>'禁用','1'=>'启用',];
    }

    public function getTypeList()
    {
        return ['customer'=>'客户','customer_contacts'=>'客户联系人','client_order'=>'订单','contract'=>'合同',];
    }


}