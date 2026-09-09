<?php

namespace app\install\controller;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Env;
use think\facade\View;

require_once __DIR__ . '/../common.php';

class Index extends BaseController
{
    protected function initialize()
    {
        if (web_is_installed()) {
            $this->error('网站已经安装', '/');
        }

        if (!is_writable(app()->getRootPath(). 'config')) {
            abort(500, '目录' . realpath(app()->getRootPath(). 'config') . '无法写入！');
        }

    }

    protected function _initializeView()
    {
        config('template.view_path', dirname(__DIR__) . '/view/');
    }

    // 安装首页
    public function index()
    {
        return View::fetch(":index");
    }

    public function step2()
    {

        $data = [];
        $data['phpversion'] = @phpversion();
        $data['os'] = PHP_OS;
        $tmp = function_exists('gd_info') ? gd_info() : [];
//        $server             = $_SERVER["SERVER_SOFTWARE"];
//        $host               = $this->request->host();
//        $name               = $_SERVER["SERVER_NAME"];
//        $max_execution_time = ini_get('max_execution_time');
//        $allow_reference    = (ini_get('allow_call_time_pass_reference') ? '<font color=green>[√]On</font>' : '<font color=red>[×]Off</font>');
//        $allow_url_fopen    = (ini_get('allow_url_fopen') ? '<font color=green>[√]On</font>' : '<font color=red>[×]Off</font>');
//        $safe_mode          = (ini_get('safe_mode') ? '<font color=red>[×]On</font>' : '<font color=green>[√]Off</font>');

        $err = 0;


        if (class_exists('pdo')) {
            $data['pdo'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['pdo'] = '<i class="fa fa-remove error"></i> 未开启';
            $err++;

        }

        if (extension_loaded('pdo_mysql')) {
            $data['pdo_mysql'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['pdo_mysql'] = '<i class="fa fa-remove error"></i> 未开启';
            $err++;

        }
        if (function_exists('fsockopen')) {
            $data['fsockopen'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['fsockopen'] = '<i class="fa fa-remove error"></i> 未开启';
            $err++;

        }

        if (extension_loaded('curl')) {
            $data['curl'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['curl'] = '<i class="fa fa-remove error"></i> 未开启';
            $err++;
        }

        if (extension_loaded('gd')) {
            $data['gd'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['gd'] = '<i class="fa fa-remove error"></i> 未开启';
            if (function_exists('imagettftext')) {
                $data['gd'] .= '<br><i class="fa fa-remove error"></i> FreeType Support未开启';
            }
            $err++;
        }

        if (extension_loaded('mbstring')) {
            $data['mbstring'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['mbstring'] = '<i class="fa fa-remove error"></i> 未开启';
            if (function_exists('imagettftext')) {
                $data['mbstring'] .= '<br><i class="fa fa-remove error"></i> FreeType Support未开启';
            }
            $err++;
        }

        if (extension_loaded('fileinfo')) {
            $data['fileinfo'] = '<i class="fa fa-check correct"></i> 已开启';
        } else {
            $data['fileinfo'] = '<i class="fa fa-remove error"></i> 未开启';
            $err++;
        }

        if (ini_get('file_uploads')) {
            $data['upload_size'] = '<i class="fa fa-check correct"></i> ' . ini_get('upload_max_filesize');
        } else {
            $data['upload_size'] = '<i class="fa fa-remove error"></i> 禁止上传';
        }

        if (function_exists('session_start')) {
            $data['session'] = '<i class="fa fa-check correct"></i> 支持';
        } else {
            $data['session'] = '<i class="fa fa-remove error"></i> 不支持';
            $err++;
        }

        if (version_compare(phpversion(), '5.6.0', '>=') && version_compare(phpversion(), '7.0.0', '<') && ini_get('always_populate_raw_post_data') != -1) {
            $data['always_populate_raw_post_data'] = '<i class="fa fa-remove error"></i> 未关闭';
            $data['show_always_populate_raw_post_data_tip'] = true;
            $err++;
        } else {

            $data['always_populate_raw_post_data'] = '<i class="fa fa-check correct"></i> 已关闭';
        }

        $folders = [
            realpath(app()->getRootPath() .'public'.DIRECTORY_SEPARATOR.'upload') . DIRECTORY_SEPARATOR,
            realpath(app()->getRootPath() . 'config') . DIRECTORY_SEPARATOR,
            realpath(app()->getRootPath() . 'runtime') . DIRECTORY_SEPARATOR,
        ];
        $newFolders = [];
        foreach ($folders as $dir) {
            $testDir = $dir;
            fhy_dir_create($testDir);
            if (fhy_testwrite($testDir)) {
                $newFolders[$dir]['w'] = true;
            } else {
                $newFolders[$dir]['w'] = false;
                $err++;
            }
            if (is_readable($testDir)) {
                $newFolders[$dir]['r'] = true;
            } else {
                $newFolders[$dir]['r'] = false;
                $err++;
            }
        }
        $data['folders'] = $newFolders;
        $data['err'] = $err;

        View::assign($data);
        return View::fetch(":step2");
    }

    public function step3()
    {
        return View::fetch(":step3");
    }

    public function step4()
    {
        session(null);
        if ($this->request->isPost()) {
            //创建数据库
            $dbConfig = [];
            $dbConfig['type'] = "mysql";
            $dbConfig['hostname'] = $this->request->param('dbhost','','trim');
            $dbConfig['username'] = $this->request->param('dbuser','','trim');
            $dbConfig['password'] = $this->request->param('dbpw','','trim');
            $dbConfig['hostport'] = $this->request->param('dbport','','trim');
            $dbConfig['charset'] = $this->request->param('dbcharset', 'utf8mb4');

            $userLogin = $this->request->param('manager');
            $userPass = $this->request->param('manager_pwd');
            $userEmail = $this->request->param('manager_email');
            //检查密码。空 6-32字符。
            empty($userPass) && $this->error("密码不可以为空");
            strlen($userPass) < 6 && $this->error("密码长度最少6位");
            strlen($userPass) > 32 && $this->error("密码长度最多32位");

            $this->updateDbConfig($dbConfig);
            $db     = Db::connect('install_db');
            $dbName = $this->request->param('dbname');
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET " . $dbConfig['charset'];
            $db->execute($sql);

            $dbConfig['database'] = $dbName;

            $dbConfig['prefix'] = $this->request->param('dbprefix', '', 'trim');

            session('install.db_config', $dbConfig);

//            $sql = web_split_sql(dirname(__DIR__) . '/data/web.sql', $dbConfig['prefix'], $dbConfig['charset']);


//            session('install.sql', $sql);

//            View::assign('sql_count', count($sql));
            View::assign('sql_count', 1);

            session('install.error', 0);

            $siteName = $this->request->param('sitename');
            $seoKeywords = $this->request->param('sitekeywords');
            $siteInfo = $this->request->param('siteinfo');

            session('install.site_info', [
                'site_name' => $siteName,
                'site_seo_title' => $siteName,
                'site_seo_keywords' => $seoKeywords,
                'site_seo_description' => $siteInfo
            ]);

            session('install.admin_info', [
                'username' => $userLogin,
                'pwd' => $userPass,
                'email' => $userEmail
            ]);

            return View::fetch(":step4");

        }
    }

    public function install()
    {
        $dbConfig = session('install.db_config');
//        $sql = session('install.sql');

      if (empty($dbConfig)) {
            $this->error("非法安装!");
        }

//        $sqlIndex = $this->request->param('sql_index', 0, 'intval');

            $sql=file_get_contents(dirname(__DIR__) . '/data/web.sql');
        $sql = str_replace(" `ymwl_", " `{$dbConfig['prefix']}", $sql);
        if($dbConfig['charset'] != 'utf8mb4'){
            // 全局替换编码,兼容 CHARSET=utf8mb4、COLLATE=utf8mb4_unicode_ci 等无空格写法
            $sql = str_replace("utf8mb4", $dbConfig['charset'], $sql);
        }

        $this->updateDbConfig($dbConfig);
        $instance     = Db::connect('install_db');

            // 查询一次SQL,判断连接是否正常
         $instance->execute("SELECT 1");
        $instance->getPdo()->exec($sql);
//        thinkphp5.0可直接getPdo
//        $instance->getPdo()->exec($sql);
            // 调用原生PDO对象进行批量查询 getPdo()

//            $installError = session('install.error');
        session("install.step",4);
        Cache::clear();
            $this->success("安装完成!", '', ['done' => 1, 'error' => 0]);


    }

    public function setDbConfig()
    {
        $dbConfig = session('install.db_config');

        $result = fhy_create_db_config($dbConfig);

        if ($result) {
            $this->success("数据配置文件写入成功!");
        } else {
            $this->error("数据配置文件写入失败!");
        }
    }

    public function setSite()
    {
        $dbConfig = session('install.db_config');

        if (empty($dbConfig)) {
            $this->error("非法安装!");
        }
        $siteInfo = session('install.site_info');
        $admin = session('install.admin_info');
        $admin['salt']=rand_string(12);
        $admin['pwd'] = md5($admin['pwd'] .$admin['salt']);
        $admin['group_id'] = 1;
        $admin['ip'] = getRealIp();
        $admin['add_time'] = time();
        $admin['is_open'] = 1;

        try {
            Db::name('admin')->where(['admin_id' => 1])->update($admin);
           /* Db::name('system')->where(['id' => 1])->update([
                'name' => $siteInfo['site_name'],
//                'site_abbr' => $siteInfo['site_seo_title']
                'site_abbr' => $siteInfo['site_seo_keywords'],
//                'des' => $siteInfo['site_seo_description']
            ]);*/
            \think\facade\Db::name('system_config')->where('field','=','name')->update(['value'=>$siteInfo['site_name']]);
            \think\facade\Db::name('system_config')->where('field','=','site_abbr')->update(['value'=>$siteInfo['site_seo_keywords']]);
            // 重新生成安装唯一标识(UUID v4),避免所有站点共用web.sql中的固定install_id(离线授权使用)
            $bytes = random_bytes(16);
            $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
            $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
            $installId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
            \think\facade\Db::name('system_config')->where('field','=','install_id')->update(['value'=>$installId, 'update_time'=>time()]);
            // 为当前部署生成独立的 JWT 签名密钥(app_key),写入 config/secret.php,避免所有站点共用相同密钥
            // HS256 要求密钥至少 32 字节,这里生成 64 位十六进制(256 位)
            fhy_update_secret('app_key', bin2hex(random_bytes(32)));
        } catch (\Exception $e) {
            $this->error("网站创建失败!" . $e->getMessage());
        }

        $this->success("网站创建完成!");

    }










    protected function _deleteDir($R)
    {
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            if ($item != '.' and $item != '..') {
                if (is_dir($R . '/' . $item)) {
                    $this->_deleteDir($R . '/' . $item);
                } else {
                    unlink($R . '/' . $item);
                }
            }
        }
        closedir($handle);
        return rmdir($R);
    }

    public function step5()
    {
        if (session("install.step") == 4) {
//            $this->_deleteDir(app()->getRootPath().'runtime/admin');
            @touch(app()->getRootPath() . 'config/install.lock');
            return View::fetch(":step5");
        } else {
            $this->error("非法安装！");
        }
    }

    public function testDbPwd()
    {
        if ($this->request->isPost()) {
            $dbConfig = $this->request->param();
            $dbConfig['type'] = "mysql";

            $supportInnoDb = false;

            try {
                $this->updateDbConfig($dbConfig);
                $engines = Db::connect('install_db')->query("SHOW ENGINES;");

                foreach ($engines as $engine) {
                    if ($engine['Engine'] == 'InnoDB' && $engine['Support'] != 'NO') {
                        $supportInnoDb = true;
                        break;
                    }
                }
            } catch (\Exception $e) {
                $this->error('数据库账号或密码不正确！' . $e->getMessage());
            }
            if ($supportInnoDb) {
                $this->success('验证成功！');
            } else {
                $this->error('数据库账号密码验证通过，但不支持InnoDb!');
            }
        } else {
            $this->error('非法请求方式！');
        }

    }

    public function testDataExist()
    {
        if ($this->request->isPost()) {
            $dbConfig = $this->request->param();
            $dbConfig['type'] = "mysql";
            $canCreateDbAndImportData = false;
            try {
                //检查 cmf_admin_menu
                $table = $dbConfig['dbprefix'] . "admin_menu";
                $this->updateDbConfig($dbConfig);
                $tableExist = Db::connect('install_db')->query("show tables like '" . $table . "';");
                if ($tableExist) {
                    $dataExist = Db::connect('install_db')->query("select * from " . $table . " where 1;");
                    //存在数据，则警告
                    if (is_array($dataExist) && count($dataExist) > 0) {
                        $canCreateDbAndImportData = false;
                    } else {
                        $canCreateDbAndImportData = true;
                    }
                } else {
                    $canCreateDbAndImportData = true;
                }
            } catch (\Exception $e) {
                $this->success('验证成功！');
            }
            if ($canCreateDbAndImportData) {
                $this->success('验证成功！');
            } else {
                $this->error('配置的数据库存在数据,请更换数据库或者清空数据');
            }
        } else {
            $this->error('非法请求方式！');
        }

    }

    public function testRewrite()
    {
        $this->success('success');
    }

    private function updateDbConfig($dbConfig)
    {
        $oldDbConfig                              = config('database');
        $oldDbConfig['connections']['install_db'] = $dbConfig;
        config($oldDbConfig, 'database');
    }

}

