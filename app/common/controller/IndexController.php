<?php


namespace app\common\controller;


use app\BaseController;
use think\App;
use think\facade\Db;
use think\facade\Lang;
use think\facade\View;
use think\Model;
use app\admin\controller\Common;


class IndexController extends BaseController{

    public $params;
    protected $layout;
    public $model;
    public $table;
    public $prefix;
    public $name;

    public function __construct(App $app)
    {

        parent::__construct($app);
        $this->params = $this->request->param();



    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return mixed
     */
    protected function assign($name, $value = null)
    {
        return $this->app->view->assign($name, $value);
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template
     * @param array $vars
     * @return mixed
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->app->view->fetch($template, $vars);
    }

    protected function display($template = '', $vars = [])
    {
        return $this->app->view->display($template, $vars);
    }

}