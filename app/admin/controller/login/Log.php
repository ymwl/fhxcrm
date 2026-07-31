<?php

namespace app\admin\controller\login;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="login_log")
 */
class Log extends AdminController
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
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();
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


    
}