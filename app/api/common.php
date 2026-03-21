<?php

use Firebase\JWT\JWT;
use think\facade\Cache;

/**
 * 验证验证码是否正确
 *
 * @param $uni
 * @param string $code
 * @return bool
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function checkCaptcha($uni, string $code): bool
{
    $cacheName = 'key.cap.' . $uni;
    if (!Cache::has($cacheName)) {
        return false;
    }

    $key = Cache::get($cacheName);

    $code = mb_strtolower($code, 'UTF-8');

    $res = password_verify($code, $key);

    if ($res) {
        Cache::delete($cacheName);
    }

    return $res;
}

/**
 * @param string $type
 * @param array $params
 * @return string
 */
function getToken($params ,$exp)
{
    $host = app()->request->host();
    $time = time();

    $params += [
        'iss' => $host,
        'aud' => $host,
        'iat' => $time,
        'nbf' => $time,
//        'exp' => strtotime('+5 days'),
        'exp' =>$exp,
    ];

    $token = JWT::encode($params, config('app.app_key'),'HS256');
    return $token;
}
