<?php

namespace app\api\controller\crm;

use app\api\controller\Authority;

use think\App;

/**
 * @ControllerAnnotation(title="crm_hangye")
 */
class Hangye extends Authority
{
    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'DESC',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmHangye();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    
}