<?php

    /**
     * 获取网站根目录
     * @return string 网站根目录
     */
    function web_get_root()
    {
        $root = request()->root();
        $root = str_replace("//", '/', $root);
        $root = str_replace('/index.php', '', $root);
        if (defined('APP_NAMESPACE') && APP_NAMESPACE == 'api') {
            $root = preg_replace('/\/api(.php)$/', '', $root);
        }

        $root = rtrim($root, '/');

        return $root;
    }

function fhy_testwrite($d)
{
    $tfile = "_test.txt";
    $fp = @fopen($d . "/" . $tfile, "w");
    if (!$fp) {
        return false;
    }
    fclose($fp);
    $rs = @unlink($d . "/" . $tfile);
    if ($rs) {
        return true;
    }
    return false;
}

function fhy_dir_create($path, $mode = 0777)
{
    if (is_dir($path))
        return true;
    $ftp_enable = 0;
    $path = fhy_dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir))
            continue;
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}

function fhy_dir_path($path)
{
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

function fhy_execute_sql($db, $sql)
{
    $sql = trim($sql);
    preg_match('/CREATE TABLE .+ `([^ ]*)`/', $sql, $matches);
    if ($matches) {
        $table_name = $matches[1];
        $msg = "创建数据表{$table_name}";
        try {
            $db->execute($sql);
            return [
                'error' => 0,
                'message' => $msg . ' 成功！'
            ];
        } catch (\Exception $e) {
            return [
                'error' => 1,
                'message' => $msg . ' 失败！',
                'exception' => $e->getTraceAsString()
            ];
        }

    } else {
        try {
            $db->execute($sql);
            return [
                'error' => 0,
                'message' => 'SQL执行成功!'
            ];
        } catch (\Exception $e) {
            return [
                'error' => 1,
                'message' => 'SQL执行失败！',
                'exception' => $e->getTraceAsString()
            ];
        }
    }
}

/**
 * 显示提示信息
 * @param string $msg 提示信息
 */
function fhy_show_msg($msg, $class = '')
{
    echo "<script type=\"text/javascript\">showmsg(\"{$msg}\", \"{$class}\")</script>";
    flush();
    ob_flush();
}

/**
 * 判断网站是否安装
 * @return bool
 */
function web_is_installed()
{
    static $cmfIsInstalled;
    if (empty($cmfIsInstalled)) {
        $cmfIsInstalled = file_exists(app()->getRootPath() . 'config/' . 'install.lock');
    }
    return $cmfIsInstalled;
}



function fhy_create_db_config($config)
{
    if (is_array($config)) {
        //读取配置内容
        $conf = file_get_contents(__DIR__ . '/data/database.php');

        //替换配置项
        foreach ($config as $key => $value) {
            $conf = str_replace("#{$key}#", $value, $conf);
        }

        $confDir = app()->getRootPath() . 'config/';


        try {

            if (!file_exists($confDir)) {
                mkdir($confDir, 0777, true);
            }
            $result=file_put_contents($confDir . 'database.php', $conf);
            if ($result === false) {
                return false;
            }
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($confDir . 'database.php', true);
            }
           fhy_update_secret('cookie_crypt_key', rand_string(18));
        } catch (\Exception $e) {

            return false;

        }

        return true;

    }
}

/**

 * 更新或追加根目录 .env 文件中的某个配置项
 * 用于安装时为当前部署生成独立的密钥（如 APP_KEY）
 * @param string $key   环境变量名（如 APP_KEY）
 * @param string $value 值
 * @return bool 是否写入成功
 */
function fhy_update_secret($key, $value)
{
    $secretFile = app()->getRootPath() . 'config/secret.php';
    try {
        $data = is_file($secretFile) ? (array)(include $secretFile) : [];
        $data[$key] = $value;
        $content = "<?php\n\n// 部署专用密钥（由安装向导自动生成，勿提交版本库、勿泄露）\nreturn " . var_export($data, true) . ";\n";
        $result = file_put_contents($secretFile, $content);
        if ($result !== false && function_exists('opcache_invalidate')) {
            @opcache_invalidate($secretFile, true);
        }
        return $result !== false;
    } catch (\Throwable $e) {
        return false;
    }
}

function fhy_update_env_value($key, $value)
{
    $envFile = app()->getRootPath() . '.env';
    try {
        $content = file_exists($envFile) ? file_get_contents($envFile) : '';
        if ($content === false) {
            $content = '';
        }
        $line = $key . ' = ' . $value;
        // 已存在同名配置项则替换整行，否则追加到末尾
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=.*$/m', $content)) {
            $content = preg_replace('/^\s*' . preg_quote($key, '/') . '\s*=.*$/m', $line, $content);
        } else {
            $content = rtrim($content, "\r\n");
            $content = ($content === '' ? '' : $content . PHP_EOL) . $line . PHP_EOL;
        }
        $result = file_put_contents($envFile, $content);
        return $result !== false;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 切分SQL文件成多个可以单独执行的sql语句
 * @param        $file            string sql文件路径
 * @param        $tablePre        string 表前缀
 * @param string $charset 字符集
 * @param string $defaultTablePre 默认表前缀
 * @param string $defaultCharset 默认字符集
 * @return array
 */
function web_split_sql($file, $tablePre, $charset = 'utf8mb4', $defaultTablePre = 'ymwl_', $defaultCharset = 'utf8mb4')
{
    if (file_exists($file)) {
        //读取SQL文件
        $sql = file_get_contents($file);
        $sql = str_replace("\r", "\n", $sql);
        $sql = str_replace("BEGIN;\n", '', $sql);//兼容 navicat 导出的 insert 语句
        $sql = str_replace("COMMIT;\n", '', $sql);//兼容 navicat 导出的 insert 语句
        $sql = str_replace($defaultCharset, $charset, $sql);
        $sql = trim($sql);
        //替换表前缀
        $sql = str_replace(" `{$defaultTablePre}", " `{$tablePre}", $sql);
        $sqls = explode(";\n", $sql);
        return $sqls;
    }

    return [];
}
