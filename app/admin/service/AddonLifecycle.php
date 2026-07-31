<?php

declare(strict_types=1);

namespace app\admin\service;

use Exception;
use Throwable;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Lang;

/**
 * 插件生命周期服务
 *
 * 负责编排插件的安装与卸载完整流程，包括：前置检查、数据库事务、
 * 菜单处理、SQL 执行、文件移动/清理以及缓存更新。
 */
class AddonLifecycle
{
    /**
     * 老插件白名单：表命名规范校验豁免（增量兼容，不影响已安装插件）
     * @var array
     */
    protected array $legacyAddons = ['wechat', 'database', 'databaseapp'];

    /**
     * 插件下载服务
     * @var AddonDownloader
     */
    protected AddonDownloader $downloader;

    /**
     * 插件文件管理服务
     * @var AddonFileManager
     */
    protected AddonFileManager $fileManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->downloader  = new AddonDownloader();
        $this->fileManager = new AddonFileManager();
    }

    /**
     * 安装插件
     *
     * @param string $name 插件标识名
     * @param string|null $no 插件授权编号（云端下载时使用）
     * @return void
     * @throws Exception
     */
    public function install(string $name, ?string $no = null): void
    {
        $addonsPath = $this->getAddonsPath($name);
        $addonFile  = $addonsPath . 'Plugin.php';

        // 插件目录不存在时从云端下载
        if (!file_exists($addonFile)) {
            if (empty($no)) {
                throw new Exception('插件安装失败，请联系作者');
            }
            $license = $this->getLicenseKey();
            $this->downloader->download($name, $no, $license);
        }

        $class     = get_addons_instance($name);
        $addonInfo = $class->getInfo();

        if (isset($addonInfo['status']) && $addonInfo['status'] == 1) {
            throw new Exception('插件已安装，无需重新安装');
        }

        // 版本兼容性检查（info.json 未声明兼容性字段时自动跳过）
        $this->checkCompatibility($name, $addonInfo);

        $result = $class->install();
        if ($result === false) {
            throw new Exception('插件安装失败');
        }

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // 第一阶段：数据库事务（菜单 + install.sql）
        try {
            Db::startTrans();

            // 添加菜单
            $menuConfig = get_addons_menu($name);
            if (!empty($menuConfig)) {
                $addonService = new AddonService();
                $addonService->addAddonMenu($menuConfig, $menuConfig[0]['pid'], $name);
            }

            // 执行安装 SQL
            $createdTables = [];
            $sqlFile = $addonsPath . 'install.sql';
            if (is_file($sqlFile)) {
                $prefix     = getDataBaseConfig('prefix');
                $sqlContent = file_get_contents($sqlFile) ?: '';
                // 校验插件表命名规范（新插件强制 addon_{name}_ 前缀，老插件白名单豁免）
                $this->checkTableNaming($sqlContent, $name);
                $createdTables = $this->parseCreatedTables($sqlContent);
                $this->executeSqlFile($sqlFile, $prefix);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 记录插件创建的数据表清单，供卸载时兜底清理
        if (!empty($createdTables)) {
            file_put_contents(
                $addonsPath . 'db_map.json',
                json_encode(['version' => 1, 'tables' => $createdTables], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }

        // 第二阶段：移动插件文件到项目目录
        $fileMap = [];
        try {
            $result = $this->fileManager->move($addonsPath . 'app', root_path() . 'app');
            // 路径加回 app/ 前缀，使 moveBack 时能正确定位到 addonsPath/app/ 和 root_path()/app/
            $appPrefix = 'app' . DIRECTORY_SEPARATOR;
            foreach ($result['map'] as $relPath => $_) {
                $fileMap[$appPrefix . $relPath] = $appPrefix . $relPath;
            }

            $result = $this->fileManager->move($addonsPath . 'public', root_path() . 'public');
            $publicPrefix = 'public' . DIRECTORY_SEPARATOR;
            foreach ($result['map'] as $relPath => $_) {
                $fileMap[$publicPrefix . $relPath] = $publicPrefix . $relPath;
            }

            // 记录文件映射，供卸载时移回文件
            if (!empty($fileMap)) {
                $mapData = [
                    'version' => 1,
                    'files'   => $fileMap,
                ];
                file_put_contents(
                    $addonsPath . 'file_map.json',
                    json_encode($mapData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                );
            }
        } catch (Exception $e) {
            // 清理已写入的 file_map.json
            $mapFile = $addonsPath . 'file_map.json';
            if (is_file($mapFile)) {
                unlink($mapFile);
            }
            throw new Exception('插件文件移动失败: ' . $e->getMessage());
        }

        // 第三阶段：更新配置与缓存
        try {
            Config::set([], $name);

            $addonsList               = get_addons_list();
            $addonInfo['status']      = 1;
            $addonInfo['install']     = 1;
            $addonsList[$name]        = $addonInfo;
            Cache::set('addons_list', json_encode($addonsList));
            $class->setInfo($name, $addonsList[$name]);

            $this->clearCache();
        } catch (Exception $e) {
            // 回滚：将已移动的文件移回插件目录
            if (!empty($fileMap)) {
                $this->fileManager->moveBack($fileMap, root_path(), $addonsPath);
            }
            $mapFile = $addonsPath . 'file_map.json';
            if (is_file($mapFile)) {
                unlink($mapFile);
            }
            throw $e;
        }
    }

    /**
     * 卸载插件
     *
     * @param string $name 插件标识名
     * @return void
     * @throws Exception
     */
    public function uninstall(string $name): void
    {
        $addonsPath = $this->getAddonsPath($name);
        $class      = get_addons_instance($name);

        $result = $class->uninstall();
        if ($result === false) {
            throw new Exception('插件卸载失败');
        }

        // 第一阶段：数据库事务（删除菜单 + uninstall.sql）
        try {
            Db::startTrans();

            $menuConfig = get_addons_menu($name);
            if (!empty($menuConfig)) {
                Db::name('auth_rule')->where('plugin', $name)->delete();
            }

            $sqlFile = $addonsPath . 'uninstall.sql';
            if (is_file($sqlFile)) {
                $prefix = getDataBaseConfig('prefix');
                $this->executeSqlFile($sqlFile, $prefix);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 兜底清理插件残留数据（数据表、自定义字段配置、插件配置及缓存）
        $this->cleanupAddonData($name, $addonsPath);

        // 第二阶段：根据 file_map.json 移回插件文件
        $mapFile = $addonsPath . 'file_map.json';
        if (is_file($mapFile)) {
            $mapData = json_decode(file_get_contents($mapFile), true);
            if (!empty($mapData['files'])) {
                $this->fileManager->moveBack($mapData['files'], root_path(), $addonsPath);
            }
//            unlink($mapFile);
        }

        // 第三阶段：更新插件列表缓存与路由映射
        $addonsList                  = get_addons_list();
        $addonsList[$name]['status'] = 0;
        $addonsList[$name]['install'] = 0;
        Cache::set('addons_list', json_encode($addonsList));

        $class->setInfo($name, $addonsList[$name]);

        $this->clearAddonRoutes($name);
        $this->clearCache();
    }

    /**
     * 升级插件
     *
     * @param string $name 插件标识名
     * @param string $no 插件授权编号
     * @param string $license 授权码
     * @param string $build 当前插件构建版本
     * @return array 包含 version（升级目标版本）和 count（升级包数量）
     * @throws Exception
     */
    public function upgrade(string $name, string $no, string $license, string $build): array
    {
        $class     = get_addons_instance($name);
        $addonInfo = $class->getInfo();

        if (empty($addonInfo['status']) || $addonInfo['status'] != 1) {
            throw new Exception('插件未安装，升级失败');
        }

        $result = $this->downloader->downloadUpgrade($name, $no, $license, $build);

        $addonsPath = $result['path'] . DIRECTORY_SEPARATOR;
        $version    = $result['version'];
        $count      = $result['count'];

        // 版本兼容性检查：升级包解压后 info.json 已更新，直接读取文件获取最新声明
        // （不用 getInfo() 避免取到同请求内的旧缓存）
        $this->checkCompatibility($name);

        // 菜单更新与升级 SQL 纳入事务，保证升级失败时可回滚（DDL 除外）
        try {
            Db::startTrans();

            // 更新菜单
            $menu = $addonsPath . 'update' . DIRECTORY_SEPARATOR . 'menu_' . $version . '.php';
            if (is_file($menu)) {
                $menuConfig = require_once($menu);
                if (!empty($menuConfig)) {
                    $addonService = new AddonService();
                    $addonService->updateMenu($menuConfig, $menuConfig[0]['pid'], $name);
                }
            }

            // 执行升级 SQL
            $updateSql = $addonsPath . 'update' . DIRECTORY_SEPARATOR . $version . '.sql';
            if (is_file($updateSql)) {
                \tools\Hs::sql($updateSql);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        $this->clearCache();

        return ['version' => $version, 'count' => $count];
    }

    /**
     * 插件版本兼容性检查
     *
     * 读取插件 info.json 中可选的兼容性声明字段：
     * - require_php：要求的最低 PHP 版本，如 "8.0.0"
     * - require_crm：要求的最低 CRM 系统版本，如 "5.0.0"（对比 config/version.php 的 version）
     * - max_crm：兼容的最高 CRM 系统版本（可选）
     * 未声明对应字段时跳过该项检查，存量老插件不受影响。
     *
     * @param string $name 插件标识名
     * @param array|null $info 插件信息数组，为 null 时自动读取插件目录的 info.json
     * @return void
     * @throws Exception 版本不满足要求时抛出
     */
    public function checkCompatibility(string $name, ?array $info = null): void
    {
        if ($info === null) {
            $infoFile = $this->getAddonsPath($name) . 'info.json';
            $info     = is_file($infoFile) ? json_decode((string)file_get_contents($infoFile), true) : [];
            if (!is_array($info)) {
                $info = [];
            }
        }

        // PHP 版本检查
        if (!empty($info['require_php']) && version_compare(PHP_VERSION, (string)$info['require_php'], '<')) {
            throw new Exception("插件 {$name} 要求 PHP >= {$info['require_php']}，当前 PHP 版本为 " . PHP_VERSION);
        }

        // CRM 系统版本检查
        $crmVersion = (string)(config('version.version') ?: '');
        if ($crmVersion !== '') {
            if (!empty($info['require_crm']) && version_compare($crmVersion, (string)$info['require_crm'], '<')) {
                throw new Exception("插件 {$name} 要求系统版本 >= {$info['require_crm']}，当前系统版本为 {$crmVersion}，请先升级系统");
            }
            if (!empty($info['max_crm']) && version_compare($crmVersion, (string)$info['max_crm'], '>')) {
                throw new Exception("插件 {$name} 最高兼容系统版本 {$info['max_crm']}，当前系统版本为 {$crmVersion}，插件可能不兼容");
            }
        }
    }

    /**
     * 校验 install.sql 中的表命名规范
     *
     * 新插件的数据表必须以 addon_{插件名}_ 为前缀（或恰好等于 addon_{插件名}），
     * 老插件在 $legacyAddons 白名单中豁免。
     *
     * @param string $sql install.sql 文件内容
     * @param string $name 插件标识名
     * @return void
     * @throws Exception
     */
    private function checkTableNaming(string $sql, string $name): void
    {
        if (in_array($name, $this->legacyAddons)) {
            return;
        }
        $tables = $this->parseCreatedTables($sql);
        foreach ($tables as $table) {
            if ($table !== 'addon_' . $name && strpos($table, 'addon_' . $name . '_') !== 0) {
                throw new Exception("插件数据表 {$table} 不符合命名规范，表名必须以 addon_{$name}_ 开头");
            }
        }
    }

    /**
     * 解析 SQL 中所有 CREATE TABLE 的表名（不含前缀）
     *
     * @param string $sql SQL 内容
     * @return array 表名列表（已去除 ymwl_ 占位前缀）
     */
    private function parseCreatedTables(string $sql): array
    {
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`ymwl_([a-zA-Z0-9_]+)`/i', $sql, $matches);
        return array_values(array_unique($matches[1]));
    }

    /**
     * 卸载时兜底清理插件残留数据
     *
     * 清理内容：
     * 1. db_map.json 记录的表清单 + addon_{name} 前缀扫描到的残留表
     * 2. system_field 中该插件表的自定义字段配置及对应字段缓存
     * 3. addon_config 表中该插件的独立配置及缓存
     *
     * @param string $name 插件标识名
     * @param string $addonsPath 插件目录绝对路径
     * @return void
     */
    private function cleanupAddonData(string $name, string $addonsPath): void
    {
        $prefix = getDataBaseConfig('prefix');
        $tables = [];

        // 1. 读取安装时记录的表清单
        $dbMapFile = $addonsPath . 'db_map.json';
        if (is_file($dbMapFile)) {
            $dbMap = json_decode(file_get_contents($dbMapFile), true);
            if (!empty($dbMap['tables']) && is_array($dbMap['tables'])) {
                $tables = $dbMap['tables'];
            }
        }

        // 2. 按 addon_{name} 前缀扫描兜底（防止 db_map.json 丢失或清单不全）
        $like = str_replace('_', '\_', $prefix . 'addon_' . $name) . '%';
        $rows = Db::query("SHOW TABLES LIKE '{$like}'");
        foreach ($rows as $row) {
            $tables[] = substr((string)current($row), strlen($prefix));
        }
        $tables = array_values(array_unique($tables));

        // 3. 删除数据表
        foreach ($tables as $table) {
            Db::execute('DROP TABLE IF EXISTS `' . $prefix . $table . '`');
        }

        // 4. 清理 system_field 中该插件表的自定义字段配置及字段缓存
        if (!empty($tables)) {
            try {
                Db::name('system_field')->whereIn('table', $tables)->delete();
                $defaultWhere = [['list', '=', 1]];
                foreach ($tables as $table) {
                    Cache::delete($table . '_fields_' . md5(serialize($defaultWhere)));
                }
            } catch (Throwable $e) {
                // system_field 清理失败不阻断卸载流程
            }
        }

        // 5. 清理插件独立配置及缓存
        try {
            Db::name('addon_config')->where('addon', $name)->delete();
            Cache::delete('addon_config_' . $name);
        } catch (Throwable $e) {
            // addon_config 表尚未创建时忽略
        }

        // 6. 删除表清单文件
        if (is_file($dbMapFile)) {
            unlink($dbMapFile);
        }
    }

    /**
     * 获取插件目录路径
     *
     * @param string $name 插件标识名
     * @return string 插件目录绝对路径（含尾部 DIRECTORY_SEPARATOR）
     */
    protected function getAddonsPath(string $name): string
    {
        return app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取授权码
     *
     * @return string
     */
    protected function getLicenseKey(): string
    {
        return Db::name('system_config')
            ->where('field', 'license_key')
            ->where('status', '=', 1)
            ->value('value') ?: '';
    }

    /**
     * 执行 SQL 文件
     *
     * 清理注释与空行后，替换表前缀并执行。
     *
     * @param string $file SQL 文件绝对路径
     * @param string $prefix 数据库表前缀
     * @return void
     * @throws Exception
     */
    private function executeSqlFile(string $file, string $prefix): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new Exception('读取 SQL 文件失败: ' . $file);
        }

        $sql = preg_replace([
            '/^--.*$/m',     // 删除注释行
            '/^\s*$/m',      // 删除空行
            '/\n+/',         // 合并连续换行
        ], '', $sql);
        $sql = trim($sql);

        if (empty($sql)) {
            return;
        }

        $sql = str_replace('`ymwl_', '`' . $prefix, $sql);

        Db::connect('mysql')->getPdo()->exec($sql);
    }

    /**
     * 清除 config/addons.php 中指定插件的路由映射
     *
     * @param string $name 插件标识名
     * @return void
     */
    private function clearAddonRoutes(string $name): void
    {
        $addonsConfig = config('addons');
        if (!empty($addonsConfig['route'])) {
            $pluginPrefix = $name . '/';
            $modified     = false;
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
     * 清除菜单与插件列表缓存
     *
     * @return void
     */
    private function clearCache(): void
    {
        $lang    = Lang::getLangSet();
        $adminId = session('admin.admin_id') ?? 0;
        $cacheKey = 'initAdmin_' . $adminId . $lang;

        Cache::delete($cacheKey);
        Cache::delete('addons_list');
    }
}
