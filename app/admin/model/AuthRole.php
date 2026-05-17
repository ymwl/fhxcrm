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
    public static function type2level($type){
       $type_list= ['0'=>0,'1'=>10,'2'=>20,'3'=>30,'4'=>40,'sub_departments'=>50,'self_sub_departments'=>60,'5'=>70];
       return isset($type_list[$type])?$type_list[$type]:0;

    }
    // 通过修改器，在设置type时自动同步level
    public function setTypeAttr($value, $data)
    {
        $this->set('level', self::type2level($value));
        return $value;
    }


    public function getStatusList()
    {
        return ['0'=>'禁用','1'=>'启用'];
    }

    public function getChildrenGroupIds($admin,$withself = false){

        $authGroup = \think\facade\Db::name('auth_group')->field('id,pid,title')->order('pid asc,id asc')->select()->toArray();
        $tree=new \fast\Tree();
        $tree->init($authGroup);
        $childrenGroupIds=$tree->getChildrenIds($admin['group_id'],$withself);
        return  $childrenGroupIds;
    }


}