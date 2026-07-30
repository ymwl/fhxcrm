<?php

namespace tools;
use think\facade\Db;

/**
 * 技术 zrwx978
 **/

/**
 * 框架级别的工具类
 */
class Cache
{
//格式化连续时间段


    public static function zdy_fields($table,$where=[])
    {
//        通过表名返回自定义字段缓存
//        AND `list`=1 AND `jscol` is not null改成 $where数组
        if(empty($where)){
            $where = [
                ['list', '=', 1]
            ];
        }
        $cacheKey=$table.'_fields_'.md5(serialize($where));
        $fields=cache($cacheKey);
        if(!$fields){
            $fields=Db::name('system_field')->field('field,jscol,list,formtype')->where('table','=',$table)->where($where)->order('sort ASC,id ASC')->select()->toArray();
            $field_str=$jscol_str='';
            $tels=[];
            foreach ($fields as $key=>$value){
                $field_str.=$value['field'].',';
                
                $jscol_str.=$value['jscol'].',';
                
                if($value['formtype']=='tel'){
                    $tels[]=$value['field'];
                }
            }
            $fields=['field_str'=>trim($field_str,','),'jscol_str'=>trim($jscol_str,','),'tels'=>$tels];
            cache($cacheKey,$fields);
        }
        return $fields;
    }


}

