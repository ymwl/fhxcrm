<?php

namespace app\admin\service;

use think\facade\Db;

/**
 * 授权验证公共服务
 * 离线授权文件（根目录license.php / 旧版config/license.dat）的读取与RSA验签逻辑，
 * 供 crm\License 授权控制器与 Cloud 云更新等多个场景共用，保证公钥与校验规则单一来源
 */
class LicenseService
{
    /**
     * 授权文件PHP防下载头（写入根目录license.php时附加）
     */
    const LICENSE_GUARD = "<?php exit();?>\n";

    /**
     * 授权验签公钥（与授权服务器私钥配对，在线响应与离线授权文件共用）
     */
    const LICENSE_PUBKEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1n6gJ8WcEC1mrvEml4VY
piKZcKyYuSn1R2Ow+pO8/kn2zkZSr8uH+aPjbDyy+4oGGHIAuxCLo8ZEEir65YsK
GbV1acmU5E2DhYLwkBGSf2domZ8ZcsOozZjSaxtz/ZuCePHFGEA8oTgNLUFT6Ivl
vMx+lGVOBErSo+v+9AdQFXoK2Gslf6smUMl7L0ikH0jp5PR/FDlEyg6t9wiOJDA9
S3Gy8KraadDQHd5Cya8iLcRbr2jXX8cFV5Tz3S5Zc1ysmjTU0uuGy0DNwmkqKmE0
VKAZTBjf1OVrUXwGfkX78x5HAcUCY1g3jxhBGArZEo+0MGVdO5zjga6KEWYMvBmo
FwIDAQAB
-----END PUBLIC KEY-----
EOT;

    /**
     * 离线授权文件路径：项目根目录 license.php
     * 选根目录而非config：config下的php会被TP当配置自动加载导致exit终止应用；
     * 根目录不会被自动加载，且域名误绑根目录时URL访问只会执行exit，无法下载内容
     */
    public function licenseFile(): string
    {
        return root_path() . 'license.php';
    }

    /**
     * 旧版离线授权文件路径（仅兼容读取，不再写入）
     */
    public function legacyLicenseFile(): string
    {
        return root_path() . 'config' . DIRECTORY_SEPARATOR . 'license.dat';
    }

    /**
     * 剥离授权文件的PHP防下载头，返回纯授权内容
     */
    public function stripLicenseGuard(string $content): string
    {
        $content = trim($content);
        if (strpos($content, '<?php') === 0) {
            $pos = strpos($content, '?>');
            if ($pos !== false) {
                $content = trim(substr($content, $pos + 2));
            }
        }
        return $content;
    }

    /**
     * 读取离线授权文件纯内容（自动剥离防下载头）
     * 新版license.php优先，旧版license.dat兼容；文件不存在或为空返回空字符串
     */
    public function getOfflineContent(): string
    {
        $path = '';
        if (is_file($this->licenseFile())) {
            $path = $this->licenseFile();
        } elseif (is_file($this->legacyLicenseFile())) {
            $path = $this->legacyLicenseFile();
        }
        if ($path === '') {
            return '';
        }
        return $this->stripLicenseGuard((string)@file_get_contents($path));
    }

    /**
     * 校验授权文件内容：验签 + install_id + 有效期
     * 格式：base64(载荷JSON) . "." . base64(签名)
     */
    public function verifyContent(string $content): array
    {
        if (!function_exists('openssl_verify')) {
            return ['ok' => false, 'msg' => '当前环境缺少openssl扩展，无法使用离线授权'];
        }
        $parts = explode('.', $content);
        if (count($parts) != 2) {
            return ['ok' => false, 'msg' => '授权文件格式错误'];
        }
        $payloadJson = base64_decode($parts[0], true);
        $sign = base64_decode($parts[1], true);
        if ($payloadJson === false || $sign === false) {
            return ['ok' => false, 'msg' => '授权文件格式错误'];
        }
        $pubKey = openssl_pkey_get_public(self::LICENSE_PUBKEY);
        if ($pubKey === false || openssl_verify($payloadJson, $sign, $pubKey, OPENSSL_ALGO_SHA256) !== 1) {
            return ['ok' => false, 'msg' => '授权文件验签失败'];
        }
        $payload = json_decode($payloadJson, true);
        if (empty($payload['install_id'])) {
            return ['ok' => false, 'msg' => '授权文件内容无效'];
        }
        // install_id 必须与本机一致（host 仅记录不强制比对，兼容局域网 IP/localhost 访问）
        if ($payload['install_id'] !== $this->getInstallId()) {
            return ['ok' => false, 'msg' => '授权文件与本安装不匹配，请提供本机的授权申请码重新签发'];
        }
        $expire = isset($payload['expire_time']) ? (int)$payload['expire_time'] : 0;
        if ($expire > 0 && $expire < time()) {
            return ['ok' => false, 'msg' => '离线授权已过期，请联系服务商续期'];
        }
        $name = !empty($payload['license_name']) ? $payload['license_name'] : '已授权使用';
        return ['ok' => true, 'msg' => '', 'name' => $name];
    }

    /**
     * 构建云端接口授权参数（密钥与离线授权文件双通道）
     * install_id 恒定携带；离线授权文件本地验签通过时随 license_file 上送，
     * 服务端密钥未命中时按签发记录(bind_install_id)回退放行
     *
     * @param string $license 系统配置的授权密钥（可为空）
     * @return array ['ok'=>bool 密钥与离线授权至少一项可用, 'msg'=>string 不可用原因, 'params'=>array 待合并进请求的授权参数]
     */
    public function buildCloudAuthParams(string $license): array
    {
        $offlineContent = $this->getOfflineContent();
        $offlineOk = false;
        $offlineMsg = '';
        if ($offlineContent !== '') {
            $check = $this->verifyContent($offlineContent);
            $offlineOk = $check['ok'];
            $offlineMsg = $check['msg'];
        }
        $params = ['install_id' => $this->getInstallId()];
        if ($offlineOk) {
            $params['license_file'] = $offlineContent;
        }
        if (empty($license) && !$offlineOk) {
            $msg = $offlineMsg !== '' ? '离线授权验证未通过：' . $offlineMsg : '授权使用联系微信:zrwx978';
            return ['ok' => false, 'msg' => $msg, 'params' => $params];
        }
        return ['ok' => true, 'msg' => '', 'params' => $params];
    }

    /**
     * 获取本机安装标识（首次生成UUID存入system_config表）
     */
    public function getInstallId(): string
    {
        $installId = Db::name('system_config')->where('field', 'install_id')->value('value');
        if ($installId) {
            return $installId;
        }
        // 首次运行生成UUID v4
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $installId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        Db::name('system_config')->insert([
            'name' => '安装标识',
            'field' => 'install_id',
            'identification' => '',
            'value' => $installId,
            'describe' => '系统安装唯一标识,离线授权使用,请勿修改',
            'sort' => 100,
            'create_time' => time(),
            'update_time' => time(),
            'formtype' => 'input',
            'status' => 1,
            'issystem' => 1,
        ]);
        cache('system_config', null);
        return $installId;
    }
}
