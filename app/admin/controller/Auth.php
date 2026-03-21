<?php
namespace app\admin\controller;
use app\common\controller\AdminController;
use fast\Tree;
use think\facade\Cache;
use think\facade\View;

use think\facade\Db;
use clt\Leftnav;
use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\authRule;
class Auth extends AdminController
{
    //管理员列表
    public function adminList(){
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            $this->model=new \app\admin\model\Admin();
            list($page, $limit, $where) = $this->buildTableParames();



            $list=[];

            //用户组1属于超级用户组不需要加范围
            if($this->admin['group_id']>1){
                $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin,true);
                $where[] = ['admin_id', 'in', $adminIds];
            }

            $count = $this->model
                ->withJoin(['authGroup' => ['title'],'authRole' => ['name']],'LEFT')
                ->where($where)
                ->count();

            if($count){
                $list = $this->model->withoutField('pwd,salt,ip')
                    ->withJoin(['authGroup' => ['title'],'authRole' => ['name']],'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order('admin_id DESC')
                    ->select()->toArray();
            }


            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }

        return $this->fetch();
    }


    public function adminAdd(){
        if($this->request->isAjax()){
            $data = $this->request->post();
            $max_admin_num=config('app.max_admin_num');
            if($max_admin_num && $max_admin_num>0){
                $adminCount=Admin::where('group_id','>',0)->count();
                if($adminCount>=$max_admin_num){
                    $this->error('管理员人数超过限制，请联系管理员提高限制');
                }
            }
            $admin_id = Admin::where(['username'=>$data['username']])->value('admin_id');
            if ($admin_id) {
                $this->error(fy("The user name already exists, please re-enter the user name"));
            }

            $data['pwd'] = input('post.pwd', '','trim');
            $data['add_time'] = time();
            $data['ip'] = getRealIp();

            if($this->admin['group_id']>1){
                $groupIds=(new \app\admin\model\AuthGroup())->getChildrenGroupIds($this->admin,true);
                if(!in_array($data['group_id'],$groupIds)){
                    return json(['code'=>0,'msg'=>fy("Illegally unauthorized selection of user groups")]);
                }

            }
            //验证
    try {
                validate('app\admin\validate\Admin')->check($data);
            } catch (\think\exception\ValidateException $e) {
                // 验证失败 输出错误信息
                $this->error($e->getError());
            }

            if (empty($data['pwd'])) {
                $this->error(fy("Password cannot be empty"));
            }
            $data['salt']=rand_string(12);
            $data['pwd']=md5($data['pwd'].$data['salt']);
            //添加
            if (Admin::create($data)) {
                $this->success(fy("Successfully added"),myurl('adminList'));
            } else {
                $this->error(fy("Add failed"));

            }
        }else{
            return view();
        }
    }
    //删除管理员
    public function adminDel(){
        $admin_id=input('get.admin_id');
        if($admin_id==1){
            return json(['code'=>0,'msg'=>fy("Super administrator accounts are prohibited from being deleted")]);
        }
        if($admin_id==$this->admin['admin_id']){
            return json(['code'=>0,'msg'=>fy("Forbid deleting itself")]);
        }
        $this->modifyPermissions($admin_id);
        Admin::where('admin_id','=',$admin_id)->delete();
        return json(['code'=>1,'msg'=>fy("Delete succeeded").'!']);
    }
    //修改管理员状态
    public function adminState(){
        $id=input('post.id');
        $is_open=input('post.is_open');
        if (empty($id)){
            $result['status'] = 0;
            $result['info'] = fy('Wrong request parameters');
            $result['url'] = myurl('adminList');
            return json($result);
        }
        $this->modifyPermissions($id);
        \think\facade\Db::name('admin')->where('admin_id','=',$id)->update(['is_open'=>$is_open]);
        $result['status'] = 1;
        $result['info'] = fy("Modification succeeded");
        $result['url'] = myurl('adminList');
        return json($result);
    }
    //设置是否查看手机号
    public function adminPhone(){

        $id=input('post.id');
        $isphone=input('post.isphone');
        if (empty($id)){
            $result['status'] = 0;
            $result['info'] = fy('Wrong request parameters');
            $result['url'] = myurl('adminList');
            return json($result);
        }
        \think\facade\Db::name('admin')->where('admin_id='.$id)->update(['isphone'=>$isphone]);
        $result['status'] = 1;
        $result['info'] = fy("Modification succeeded");
        $result['url'] = myurl('adminList');
        return json($result);
    }
    //更新管理员信息
    public function adminEdit(){
        $admin_id=$this->request->param('admin_id',0,'int');
        $this->modifyPermissions($admin_id);
        if($this->request->isPost()){
            $data = $this->request->post();
            $pwd=input('post.pwd');
            $map[] = ['admin_id','<>',$admin_id];
            $where['admin_id'] = $admin_id;

            if($this->admin['group_id']>1){
                $adminIds=(new \app\admin\model\Admin())->getChildrenAdminIds($this->admin,true);

                if(!in_array($admin_id,$adminIds)){
                    return json(['code'=>0,'msg'=>fy("Illegal ultra vires operation")]);
                }
                $groupIds=(new \app\admin\model\AuthGroup())->getChildrenGroupIds($this->admin,true);

                if(!in_array($data['group_id'],$groupIds)){
                    return json(['code'=>0,'msg'=>fy("Illegally unauthorized selection of user groups")]);
                }

            }

            if($data['username']){
                $map[] = ['username','=',$data['username']];
                $check_user = Admin::where($map)->find();
                if ($check_user) {
                    return json(['code'=>0,'msg'=>fy("The user name already exists, please re-enter the user name")]);
                }
            }
            if ($pwd){
                $data['salt']=rand_string(12);
                $data['pwd']=input('post.pwd','');
                $data['pwd']=md5($data['pwd'].$data['salt']);
            }else{
                unset($data['pwd']);
            }
            try {
                if($admin_id==1){
                    unset($data['group_id']);
                }
                validate('app\admin\validate\Admin')->check($data);
            } catch (\think\exception\ValidateException $e) {
                // 验证失败 输出错误信息
                $this->error($e->getError());
            }
            $model=Admin::update($data,$where);

            if( $admin_id == $this->admin['admin_id']){
                $admin=Db::name('admin')->withoutField('pwd,salt,ip')->where('admin_id', '=',$this->admin['admin_id'])->find();
                session('admin',$admin);
            }
            return json(['code'=>1,'msg'=>fy("Modification succeeded"),'url'=>myurl('adminList')]);
        }else{

            $admin = new Admin();
            $row = $admin->getInfo($admin_id);
            View::assign('row', $row);
            return view();
        }
    }
    /*-----------------------用户组管理----------------------*/
    //用户组管理
    public function adminGroup(){
        if($this->request->isPost()){
            $list = AuthGroup::select()->toArray();
            return json(['code'=>0,'msg'=>fy('Get successful').'!','data'=>$list,'rel'=>1]);
        }
        return view();
    }
    //删除管理员分组
    public function groupDel(){
        $id=input('id',0,'intval');
        if($id==1){
            return json(['code'=>0,'msg'=>fy("The system's own user groups cannot be deleted")]);
        }
        AuthGroup::where('id','=',$id)->delete();
        return json(['code'=>1,'msg'=>fy("Delete succeeded").'!']);
    }
    //添加分组
    public function groupAdd(){
        if($this->request->isPost()){
            $data=$this->request->post();
            if(empty($data['title'])){
                $this->error(fy("User group name submissions cannot be empty"));
            }
            $group_id=Db::name('auth_group')->where('title','=',$data['title'])->value('id');
            if($group_id){
                $this->error(fy("The user group name already exists and the save failed"));
            }
            $data['addtime']=time();
            Db::name('auth_group')->strict(false)->insert($data);
            $this->success(fy("Successfully added"),myurl('adminGroup'));
        }else{
            $authGroup = Db::name('auth_group')->field('id AS id,pid,title')->order('pid asc,id asc')->select();
            $nav = new Leftnav();
            $authGroupTree = $nav->menu($authGroup);
            View::assign('authGroupTree',$authGroupTree);

            View::assign('title',fy("Add").' '.fy("Group"));
            View::assign('info','null');
            View::assign('info_raw',[]);
            return \think\facade\View::fetch('groupForm');
        }
    }
    //修改分组
    public function groupEdit(){
        if($this->request->isPost()) {
            $data=$this->request->post();
            if(empty($data['title'])){
                $this->error(fy("User group name submissions cannot be empty"));
            }
            $group_id=Db::name('auth_group')->where('title','=',$data['title'])->where('id','<>',$data['id'])->value('id');
            if($group_id){
                $this->error(fy("The user group name already exists and the save failed"));
            }
            Db::name('auth_group')
                ->where('group_id','=',$data['group_id'])
                ->update($data);
            $this->success(fy("Modification succeeded"),myurl('adminGroup'));
        }else{
            $authGroup = Db::name('auth_group')->field('group_id AS id,pid,title')->order('pid asc,group_id asc')->select();
            $nav = new Leftnav();
            $authGroupTree = $nav->menu($authGroup);
            View::assign('authGroupTree',$authGroupTree);
            $id = input('id');
            $info=Db::name('auth_group')->where('group_id','=',$id)->find();
            View::assign('info', json_encode($info,true));
            View::assign('info_raw', $info);
            View::assign('title',fy("Edit").fy("Group"));
            return View::fetch('groupForm');
        }
    }
    //分组配置规则
    public function groupAccess(){
        $nav = new Leftnav();
        $admin_rule=\think\facade\Db::name('auth_rule')->field('id,pid,title')->order('sort asc')->select();
        $rules = \think\facade\Db::name('auth_group')->where('id',input('id'))->value('rules');
        $arr = $nav->auth($admin_rule,$pid=0,$rules);
        $arr[] = [ "id"=>0,
            "pid"=>0,
            "title"=>fy("All"),
            "open"=>true];
        View::assign('data',json_encode($arr,true));
        $this->app->view->engine()->layout(false);
        return View::fetch();
    }
    public function groupSetaccess(){
        $rules = input('post.rules');
        if(empty($rules)){
            return json(['msg'=>fy("Please select permissions"),'code'=>0]);
        }
        $data = $this->request->post();
        $where['id'] = $data['id'];
        unset($data['id']);
        if(AuthGroup::update($data,$where)){
            Cache::clear();
            return json(['msg'=>fy("Save successfully"),'url'=>myurl('auth.group/index'),'code'=>1]);
        }else{
            return json(['msg'=>fy("Save failed"),'code'=>0]);
        }
    }

    /********************************权限管理*******************************/
    public function adminRule(){
        if($this->request->isPost()){
            $arr = cache('authRuleList');
            if(!$arr){
				$arr = Db::name('authRule')->where('status','=',1)->order('pid asc,sort asc')->select()->toArray();
				foreach($arr as $k=>$v){
                    $arr[$k]['lay_is_open']=false;
                }
                cache('authRuleList', $arr, 3600);
            }
            return json(['code'=>0,'msg'=>fy('Get successful').'!','data'=>$arr,'is'=>true]);
        }
//        $this->app->view->engine()->layout(false);
        return View::fetch();
    }
    public function clear(){
//        清除不存在父级的节点
        // 查询所有父级不存在的子节点 ID
        $ids = Db::name('auth_rule')
            ->alias('a')
            ->leftJoin('auth_rule b', 'a.pid = b.id')
            ->where('a.pid', '>', 0)
            ->whereNull('b.id')
            ->column('a.id');

        if (!empty($ids)) {
            Db::name('authRule')->delete($ids);
        }

        Cache::clear();
        $this->success(fy("Clearing succeeded"));
    }
    public function ruleAdd(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $data['addtime'] = time();
            authRule::create($data);
            Cache::clear();
            return json(['code'=>1,'msg'=>fy("Successfully added"),'url'=>myurl('adminRule')]);
        }else{

            $arr = cache('addAuthRuleList');

            if(!$arr){
                $authRule = Db::name('authRule')->order('pid asc,sort asc')->select();
                $nav = new Leftnav();
                $arr = $nav->menu($authRule);
                cache('addAuthRuleList', $arr, 3600);
            }
            $this->app->view->engine()->layout(false);
            View::assign('admin_rule',$arr);//权限列表
            return View::fetch();
        }
    }
    public function ruleOrder(){
        $auth_rule=\think\facade\Db::name('auth_rule');
        $data = $this->request->post();
        if($auth_rule->update($data)!==false){
            Cache::clear();
            return json(['code'=>1,'msg'=>fy("Update succeeded"),'url'=>myurl('adminRule')]);
        }else{
            return json(['code'=>0,'msg'=>fy("Update failed")]);
        }
    }
    //设置权限菜单显示或者隐藏
    public function ruleState(){
        $id=input('post.id');
        $menustatus=input('post.menustatus');
        if(Db::name('auth_rule')->where('id='.$id)->update(['menustatus'=>$menustatus])!==false){
            Cache::clear();
            return json(['status'=>1,'msg'=>fy("Setting succeeded")]);
        }else{
            return json(['status'=>0,'msg'=>fy("Setting failed")]);
        }
    }
    //设置权限是否验证
    public function ruleTz(){
        $id=input('post.id');
        $authopen=input('post.authopen');
        if(Db::name('auth_rule')->where('id='.$id)->update(['authopen'=>$authopen])!==false){
            Cache::clear();
            return json(['status'=>1,'msg'=>fy("Setting succeeded")]);
        }else{
            return json(['status'=>0,'msg'=>fy("Setting failed")]);
        }
    }
    public function ruleDel(){
        $id=input('post.id');
        authRule::destroy(['id'=>$id]);
        Cache::clear();
        return json(['code'=>1,'msg'=>fy("Delete succeeded").'!']);
    }

    public function ruleEdit(){
        $id=input('id',0,'trim');
        $id=$id+0;
        if(!$id){
            $this->error(fy("Parameter error"));
        }
        if($this->request->isPost()) {
            $post = $this->request->post();

            if(!empty($post['pid'])){
                Tree::instance()->init(Db::name('authRule')->where([['menustatus','=',1]])->order('pid asc,sort asc')->select()->toArray());
                // 父节点不能是它自身的子节点或自己本身
                if (in_array($post['pid'], Tree::instance()->getChildrenIds($id, true))) {
                    $this->error('父级不能是当前菜单的子级和自身');
                }
            }

            if(authRule::update($post)) {
                Cache::clear();
                return json(['code' => 1, 'msg' => fy("Save successfully"), 'url' => myurl('adminRule')]);
            } else {
                return json(['code' => 0, 'msg' =>fy("Save failed")]);
            }
        }else{

            $arr = cache('addAuthRuleList');

            if(!$arr){
                $authRule = Db::name('authRule')->where([['menustatus','=',1]])->order('pid asc,sort asc')->select();
                $nav = new Leftnav();
                $arr = $nav->menu($authRule);
                cache('addAuthRuleList', $arr, 3600);
            }
            View::assign('admin_rule',$arr);//权限列表

            $admin_rule=Db::name('auth_rule')->where('id','=',input('id'))->find();
            View::assign('rule',$admin_rule);
            $this->app->view->engine()->layout(false);
            return View::fetch();
        }
    }

}
