<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class Addon extends TimeModel
{

    protected $name = "addon";

    protected $deleteTime = false;

    public function sync($addons){
//        先清空数据库 truncate
//        \think\facade\Db::execute('TRUNCATE TABLE ' . $this->getTable());

        if($addons){
            $time=time();
            // 过滤掉 info.json 中不属于 addon 表的字段（如 require_php 仅用于安装兼容性检查），避免 insertAll 报错
            $tableFields = \think\facade\Db::name($this->name)->getTableFields();
            foreach ($addons as &$addon) {
                $addon = array_intersect_key($addon, array_flip($tableFields));
            }
            unset($addon);
            $this->duplicate(['scope'=>'local',
                'title' => \think\facade\Db::raw('VALUES(title)'),
                'description' => \think\facade\Db::raw('VALUES(description)'),
                'status' => \think\facade\Db::raw('VALUES(status)'),
                'is_set' => \think\facade\Db::raw('VALUES(is_set)'),
                'version' => \think\facade\Db::raw('VALUES(version)'),
                'build' => \think\facade\Db::raw('VALUES(build)'),
                'install' => \think\facade\Db::raw('VALUES(install)'),
                'author' => \think\facade\Db::raw('VALUES(author)'),
                'update_time' => $time,
            ])->insertAll($addons);
            //        删除掉数据库中不存在的本地插件
            $this->where('scope', '=','local')->whereNotIn('name', array_column($addons, 'name'))->where('update_time', '<', $time)->delete();
        }
    }

    public function net_sync($addons){
//同步互联网上的版本
        if($addons){
            $time=time();
            $this->duplicate([
                'net_version' => \think\facade\Db::raw('VALUES(net_version)'),
                'net_build' => \think\facade\Db::raw('VALUES(net_build)'),
                'no' => \think\facade\Db::raw('VALUES(no)'),
                'update_time' => $time,
            ])->insertAll($addons);
            //        删除掉数据库中不存在的互联网插件
            $this->where('net_version', '<>','')->where('update_time', '<', $time)->delete();
        }
    }

    
    

}