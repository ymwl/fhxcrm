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
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
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
//                ->withJoin('productType', 'LEFT')
                    ->withJoin('type', 'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($this->sort)  //->fetchSql()
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
        return $this->fetch();
    }
    
}