<?php

namespace app\api\controller;

use app\api\controller\Authority;

use think\App;

/**
 * @ControllerAnnotation(title="product")
 */
class Product extends Authority
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
            list($page, $limit, $where) = $this->buildTableParames();
            $status=$this->request->get('status',0,'intval');
            if($status){
                $where[]=['status','=',$status];
            }
            $count = $this->model ->withJoin('type', 'LEFT')
                ->where($where)->count();
//            var_dump($count);exit();
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
        
    }

}
