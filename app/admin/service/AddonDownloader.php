<?php

declare(strict_types=1);

namespace app\admin\service;

use Exception;

/**
 * 插件云端下载解压服务
 *
 * 将插件安装/升级过程中重复的云端下载与 ZIP 解压逻辑抽取到本服务，
 * 供 Addon 控制器的 install()、upgrade() 方法复用。
 */
class AddonDownloader
{
    /**
     * 云端安装接口地址
     */
    protected string $cloudInstallUrl = 'https://cloud.laikephp.com/index/fhx.addon/install';

    /**
     * 云端升级接口地址
     */
    protected string $cloudUpgradeUrl = 'https://cloud.laikephp.com/index/fhx.addon/upgrade';

    /**
     * 下载并解压插件安装包
     *
     * @param string $name 插件标识名
     * @param string $no 插件授权编号
     * @param string $license 授权码
     * @return string 解压后的插件目录路径，如 addons/{name}
     * @throws Exception
     */
    public function download(string $name, string $no, string $license): string
    {
        if (empty($no)) {
            throw new Exception('插件安装失败，请联系作者');
        }

        // 授权参数：密钥与离线授权文件二选一，全部不可用则终止下载
        $auth = (new LicenseService())->buildCloudAuthParams($license);
        if (!$auth['ok']) {
            throw new Exception($auth['msg']);
        }

        $data = [
            'domain'  => $_SERVER['HTTP_HOST'] ?? '',
            'license' => $license,
            'php'     => PHP_VERSION,
            'no'      => $no,
        ];
        $data = array_merge($data, $auth['params']);

        $res     = httpRequest($this->cloudInstallUrl, 'POST', $data);
        $res_arr = json_decode($res, true);

        if (!$res_arr) {
            throw new Exception($res);
        }

        if ($res_arr['code'] != 1) {
            throw new Exception($res_arr['msg']);
        }

        $url = isset($res_arr['url']) ? $res_arr['url'] : '';
        if (empty($url)) {
            throw new Exception('插件下载地址获取失败');
        }

        $this->downloadByUrl($url);

        return app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * 下载并解压插件升级包
     *
     * @param string $name 插件标识名
     * @param string $no 插件授权编号
     * @param string $license 授权码
     * @param string $build 当前插件构建版本
     * @return array 返回下载结果，包含 path（解压后的插件目录路径）、version（升级目标版本）、count（升级包数量）
     * @throws Exception
     */
    public function downloadUpgrade(string $name, string $no, string $license, string $build): array
    {
        // 授权参数：密钥与离线授权文件二选一，全部不可用则终止升级
        $auth = (new LicenseService())->buildCloudAuthParams($license);
        if (!$auth['ok']) {
            throw new Exception($auth['msg']);
        }

        $data = [
            'domain'  => $_SERVER['HTTP_HOST'] ?? '',
            'license' => $license,
            'build'   => $build,
            'php'     => PHP_VERSION,
            'no'      => $no,
        ];
        $data = array_merge($data, $auth['params']);

        $res     = httpRequest($this->cloudUpgradeUrl, 'POST', $data);
        $res_arr = json_decode($res, true);

        if (!$res_arr) {
            throw new Exception($res);
        }

        if ($res_arr['code'] != 1) {
            throw new Exception($res_arr['msg']);
        }

        $url = isset($res_arr['url']) ? $res_arr['url'] : '';
        if (empty($url)) {
            // 云端未返回下载地址时，通常表示无可用升级包，返回云端提示信息
            throw new Exception($res_arr['msg']);
        }

        $this->downloadByUrl($url);

        return [
            'path'    => app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $name,
            'version' => $res_arr['version'] ?? '',
            'count'   => $res_arr['count'] ?? 1,
        ];
    }

    /**
     * 根据 URL 下载 ZIP 并解压到 addons 目录
     *
     * @param string $url 插件包下载地址
     * @return string 解压后的插件目录路径
     * @throws Exception
     */
    private function downloadByUrl(string $url): string
    {
        $remoteUrl = $this->resolveUrl($url);
        $tempPath  = $this->ensureTempPath();
        $localFile = $tempPath . DIRECTORY_SEPARATOR . basename($url);

        set_time_limit(0);

        $ch = curl_init($remoteUrl);
        $fp = fopen($localFile, 'wb');

        if (!$fp) {
            throw new Exception('无法创建临时文件');
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36');

        if (curl_exec($ch) === false) {
            @unlink($localFile);
            throw new Exception('插件下载失败: ' . curl_error($ch));
        }

        curl_close($ch);
        fclose($fp);

        $targetDir = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
        $this->extractZip($localFile, $targetDir);

        return $targetDir;
    }

    /**
     * 处理相对 URL，补全为完整下载地址
     *
     * @param string $url 原始 URL
     * @return string 完整 URL
     */
    private function resolveUrl(string $url): string
    {
        return strpos($url, '/') === 0 ? config('fhx.api_domain') . $url : $url;
    }

    /**
     * 解压 ZIP 到指定目录
     *
     * @param string $localFile ZIP 本地文件路径
     * @param string $targetDir 解压目标目录
     * @return void
     * @throws Exception
     */
    private function extractZip(string $localFile, string $targetDir): void
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('PHP环境解压类ZipArchive没有开启，插件安装失败');
        }

        $zip = new \ZipArchive();

        if ($zip->open($localFile) !== true) {
            @unlink($localFile);
            throw new Exception('打开插件安装包失败');
        }

        $zip->extractTo($targetDir);
        $zip->close();

        @unlink($localFile);
    }

    /**
     * 确保临时下载目录存在
     *
     * @return string 临时目录路径
     */
    private function ensureTempPath(): string
    {
        $tempPath = app()->getRuntimePath() . 'addons';

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        return $tempPath;
    }
}
