<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmBusiness extends TimeModel
{


    protected $deleteTime = false;

    
    
    public function getIsEndList()
    {
        return ['0'=>fy("Under negotiation"),'1'=>fy("Dealed"),'2'=>fy("Failed"),'3'=>fy("Invalid"),];
    }

    public function ownerAdmin()
    {
        return $this->hasOne('app\admin\model\Admin', 'admin_id','owner_admin_id');
    }

    public function crmCustomer()
    {
        return $this->hasOne('app\admin\model\CrmCustomer', 'id','customer_id');
    }


}