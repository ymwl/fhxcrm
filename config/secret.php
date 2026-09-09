<?php

// 部署专用密钥（安装向导自动生成，勿提交版本库、勿泄露；PHP 执行可避免 .env 被误绑站点根目录时的明文下载）
return [
    'app_key'          => '55a5756572fe12946e8df41f8591fe6c517b998ed08e534cf805659f70200fcf',
    'cookie_crypt_key' => '946be2ba9906ab9b4a',
];