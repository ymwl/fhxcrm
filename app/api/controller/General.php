<?php
namespace app\api\controller;
use think\facade\Db;


class General extends Authority
{
    //个人信息修改
    public function profile(){
        if($this->request->isPost()){
            $data['avatar']=$this->request->post('avatar','/static/admin/images/0.jpg','trim');
            $data['email']=$this->request->post('email','','trim');
            $data['phone']=$this->request->post('phone','','trim');
            $data['wechat']=$this->request->post('wechat','','trim');
            $newpwd=$this->request->post('newpwd','','trim');
            if ($newpwd){
                $newpwd2=$this->request->post('newpwd2','','trim');
                if ($newpwd!=$newpwd2){
                    $this->error('两次密码输入不一致');
                }
//                密码验证是否正确
                $oldpwd=$this->request->post('oldpwd','','trim');

                if (empty($oldpwd)){
                    $this->error('修改密码时当前密码必须填写');
                }
                $user=Db::name('admin')->field('`salt`,`pwd`')->where('username',$this->admin['username'])->find();
                if ($user['pwd']!=md5($oldpwd.$user['salt'])){
                    $this->error('当前密码错误');
                }


                $data['salt']=rand_string(12);
                $data['pwd']=md5($newpwd.$data['salt']);
            }
            Db::name('admin')->where('admin_id', '=',$this->admin['admin_id'])->update($data);
            $admin=Db::name('admin')->withoutField('pwd,salt,ip')->where('admin_id', '=',$this->admin['admin_id'])->find();
            if ($newpwd){
//                修改密码则退出登录
                session('admin',null);
            }else{
                session('admin',$admin);
            }

            $this->success(fy("Modification succeeded"));

        }else{

            $admin=Db::name('admin')->withoutField('pwd,salt,ip')->where('admin_id', '=',$this->admin['admin_id'])->find();
            $this->success('个人信息获取成功', '', $admin);
        }
    }

}