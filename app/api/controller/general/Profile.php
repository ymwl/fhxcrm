<?php

namespace app\api\controller\general;

use think\facade\View;
use think\facade\Db;
use app\api\controller\Authority;


class Profile extends Authority
{
    //个人信息获取
    public function getInfo()
    {

        $user = Db::name('admin')->field('`admin_id`,`username`,`realname`,`group_id`,`avatar`,`isphone`,`is_open`,`email`,`tel`')->where('admin_id', $this->admin['admin_id'])->find();
        $user['avatar'] = real_resourse($this->system['domain'], $user['avatar']);
        $this->success('个人信息获取成功!','', $user);

    }
    //个人信息修改
    public function update()
    {
        if ($this->request->isPost()) {
            $data['avatar'] = $this->request->post('avatar', '/static/admin/images/0.jpg', 'trim');
            $data['avatar']=str_replace(trim($this->system['domain'],'/'),'',$data['avatar']);
            $data['email'] = $this->request->post('email', '', 'trim');
            $data['realname'] = $this->request->post('realname', '', 'trim');
            $data['tel'] = $this->request->post('tel', '', 'trim');
            $pwd = $this->request->post('pwd', '', 'trim');
            if ($pwd) {
                $data['salt'] = rand_string(12);
                $data['pwd'] = md5($pwd . $data['salt']);
            }
            Db::name('admin')->where('admin_id', '=', $this->admin['admin_id'])->update($data);
            $this->success(fy("Modification succeeded"));
        }
    }

}
