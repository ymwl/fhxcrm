<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="crm_record_type")
 */
class RecordType extends AdminController
{

    /**
     * 字段排序
     * @var array
     */
    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'DESC',
    ];
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmRecordType();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    
}