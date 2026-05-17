<?php

namespace app\api\controller\auth;

use app\api\controller\Authority;

use think\App;


class Role extends Authority
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