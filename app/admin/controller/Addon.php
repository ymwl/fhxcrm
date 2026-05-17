<?php
//移除或绕过授权验证，保留追究法律责任的权利
namespace app\admin\controller;

use app\common\controller\AdminController;

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
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);


        }
        return $this->fetch();
    }
    public function install($name){


        $addons_path=app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR .$name.DIRECTORY_SEPARATOR;
        $addonFile =  $addons_path. 'Plugin.php';
        if (!file_exists($addonFile)) {
//            不存在从后台获取插件包
            $no=input('no', '','trim');
            if(empty($no)){
                $this->error('插件安装失败，请联系作者');
            }
            // 执行下载文件
            $tempPath=app()->getRuntimePath().'addons';

            if(!is_dir($tempPath)){
                mkdir($tempPath,0777,true);
            }


            set_time_limit(0);

            $domain=$_SERVER['HTTP_HOST'];
            $license=$this->system['license_key'];
            if(empty($license)){
                $this->error('授权使用联系微信:zrwx978');
            }
            $data=[
                'domain'=>$domain,
                'license'=>$license,
                'php'=>PHP_VERSION,
                'no'=>$no
            ];
            $res=httpRequest('https://cloud.laikephp.com/index/fhx.addon/install','POST',$data);
            $res_arr=json_decode($res,true);
            if($res_arr){
                if($res_arr['code']==1){
                    $url=isset($res_arr['url'])?$res_arr['url']:'';
                    if($url){
                        $remoteUrl=strpos($url,'/')===0?config('fhx.api_domain').$url:$url;
//进入文件下载

                        $localFile = $tempPath.DIRECTORY_SEPARATOR.basename($url); // 临时存储路径

                        $ch = curl_init($remoteUrl);
                        $fp = fopen($localFile, 'wb');

                        if (!$fp) {
                            $this->error('无法创建临时文件');
                        }

                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36');

                        if (curl_exec($ch) === false) {
                            unlink($localFile);
                            $this->error('插件下载失败: ' . curl_error($ch));
                        }
                        curl_close($ch);
                        fclose($fp);

                        //下载完成执行解压
                        if (!class_exists('ZipArchive')) {
                            $this->error('PHP环境解压类ZipArchive没有开启，插件安装失败');
                        }

                        $zip = new \ZipArchive;//新建一个ZipArchive的对象
                        /*
                        通过ZipArchive的对象处理zip文件
                        $zip->open这个方法的参数表示处理的zip文件名。
                        如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
                        */
                        if ($zip->open($localFile) === TRUE) {
                            $zip->extractTo(app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR);//解压缩到某路径下
                            $zip->close();//关闭处理的zip文件
                            unlink($localFile);
                        }else{
                            unlink($localFile);
                            $this->error('打开插件安装包失败');
                        }

                    }else{
                        $this->error('插件下载地址获取失败');
                    }

                }else{
                    $this->error($res_arr['msg']);
                }
            }else{
                $this->error($res);
            }

        }
        $class = get_addons_instance($name);
        $addonInfo=$class->getInfo();
        unset($addonInfo['url']);
        if(isset($addonInfo['status']) && $addonInfo['status']==1){
            $this->error("插件已安装，无需重新安装");
        }
        $result=$class->install();
        if($result===false){
            $this->error("插件安装失败");
        }
        set_time_limit(0);ini_set("memory_limit",'-1');

        try {
            \think\facade\Db::startTrans();
            //添加菜单
            $menu_config=get_addons_menu($name);
            if(!empty($menu_config)){
                $addonService=new \app\admin\service\AddonService();
                $addonService->addAddonMenu($menu_config,$menu_config[0]['pid'],$name);
            }
            //        安装数据库
            $sqlFile =  $addons_path . 'install.sql';
            if (is_file($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $sql = preg_replace([
                    '/^--.*$/m',     // 删除注释行
                    '/^\s*$/m'
                    , '/\n+/'      // 删除空行
                ], '', $sql);
                $sql = trim($sql);
                $prefix = getDataBaseConfig('prefix');
                $sql = str_replace("`ymwl_", "`{$prefix}", $sql);
                \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
            }
            \think\facade\Db::commit();
        }catch (\Exception $e) {
            \think\facade\Db::rollback();
                $this->error($e->getMessage());
        }catch (\Throwable $e) {
            \think\facade\Db::rollback();
                $this->error($e->getMessage());
            }
        if(is_dir( $addons_path."app")){
            \tools\Hs::copydirs( $addons_path."app", root_path().'app');
        }
        if(is_dir( $addons_path."public")){
            \tools\Hs::copydirs( $addons_path."public", root_path().'public');
        }
        Config::set([],$name);

        $addonslist = get_addons_list();
        $addonInfo['status'] = 1;
        $addonInfo['install'] = 1;
        $addonslist[$name]=$addonInfo;
        Cache::set('addonslist',  json_encode($addonslist));
        $class->setInfo($name,$addonslist[$name]);
        $this->clearCache();
        $this->success("插件安装成功");
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
     * 插件的卸载
     */
    public function uninstall($name){
        $addons_path=app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR .$name.DIRECTORY_SEPARATOR;
        $class = get_addons_instance($name);
        $result=$class->uninstall();
        if($result===false){
            $this->error("插件卸载失败");
        }
        //删除菜单
        $menu_config=get_addons_menu($name);
        if(!empty($menu_config)){
            \think\facade\Db::name("auth_rule")->where("plugin",$name)->delete();
        }
        set_time_limit(0);
//        卸载数据库
        $sqlFile =  $addons_path . 'uninstall.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = preg_replace([
                '/^--.*$/m',     // 删除注释行
                '/^\s*$/m'
                ,'/\n+/'      // 删除空行
            ], '', $sql);
            $sql = trim($sql);
            $prefix=getDataBaseConfig('prefix');
            $sql = str_replace("`ymwl_", "`{$prefix}",$sql);
            \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
        }

        if(is_dir( $addons_path."app")){
//            找到文件夹下的所有文件包括子目录下的文件
            $fileObj=new \fhx\RecursiveFileFinder( $addons_path."app");
            $fileLst=$fileObj->findFiles();
//
            $base_path=base_path();
            foreach ($fileLst as $file){
               if(is_file($base_path.$file['relative_path'])){
//                   移回插件包里
                   rename ($base_path.$file['relative_path'],$file['path']);
               }
            }
        }
        if(is_dir( $addons_path."public")){
//            找到文件夹下的所有文件包括子目录下的文件
            $fileObj=new \fhx\RecursiveFileFinder( $addons_path."public");
            $fileLst=$fileObj->findFiles();
            $public_path=public_path();
            foreach ($fileLst as $file){
                if(is_file($public_path.$file['relative_path'])){
//                   移回插件包里
                    rename ($public_path.$file['relative_path'],$file['path']);
                }
            }
        }
        $addonslist = get_addons_list();
        $addonslist[$name]['status'] = 0;
        $addonslist[$name]['install'] = 0;
        Cache::set('addonslist',  json_encode($addonslist));

        $class->setInfo($name,$addonslist[$name]);
        // 清除 config/addons.php 中该插件的路由映射
        $this->clearAddonRoutes($name);
        $this->clearCache();
        $this->success("插件卸载成功");
    }

    public function upgrade(){
//        插件升级
        if (!function_exists('fsockopen')) {
            $this->error('本站：PHP环境不支持fsockopen，升级失败');
        }
        $name=input('name', '','trim');
        $no=input('no', '','trim');
        if(empty($name)){
            $this->error('请选择要升级的插件');
        }
        if(empty($no)){
            $this->error('请求非法');
        }
        // 执行下载文件
        $tempPath=app()->getRuntimePath().'addons';

        if(!is_dir($tempPath)){
            mkdir($tempPath,0777,true);
        }


        set_time_limit(0);

        $domain=$_SERVER['HTTP_HOST'];
        $license=$this->system['license_key'];
        if(empty($license)){
            $this->error('授权使用联系微信:zrwx978');
        }
//        domain={$domain}&license={$license}&build={$version['build']}&php=".PHP_VERSION."&no={$version['no']}
        $class = get_addons_instance($name);
        $addonInfo=$class->getInfo();
        unset($addonInfo['url']);
        if($addonInfo['status']!=1){
            $this->error("插件未安装，升级失败");
        }
        $data=[
            'domain'=>$domain,
            'license'=>$license,
            'build'=>empty($addonInfo['build'])?'2025-08-11 22:43:03':$addonInfo['build'],
            'php'=>PHP_VERSION,
            'no'=>$no
        ];
        $res=httpRequest('https://cloud.laikephp.com/index/fhx.addon/upgrade','POST',$data);
        $res_arr=json_decode($res,true);
        $addons_path=app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR .$name.DIRECTORY_SEPARATOR;
        if($res_arr){
            if($res_arr['code']==1){
                $url=isset($res_arr['url'])?$res_arr['url']:'';
                if($url){
                    $remoteUrl=strpos($url,'/')===0?config('fhx.api_domain').$url:$url;
//进入文件下载

                    $localFile = $tempPath.DIRECTORY_SEPARATOR.basename($url); // 临时存储路径

                    $ch = curl_init($remoteUrl);
                    $fp = fopen($localFile, 'wb');

                    if (!$fp) {
                        $this->error('无法创建临时文件');
                    }

                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36');

                    if (curl_exec($ch) === false) {
                        unlink($localFile);
                        $this->error('下载失败: ' . curl_error($ch));
                    }
                    curl_close($ch);
                    fclose($fp);

                    //下载完成执行解压
                    if (!class_exists('ZipArchive')) {
                        $this->error('PHP环境解压类ZipArchive没有开启，升级失败');
                    }

                    $zip = new \ZipArchive;//新建一个ZipArchive的对象
                    /*
                    通过ZipArchive的对象处理zip文件
                    $zip->open这个方法的参数表示处理的zip文件名。
                    如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
                    */
                    if ($zip->open($localFile) === TRUE) {
                        $zip->extractTo(app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR);//解压缩到某路径下
                        $zip->close();//关闭处理的zip文件
                        $updateSql= $addons_path.'update/'.$res_arr['version'].'.sql';
                        $menu= $addons_path.'update/menu_'.$res_arr['version'].'.php';
                        if(is_file($menu)){
                            $menu_config=require_once($menu);
                            if(!empty($menu_config)){
                                $addonService=new \app\admin\service\AddonService();
                                $addonService->updateMenu($menu_config,$menu_config[0]['pid'],$name);
                            }

                        }
                        if(is_file($updateSql)){
                            \tools\Hs::sql($updateSql);

                        }
                        unlink($localFile);
                        $msg='升级完成';
                        if($res_arr['count']>1){
                            $msg="升级到版本{$res_arr['version']}成功";
                        }
                        $this->clearCache();
                        $this->success($msg,null,['count'=>$res_arr['count'],'version'=>$res_arr['version']]);
                    }else{
                        unlink($localFile);
                        $this->error('打开升级包失败');
                    }

                }
                $this->success($res_arr['msg']);
            }else{
                $this->error($res_arr['msg']);
            }
        }else{
            $this->error($res);
        }
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