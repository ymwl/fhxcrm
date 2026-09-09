<?php

declare(strict_types=1);

namespace app\admin\service;

use Exception;
use Throwable;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Lang;
use think\facade\Log;

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
    protected array $legacyAddons = ['wechat', 'database'];

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

            // 幂等保护：先清理该插件历史残留的权限节点（防止卸载不彻底或中途失败后重装重复插入）
            Db::name('auth_rule')->where('plugin', $name)->delete();

            // 添加菜单（直接 require 而非 get_addons_menu()：其内部 include_once
            // 在同一个 PHP 进程内重复调用会返回 true 而非菜单数组，导致重装时取 pid 报错）
            $menuFile = $addonsPath . 'menu.php';
            $menuConfig = is_file($menuFile) ? require $menuFile : [];
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

        // 第二阶段：移动插件文件到项目目录（整体原子，全部移动成功才表示安装成功）
        $fileMap = [];
        try {
            $plans = [
                [
                    'source'    => $addonsPath . 'app',
                    'target'    => root_path() . 'app',
                    'overwrite' => true,
                    'srcPrefix' => 'app' . DIRECTORY_SEPARATOR,
                    'dstPrefix' => 'app' . DIRECTORY_SEPARATOR,
                ],
                [
                    'source'    => $addonsPath . 'public',
                    'target'    => root_path() . 'public',
                    'overwrite' => true,
                    'srcPrefix' => 'public' . DIRECTORY_SEPARATOR,
                    'dstPrefix' => 'public' . DIRECTORY_SEPARATOR,
                ],
            ];

            // 移动手机端前端文件（可选：插件存在 uniapp/ 目录且项目存在 crm_uniapp/ 目录时）
            if (is_dir($addonsPath . 'uniapp') && is_dir(root_path() . 'crm_uniapp')) {
                $plans[] = [
                    'source'    => $addonsPath . 'uniapp',
                    'target'    => root_path() . 'crm_uniapp',
                    'overwrite' => true,
                    'srcPrefix' => 'uniapp' . DIRECTORY_SEPARATOR,
                    'dstPrefix' => 'crm_uniapp' . DIRECTORY_SEPARATOR,
                ];
            }

            // 整体原子移动，全部成功才返回映射；任一步失败 moveBatch 内部已回滚全部已移动文件
            $fileMap = $this->fileManager->moveBatch($plans)['map'];

            // 全部移动成功后才写入映射文件，作为「安装成功」的提交点（原子写入）
            if (!empty($fileMap)) {
                $this->writeMapFileAtomically($addonsPath . 'file_map.json', [
                    'version' => 1,
                    'files'   => $fileMap,
                ]);
            }
        } catch (Exception $e) {
            // 清理可能残留的映射文件，保证映射文件存在性 = 文件已全部移动到项目目录
            $mapFile = $addonsPath . 'file_map.json';
            if (is_file($mapFile)) {
                unlink($mapFile);
            }
            throw new Exception('插件文件移动失败: ' . $e->getMessage());
        }

        // 第三阶段：应用插件补丁、更新配置与缓存
        try {
            // 应用插件补丁（对现有文件的增量修改，如后台框架 JS、uniapp 配置）
            $this->applyPatches($addonsPath . 'patches');

            Config::set([], $name);

            $addonsList               = get_addons_list();
            $addonInfo['status']      = 1;
            $addonInfo['install']     = 1;
            $addonsList[$name]        = $addonInfo;
            Cache::set('addons_list', json_encode($addonsList));
            $class->setInfo($name, $addonsList[$name]);

            $this->clearCache();
        } catch (Exception $e) {
            // 回滚：还原已应用的补丁、将已移动的文件移回插件目录
            $this->revertPatches($addonsPath . 'patches');
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

        // 第零阶段：还原插件补丁（移除对现有文件的增量修改，保证卸载后无残留）
        $this->revertPatches($addonsPath . 'patches');

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

        // 第二阶段：根据 file_map.json 移回插件文件（整体原子，全部移回才表示卸载成功）
        // 账本文件缺失或内容损坏时不做文件移回，直接进入后续卸载流程：
        // 即「整体移回成功才删账本」，账本不存在则不删也不移、安全跳过。
        $mapFile = $addonsPath . 'file_map.json';
        if (is_file($mapFile)) {
            $mapData = json_decode((string)file_get_contents($mapFile), true);
            if (is_array($mapData) && !empty($mapData['files']) && is_array($mapData['files'])) {
                $this->fileManager->moveBack($mapData['files'], root_path(), $addonsPath);
            }
            // 文件全部移回成功后才删除映射文件，作为「卸载成功」的提交点；
            // moveBack 失败会向上抛异常并回滚，此处的 unlink 不会执行，映射文件保持存在。
            unlink($mapFile);
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
     * 应用插件补丁（对现有文件的增量修改）
     *
     * 补丁目录 addons/{name}/patches/ 下每个 *.json 定义一个补丁：
     * - type=marker：文本标记块，在目标文件锚点处插入带标记的代码块，卸载时按标记删除
     * - type=json_structure：JSON 数组结构操作（如 crm_uniapp/pages.json 的分包注册）
     * 已应用过的补丁跳过（幂等），失败抛出异常中止安装。
     *
     * @param string $patchDir 补丁目录绝对路径
     * @return void
     * @throws Exception
     */
    private function applyPatches(string $patchDir): void
    {
        if (!is_dir($patchDir)) {
            return;
        }
        // Windows 下 glob 将反斜杠当作转义符，需统一为正斜杠并补齐目录分隔符
        $patchPattern = rtrim(str_replace('\\', '/', $patchDir), '/') . '/*.json';
        foreach (glob($patchPattern) ?: [] as $patchFile) {
            $patch = json_decode((string)file_get_contents($patchFile), true);
            if (!is_array($patch) || empty($patch['type']) || empty($patch['target'])) {
                throw new Exception('插件补丁格式错误: ' . basename($patchFile));
            }
            $target = root_path() . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $patch['target']);
            if ($patch['type'] === 'marker') {
                $this->applyMarkerPatch($target, $patch, $patchFile);
            } elseif ($patch['type'] === 'json_structure') {
                $this->applyJsonPatch($target, $patch, $patchFile);
            } elseif ($patch['type'] === 'json_object') {
                $this->applyJsonObjectPatch($target, $patch, $patchFile);
            } else {
                throw new Exception('插件补丁类型不支持: ' . $patch['type']);
            }
        }
    }

    /**
     * 应用文本标记块补丁
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义
     * @param string $patchFile 补丁文件名（错误提示用）
     * @return void
     * @throws Exception
     */
    private function applyMarkerPatch(string $target, array $patch, string $patchFile): void
    {
        if (!is_file($target)) {
            throw new Exception('补丁目标文件不存在: ' . $patch['target']);
        }
        $content = (string)file_get_contents($target);
        if (strpos($content, '==== ' . $patch['marker'] . ' BEGIN ====') !== false) {
            return; // 已应用过，跳过
        }
        $inserts = $patch['inserts'] ?? [];
        if (empty($inserts)) {
            throw new Exception('插件补丁缺少 inserts: ' . basename($patchFile));
        }
        foreach ($inserts as $insert) {
            $anchor   = (string)($insert['anchor'] ?? '');
            $position = (string)($insert['position'] ?? 'after');
            if ($anchor === '') {
                throw new Exception('插件补丁缺少锚点: ' . basename($patchFile));
            }
            $match = $this->findAnchorOffset($content, $anchor);
            if ($match === null) {
                throw new Exception('补丁锚点未找到 (' . $anchor . ') 于 ' . $patch['target']);
            }
            [$pos, $len] = $match;
            $block = (string)($insert['content'] ?? '');
            if ($position === 'before') {
                $content = substr($content, 0, $pos) . $block . substr($content, $pos);
            } else {
                $content = substr($content, 0, $pos + $len) . $block . substr($content, $pos + $len);
            }
        }
        file_put_contents($target, $content);
    }

    /**
     * 在目标内容中查找锚点，返回 [偏移, 匹配长度]；找不到返回 null。
     *
     * 锚点字符串中的换行按 LF（\n）书写，而目标文件可能是 LF 或 CRLF（Windows）。
     * 先做精确匹配（快路径），失败后再做换行符不敏感匹配（把锚点换行视为 \r?\n），
     * 避免因换行符差异导致锚点匹配失败中断安装。
     *
     * @param string $content 目标文件内容
     * @param string $anchor  锚点字符串（换行通常为 \n）
     * @return array|null [偏移, 匹配长度]
     */
    private function findAnchorOffset(string $content, string $anchor): ?array
    {
        $pos = strpos($content, $anchor);
        if ($pos !== false) {
            return [$pos, strlen($anchor)];
        }

        // 统一 CRLF -> LF，其余部分按正则字面量转义，换行视为 \r?\n 以兼容两种换行
        $seed    = str_replace("\r\n", "\n", $anchor);
        $pattern = '/' . str_replace("\n", "\r?\n", preg_quote($seed, '/')) . '/';

        if (preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
            return [$m[0][1], strlen($m[0][0])];
        }

        return null;
    }

    /**
     * 应用 JSON 结构补丁（向目标 JSON 文件的数组追加匹配项）
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义
     * @param string $patchFile 补丁文件名（错误提示用）
     * @return void
     * @throws Exception
     */
    private function applyJsonPatch(string $target, array $patch, string $patchFile): void
    {
        if (!is_file($target)) {
            throw new Exception('补丁目标文件不存在: ' . $patch['target']);
        }
        $data = json_decode((string)file_get_contents($target), true);
        if (!is_array($data)) {
            throw new Exception('补丁目标 JSON 解析失败: ' . $patch['target']);
        }
        $path = (string)($patch['array_path'] ?? '');
        if ($path === '' || !isset($data[$path]) || !is_array($data[$path])) {
            throw new Exception('插件补丁数组路径无效: ' . basename($patchFile));
        }
        $matchKey = (string)($patch['match_key'] ?? 'root');
        $item     = $patch['item'] ?? null;
        if (!is_array($item)) {
            throw new Exception('插件补丁缺少 item: ' . basename($patchFile));
        }
        foreach ($data[$path] as $exists) {
            if (is_array($exists) && ($exists[$matchKey] ?? null) === ($item[$matchKey] ?? null)) {
                return; // 已存在，跳过
            }
        }
        $data[$path][] = $item;
        file_put_contents($target, $this->prettyJson($data));
    }

    /**
     * 应用 JSON 对象补丁（向目标 JSON 的对象节点写入指定键）
     *
     * 用于 manifest.json 的 nativePlugins 这类“对象”结构的键增删，安装时写入声明键。
     * 与 json_structure 只支持数组不同，本类型直接对对象键赋值。
     * 空对象 {} 通过对象模式（stdClass）保留，不会被 json_decode(assoc) 转成数组。
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义（含 object_path / key / value）
     * @param string $patchFile 补丁文件名（错误提示用）
     * @return void
     * @throws Exception
     */
    private function applyJsonObjectPatch(string $target, array $patch, string $patchFile): void
    {
        if (!is_file($target)) {
            throw new Exception('补丁目标文件不存在: ' . $patch['target']);
        }
        $data = $this->readJsonObjectFile($target, $patch);
        $path = (string)($patch['object_path'] ?? '');
        $key  = (string)($patch['key'] ?? '');
        if ($path === '' || $key === '') {
            throw new Exception('插件补丁缺少 object_path 或 key: ' . basename($patchFile));
        }
        // 多级路径定位（如 app-plus.nativePlugins）
        $node = $this->resolveJsonPathNode($data, $path, $patchFile, true);
        // 已存在则跳过，保证幂等
        if (property_exists($node, $key)) {
            return;
        }
        // 从原始补丁文件以对象模式读取 value，保留空对象 {} 与嵌套结构
        $raw   = json_decode((string)file_get_contents($patchFile));
        $value = $raw->value ?? null;
        if ($value === null) {
            throw new Exception('插件补丁缺少 value: ' . basename($patchFile));
        }
        $node->{$key} = $value;
        file_put_contents($target, $this->prettyJson($data));
    }

    /**
     * 读取 JSON 文件为对象（剥离块注释后解析）
     *
     * manifest.json 含 HBuilderX 生成的块注释，标准 json_decode 会失败，
     * 此处先剥离注释再按对象模式解析，使空对象 {} 保持为 stdClass。
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义（错误提示用）
     * @return \stdClass
     * @throws Exception
     */
    private function readJsonObjectFile(string $target, array $patch): \stdClass
    {
        $content = (string)file_get_contents($target);
        // 剥离块注释（manifest.json 字符串值内不含 /* */ 序列）
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        $data = json_decode($content);
        if (!is_object($data)) {
            throw new Exception('补丁目标 JSON 解析失败: ' . $patch['target']);
        }
        return $data;
    }

    /**
     * 按点号分隔的多级路径定位 JSON 对象节点
     *
     * 如 object_path 为 "app-plus.nativePlugins" 时返回 nativePlugins 节点对象。
     *
     * @param \stdClass $root 根对象
     * @param string $path 点号分隔的路径
     * @param string $patchFile 补丁文件名（错误提示用）
     * @param bool $throw 节点不存在或非对象时是否抛异常
     * @return \stdClass|null
     * @throws Exception
     */
    private function resolveJsonPathNode(\stdClass $root, string $path, string $patchFile, bool $throw = true): ?\stdClass
    {
        $node = $root;
        foreach (explode('.', $path) as $seg) {
            if ($seg === '' || !is_object($node) || !property_exists($node, $seg)) {
                if ($throw) {
                    throw new Exception('插件补丁对象路径无效: ' . basename($patchFile));
                }
                return null;
            }
            $node = $node->{$seg};
        }
        if (!is_object($node)) {
            if ($throw) {
                throw new Exception('插件补丁对象路径无效: ' . basename($patchFile));
            }
            return null;
        }
        return $node;
    }

    /**
     * 还原插件补丁（卸载/安装回滚时移除对现有文件的增量修改）
     *
     * 还原失败不阻断卸载流程，记录日志交由运维处理。
     *
     * @param string $patchDir 补丁目录绝对路径
     * @return void
     */
    private function revertPatches(string $patchDir): void
    {
        if (!is_dir($patchDir)) {
            return;
        }
        // Windows 下 glob 将反斜杠当作转义符，需统一为正斜杠并补齐目录分隔符
        $patchPattern = rtrim(str_replace('\\', '/', $patchDir), '/') . '/*.json';
        foreach (glob($patchPattern) ?: [] as $patchFile) {
            $patch = json_decode((string)file_get_contents($patchFile), true);
            if (!is_array($patch) || empty($patch['target'])) {
                continue;
            }
            $target = root_path() . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $patch['target']);
            if (!is_file($target)) {
                continue;
            }
            try {
                if ($patch['type'] === 'marker') {
                    $this->revertMarkerPatch($target, $patch);
                } elseif ($patch['type'] === 'json_structure') {
                    $this->revertJsonPatch($target, $patch);
                } elseif ($patch['type'] === 'json_object') {
                    $this->revertJsonObjectPatch($target, $patch, $patchFile);
                }
            } catch (Throwable $e) {
                Log::warning('插件补丁还原失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 删除目标文件中的所有标记块
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义（含 marker 名）
     * @return void
     */
    private function revertMarkerPatch(string $target, array $patch): void
    {
        $marker   = preg_quote((string)($patch['marker'] ?? 'addon:call'), '/');
        $content  = (string)file_get_contents($target);
        $pattern  = '/[ \t]*\/\/ ==== ' . $marker . ' BEGIN ====.*?\/\/ ==== ' . $marker . ' END ====[ \t]*\r?\n?/s';
        $newContent = preg_replace($pattern, '', $content);
        if ($newContent !== $content) {
            file_put_contents($target, $newContent);
        }
    }

    /**
     * 从目标 JSON 文件数组中移除匹配项
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义
     * @return void
     */
    private function revertJsonPatch(string $target, array $patch): void
    {
        $data = json_decode((string)file_get_contents($target), true);
        if (!is_array($data)) {
            return;
        }
        $path       = (string)($patch['array_path'] ?? '');
        $matchKey   = (string)($patch['match_key'] ?? 'root');
        $item       = $patch['item'] ?? null;
        $matchValue = is_array($item) ? ($item[$matchKey] ?? null) : null;
        if ($path === '' || !isset($data[$path]) || !is_array($data[$path])) {
            return;
        }
        $changed = false;
        foreach ($data[$path] as $key => $exists) {
            if (is_array($exists) && ($exists[$matchKey] ?? null) === $matchValue) {
                unset($data[$path][$key]);
                $changed = true;
            }
        }
        if ($changed) {
            $data[$path] = array_values($data[$path]);
            file_put_contents($target, $this->prettyJson($data));
        }
    }

    /**
     * 从目标 JSON 对象节点移除指定键（json_object 补丁的卸载还原）
     *
     * @param string $target 目标文件绝对路径
     * @param array $patch 补丁定义（含 object_path / key）
     * @return void
     */
    private function revertJsonObjectPatch(string $target, array $patch, string $patchFile): void
    {
        $data = $this->readJsonObjectFile($target, $patch);
        $path = (string)($patch['object_path'] ?? '');
        $key  = (string)($patch['key'] ?? '');
        if ($path === '' || $key === '') {
            return;
        }
        $node = $this->resolveJsonPathNode($data, $path, $patchFile, false);
        if ($node === null || !is_object($node) || !property_exists($node, $key)) {
            return;
        }
        unset($node->{$key});
        file_put_contents($target, $this->prettyJson($data));
    }

    /**
     * 格式化 JSON 为 tab 缩进（与 crm_uniapp/pages.json 原格式保持一致）
     *
     * @param array $data
     * @return string
     */
    private function prettyJson($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return str_replace('    ', "\t", $json);
    }

    /**
     * 原子写入映射文件（先写临时文件再改名提交）
     *
     * 避免写入过程中进程中断/磁盘满导致 file_map.json 半写入损坏，
     * 从而保证映射文件存在即内容完整可读。
     *
     * @param string $filePath 映射文件绝对路径
     * @param array $data 待写入的数据
     * @return void
     * @throws Exception
     */
    private function writeMapFileAtomically(string $filePath, array $data): void
    {
        $tmpFile = $filePath . '.tmp';
        $bytes   = file_put_contents(
            $tmpFile,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        if ($bytes === false) {
            @unlink($tmpFile);
            throw new Exception('插件文件映射写入失败: ' . $filePath);
        }

        if (!rename($tmpFile, $filePath)) {
            @unlink($tmpFile);
            throw new Exception('插件文件映射提交失败: ' . $filePath);
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
