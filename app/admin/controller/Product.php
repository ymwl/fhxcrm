<?php

namespace app\admin\controller;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="product")
 */
class Product extends AdminController
{

    public $relationSearch=true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Product();

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
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $status=$this->request->get('status',0,'intval');
            if($status){
                $where[]=['status','=',$status];
            }
            $count = $this->model ->withJoin('type', 'LEFT')
                ->where($where)->count();
            $list=[];
            if($count){
                $list = $this->model
//                ->withJoin('productType', 'LEFT')
                    ->withJoin('type', 'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)  //->fetchSql()
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

}
