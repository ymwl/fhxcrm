<?php
// +----------------------------------------------------------------------
// |CRM销售客户关系管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://fhy.laikephp.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: QQ3623820285
// +----------------------------------------------------------------------
define('IS_ROOT_ACCESS', true);
// 是否是开发者模式
if (!is_file(__DIR__ .'/config/install.lock')) {
    header("location:/install.php");
    exit;
}

// 引入公共入口文件

require __DIR__ . '/vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new \think\App())->http;

$response = $http->name('index')->run();

$response->send();

$http->end($response);