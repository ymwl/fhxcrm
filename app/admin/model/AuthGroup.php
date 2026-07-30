<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class AuthGroup extends TimeModel
{

    protected $name = "auth_group";

    protected $deleteTime = false;
    protected $key='id';

    

    public function getChildrenGroupIds($admin,$withself = false){

        $authGroup = \think\facade\Db::name('auth_group')->field('id,pid,title')->order('pid asc,id asc')->select()->toArray();
        $childrenGroupIds = \fhx\Tree::getChildrenIds($authGroup, $admin['group_id'], $withself);
        return  $childrenGroupIds;
    }

}