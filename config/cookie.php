<?php
// +----------------------------------------------------------------------
// | Cookie设置
// +----------------------------------------------------------------------

return [
    // cookie 保存时间
    'expire'    => 0,
    // cookie 保存路径
    'path'      => '/',
    // cookie 有效域名
    'domain'    => '',
    //  cookie 启用安全传输
    'secure'    => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'? true : false,
    // httponly设置
    'httponly'  => true,
    // 是否使用 setcookie
    'setcookie' => true,
];
