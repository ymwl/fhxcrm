<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/5/2 0002
 * Time: 09:23
 */
namespace app\common\model;
use think\facade\Db;
use think\Model;

class SystemField extends Model
{
    protected $name = "system_field";

    protected $pk = "id";
    public function getTypeList(){
        return [
            'tinyint'=>'tinyint',
            'smallint'=>'smallint',
            'mediumint'=>'mediumint',
            'int'=>'int',
            'integer'=>'integer',
            'bigint'=>'bigint',
            'double'=>'double',
            'float'=>'float',
            'decimal'=>'decimal',
            'numeric'=>'numeric',
            'char'=>'char',
            'varchar'=>'varchar',
            'datetime'=>'datetime',
            'tinytext'=>'tinytext',
            'text'=>'text',
            'mediumtext'=>'mediumtext',
            'longtext'=>'longtext',
        ];
    }
    public function getFormtypeList(){
        return [
            'input'=>'单行文本',
            'textarea'=>'多行文本',
            'file'=>'单文件',
            'files'=>'多文件',
            'image'=>'单图片',
            'images'=>'多图片',
            'video'=>'视频',
            'radio'=>'单选框',
            'checkbox'=>'多选框',
            'select'=>'下拉框',
            'color'=>'颜色',
            'editor'=>'编辑框',
            'none'=>'隐藏框',
            'password'=>'密码框',
            'optgroup'=>'下拉组',
            'switch'=>'开关',
            'json'=>'json数组',
            'lradio'=>'联动单选框',
            'lcheckbox'=>'联动多选框',
            'lselect'=>'联动下拉框',
            'datetime'=>'时间',
        ];
    }
    public function getTableList(){
        $database = getDataBaseConfig('database');
        $prefix=config('database.connections.mysql.prefix');
        $sql = "select TABLE_NAME  AS 'table',TABLE_COMMENT as 'name' from information_schema.tables where table_schema='".$database."'";
        $array = Db::query($sql);
        $lst=[];
        foreach ($array as $k=>$v){
            $name=$v['name']?'('.$v['name'].')':'';
            $v['table']=preg_replace("/^$prefix/",'',$v['table'],1);
            $lst[]=['name'=>$v['table'].$name,'value'=>$v['table']];
        }
        return $lst;
    }
}