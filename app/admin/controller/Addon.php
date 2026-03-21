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


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Addon();
        
    }
    public function index()
    {
        if ($this->request->isAjax()) {
            //获取插件列表数据
            $addons=get_addons_list();
//            把$addons同步到数据库 中
//            每天同步一次互联网上插件数据
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
                $res=httpRequest('https://cloud.laikephp.com/index/fhx.addon/index','POST',$data);
                $res_arr=json_decode($res,true);

                if($res_arr){
                    if($res_arr['code']==1){
                        $this->model->net_sync($res_arr['data']);
                        Cache::set('addons_sync',1,86400);
                    }else{
                        $data = [
                            'code'  => -1,
                            'msg'   =>$res_arr['msg'],
                        ];
                        return json($data);
                    }
                }else{
                    $data = [
                        'code'  => -1,
                        'msg'   => $res,
                    ];
                    return json($data);
                }
            }



            $scope=$this->request->get('scope', 'local','trim');
            list($page, $limit, $where,$sort) = $this->buildTableParames();

            if($scope=='net'){
                $where[]=['net_version','<>',''];
//               判断今天是否同步过
            }else{
                $where[]=['version','<>',''];
                $this->model->sync($addons);
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



        $addonFile = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR .$name.DIRECTORY_SEPARATOR. 'Plugin.php';
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
//        var_dump($res);
            $res_arr=json_decode($res,true);
            if($res_arr){
                if($res_arr['code']==1){
                    $url=isset($res_arr['url'])?$res_arr['url']:'';
                    if($url){
                        $remoteUrl=strpos($url,'/')===0?config('fhx.api_domain').$url:$url;
//进入文件下载

                        $localFile = $tempPath.DIRECTORY_SEPARATOR.basename($url); // 临时存储路径

//                    var_dump($remoteUrl);
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
        //添加菜单
        $menu_config=get_addons_menu($name);
        if(!empty($menu_config)){
            $addonService=new \app\admin\service\AddonService();
            $addonService->addAddonMenu($menu_config,$menu_config[0]['pid'],$name);
        }
        try {
            //        安装数据库
            $sqlFile = $class->addon_path . 'install.sql';
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
//                \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
            }
        }catch (\Exception $e) {
                $this->error($e->getMessage());
        }catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
        if(is_dir($class->addon_path."app")){
            \tools\hs::copydirs($class->addon_path."app", root_path().'app');
        }
        if(is_dir($class->addon_path."public")){
            \tools\hs::copydirs($class->addon_path."public", root_path().'public');
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
     * 插件的卸载
     */
    public function uninstall($name){
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
        $sqlFile = $class->addon_path . 'uninstall.sql';
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

        if(is_dir($class->addon_path."app")){
//            找到文件夹下的所有文件包括子目录下的文件
            $fileObj=new \fhx\RecursiveFileFinder($class->addon_path."app");
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
        if(is_dir($class->addon_path."public")){
//            找到文件夹下的所有文件包括子目录下的文件
            $fileObj=new \fhx\RecursiveFileFinder($class->addon_path."public");
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
//        var_dump($res);
        $res_arr=json_decode($res,true);
        if($res_arr){
            if($res_arr['code']==1){
                $url=isset($res_arr['url'])?$res_arr['url']:'';
                if($url){
                    $remoteUrl=strpos($url,'/')===0?config('fhx.api_domain').$url:$url;
//进入文件下载

                    $localFile = $tempPath.DIRECTORY_SEPARATOR.basename($url); // 临时存储路径

//                    var_dump($remoteUrl);
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
                        $updateSql=$class->addon_path.'update/'.$res_arr['version'].'.sql';
                        $menu=$class->addon_path.'update/menu_'.$res_arr['version'].'.php';
                        if(is_file($menu)){
                            $menu_config=require_once($menu);
                            if(!empty($menu_config)){
                                $addonService=new \app\admin\service\AddonService();
                                $addonService->updateMenu($menu_config,$menu_config[0]['pid'],$name);
                            }

                        }
                        if(is_file($updateSql)){
                            \tools\hs::sql($updateSql);

                        }
                        unlink($localFile);
                        $msg='升级完成';
                        if($res_arr['count']>1){
                            $msg="升级到版本{$res_arr['version']}成功";
                        }
                        $this->clearCache();
                        $this->success($msg,'',['count'=>$res_arr['count'],'version'=>$res_arr['version']]);
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

    protected function clearCache()
    {
        $lang = Lang::getLangSet();
        $cache_key='initAdmin_' .$this->admin['admin_id'].$lang;
//        清除菜单缓存
        Cache:: delete($cache_key);
        Cache:: delete('addons_list');
    }

    
}