    <?php
return [
    'pathinfo_depr'          => '/',
    // 后台认证中间件
    'middleware' => [
        \app\admin\middleware\Auth::class,
    ],
];