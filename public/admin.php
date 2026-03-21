<?php
//此源码禁止用于含木马、病毒、色情、赌博、诈骗等违法用途。对违法违规用途的用户，拒绝提供后续技术支持，并协助有关行政机关等进行追索和查处。
//PHP建站问题处理，PHP二次开发联系QQ3623820285(2021-08-25 15:13)


// [ 应用入口文件 ]
define('IS_ROOT_ACCESS', false);
// 是否是开发者模式
if (!is_file(dirname(__DIR__) .'/config/install.lock')) {
    header("location:/install.php");
    exit;
}

// 引入公共入口文件

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new \think\App())->http;

$response = $http->name('admin')->run();

$response->send();

$http->end($response);
