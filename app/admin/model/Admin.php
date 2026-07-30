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



    public function authGroup()
    {
        return $this->belongsTo('app\admin\model\AuthGroup', 'group_id', 'id');
    }
    public function authRole()
    {
        return $this->belongsTo('app\admin\model\AuthRole', 'role_id', 'id');
    }



}
