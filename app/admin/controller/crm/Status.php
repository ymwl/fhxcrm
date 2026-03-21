<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="crm_status")
 */
class Status extends AdminController
{

    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'DESC',
    ];
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmStatus();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    
}