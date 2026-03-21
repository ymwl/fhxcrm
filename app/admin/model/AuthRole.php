<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class AuthRole extends TimeModel
{

    protected $name = "auth_role";

    protected $deleteTime = false;

    
    
    public function getTypeList()
    {
        return ['0'=>'本人','1'=>'本人及下属','2'=>'本人及本部门','3'=>'本人及下属部门','4'=>'本人及本部门和下属部门','sub_departments'=>'本人及所有下级部门','self_sub_departments'=>'本人及本部门和所有下级部门','5'=>'全部'];
    }

    public function getStatusList()
    {
        return ['0'=>'禁用','1'=>'启用'];
    }


}