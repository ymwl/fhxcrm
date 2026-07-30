<?php

namespace app\service;

use think\facade\Cache;
use think\facade\Db;


class SystemFieldService
{
    /**
     * 获取crm_customer可编辑字段列表（带缓存）
     * @return array
     */
    public static function getRecordFields()
    {
        $cacheKey = 'system_field:record_fields';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $record_fields_lst = [];
        $prefix = getDataBaseConfig('prefix');
        $fields = Db::query(
            'SELECT `name`,`xsname`,`field` FROM `' . $prefix . 'system_field` WHERE `form`=1 AND `table`="crm_customer" AND `editinput` is not null AND `field`<>"id" ORDER BY `sort` ASC,id ASC'
        );
        foreach ($fields as $f) {
            $record_fields_lst[] = ['name' => empty($f['xsname']) ? $f['name'] : $f['xsname'], 'field' => $f['field']];
        }

        Cache::set($cacheKey, $record_fields_lst, 3600);
        return $record_fields_lst;
    }

    /**
     * 获取crm_customer所有字段列表（带缓存）
     * @return array
     */
    public static function getBigShowFields()
    {
        $cacheKey = 'system_field:big_show_fields';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $fields_lst = [];
        $prefix = getDataBaseConfig('prefix');
        $fields = Db::query(
            'SELECT `name`,`xsname`,`field` FROM `' . $prefix . 'system_field` WHERE `table`="crm_customer" ORDER BY `sort` ASC,id ASC'
        );
        foreach ($fields as $f) {
            $fields_lst[] = ['name' => empty($f['xsname']) ? $f['name'] : $f['xsname'], 'field' => $f['field']];
        }

        Cache::set($cacheKey, $fields_lst, 3600);
        return $fields_lst;
    }

    /**
     * 获取指定表的字段配置（通用方法，带缓存）
     * @param string $table 表名
     * @param string $source 场景（index/addForm/editForm/search/seas）
     * @return array
     */
    public static function getTableFieldsConfig($table, $source = 'index')
    {
        $cacheKey = 'system_field:config:' . $table . ':' . $source;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $prefix = getDataBaseConfig('prefix');

        $field = '`id`,`list_sort`,`default` as value,`join_table`,`formtype`,`foreign_key`,`relationship_primary_key`,`field`,`rule`,if(`xsname`<>"",`xsname`,`name`) title,`option`,`width`';

        switch ($source) {
            case 'index':
                $where = '`list`=1 AND `table`=:table';
                break;
            case 'addForm':
                $where = '`form`=1 AND `table`=:table AND `addinput` is not null AND `is_key`<>1';
                $field = '`id`,`list_sort`,`default` as value,`join_table`,`formtype`,`foreign_key`,`relationship_primary_key`,`field`,`rule`,if(`xsname`<>"",`xsname`,`name`) title,`option`,`width`,`href`';
                break;
            case 'editForm':
                $where = '`form`=1 AND `table`=:table AND `editinput` is not null AND `is_key`<>1';
                $field = '`id`,`list_sort`,`default` as value,`join_table`,`formtype`,`foreign_key`,`relationship_primary_key`,`field`,`rule`,if(`xsname`<>"",`xsname`,`name`) title,`option`,`width`,`href`';
                break;
            case 'search':
                $where = '`search`=1 AND `table`=:table';
                break;
            case 'seas':
                $where = "`table`=:table AND (`list` = 1 OR `field` IN ('to_gh_time', 'pr_user_bef'))";
                break;
            default:
                $where = '`form`=1 AND `table`=:table AND `editinput` is not null AND `is_key`<>1';
                break;
        }

        $fields = Db::query(
            'SELECT ' . $field . ' FROM `' . $prefix . 'system_field` WHERE ' . $where . ' ORDER BY `sort` ASC,id ASC',
            ['table' => $table]
        );

        Cache::set($cacheKey, $fields, 3600);
        return $fields;
    }

    /**
     * 清除指定表或所有表的字段配置缓存
     * @param string $table 表名，为空则清除所有
     */
    public static function clearFieldsCache($table = '')
    {
        if ($table) {
            foreach (['index', 'addForm', 'editForm', 'search', 'seas'] as $source) {
                Cache::delete('system_field:config:' . $table . ':' . $source);
            }
            if ($table === 'crm_customer') {
                Cache::delete('system_field:record_fields');
                Cache::delete('system_field:big_show_fields');
            }
        } else {
            Cache::clear();
        }
    }
}
