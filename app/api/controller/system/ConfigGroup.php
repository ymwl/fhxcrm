<?php

namespace app\api\controller\system;

use app\api\controller\Authority;

use think\App;

class ConfigGroup extends Authority
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