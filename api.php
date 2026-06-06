<?php
//此源码禁止用于含木马、病毒、色情、赌博、诈骗等违法用途。对违法违规用途的用户，拒绝提供后续技术支持，并协助有关行政机关等进行追索和查处。
//PHP建站问题处理，PHP二次开发联系QQ1500203929(2021-08-25 15:13)


// [ 应用入口文件 ]
define('IS_ROOT_ACCESS', false);
// 引入公共入口文件
require __DIR__ . '/../vendor/autoload.php';



// 添加允许跨域请求头
header("'Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, X-Token, X-HTTP-Method-Override,access-control-allow-headers,access-control-allow-methods,access-control-allow-origin,token,uid");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 执行HTTP应用并响应
$http = (new \think\App())->http;
$response = $http->name('api')->run();
$response->send();
$http->end($response);
