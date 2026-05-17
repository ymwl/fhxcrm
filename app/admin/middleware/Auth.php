<?php
namespace app\admin\middleware;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use think\facade\Lang;

/**
 * 后台认证中间件
 * 处理登录验证和权限检查
 */
class Auth
{
    /**
     * 无需登录的控制器/方法
     */
    protected $noLogin = [
        'login/index',
        'login/verify',
        'login/logout',
    ];

    /**
     * 无需权限验证的控制器/方法
     */
    protected $noAuth = [
        'index/index',
        'index/home',
        'index/clear',
        'upload/upload',
        'upload/uploadEditor',
        'message/index',
    ];

    public function handle($request, \Closure $next)
    {
        $controller = strtolower($request->controller());
        $action = strtolower($request->action());
        $path = $controller . '/' . $action;

        // 判断是否无需登录
        if (in_array($path, $this->noLogin)) {
            return $next($request);
        }

        // 获取当前管理员
        $admin = Session::get('admin');

        // 未登录检查
        if (empty($admin)) {
            Session::set('referer', $request->url());
            if ($request->isAjax()) {
                return json([
                    'code' => -200,
                    'msg' => lang('Please log in'),
                    'url' => myurl('login/index'),
                    'data' => []
                ]);
            }
            return redirect(myurl('login/index'));
        }

        // 超级管理员 ID
        $groupId = Db::name('admin')
            ->cache('admin_id_' . $admin['admin_id'])
            ->where('admin_id', $admin['admin_id'])
            ->value('group_id');

        // 获取当前操作的权限 ID
        $hrefId = Db::name('auth_rule')
            ->whereRaw('LOWER(`href`)=:href AND authopen=1', ['href' => $path])
            ->value('id');

        // 记录当前请求的权限 ID 到 request，供后续使用
        $request->hrefId = $hrefId;
        $request->adminGroupId = $groupId;
        $request->adminInfo = $admin;

        // 判断是否需要权限验证
        if (!in_array($path, $this->noAuth) && $groupId != 1 && $hrefId) {
            // 获取当前管理员权限
            $prefix = getDataBaseConfig('prefix');
            $rules = Db::name('admin')
                ->alias('a')
                ->cache('rules_' . $admin['admin_id'])
                ->join($prefix . 'auth_group ag', 'a.group_id = ag.id', 'left')
                ->where('a.admin_id', $admin['admin_id'])
                ->value('ag.rules');

            $adminRules = explode(',', $rules);
            if (!in_array($hrefId, $adminRules)) {
                if ($request->isAjax()) {
                    return json([
                        'code' => 0,
                        'msg' => lang('You do not have this operation permission'),
                        'url' => '',
                        'data' => []
                    ]);
                }
                throw new \think\exception\HttpException(403, lang('You do not have this operation permission'));
            }
        }

        // 加载系统配置
        $system = cache('System');
        if (empty($system)) {
            $system = savecache('System');
        }

        // 加载语言包
        $this->loadLang($request);

        // 注入视图变量
        View::assign('system', $system);
        View::assign('admin', $admin);

        // 注入 JS 配置变量
        $this->assignViewConfig($request, $admin, $system);

        // 记录操作日志
        if ($hrefId && $request->isPost()) {
            \app\common\model\AdminLog::record($admin, $path, $hrefId);
        }

        return $next($request);
    }

    /**
     * 加载语言文件
     */
    protected function loadLang($request)
    {
        $controller = parse_name($request->controller());
        $controllerArr = explode('.', $controller);
        $jsPath = null;
        foreach ($controllerArr as $vo) {
            empty($jsPath) ? $jsPath = $vo : $jsPath .= '/' . $vo;
        }

        if ($request->get('callback') != 'define') {
            $name = preg_match("/^([a-zA-Z0-9_\.\/]+)\$/i", $jsPath) ? $jsPath : 'index';
            $lang = Lang::getLangSet();
            $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';
            Lang::load(app()->getBasePath() . 'common/lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
        }
    }

    /**
     * 注入视图配置
     */
    protected function assignViewConfig($request, $admin, $system)
    {
        $module = app('http')->getName();
        $controller = $request->controller();
        $action = $request->action();

        $controllerArr = explode('.', $controller);
        $jsPath = null;
        foreach ($controllerArr as $vo) {
            empty($jsPath) ? $jsPath = parse_name($vo) : $jsPath .= '/' . parse_name($vo);
        }

        $data = [
            'ADMIN'               => $admin,
            'MODULE'              => $module,
            'CONTROLLER'          => parse_name($controller),
            'JSPATH'              => $jsPath,
            'ACTION'              => $action,
            'thisRequest'         => parse_name("{$module}/{$controller}/{$action}"),
            'CONTROLLER_JS_PATH'  => "{$module}/js/{$jsPath}.js",
            'AUTOLOAD_JS'         => true,
            'IS_SUPER_ADMIN'      => $admin['admin_id'] == SUPER_ADMIN_ID,
            'IS_DEV'              => IS_DEV,
            'LANG'                => Lang::getLangSet(),
            'VERSION'             => env('APP_DEBUG') ? time() : config('version.version'),
            'SOFT_VERSION'        => config('version.version'),
            'SOFT_NAME'           => config('version.name'),
            'SOFT_ID'             => config('version.id'),
            'CSRF_TOKEN'          => token(),
            'ADMINPAGESIZE'       => $system['admin_pagesize'] ?? 15,
            'MY_PUBLIC'           => __MY_PUBLIC__,
            'LICENSE'             => @file_get_contents(app()->getRootPath() . 'config/license.key'),
            'MODULEURL'           => rtrim((string) url("/", [], false), '/'),
        ];

        View::assign('config', $data);
    }
}
