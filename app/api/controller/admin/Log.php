<?php

namespace app\api\controller\admin;

use app\api\controller\Authority;

use think\App;

/**
 * @ControllerAnnotation(title="admin_log")
 */
class Log extends Authority
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
            list($page, $limit, $where) = $this->buildTableParames();
            if($this->admin['group_id']>1){
                $adminLst=(new \app\admin\model\Admin())->getChildrenAdminIds($this->admin,true);
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
                    ->order($this->sort)
                    ->select();
            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        
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
        
    }

    
}