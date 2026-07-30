<?php
// +----------------------------------------------------------------------
use think\facade\Env;

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => Env::get('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
    // 驱动方式
    'type'   => 'File',
    // 缓存保存目录
    'path'   => '',
    // 缓存前缀
    'prefix' => '',
    // 缓存有效期 0表示永久缓存
    'expire' => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 跨应用共享文件缓存（admin与api应用runtime缓存目录相互隔离，扫码登录等跨应用数据用此通道）
        'share' => [
            'type'   => 'File',
            'path'   => root_path() . 'runtime/cache/share/',
            'prefix' => '',
            'expire' => 0,
            'tag_prefix' => 'tag:',
            'serialize'  => [],
        ],
        // 更多的缓存连接
    ],
];
