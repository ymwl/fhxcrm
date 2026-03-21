<?php

namespace app\admin\controller\system;

use app\common\controller\AdminController;

use think\App;

class ConfigGroup extends AdminController
{

    protected $modelValidate=true;
    protected $modelSceneValidate=false;
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\SystemConfigGroup();
        $this->assign('getStatusList', $this->model->getStatusList());
        
    }

    
}