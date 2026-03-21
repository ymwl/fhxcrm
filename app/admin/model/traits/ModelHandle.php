<?php
// 模型处理 定制开发zrwx978
namespace app\admin\model\traits;
use think\facade\Db;

class ModelHandle
{
    /**创建表
     * @param $data
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public static function createTable($data){
//        dump();exit;
//        return json_encode($data);
        if(empty($data['prefix'])){
            $data['prefix'] = getDataBaseConfig('prefix');
        }
        $table = $data['prefix'].$data['table'];
//        先判断表是否存在，存在才需要创建表哦
        $result = Db::query("SHOW TABLES LIKE '{$table}'");

        if ($result) {
//            echo "表存在";
        } else {
            $sql = "CREATE TABLE `".$table."` (
`id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID' ,
`name`  varchar(150) NULL DEFAULT '' COMMENT '名称[show:1,edit:1,search:0,total:0]' ,
`sort`  int(10) NULL DEFAULT 100 COMMENT '排序{input}[show:1,edit:1]' ,
`create_time`  int(11) NULL DEFAULT 0 COMMENT '创建时间{datetime}[show:1,edit:0,search:1,total:0]' ,
`update_time`  int(11) NULL DEFAULT 0 COMMENT '更新时间{datetime}' ,
`status`  int(1) NULL DEFAULT 1 COMMENT '状态{switch}[show:1,edit:1,search:0,total:0]' ,
`delete_time`  int(11) NULL DEFAULT 0 COMMENT '软删除{datetime}' ,
PRIMARY KEY (`id`)) ENGINE=".$data['engine']." COMMENT='".$data['name']."'";
            Db::query($sql);
        }



        $database = getDataBaseConfig('database');

        $bool = TablHandle::updatetable($data['table'],$data['prefix'],$database,$database);
        if($bool){
            Db::name('system_model')
                ->where('table',$data['table'])
                ->where('databases',$data['databases'])
                ->update(['is_page'=>$data['is_page'],'tabletype'=>$data['tabletype']]);
        }
        return $bool;
    }

    /**更新表格
     * @param array $primaryData
     * @param array $newData
     * @return bool
     */
    public static function updateTable($primaryData=[],$newData=[]){
//        dump($primaryData,$newData);exit;
        $str = "";
        $prefix = getDataBaseConfig('prefix');
        if($primaryData['table']==$newData['table']){
            $table = $prefix.$primaryData['table'];
            $sql = "ALTER TABLE `$table`";
            if($primaryData['engine']!=$newData['engine']){
                $engine = $newData['engine'];
                $str .= " ENGINE=$engine";
            }
            if($primaryData['name']!=$newData['name']){
                $name = $newData['name'];
                $str .= " COMMENT='$name'";
            }
            if(empty($str)){
                return true;
            }
            $sql = $sql.$str;

        }else{
            $table = $prefix.$primaryData['table'];
            $newtable = $prefix.$newData['table'];
            $sql = "ALTER TABLE `$table` rename to `$newtable`";
            try{
                Db::connect($primaryData['databases'])->query($sql);
            }catch (\Exception $e){
                return false;
            }
            if($primaryData['engine']!=$newData['engine']){
                $engine = $newData['engine'];
                $str .= " ENGINE=$engine";
            }
            if($primaryData['name']!=$newData['name']){
                $name = $newData['name'];
                $str .= " COMMENT='$name'";
            }
            if(!empty($str)){
                $str = "ALTER TABLE `$newtable`".$str;
                try{
                    Db::connect($primaryData['databases'])->query($str);
                }catch (\Exception $e){
                    $sql = "ALTER TABLE `$newtable` rename to `$table`";
                    Db::connect($primaryData['databases'])->query($sql);
                    return false;
                }
            }
            if($primaryData['table']!=$newData['table']){
                Db::name('system_field')
                    ->where('table',$primaryData['table'])
                    ->update(['table'=>$newData['table'],'update_time'=>time()]);
            }
            return true;
        }
//        echo $sql;exit;
        try{
            Db::connect($primaryData['databases'])->query($sql);
        }catch (\Exception $e){
            return false;
        }
        return true;
    }

    /**删除表
     * @param array $array
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function deleteTable($array=[]){
//        $models = Db::name('system_model')
//            ->where('id','in',$array)
//            ->select()->toArray();
        $prefix = getDataBaseConfig('prefix');
        foreach ($array as $value){
            $table = $prefix.$value['table'];
            $sql = "DROP TABLE `$table`";
            try{
                Db::connect($value['databases'])->query($sql);
            }catch (\Exception $e){
                return false;
            }
            Db::name('system_field')
                ->where('table',$value['table'])
                ->delete();
        }
        return true;
    }
    /**排序函数
     * @param string $table_name
     * @param string $sort
     * @param string $where
     */
    public static function GetRanking(string $table_name,string $sort,string $where='',string $field='*',string $rownum='rownum'){
        $prefix = getDataBaseConfig('prefix');
        if(strpos($table_name,$prefix)===false){
            $table_name = $prefix.$table_name;
        }
        if(empty($where)){
            $sql = "SELECT t.$field, @$rownum := @$rownum + 1 AS $rownum FROM (SELECT @$rownum := 0) r, (SELECT * FROM $table_name ORDER BY $sort) AS t;";
        }else{
            $sql = "SELECT b.$field FROM (SELECT t.*, @$rownum := @$rownum + 1 AS $rownum FROM (SELECT @$rownum := 0) r,(SELECT * FROM $table_name ORDER BY $sort) AS t) AS b WHERE $where";
        }
        $array = Db::query($sql);
        return $array;
    }
}
