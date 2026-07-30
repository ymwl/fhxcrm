<?php

namespace tools;
/**
 * 技术 zrwx978
 **/

/**
 * 框架级别的工具类
 */
class System
{
//格式化连续时间段


    public static function tool($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }


}

