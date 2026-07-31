<?php

namespace app\admin\controller\crm;

use app\admin\service\LicenseService;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;

class License extends AdminController
{

    /**
     * 授权文件PHP防下载头（写入根目录license.php时附加，常量迁移至LicenseService统一维护）
     */
    const LICENSE_GUARD = LicenseService::LICENSE_GUARD;

    /**
     * 授权验签公钥（与授权服务器私钥配对，在线响应与离线授权文件共用，常量迁移至LicenseService统一维护）
     */
    const LICENSE_PUBKEY = LicenseService::LICENSE_PUBKEY;

    /**
     * 授权验证公共服务（离线授权文件读取与验签逻辑的单一来源）
     * @var LicenseService
     */
    protected $licenseService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->licenseService = new LicenseService();
    }

    /**
     * 授权验证入口（首页AJAX调用）
     * 优先级：有效缓存 → 离线授权文件 → 在线验证 → 30天宽限期 → 仅提示不锁功能
     */
    public function index()
    {
        try {
            // 1. 有效缓存直接通过
            $license_name = cache('license_name');
            if ($license_name) {
                return json(['code' => 1, 'msg' => $license_name]);
            }

            // 2. 存在离线授权文件时优先本地验签（局域网零等待，不发在线请求）
            $offlineMsg = '';
            if (is_file($this->licenseFile()) || is_file($this->legacyLicenseFile())) {
                $offline = $this->verifyOffline();
                if ($offline['ok']) {
                    $this->markVerifyOk($offline['name']);
                    return json(['code' => 1, 'msg' => $offline['name']]);
                }
                // 离线失败自动回退在线分支（兼容文件过期后服务器又能联网的场景）
                $offlineMsg = $offline['msg'];
            }

            // 3. 在线验证
            $key = isset($this->system['license_key']) ? $this->system['license_key'] : '';
            if (!$key) {
                return json(['code' => 0, 'msg' => '请先配置授权码或秘钥']);
            }
            $online = $this->verifyOnline($key);
            if ($online['ok']) {
                $this->markVerifyOk($online['name']);
                return json(['code' => 1, 'msg' => $online['name']]);
            }
            // 验签通过的明确拒绝（域名未授权/已过期/禁用）直接展示，不走宽限期
            if (!empty($online['definitive'])) {
                return json(['code' => 0, 'msg' => $online['msg']]);
            }

            // 4. 环境性失败走30天宽限期（曾验证成功即放行）
            $lastOk = cache('license_last_ok');
            if ($lastOk) {
                return json(['code' => 1, 'msg' => $lastOk]);
            }

            // 5. 全部失败仅提示，不锁功能不删文件
            $failMsg = $offlineMsg !== '' ? $offlineMsg : ($online['msg'] !== '' ? $online['msg'] : '授权验证失败');
            return json(['code' => 0, 'msg' => $failMsg]);
        } catch (\Throwable $t) {
            // 任何异常均降级：宽限期兜底，绝不阻断使用
            $lastOk = cache('license_last_ok');
            if ($lastOk) {
                return json(['code' => 1, 'msg' => $lastOk]);
            }
            return json(['code' => 0, 'msg' => '授权验证失败']);
        }
    }

    /**
     * 获取授权申请码（局域网客户复制后发给服务商离线签发）
     */
    public function applyCode()
    {
        try {
            $version = config('version');
            $payload = [
                'install_id' => $this->getInstallId(),
                'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
                'version' => isset($version['version']) ? $version['version'] : '',
            ];
            $code = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return json(['code' => 1, 'msg' => '获取成功', 'data' => ['apply_code' => $code]]);
        } catch (\Throwable $t) {
            return json(['code' => 0, 'msg' => $t->getMessage()]);
        }
    }

    /**
     * 后台上传离线授权文件（仅接受.dat后缀，杜绝上传PHP文件风险）
     * 安全链：后缀白名单 -> 大小限制 -> 内容格式白名单(纯base64.base64) -> RSA验签 -> 通过才落地
     * 落地时由系统自行转为带防下载头的根目录 license.php（上传内容永远不会原样保存为php）
     */
    public function uploadLicense()
    {
        try {
            $file = request()->file('file');
            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择授权文件']);
            }
            // 1. 后缀白名单：只允许 .dat（不信任前端accept限制）
            $ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
            if ($ext !== 'dat') {
                return json(['code' => 0, 'msg' => '仅允许上传 .dat 后缀的授权文件']);
            }
            // 2. 大小限制：授权文件不会超过10KB
            if ($file->getSize() > 10240) {
                return json(['code' => 0, 'msg' => '授权文件异常（超过大小限制），请确认文件来源']);
            }
            $content = trim((string)file_get_contents($file->getPathname()));
            if ($content === '') {
                return json(['code' => 0, 'msg' => '授权文件内容为空']);
            }
            // 3. 内容格式白名单：必须为纯 base64载荷.base64签名，含任何其他字符（如<?php、script等）直接拒绝
            if (!preg_match('/^[A-Za-z0-9+\/=]+\.[A-Za-z0-9+\/=]+$/', $content)) {
                return json(['code' => 0, 'msg' => '授权文件内容格式非法，已拒绝（请上传服务商签发的原始 license.dat）']);
            }

            // 4. RSA验签+install_id校验，不通过不落地
            $check = $this->verifyLicenseContent($content);
            if (!$check['ok']) {
                return json(['code' => 0, 'msg' => $check['msg']]);
            }

            $dest = $this->licenseFile();
            $dir = dirname($dest);
            if (!is_writable($dir) && !(is_file($dest) && is_writable($dest))) {
                return json(['code' => 0, 'msg' => '网站根目录不可写，请赋予写权限或手动将 license.php 放到网站根目录']);
            }
            // 写入时附加PHP防下载头：即使域名误绑定到项目根目录，URL访问也只会执行exit
            if (file_put_contents($dest, self::LICENSE_GUARD . $content) === false) {
                return json(['code' => 0, 'msg' => '授权文件写入失败，请检查网站根目录权限']);
            }

            // 生效：清缓存并解除软禁用标记
            cache('license_name', null);
            $this->markVerifyOk($check['name']);
            return json(['code' => 1, 'msg' => '授权文件上传成功']);
        } catch (\Throwable $t) {
            return json(['code' => 0, 'msg' => $t->getMessage()]);
        }
    }

    /**
     * 离线验证：读取 config/license.php（优先）或旧版 license.dat 验签
     */
    protected function verifyOffline(): array
    {
        $path = is_file($this->licenseFile()) ? $this->licenseFile() : $this->legacyLicenseFile();
        $content = $this->stripLicenseGuard((string)@file_get_contents($path));
        if ($content === '') {
            return ['ok' => false, 'msg' => '离线授权文件读取失败'];
        }

        // 旧版license.dat验证通过后自动迁移为带防下载头的license.php并删除旧文件
        $needMigrate = ($path === $this->legacyLicenseFile());

        // 时钟回拨检测（仅离线模式，容忍24小时内回拨）
        $maxSeen = (int)cache('license_clock_max');
        if ($maxSeen > 0 && $maxSeen - time() > 86400) {
            return ['ok' => false, 'msg' => '系统时间异常，离线授权验证失败'];
        }
        if (time() > $maxSeen) {
            cache('license_clock_max', time(), 86400 * 365);
        }

        $result = $this->verifyLicenseContent($content);
        if ($result['ok'] && $needMigrate) {
            if (@file_put_contents($this->licenseFile(), self::LICENSE_GUARD . $content) !== false) {
                @unlink($this->legacyLicenseFile());
            }
        }
        return $result;
    }

    /**
     * 校验授权文件内容：验签 + install_id + 有效期
     * 格式：base64(载荷JSON) . "." . base64(签名)
     */
    protected function verifyLicenseContent(string $content): array
    {
        return $this->licenseService->verifyContent($content);
    }

    /**
     * 在线验证：HTTPS请求授权服务器并对响应验签
     */
    protected function verifyOnline(string $key): array
    {
        $version = config('version');
        $host = base64_decode('YXBpLmxhaWtlcGhwLmNvbQ==');
        $url = 'https://' . $host . '/auth/verify';
        $param = [
            'k' => $key,
            'h' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'v' => isset($version['version']) ? $version['version'] : '',
            't' => time(),
        ];

        $canVerify = function_exists('openssl_verify');
        $response = $canVerify ? $this->secureRequest($url, $param) : httpRequest($url, 'post', $param);
        if (!$response) {
            return ['ok' => false, 'msg' => '', 'definitive' => false];
        }
        $res = json_decode($response, true);
        if (!is_array($res) || !isset($res['data']['status'])) {
            return ['ok' => false, 'msg' => isset($res['msg']) ? $res['msg'] : '', 'definitive' => false];
        }

        // openssl扩展缺失时自动降级为不验签的在线验证（等同旧版，不阻断不提示）
        $verified = false;
        if ($canVerify && !empty($res['sign']) && is_array($res['data'])) {
            $verified = $this->verifyOnlineSign($res['data'], $res['sign']);
        }

        $status = (int)$res['data']['status'];
        $name = !empty($res['data']['license_name']) ? $res['data']['license_name'] : (isset($res['msg']) ? $res['msg'] : '');

        if ($canVerify && !$verified) {
            // 有openssl但验签失败=响应不可信（劫持/伪造），按环境性失败走宽限期
            return ['ok' => false, 'msg' => '授权服务器响应验签失败', 'definitive' => false];
        }

        if ($status == 1) {
            return ['ok' => true, 'msg' => '', 'name' => $name];
        }
        if ($status == -1) {
            // 仅验签通过的禁用指令才写软禁用标记（环境性失败绝不触发）
            if ($verified) {
                $this->writeLock($response);
            }
            return ['ok' => false, 'msg' => '系统授权已被禁用，请联系服务商', 'definitive' => $verified];
        }
        // status=0：未授权/域名未授权/已过期
        return ['ok' => false, 'msg' => $name !== '' ? $name : '未授权', 'definitive' => $verified];
    }

    /**
     * 在线响应验签三要素：签名有效 + 时间戳偏差≤300秒 + 域名一致
     */
    protected function verifyOnlineSign(array $data, string $sign): bool
    {
        $signRaw = base64_decode($sign, true);
        if ($signRaw === false) {
            return false;
        }
        $pubKey = openssl_pkey_get_public(self::LICENSE_PUBKEY);
        if ($pubKey === false) {
            return false;
        }
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (openssl_verify($payload, $signRaw, $pubKey, OPENSSL_ALGO_SHA256) !== 1) {
            return false;
        }
        // 时间戳防重放
        if (!isset($data['timestamp']) || abs(time() - (int)$data['timestamp']) > 300) {
            return false;
        }
        // 域名一致性（防止拿其他站点的合法响应冒用）
        $localDomain = $this->normalizeDomain(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        if (!isset($data['domain']) || $data['domain'] !== $localDomain) {
            return false;
        }
        return true;
    }

    /**
     * 安全HTTPS请求：开启证书校验+内置CA包，失败自动降级重试
     * （防伪主防线是RSA验签而非TLS，降级不影响安全）
     */
    protected function secureRequest(string $url, array $param)
    {
        $doRequest = function (bool $sslVerify) use ($url, $param) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            if ($sslVerify) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                // 内置CA证书包，规避宝塔/phpStudy/Windows未配置curl.cainfo的问题
                $cacert = root_path() . 'extend' . DIRECTORY_SEPARATOR . 'cacert.pem';
                if (is_file($cacert)) {
                    curl_setopt($ch, CURLOPT_CAINFO, $cacert);
                }
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            $result = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            return [$result, $errno];
        };

        [$result, $errno] = $doRequest(true);
        // SSL相关错误自动降级为关闭校验重试（35=SSL连接错误 58/77/60=证书问题）
        if ($result === false && in_array($errno, [35, 51, 58, 60, 77, 83, 90, 91])) {
            [$result, $errno] = $doRequest(false);
        }
        return $result;
    }

    /**
     * 验证成功统一处理：写缓存+宽限期标记+解除软禁用
     */
    protected function markVerifyOk(string $name): void
    {
        cache('license_name', $name, 86400);
        cache('license_last_ok', $name, 86400 * 30);
        $this->clearLock();
    }

    /**
     * 获取本机安装标识（首次生成UUID存入system_config表）
     */
    protected function getInstallId(): string
    {
        return $this->licenseService->getInstallId();
    }

    /**
     * 域名归一化（与服务端保持一致：转小写、去端口、去www前缀）
     */
    protected function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];
        if (strpos($domain, ':') !== false && substr_count($domain, ':') == 1) {
            $domain = explode(':', $domain)[0];
        }
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, 4);
        }
        return $domain;
    }

    /**
     * 离线授权文件路径：项目根目录 license.php
     * 选根目录而非config：config下的php会被TP当配置自动加载导致exit终止应用；
     * 根目录不会被自动加载，且域名误绑根目录时URL访问只会执行exit，无法下载内容
     */
    protected function licenseFile(): string
    {
        return $this->licenseService->licenseFile();
    }

    /**
     * 旧版离线授权文件路径（仅兼容读取，不再写入）
     */
    protected function legacyLicenseFile(): string
    {
        return $this->licenseService->legacyLicenseFile();
    }

    /**
     * 剥离授权文件的PHP防下载头，返回纯授权内容
     */
    protected function stripLicenseGuard(string $content): string
    {
        return $this->licenseService->stripLicenseGuard($content);
    }

    /**
     * 软禁用标记文件路径
     */
    protected function lockFile(): string
    {
        return app()->getRuntimePath() . 'license.lock';
    }

    /**
     * 写入软禁用标记（内容为签名过的原始响应，供人工核查）
     */
    protected function writeLock(string $rawResponse): void
    {
        @file_put_contents($this->lockFile(), $rawResponse);
    }

    /**
     * 解除软禁用标记（误判可自动恢复）
     */
    protected function clearLock(): void
    {
        if (is_file($this->lockFile())) {
            @unlink($this->lockFile());
        }
    }
}
