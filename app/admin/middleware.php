<?php
// 全局中间件定义文件
return [
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
//     \think\middleware\LoadLangPack::class,
    // Session初始化
     \think\middleware\SessionInit::class,
    // 后台认证中间件(登录验证、权限校验、视图变量注入)
    // 注意:此处注册才会被框架加载,app/admin/config.php 中的 middleware 配置项不生效
    \app\admin\middleware\Auth::class,
];
