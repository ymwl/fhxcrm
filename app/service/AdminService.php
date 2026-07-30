<?php
/**
 * 管理员相关静态方法
 * 
 */

namespace app\service;

use think\facade\Db;

class AdminService
{
    public static function getChildrenAdminName($admin,$withself = false){
//取出所有用户组
        $authGroup = \think\facade\Db::name('auth_group')->field('id,pid,title')->order('pid asc,id asc')->select()->toArray();
//        回去当前组的所有下级
        $childrenGroupIds = \fhx\Tree::getChildrenIds($authGroup, $admin['group_id'], false);

        $adminList=[];
        if($withself)$adminList[]=$admin['username'];
        if(!$childrenGroupIds) return $adminList;
        $adminInfos=\think\facade\Db::name('admin')->where('group_id','in',$childrenGroupIds)->column('username');
        return  array_merge($adminInfos,$adminList);
    }

    //    获取下级所有Id信息
    public static function getChildrenAdminIds($admin,$withself = false,$groupself = false){
//取出所有用户组
        $authGroup = \think\facade\Db::name('auth_group')->field('id,pid,title')->order('pid asc,id asc')->select()->toArray();
//        回去当前组的所有下级
        $childrenGroupIds = \fhx\Tree::getChildrenIds($authGroup, $admin['group_id'], $groupself);
        $adminList=[];
        if($withself)$adminList[]=$admin['admin_id'];
        if(!$childrenGroupIds) return $adminList;
        $adminInfos=\think\facade\Db::name('admin')->where('group_id','in',$childrenGroupIds)->column('admin_id');
        return  array_merge($adminInfos,$adminList);
    }
//    返回当前用户角色查看权限对应用户名
    public static function getViewAdminName($admin,$withself = false){
        $adminIds=self::getViewAdminIds($admin,$withself);
        if($adminIds!=='ALL' && !empty($adminIds)){

            $adminIds =\think\facade\Db::name('admin')->where('admin_id' , 'in', $adminIds)->column('username');
        }
        return $adminIds;
    }
    //    根据条件获取条件所有Id信息
    public static function getViewAdminIds($admin,$withself = false){
// (0:本人,1:下属, 2:本部门, 3:仅下属部门,4:本部门及下属部门,5:全部)
        //展示下属的和自己的
        $type = \think\facade\Db::name('auth_role')->where('id','=',$admin['role_id'])->cache('admin_auth_role_id'.$admin['role_id'])->value('type');
        if($type=='5'){
            return 'ALL';
        }
        $admin_ids = [];
        switch ($type) {
            case '0'://本人
                break;
            case 'sub_departments'://所有下级部门
                $admin_ids = self::getChildrenAdminIds($admin);
                break;
            case 'self_sub_departments'://本部门和所有下级部门
                $admin_ids = self::getChildrenAdminIds($admin,false,true);

                break;
            case '1'://下属
                $admin_ids = \think\facade\Db::name('admin')->where([
                    'parent_id' => $admin['admin_id'],
                ])->column('admin_id');
                break;
            case '2'://本部门
                $admin_ids = \think\facade\Db::name('admin')->where([
                    'group_id' => ['=', $admin['group_id']],
                    'admin_id' => ['<>', $admin['admin_id']]
                ])->column('admin_id');
                break;
            case '3'://仅下属部门
//                找到所有下属的部门
                $xiashu_group_ids = \think\facade\Db::name('admin')->where([
                    'parent_id' => $admin['admin_id'],
                ])->column('group_id');
//                找到所有下属部门的人
                if($xiashu_group_ids){
//                    找到所有下属部门的人
                    $admin_ids = \think\facade\Db::name('admin')->where([
                        ['group_id' ,'in', $xiashu_group_ids],
                        ['admin_id' ,'<>', $admin['admin_id']]
                    ])->column('admin_id');
                }
                break;
            case '4'://本部门及下属部门
                //                找到所有下属的部门包括本部门
                $xiashu_or_group_ids = \think\facade\Db::name('admin')->where(
                    'parent_id' ,'=',$admin['admin_id']
                )->whereOr('group_id','=',$admin['group_id'])->column('group_id');
//                找到所有下属部门的人
                if($xiashu_or_group_ids){
//                    找到所有下属部门的人
                    $admin_ids = \think\facade\Db::name('admin')->where([['group_id' ,'in', $xiashu_or_group_ids], ['admin_id','<>',$admin['admin_id']]])->column('admin_id');
                }
                break;
            case '5'://全部
                return 'ALL';
                break;

        }
        if($withself){
            $admin_ids=array_merge($admin_ids,[$admin['admin_id']]);
        }
        return $admin_ids;
    }
}
