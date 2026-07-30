<?php

namespace app\admin\controller\admin;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="admin_log")
 */
class Log extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\AdminLog();
        
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            if($this->admin['group_id']>1){
                $adminLst=\app\service\AdminService::getChildrenAdminIds($this->admin,true);
                $where[] = ['admin_id', 'in', $adminLst];
            }
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
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

    public function add(){
        exit();
    }

    public function edit($id){
        exit();
    }
    public function detail($id){
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->assign('row', $row);
        return $this->fetch();
    }

    
}