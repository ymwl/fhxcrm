<?php

namespace app\admin\controller\open;

use app\common\controller\AdminController;
use think\App;

/**
 * @ControllerAnnotation(title="open_apps")
 */
class Apps extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\OpenApps();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();
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
        $this->assignconfig('rooturl',myurl('/',[],false,true));
        return $this->fetch();
    }
    
    public function doc($id){
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->assign('row', $row);
        return $this->fetch();
    }

}
