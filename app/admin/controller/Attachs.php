<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use think\App;
use think\facade\Cache;
use think\facade\View;
use think\facade\Db;

class Attachs extends AdminController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

    }

    public function index(){
        $attachs=$this->request->get('attachs');
        $attachs=explode('|',$attachs);
        $list=db::name('system_uploadfile')->where('file_path','in',$attachs)->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
    
}
