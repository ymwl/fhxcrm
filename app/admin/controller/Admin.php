<?php

namespace app\admin\controller;

use app\common\controller\AdminController;

use think\App;


class Admin extends AdminController
{

    protected $selectpageFields = '`admin_id`,`username`,`realname`';

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Admin();
        
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        exit('This method is not allowed');
        if ($this->request->isAjax()) {
            
            list($page, $limit, $where) = $this->buildTableParames();
            $status=$this->request->get('status',0,'intval');
            if($status){
                $where[]=['status','=',$status];
            }
            $count = $this->model ->withJoin('type', 'LEFT')
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->withJoin('type', 'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($this->sort)  //->fetchSql()
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

    public function selectpage()
    {
        $allTeam = $this->request->param('custom/all_team', '0');
        if ($allTeam !== '1' && $this->admin['group_id'] != 1) {
            $adminIds=\app\service\AdminService::getViewAdminIds($this->admin,true);
            if(empty($adminIds)){
                return json(['list' => [], 'total' => 0]);
            }
            if($adminIds!=='ALL'){
                $this->scopeWhere =[['admin_id', 'in', $adminIds]];
            }
        }

        return parent::selectpage();
    }
    
}