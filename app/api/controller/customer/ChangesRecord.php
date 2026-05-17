<?php

namespace app\api\controller\customer;

use app\api\controller\Authority;

use think\App;


class ChangesRecord extends Authority
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CustomerChangesRecord();
        
    }

    
}