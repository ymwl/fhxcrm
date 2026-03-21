<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmCustomerContacts extends TimeModel
{

    protected $name = "crm_customer_contacts";

    protected $deleteTime = false;

    public function crmCustomer()
    {
        return $this->hasOne('app\admin\model\CrmCustomer', 'id','customer_id');
    }
    

}