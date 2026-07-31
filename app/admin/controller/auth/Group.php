<?php

namespace app\admin\controller\auth;

use app\admin\model\AuthGroup;
use app\common\controller\AdminController;

use think\App;
use fhx\Tree;
use think\facade\Cache;
use think\facade\View;

/**
 * @ControllerAnnotation(title="auth_group")
 */
class Group extends AdminController
{

    /**
     * 允许修改的字段
     * @var array
     */
    protected $allowModifyFields = [
        'max_customers_num'
    ];
    protected $sort = [
        'id'   => 'ASC',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\AuthGroup();
        
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model->field('id,pid,title,create_time,max_customers_num')
                    ->where($where)
                    ->order($sort)
                    ->select()->toArray();
                foreach ($list as $k=>$v){
                    $list[$k]['title']=fy($v['title']);
                }
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();

            $rule = [
                'title|用户组名'  => 'require|unique:AuthGroup',
            ];
            $this->validater($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        if ($this->request->isPost()) {

            $post = $this->request->post();
            $rule = [
                'title|用户组名'  => 'require|unique:AuthGroup',
            ];
            if(!empty($post['pid'])){
                $groupData = $this->model->where('status','=',1)->select()->toArray();
                // 父节点不能是它自身的子节点或自己本身
                if (in_array($post['pid'], \fhx\Tree::getChildrenIds($groupData, $row->id, true))) {
                    $this->error('父级不能是它的子级和自身');
                }
            }

            $this->validater($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed'));
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $son_id=\think\facade\Db::name('auth_group')->where('pid','=',$id)->value('id');
            if($son_id){
                throw new \Exception('当前角色存在下级角色！', 0);
            }
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败：'.$e->getMessage());
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    public function access(){
        if ($this->request->isPost()) {
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
        }else{
            $id=input('id',0,'trim');
            if(empty($id)){
                $this->error('非法访问!');
            }
            $admin_rule=\think\facade\Db::name('auth_rule')->field('id,pid,title')->order('sort asc')->select();
            $rules = \think\facade\Db::name('auth_group')->field('rules,title')->where('id',$id)->find();
            if(empty($rules['title'])){
                $this->error('用户组不存在!');
            }
            $arr = \fhx\Leftnav::auth($admin_rule,$pid=0,$rules['rules']);
            $arr[] = [ "id"=>0,
                "pid"=>0,
                "title"=>fy("All"),
                "open"=>true];
            View::assign('data',json_encode($arr,true));
            View::assign('group_title',$rules['title']);
            $this->app->view->engine()->layout(false);
            return $this->fetch();
        }

    }

    
}