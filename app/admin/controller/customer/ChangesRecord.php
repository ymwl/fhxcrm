<?php

namespace app\admin\controller\customer;

use app\common\controller\AdminController;

use think\App;


class ChangesRecord extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CustomerChangesRecord();
        
    }

    
}