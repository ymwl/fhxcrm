<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="crm_source")
 */
class Source extends AdminController
{

    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'DESC',
    ];
    /**
     * 下拉选择条件
     * @var array
     */
    protected $selectWhere = ['status'=>1];
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmSource();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    
}