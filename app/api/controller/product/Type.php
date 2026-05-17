<?php

namespace app\api\controller\product;

use app\api\controller\Authority;
use think\App;

/**
 * @ControllerAnnotation(title="product_type")
 */
class Type extends Authority
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\ProductType();

    }
    /**
     * 字段排序
     * @var array  pid asc,sort asc,id asc
     */
    protected $sort = [
        'pid'   => 'ASC',
        'sort'   => 'ASC',
        'id'   => 'ASC',
    ];

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
//        判断当前是否还有子分类  存在子分类禁止删除
        $pid=\think\facade\Db::name('product_type')->whereIn('pid', $id)->value('id');
        if($pid){
            $this->error(fy("Product subcategories exist and cannot be deleted"));
        }
        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

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
                $list = $this->model
                    ->where($where)
                    ->page($page, 1000)
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




}
