<?php
//技术支持微信：zrwx978
namespace app\index\controller;

use app\BaseController;

class Index  extends BaseController{
    public function index(){

        return 'CRM客户管理系统，为了系统安全我们已经针对后台地址进行隐藏处理，请直接输入完整的后台地址进行登录';
    }
}