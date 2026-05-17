<?php
namespace app\admin\model;
use think\Model;

use think\facade\Db;
class Admin extends Model
{
    protected $pk = 'admin_id';
    public function login($data){

        $user=Db::name('admin')->field('`admin_id`,`username`,`salt`,`realname`,`pwd`,`group_id`,`avatar`,`isphone`,`is_open`,`role_id`,phone,email,wechat')->where('username',$data['username'])->find();
        if($user) {
            if($user['is_open']<1){
                return ['code' => 0, 'msg' => 'The currently logged-on user is disabled and logon is prohibited'];
            }
            if ($user['is_open']==1 && $user['pwd'] == md5($data['password'].$user['salt'])){
                unset($user['pwd']);
                unset($user['salt']);
                unset($user['is_open']);
//                登录成功后记录最后一次登录时间
                Db::name('admin')->where('admin_id',$user['admin_id'])->update(['logintime'=>time()]);

                return ['code' => 1, 'msg' => 'Login succeeded', 'admin' => $user]; //信息正确
            }else{
                return ['code' => 0, 'msg' => 'Login user name or password error, login failed']; //密码错误
            }
        }else{
            return ['code' => 0, 'msg' => 'Login user name or password error, login failed']; //用户不存在
        }
    }
    public function getInfo($admin_id){
        $info = Db::name('admin')->withoutField('pwd,salt')->find($admin_id);
        return $info;
    }
    public function check($code){
        return captcha_check($code);
    }
    //    获取下级所有用户信息
    public function getChildrenAdminName($admin,$withself = false){
//取出所有用户组
        $authGroup = \think\facade\Db::name('auth_group')->field('id,pid,title')->order('pid asc,id asc')->select()->toArray();
//        回去当前组的所有下级
       $tree=new \fast\Tree();
       $tree->init($authGroup);
        $childrenGroupIds=$tree->getChildrenIds($admin['group_id'],false);

        $adminList=[];
        if($withself)$adminList[]=$admin['username'];
        if(!$childrenGroupIds) return $adminList;
        $adminInfos=\think\facade\Db::name('admin')->where('group_id','in',$childrenGroupIds)->column('username');
        return  array_merge($adminInfos,$adminList);
    }

    //    获取下级所有Id信息
    public function getChildrenAdminIds($admin,$withself = false,$groupself = false){
//取出所有用户组
        $authGroup = \think\facade\Db::name('auth_group')->field('id,pid,title')->order('pid asc,id asc')->select()->toArray();
//        回去当前组的所有下级
        $tree=new \fast\Tree();
        $tree->init($authGroup);
        $childrenGroupIds=$tree->getChildrenIds($admin['group_id'],$groupself);
        $adminList=[];
        if($withself)$adminList[]=$admin['admin_id'];
        if(!$childrenGroupIds) return $adminList;
        $adminInfos=\think\facade\Db::name('admin')->where('group_id','in',$childrenGroupIds)->column('admin_id');
        return  array_merge($adminInfos,$adminList);
    }
//    返回当前用户角色查看权限对应用户名
    public function getViewAdminName($admin,$withself = false){
        $adminIds=$this->getViewAdminIds($admin,$withself);
        if($adminIds!=='ALL' && !empty($adminIds)){
            $adminIds =self::where('admin_id' , 'in', $adminIds)->column('username');
        }
        return $adminIds;
    }
    //    根据条件获取条件所有Id信息
    public function getViewAdminIds($admin,$withself = false){
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
                $admin_ids = $this->getChildrenAdminIds($admin);
                break;
            case 'self_sub_departments'://本部门和所有下级部门
                $admin_ids = $this->getChildrenAdminIds($admin,false,true);

                break;
            case '1'://下属
                $admin_ids = self::where([
                    'parent_id' => $admin['admin_id'],
                ])->column('admin_id');
                break;
            case '2'://本部门
                $admin_ids = self::where([
                    'group_id' => ['=', $admin['group_id']],
                    'admin_id' => ['<>', $admin['admin_id']]
                ])->column('admin_id');
                break;
            case '3'://仅下属部门
//                找到所有下属的部门
                $xiashu_group_ids = self::where([
                    'parent_id' => $admin['admin_id'],
                ])->column('group_id');
//                找到所有下属部门的人
                if($xiashu_group_ids){
//                    找到所有下属部门的人
                    $admin_ids = self::where([
                        ['group_id' ,'in', $xiashu_group_ids],
                        ['admin_id' ,'<>', $admin['admin_id']]
                    ])->column('admin_id');
                }
                break;
            case '4'://本部门及下属部门
                //                找到所有下属的部门包括本部门
                $xiashu_or_group_ids = self::where(
                    'parent_id' ,'=',$admin['admin_id']
                )->whereOr('group_id','=',$admin['group_id'])->column('group_id');
//                找到所有下属部门的人
                if($xiashu_or_group_ids){
//                    找到所有下属部门的人
                    $admin_ids = self::where([['group_id' ,'in', $xiashu_or_group_ids], ['admin_id','<>',$admin['admin_id']]])->column('admin_id');
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


    public function authGroup()
    {
        return $this->belongsTo('app\admin\model\AuthGroup', 'group_id', 'id');
    }
    public function authRole()
    {
        return $this->belongsTo('app\admin\model\AuthRole', 'role_id', 'id');
    }



}

