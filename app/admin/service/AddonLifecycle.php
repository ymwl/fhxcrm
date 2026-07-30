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
            $sqlFile = $addonsPath . 'install.sql';
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

        $this->clearCache();

        return ['version' => $version, 'count' => $count];
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
