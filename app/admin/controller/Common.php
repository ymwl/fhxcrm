<?php
namespace app\admin\controller;
use think\facade\Lang;
use app\BaseController;
class Common extends BaseController
{

    protected $adminRules,$HrefId;

    public $admin=[];
    public $auto_record_log=1;
    protected function initialize()
    {
        // 登录验证与权限校验已由 Auth 中间件(app\admin\middleware\Auth)统一完成,
        // 此处仅接收中间件注入到 request 的数据
        $this->admin      = $this->request->adminInfo;
        $this->HrefId     = $this->request->hrefId;
        $this->adminRules = $this->request->adminRules ?? [];

        // 操作日志(子类可通过 $auto_record_log 属性关闭)
        if ($this->auto_record_log && $this->HrefId && $this->request->isPost()) {
            $path = strtolower($this->request->controller()) . '/' . strtolower($this->request->action());
            \app\common\model\AdminLog::record($this->admin, $path, $this->HrefId);
        }
    }
    //空操作
    public function _empty(){
        $this->error('空操作，返回上次访问页面中...');
    }
    /**
     * 解析和获取模板内容 用于输出
     * 调试模式下自动在页面顶部显示当前模板文件路径
     * @param string $template 模板文件名
     * @param array  $vars     模板变量
     * @return string
     */
    protected function fetch($template = '', $vars = [])
    {
        if (env('APP_DEBUG')) {
            $templatePath = resolve_template_path($template);
            $this->app->view->filter(function ($content) use ($templatePath) {
                return '<!--当前页面的模板文件是：' . $templatePath . ' （本代码只在开发者模式下显示）-->' . "\n" . $content;
            });
        }
        return $this->app->view->fetch($template, $vars);
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
//        $name = parse_name($name);
        $name = preg_match("/^([a-zA-Z0-9_\.\/]+)\$/i", $name) ? $name : 'index';
        $lang = Lang::getLangSet();
        $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';
        Lang::load(app()->getBasePath() . 'common/lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 渲染配置信息
     * @param mixed $name  键名或数组
     * @param mixed $value 值
     */
    protected function assignconfig($name, $value = '')
    {
        $this->app->view->config = array_merge($this->app->view->config ? $this->app->view->config : [], is_array($name) ? $name : [$name => $value]);
    }



}
