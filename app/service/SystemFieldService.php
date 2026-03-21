<?php

namespace app\service;

use think\facade\Db;



class SystemFieldService
{
   public static function getRecordFields()
    {
        $record_fields_lst=[];
        $fields=Db::query('SELECT `name`,`xsname`,`field` FROM `'.getDataBaseConfig('prefix').'system_field` WHERE `edit`=1  AND `table`="crm_customer" AND `editinput` is not null AND `field`<>"id" order BY `sort` ASC,id ASC');
        foreach ($fields as $f){
            $record_fields_lst[]=['name'=>empty($f['xsname'])?$f['name']:$f['xsname'],'field'=>$f['field']];
        }
        return $record_fields_lst;
    }
    public static function getBigShowFields()
    {
        $fields_lst=[];
        $fields=Db::query('SELECT `name`,`xsname`,`field` FROM `'.getDataBaseConfig('prefix').'system_field` WHERE `table`="crm_customer"  order BY `sort` ASC,id ASC');
        foreach ($fields as $f){
            $fields_lst[]=['name'=>empty($f['xsname'])?$f['name']:$f['xsname'],'field'=>$f['field']];
        }
        return $fields_lst;
    }
}
