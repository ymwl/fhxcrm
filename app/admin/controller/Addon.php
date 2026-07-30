<?php
//移除或绕过授权验证，保留追究法律责任的权利
namespace app\admin\controller;

use app\common\controller\AdminController;
use app\admin\service\AddonDownloader;
use app\admin\service\AddonFileManager;
use app\admin\service\AddonLifecycle;

use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Lang;


class Addon extends AdminController
{


    public function __construct(\think\App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Addon();
        
    }
    public function index()
    {
        if ($this->request->isAjax()) {

//            把$addons同步到数据库 中
//            每天同步一次互联网上插件数据


            $scope=$this->request->get('scope', 'local','trim');
            list($page, $limit, $where,$sort) = $this->buildTableParames();

            if($scope=='net'){
                $is_sync=Cache::get('addons_sync');
                if(!$is_sync){
                    $license=$this->system['license_key'];
                    if(empty($license)){
                        $data = [
                            'code'  => -1,
                            'msg'   => '授权使用联系微信:zrwx978',
                        ];
                        return json($data);
                    }
                    $domain=$_SERVER['HTTP_HOST'];
                    $data=[
                        'domain'=>$domain,
                        'source'=>'fhx_crm_plugin_ids',
                        'license'=>$license,
                        'php'=>PHP_VERSION
                    ];
//                    ($url, $method, $postfields = null, $headers = [], $debug = false)
                    $res=httpRequest('https://cloud.laikephp.com/index/fhx.addon/index','POST',$data,[],true);
                    $res_arr=json_decode($res,true);

                    if($res_arr){
                        if($res_arr['code']==1){
                            $this->model->net_sync($res_arr['data']);
                            Cache::set('addons_sync',1,86400);
                        }else{
                            $data = [
                                'code'  => 1,
                                'msg'   =>$res_arr['msg'],
                            ];
                            return json($data);
                        }
                    }else{
                        $data = [
                            'code'  => 1,
                            'msg'   => $res,
                        ];
                        return json($data);
                    }
                }
                $where[]=['net_version','<>',''];
//               判断今天是否同步过
            }else{
                //获取插件列表数据
                $addons=get_addons_list();
                $this->model->sync($addons);
                $where[]=['version','<>',''];

            }

            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
            }
            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);


        }
        return $this->fetch();
    }

    public function install($name)
    {
        if (empty($name)) {
            $this->error(lang('addon name can not be empty'));
        }
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name is not right'));
        }

        $no = input('no', '', 'trim');

        try {
            (new AddonLifecycle())->install($name, $no ?: null);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success("插件安装成功");
    }

    public function uninstall($name)
    {
        if (empty($name)) {
            $this->error(lang('addon name can not be empty'));
        }
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name is not right'));
        }

        try {
            (new AddonLifecycle())->uninstall($name);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success("插件卸载成功");
    }

    /**
     * 清除 config/addons.php 中指定插件的路由映射
     * @param string $name 插件名称
     */
    protected function clearAddonRoutes(string $name)
    {
        $addonsConfig = config('addons');
        if (!empty($addonsConfig['route'])) {
            $pluginPrefix = $name . '/';
            $modified = false;
            foreach ($addonsConfig['route'] as $key => $value) {
                if (strpos($value, $pluginPrefix) === 0) {
                    unset($addonsConfig['route'][$key]);
                    $modified = true;
                }
            }
            if ($modified) {
                $configPath = config_path() . 'addons.php';
                file_put_contents($configPath, "<?php \r\n return " . var_export($addonsConfig, true) . ';');
            }
        }
    }

    /**
     * 插件升级
     */
    public function upgrade()
    {
        $name = input('name', '', 'trim');
        $no = input('no', '', 'trim');

        if (empty($name)) {
            $this->error('请选择要升级的插件');
        }
        if (empty($no)) {
            $this->error('请求非法');
        }

        $class = get_addons_instance($name);
        $addonInfo = $class->getInfo();
        $build = empty($addonInfo['build']) ? '2025-08-11 22:43:03' : $addonInfo['build'];

        try {
            $result = (new AddonLifecycle())->upgrade($name, $no, $this->system['license_key'], $build);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $msg = $result['count'] > 1 ? "升级到版本{$result['version']}成功" : '升级完成';
        $this->success($msg, null, ['count' => $result['count'], 'version' => $result['version']]);
    }

    public function config()
    {
        $name = $this->request->get("name");
        $id = $this->request->get("id");

        $config = get_addons_config($name);

        if ($this->request->isAjax()) {
            $params = input('row/a',[],'trim');
            if ($params) {
                foreach ($config as $k=>&$v) {

                    if (isset($params[$k])) {
                        if ($v['type'] == 'array') {

                            $v['value'] = is_array($params[$k]) ? $params[$k] :json_decode($params[$k],true);
                        }
                    }
                }
                set_addons_config($name,$config);
                if(isset($config['rewrite']) && !empty($config['rewrite'])){
                    $addons=config('addons');
                    $addons['route']=array_replace($addons['route'],$config['rewrite']['value']);
                    $configPath = config_path() . '/';
                    $configFile = $configPath . 'addons.php';
                    file_put_contents($configFile, "<?php \r\n return ".var_export($addons,true).';');
                }
                Cache::set('addons_list',null);
                $this->success('设置成功');
            }
            $this->error(lang('addon can not be empty'));
        }
        if (!$name) {
            $this->error(lang('addon name can not be empty'));
        }
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name is not right'));
        }

        //模板引擎初始化
        $view = ['formData'=>$config];
        $configFile = app()->getRootPath() . 'addons' . DS . $name . DS . 'config.html';
        $viewFile = file_exists($configFile) ? $configFile : '';
        //重新加载引擎
        app()->view->engine()->layout($this->layout);
        return view($viewFile,$view);
    }


    protected function clearCache()
    {
        $lang = Lang::getLangSet();
        $cache_key='initAdmin_' .$this->admin['admin_id'].$lang;
//        清除菜单缓存
        Cache:: delete($cache_key);
        Cache:: delete('addons_list');
    }

    public function modify()
    {
        // 1. 获取前端参数
        $id = input('id', 0, 'intval');
        $field = input('field', '', 'trim');
        $value = input('value', null);

        // 2. 参数校验
        if (empty($id)) {
            $this->error('参数错误');
        }
        if ($field !== 'status') {
            $this->error('不支持的字段');
        }
        if ($value === null || !in_array((int)$value, [0, 1], true)) {
            $this->error('状态值错误');
        }
        $value = (int)$value;

        // 3. 查询插件记录
        $addon = \app\admin\model\Addon::find($id);
        if (!$addon) {
            $this->error('插件不存在');
        }
        $name = $addon->name;

        // 4. 判断是否需要变更（如果当前状态已一致则直接返回成功）
        $addoninfo = get_addons_info($name);
        if ($addoninfo['status'] == $value) {
            $this->success('操作成功');
        }

        /*try {*/
            \think\facade\Db::startTrans();

            // 5. 更新插件 info 状态
            $addoninfo['status'] = $value;
            set_addons_info($name, $addoninfo);

            // 6. 获取插件实例和菜单配置
            $class = get_addons_instance($name);
            $addonService = new \app\admin\service\AddonService();
            $menu_config = get_addons_menu($name);

            // 7. 处理菜单：启用时确保菜单存在并显示，禁用时隐藏菜单（不删除数据）
            if (!empty($menu_config)) {
                list($menu, $pid) = $addonService->getMenu($menu_config);
                if ($value) {
                    $addonService->addAddonMenu($menu, $pid, $name);
                    $addonService->setMenuStatus($name, 1);
                } else {
                    $addonService->setMenuStatus($name, 0);
                }
            }

            // 8. 处理路由映射
            if ($value) {
                $addonService->restorePluginRoutes($name);
            } else {
                $addonService->clearPluginRoutes($name);
            }

            // 9. 刷新插件缓存
            if (function_exists('refreshaddons')) {
                refreshaddons();
            }

            // 10. 保存数据库状态
            $addon->status = $value;
            $addon->save();

            \think\facade\Db::commit();

            // 11. 调用插件回调方法并触发事件
            if ($value == 1) {
                $class->enabled();
                \think\facade\Event::trigger('AddonEnabled', ['name' => $name]);
            } else {
                $class->disabled();
                \think\facade\Event::trigger('AddonDisabled', ['name' => $name]);
            }

     /*   } catch (\Exception $e) {
            \think\facade\Db::rollback();
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            \think\facade\Db::rollback();
            $this->error($e->getMessage());
        }*/

        // 12. 清除管理后台缓存
        $this->clearCache();
        $this->success('操作成功');
    }

    
}
