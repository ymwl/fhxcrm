<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021-05-01
 * Time: 11:24
 */
namespace app\admin\model;
use think\facade\Db;
use think\Model;

class SystemModels extends Model
{
    protected $name = "system_model";

    protected $pk = "id";
    public function getEngineList(){
        return ['InnoDB'=>'InnoDB','MyISAM'=>'MyISAM'];
    }
    public function getTabletypeList(){
        return ['ordinary'=>'普通表格','tree'=>'树状表格'];
    }
    public function getTableList(){

        $database = getDataBaseConfig('database');


        $sql = "select TABLE_NAME  AS 'table',TABLE_COMMENT as 'name' from information_schema.tables where table_schema='".$database."'";
        $array = Db::query($sql);
        $lst=[];
        foreach ($array as $k=>$v){
            $name=$v['name']?'('.$v['name'].')':'';
            $lst[]=['name'=>$v['table'].$name,'value'=>$v['table']];
        }
        return $lst;
    }

}