<?php
namespace app\admin\controller;
use think\facade\Lang;
use think\facade\View;
use think\facade\Db;
use app\BaseController;
class Common extends BaseController
{

    protected $mod,$system,$module,$adminRules,$HrefId;

    public $admin=[];
    public $auto_record_log=1;
    protected function initialize()
    {
        $this->admin=session('admin');

        //判断管理员是否登录
        if (empty($this->admin)) {
            session('referer',$this->request->url());
            if($this->request->isAjax()){
                $this->error(fy("Please log in"),myurl('login/index'),[],3,[],-200);
            }else{
                $this->redirect(myurl('login/index'));
            }

        }
        define('CONTROLLER',strtolower($this->request->controller()));
        define('ACTION',strtolower($this->request->action()));
        //权限管理
        //当前操作权限ID
        $group_id=\think\facade\Db::name('admin')->cache('admin_id_'.$this->admin['admin_id'])->where('admin_id','=',$this->admin['admin_id'])->value('group_id');
        $path=CONTROLLER.'/'.ACTION;
        $this->HrefId = \think\facade\Db::name('auth_rule')->whereRaw('LOWER(`href`)=:href AND authopen=1',['href'=>$path])->value('id');
        if( $group_id!=1 && $this->HrefId){
            //当前管理员权限
            $map['a.admin_id'] =  $this->admin['admin_id'];
            $prefix = getDataBaseConfig('prefix');

            $rules=Db::name('admin')->alias('a')->cache('rules_'.$this->admin['admin_id'])
                ->join($prefix.'auth_group ag','a.group_id = ag.id','left')
                ->where($map)
                ->value('ag.rules');
            $this->adminRules = explode(',',$rules);
            if($this->HrefId){
                if(!in_array($this->HrefId,$this->adminRules)){
                    $this->error(fy("You do not have this operation permission"));
                }
            }
        }

        $this->system = cache('System');
        if(empty($this->system)){
            $this->system=savecache('System');
        }
        View::assign('system',$this->system);
        View::assign('admin',$this->admin);

        if($this->auto_record_log && $this->HrefId && $this->request->isPost()){
            \app\common\model\AdminLog::record($this->admin,$path,$this->HrefId);
        }
        $this->viewInit();
    }
    //空操作
    public function _empty(){
        $this->error('空操作，返回上次访问页面中...');
    }
    /**
     * 初始化视图参数
     */
    private function viewInit(){
        list($thisModule, $thisController, $thisAction) = [app('http')->getName(), $this->request->controller(), $this->request->action()];
        list($thisControllerArr, $jsPath) = [explode('.', $thisController), null];
        foreach ($thisControllerArr as $vo) {
            empty($jsPath) ? $jsPath = parse_name($vo) : $jsPath .= '/' . parse_name($vo);
        }
//        $autoloadJs = file_exists(root_path('public') . "static/{$thisModule}/js/{$jsPath}.js") ? true : false;
        $thisControllerJsPath = "{$thisModule}/js/{$jsPath}.js";
//        $isSuperAdmin = session('admin.admin_id') == SUPER_ADMIN_ID? true : false;

        $data = [
            'ADMIN'      => $this->admin,
            'MODULE'      => $thisModule,
            'CONTROLLER'       => parse_name($thisController),
            'JSPATH'       => $jsPath,
            'ACTION'           => $thisAction,
            'thisRequest'          => parse_name("{$thisModule}/{$thisController}/{$thisAction}"),
            'CONTROLLER_JS_PATH' => "{$thisControllerJsPath}",
            'AUTOLOAD_JS'           => true,
            'IS_SUPER_ADMIN'         => false,
            'IS_DEV'         => IS_DEV,
            'LANG'         => Lang::getLangSet(),
            'VERSION'              => env('APP_DEBUG') ? time() : config('version.version'),
            'SOFT_VERSION'              => config('version.version'),
            'SOFT_NAME'              => config('version.name'),
            'SOFT_ID'              => config('version.id'),
            'CSRF_TOKEN'              => token(),
            'ADMINPAGESIZE'              => $this->system['admin_pagesize'],
            'MY_PUBLIC'              => __MY_PUBLIC__,
            'LICENSE'              => @file_get_contents(app()->getRootPath().'config/license.key'),
            'MODULEURL'      => rtrim((string)url("/", [], false), '/'),
        ];
//加载当前控制器语言包
        if($this->request->get('callback')!='define'){
            $this->loadlang($jsPath);
        }

        \think\facade\View::assign('config',$data);
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
//        D:\web\crm.laikephp.com\app\admin\/lang/en-us/index.php
//        var_dump('common');
//        var_dump($this->app->getAppPath() . 'lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
        Lang::load($this->app->getAppPath() . 'lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
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
