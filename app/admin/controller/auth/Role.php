<?php

namespace app\admin\controller\auth;

use app\common\controller\AdminController;

use think\App;


class Role extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\AuthRole();
        
        $this->assign('getTypeList', $this->model->getTypeList());
        $this->assignconfig('getTypeList', $this->model->getTypeList());

        $this->assign('getStatusList', $this->model->getStatusList());

    }

    
}