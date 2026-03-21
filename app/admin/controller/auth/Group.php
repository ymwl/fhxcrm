<?php

namespace app\admin\controller\auth;

use app\common\controller\AdminController;
use clt\Leftnav;

use think\App;
use fast\Tree;

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
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model->field('id,pid,title,create_time,max_customers_num')
                    ->where($where)
                    ->order($this->sort)
                    ->select()->toArray();
                foreach ($list as $k=>$v){
                    $list[$k]['title']=fy($v['title']);
                }
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
                Tree::instance()->init($this->model->where('status','=',1)->select()->toArray());
                // 父节点不能是它自身的子节点或自己本身
                if (in_array($post['pid'], Tree::instance()->getChildrenIds($row->id, true))) {
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

    
}