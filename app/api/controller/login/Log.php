<?php

namespace app\api\controller\login;

use app\api\controller\Authority;

use think\App;

/**
 * @ControllerAnnotation(title="login_log")
 */
class Log extends Authority
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\LoginLog();
        
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
//            不是管理员只能看到自己的
            if($this->admin['group_id']>1){
                $where[]=['admin_id','=',$this->admin['admin_id']];
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


    
}