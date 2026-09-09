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
        // 中间件在路由调度之前执行,此时 request 尚未解析 controller/action,需从 pathinfo 手动解析
        $controller = strtolower((string) $request->controller());
        $action     = strtolower((string) $request->action());
        if ('' === $controller || '' === $action) {
            // 参考框架 Route::path() 先去除 URL 后缀,再按分隔符解析
            $pathinfo = trim((string) $request->pathinfo(), '/');
            $suffix   = config('route.url_html_suffix');
            if (false === $suffix) {
                // 禁止伪静态访问
            } elseif ($suffix) {
                // 去除正常的 URL 后缀
                $pathinfo = preg_replace('/\.(' . ltrim((string) $suffix, '.') . ')$/i', '', $pathinfo);
            } else {
                // 允许任何后缀访问
                $pathinfo = preg_replace('/\.' . $request->ext() . '$/i', '', $pathinfo);
            }
            $pathArr    = array_values(array_filter(explode('/', (string) $pathinfo)));
            // 保留 URL 原始大小写写入 request(与框架 Route 解析一致:框架 setAction 同样保留原始大小写,
            // assignViewConfig 依赖它注入 CONFIG.ACTION,必须与前端 JS 方法名大小写一致,如 adminRule)
            $controller = $pathArr[0] ?? 'index';
            $action     = $pathArr[1] ?? 'index';
            $request->setController($controller)->setAction($action);
        }
        // 白名单/权限匹配统一转小写(兼容 URL 大写形式,如 Login/verify)
        $path = strtolower($controller) . '/' . strtolower($action);

        // 判断是否无需登录
        if (in_array($path, $this->noLogin)) {
            return $next($request);
        }

        // 授权软禁用拦截：存在禁用标记时拦截CRM功能（放行授权页crm.license与system.config以便重验解锁）
        if (strpos(strtolower($controller), 'crm.') === 0 && strtolower($controller) !== 'crm.license'
            && is_file(app()->getRuntimePath() . 'license.lock')) {
            if ($request->isAjax()) {
                return json([
                    'code' => 0,
                    'msg' => '系统授权已被禁用，请联系服务商',
                    'url' => '',
                    'data' => []
                ]);
            }
            throw new \think\exception\HttpException(403, '系统授权已被禁用，请联系服务商');
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
        $adminRules = [];
        if (!in_array($path, array_map('strtolower', $this->noAuth)) && $groupId != 1 && $hrefId) {
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

        // 权限规则列表注入 request,供控制器使用(如 Ajax::getMenu 菜单过滤)
        $request->adminRules = $adminRules;

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

        // 操作日志记录已移至 Common 控制器(保留 $auto_record_log 开关),避免双写

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
            'CONTROLLER_JS_PATH'  => "{$module}/js/{$jsPath}.js",
            'AUTOLOAD_JS'         => true,
            'IS_DEV'              => IS_DEV,
            'LANG'                => Lang::getLangSet(),
            'VERSION'             => env('APP_DEBUG') ? time() : config('version.version'),
            'SOFT_VERSION'        => config('version.version'),
            'SOFT_NAME'           => config('version.name'),
            'SOFT_ID'             => config('version.id'),
            'CSRF_TOKEN'          => token(),
            'ADMINPAGESIZE'       => $system['admin_pagesize'] ?? 15,
            'MY_PUBLIC'           => __MY_PUBLIC__,
            'MODULEURL'           => rtrim((string) url("/", [], false), '/'),
        ];

        View::assign('config', $data);
    }
}
