<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021-04-28
 * Time: 21:42
 */
namespace app\admin\model\traits;
use app\admin\model\publicuse\PublicUse;
use think\facade\Db;

class TablHandle
{
    /**处理表字段
     * @param array $data
     * @param string $table
     * @return array
     */
    public static function CommentToTabl(array $data,string $table=''){
//        echo '<pre>';
//        print_r($data);
//        exit;
        foreach ($data as $key=>$value){
            if(isset($value['Comment'])){
                $newdata = self::CommentConversionField($value['Comment']);
            }elseif (isset($value['comment'])){
                $newdata = self::CommentConversionField($value['comment']);
            }
            if(isset($value['Type'])){
                $newdata = array_merge($newdata,self::TypeToField($value['Type']));
            }elseif (isset($value['type'])){
                $newdata = array_merge($newdata,self::TypeToField($value['type']));
            }
            if(isset($value['Field'])){
                $newdata['field'] = $value['Field'];
            }elseif (isset($value['field'])){
                $newdata['field'] = $value['field'];
            }
            if(isset($value['Null'])){
                $newdata['is_null'] = $value['Null']=='YES'?1:0;
            }else{
                $newdata['is_null'] = $value['null']==0;
            }
//            $newdata['field'] = $value['Field'];
            $newdata['table'] = $table;
            $data[$key] = $newdata;
        }
        return $data;
    }

    /**更新单表
     * @param $dan_table
     * @throws \think\db\exception\DbException  updatetable($table,$prefix,$database)
     */
    public static function updatetable( $table,$prefix='',$database=''){

        $fullTable = $prefix . $table;
//            没有前缀的表

        $zhujian = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME= '".$fullTable."' AND COLUMN_KEY = 'PRI';";
        $zhujian = Db::query($zhujian);
        $zhujian = $zhujian[0]['COLUMN_NAME']??'';
        $models = Db::name('system_model')
            ->where('table',$table)
            ->findOrEmpty();
//                获取当前更新表信息
        $sql="select TABLE_NAME  AS 'table',TABLE_COMMENT as 'name',engine as 'engine' from information_schema.tables where table_schema='{$database}' AND TABLE_NAME ='{$fullTable}'";
        $value =Db::query($sql);
        $value=$value[0];


        if(empty($models)){
            $newsql = 'show full fields from '.$fullTable;
            $newarray = Db::query($newsql);
            $newarray = TablHandle::CommentToTabl($newarray,$table);
            $field_array = array_column($newarray,'field');
            $field_count = Db::name('system_field')
                ->where('table',$table)
                ->count();
            if(count($newarray)!==$field_count){
                Db::name('system_field')
                    ->where('table',$table)
                    ->delete();
            }else{
                $field_count = Db::name('system_field')
                    ->where('table',$table)
                    ->where('field','in',$field_array)
                    ->count();
                if($field_count!==count($newarray)){
                    Db::name('system_field')
                        ->where('table',$table)
                        ->delete();
                }
            }

            foreach ($newarray as $key=>$va){
                if($va['field']=='create_time'||$va['field']=='update_time'||$va['field']=='status'){
                    $va['sort'] = 10;
                    if($va['field']=='status'||$va['field']=='create_time'||$va['field']=='id'){
                        $va['show'] = 1;
                        if($va['field']=='status'){
                            $va['edit'] = 1;
                        }
                    }
                    if($va['field']=='create_time'||$va['field']=='update_time'){
                        $va['width'] = 200;
                    }
                }
                if($va['field']=='delete_time'){
                    $va['sort'] = 0;
                }
                if($va['field']=='id'){
                    $va['sort'] = 9999;
                    $va['show'] = 1;
                    $va['is_key'] = 1;
                    $va['width'] = 80;
                }
                if($va['field']=='name'){
                    $va['sort'] = 9000;
                }
                if($va['field']==$zhujian){
                    $va['is_key'] = 1;
                }
                if($va['field']=='status'){
                    $va['default'] = 1;
                }
                $field = Db::name('system_field')
                    ->where('table',$table)
                    ->where('field',$va['field'])
                    ->findOrEmpty();

                if(empty($field)){
                    $va['create_time'] = time();
                    $va['update_time'] = time();
                    $va['addinput']=self::create_add_input($va);
                    $va['editinput']=self::create_edit_input($va);
                    $va['jscol']=self::create_js_col($va);
                    $ids = Db::name('system_field')
                        ->insertGetId($va);
                }else{
                    $va['update_time'] = time();
                    $va['addinput']=self::create_add_input($va);
                    $va['editinput']=self::create_edit_input($va);
                    $va['jscol']=self::create_js_col($va);
                    Db::name('system_field')
                        ->where('id',$field['id'])
                        ->update($va);
                }
                $newarray[$key] = $va;
            }
            $model = [
                'name'=>$value['name'],
                'table'=>$table,
                'engine'=>$value['engine'],
                'tabletype'=>'ordinary',
                'is_page'=>1,
                'create_time'=>time(),
                'update_time'=>time(),
                'status'=>1,
            ];
            $bo = Db::name('system_model')
                ->insert($model);
            $value['model'] = $newarray;
        }else{
            $newarray=[];
            $newarray['engine'] = $value['engine'];
            $newarray['name'] = $value['name'];
            $newarray['update_time'] = time();
            Db::name('system_model')
                ->where('id',$models['id'])
                ->update($newarray);
            $newsql = 'show full fields from '.$fullTable;
            $newarray = Db::query($newsql);
            $newarray = TablHandle::CommentToTabl($newarray,$table);
            $field_array = array_column($newarray,'field');
            $field_count = Db::name('system_field')
                ->where('table',$table)
                ->count();
            if(count($newarray)!==$field_count){
                Db::name('system_field')
                    ->where('table',$table)
                    ->delete();
            }else{
                $field_count = Db::name('system_field')
                    ->where('table',$table)
                    ->where('field','in',$field_array)
                    ->count();
                if($field_count!==count($newarray)){
                    Db::name('system_field')
                        ->where('table',$table)
                        ->delete();
                }
            }
            foreach ($newarray as $key=>$va){
                $field = Db::name('system_field')
                    ->where('table',$table)
                    ->where('field',$va['field'])
                    ->findOrEmpty();

                if(empty($field)){
                    if($va['field']==$zhujian){
                        $va['is_key'] = 1;
                    }
                    if($va['field']=='id'){
                        $va['show'] = 1;
                    }
                    if($va['field']=='create_time'||$va['field']=='update_time'||$va['field']=='status'){
                        $va['sort'] = 10;
                        if($va['field']=='status'||$va['field']=='create_time'||$va['field']=='id'){
                            $va['show'] = 1;
                            if($va['field']=='status'){
                                $va['edit'] = 1;
                            }
                        }
                    }
                    if($va['field']=='delete_time'){
                        $va['sort'] = 0;
                    }
                    if($va['field']=='id'){
                        $va['sort'] = 9999;
                        $va['show'] = 1;
                        $va['is_key'] = 1;
                    }
                    if($va['field']=='name'){
                        $va['sort'] = 9000;
                    }
                    if($va['field']==$zhujian){
                        $va['is_key'] = 1;
                    }
                    if($va['field']=='status'){
                        $va['default'] = 1;
                    }
                    $va['create_time'] = time();
                    $va['update_time'] = time();
                    $va['addinput']=self::create_add_input($va);
                    $va['editinput']=self::create_edit_input($va);
                    $va['jscol']=self::create_js_col($va);
                    Db::name('system_field')
                        ->insert($va);
                }else{
                    if($va['field']==$zhujian){
                        $va['is_key'] = 1;
                    }
                    if($va['field']=='id'){
                        $va['show'] = 1;
                    }
                    $va['update_time'] = time();
                    $va['addinput']=self::create_add_input($va);
                    $va['editinput']=self::create_edit_input($va);
                    $va['jscol']=self::create_js_col($va);
                    Db::name('system_field')
                        ->where('id',$field['id'])
                        ->update($va);
                }
                $newarray[$key] = $va;
            }
            $value['model'] = $newarray;
        }


        return true;
    }
    protected static function updatetableField(){

    }

    /**强制更新
     * @param $dan_table
     * @param string $qianzui
     * @param string $mysql
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public static function updatetables($dan_table,$qianzui='',$mysql=''){
        if(empty($qianzui)){
            $qianzui = getDataBaseConfig('prefix');
        }
        if(empty($mysql)){
            $mysql = getDataBaseConfig('database');
        }
        $sql = "select TABLE_NAME  AS 'table',TABLE_COMMENT as 'name',engine as 'engine' from information_schema.tables where table_schema='".$mysql."'";
        $array = Db::query($sql);
        foreach ($array as $k=>$value){
            $table = $value['table'];
            $tables = str_replace($qianzui,'',$table);
            if($dan_table==$tables){
                $models = Db::name('system_model')
                    ->where('table',$tables)
                    ->findOrEmpty();
                if(empty($models)){
                    $data = [
                        'name'=>$value['name'],
                        'table'=>$tables,
                        'engine'=>$value['engine'],
                        'prefix'=>'',
                        'create_time'=>time(),
                        'update_time'=>time(),
                    ];
                    $data['id'] = Db::name('system_model')
                        ->insertGetId($data);
                    $newsql = 'show full fields from '.$table;
                    $newarray = Db::query($newsql);
                    $newarray = TablHandle::CommentToTabl($newarray,$tables);
                    foreach ($newarray as $key=>$va){
                        if($va['field']=='create_time'||$va['field']=='update_time'||$va['field']=='status'){
                            $va['sort'] = 10;
                            if($va['field']=='status'||$va['field']=='create_time'){
                                $va['show'] = 1;
                                if($va['field']=='status'){
                                    $va['edit'] = 1;
                                }
                            }
                        }
                        if($va['field']=='delete_time'){
                            $va['sort'] = 0;
                        }
                        if($va['field']=='id'){
                            $va['sort'] = 9999;
                            $va['show'] = 1;
                        }
                        if($va['field']=='name'){
                            $va['sort'] = 9000;
                        }
                        $field = Db::name('system_field')
                            ->where('table',$tables)
                            ->where('field',$va['field'])
                            ->findOrEmpty();
                        if(empty($field)){
                            $va['create_time'] = time();
                            $va['update_time'] = time();
                            Db::name('system_field')
                                ->insert($va);
                        }else{
                            $va['update_time'] = time();
                            Db::name('system_field')
                                ->where('id',$field['id'])
                                ->update($va);
                        }
                        $newarray[$key] = $va;
                    }
                    $value['model'] = $newarray;
                }else{
                    $newarray = [];
                    $newarray['engine'] = $value['engine'];
                    $newarray['name'] = $value['name'];
                    $newarray['update_time'] = time();
                    Db::name('system_model')
                        ->where('id',$models['id'])
                        ->update($newarray);
                    $newsql = 'show full fields from '.$table;
                    $newarray = Db::query($newsql);
//                echo '<pre>';
//                print_r($newarray);
//                exit;
                    $newarray = TablHandle::CommentToTabl($newarray,$tables);
//                    if($dan_table=='house_rent'){
//                        echo '<pre>';
//                        print_r($newarray);
//                        exit;
//                    }
                    foreach ($newarray as $key=>$va){
                        $field = Db::name('system_field')
                            ->where('table',$tables)
                            ->where('field',$va['field'])
                            ->findOrEmpty();
                        if(empty($field)){
                            $va['create_time'] = time();
                            $va['update_time'] = time();
                            Db::name('system_field')
                                ->insert($va);
                        }else{
                            $va['update_time'] = time();
                            Db::name('system_field')
                                ->where('id',$field['id'])
                                ->update($va);
                        }
                        $newarray[$key] = $va;
                    }
                    $value['model'] = $newarray;
                }
            }
//            $array[$k] = $value;
        }
        return true;
    }

    /**将type转化成字段
     * @param string $type
     * @return mixed
     */
    protected static function TypeToField(string $type=''){
        $type_start = mb_stripos($type,'(');
        $type_end = mb_stripos($type,')');
//        $newtype = '';
        $lang = '';
        if(($type_start!==false)&&($type_end!==false)){
            $lang = mb_substr($type,$type_start+1,($type_end-$type_start-1));
            $newtype = mb_substr($type,0,$type_start);
        }else{
            $newtype = $type;
        }
        $newdata['lang'] = $lang;
        $newdata['type'] = $newtype;
        return $newdata;
    }

    /**comment转化成表字段
     * @param string $comment
     * @param string $filed
     * @return mixed
     */
    protected static function CommentConversionField(string $comment='',$filed = ''){
        if(empty($comment)){
            $newdata['formtype'] = 'input';
            $newdata['option'] = '';
            if($filed=='id'){
                $newdata['show'] = 1;
            }else{
                $newdata['show'] = 0;
            }
            $newdata['edit'] = 0;
            $newdata['search'] = 0;
            $newdata['total'] = 0;
            $newdata['export'] = 0;
            if($filed=='id'){
                $newdata['name'] = 'ID';
            }else{
                $newdata['name'] = '';
            }
            return $newdata;
        }
        $newcomment_min = 0;
        $newdata = [];
        //处理表单类型
        $type_start = mb_stripos($comment,'{');
        $type_end = mb_stripos($comment,'}');
        $newdata['formtype'] = 'input';
        if(($type_start!==false)&&($type_end!==false)){
            if($newcomment_min==0){
                $newcomment_min = $type_start;
            }
            $str = mb_substr($comment,$type_start+1,($type_end-$type_start-1));
            $newdata['formtype'] = $str;
        }

        //处理选项
        $option_start = mb_stripos($comment,'(');
        $option_end = mb_stripos($comment,')');
        if(($option_start!==false)&&($option_end!==false)){
            if($newcomment_min==0){
                $newcomment_min = $option_start;
            }else{
                $newcomment_min = $newcomment_min<$option_start?$newcomment_min:$option_start;
            }
            $option = mb_substr($comment,$option_start+1,($option_end-$option_start-1));
            $array = explode(',',$option);
            $option = '';
            foreach ($array as $value){
                if($value){
                    $option_array = explode(':',$value);
                    if(count($option_array)==1){
                        if(!empty($option_array[0])){
                            $option .= $option_array[0].':'.$option_array[0].',';
                        }

                    }elseif(count($option_array)==2){
                        $option .= $option_array[0].':'.$option_array[1].',';
                    }
                }
            }
            $newdata['option'] = $option;
        }
        //处理
        $s_start = mb_stripos($comment,'[');
        $s_end = mb_stripos($comment,']');
        if(($s_start!==false)&&($s_end!==false)){
            if($newcomment_min==0){
                $newcomment_min = $s_start;
            }else{
                $newcomment_min = $newcomment_min<$s_start?$newcomment_min:$s_start;
            }
            $newstr = mb_substr($comment,$s_start+1,($s_end-$s_start-1));
            $newstr_array = explode(',',$newstr);
            foreach ($newstr_array as $value){
                if(!empty($value)){
                    $value_array = explode(':',$value);
                    $newdata[$value_array[0]] = $value_array[1];
                }
            }
        }
        if(!isset($newdata['show'])){
            $newdata['show'] = 0;
        }
        if(!isset($newdata['edit'])){
            $newdata['edit'] = 0;
        }
        if(!isset($newdata['search'])){
            $newdata['search'] = 0;
        }
        if(!isset($newdata['total'])){
            $newdata['total'] = 0;
        }
        if(!isset($newdata['export'])){
            $newdata['export'] = 0;
        }
//        echo $newcomment_min;exit;
        if($newcomment_min==0){
            $newdata['name'] = $comment;
        }else{
            $newdata['name'] = mb_substr($comment,0,$newcomment_min);
        }
        return $newdata;
    }

    /**获取当前名
     * @param $name
     * @return array
     */
    public static function getClassName($name){
        $array = explode('\\',$name);
        return $array;
    }

    /**将option的str转化成数组
     * @param string $str
     * @return array
     */
    public static function strToArray(string $str = ''){
        $newdata = [];
        $array = explode(',',$str);
        $num = 0;
        foreach ($array as $value){
            if($value){
                $newarray = explode(':',$value);
                if(count($newarray)==1){
                    if($newarray[0]){
                        $newdata[$newarray[0]] = [
                            'name'=>$newarray[0],
                            'num'=>$num
                        ];
                        $num++;
                    }
                }elseif (count($newarray)==2){
                    $newdata[$newarray[0]] = [
                        'name'=>$newarray[1],
                        'num'=>$num
                    ];
                    $num++;
                }
            }
        }
        return $newdata;
    }

    /**option转array
     * @param string $str
     * @return array
     */
    public static function optionToArray(string $str = ''){
        $newdata = [];
        $array = explode(',',$str);
        $num = 0;
        foreach ($array as $value){
            if($value){
                $newarray = explode(':',$value);
                if(count($newarray)==1){
                    if($newarray[0]){
                        $newdata[$newarray[0]] = $newarray[0];
                        $num++;
                    }
                }elseif (count($newarray)==2){
                    $newdata[$newarray[0]] = $newarray[1];
                    $num++;
                }
            }
        }
        return $newdata;
    }

    /**将table字段转化成字符串
     * @param string $table_name
     * @param string $table_id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function TableToFile($table_name='',$table_id=''){
        if(empty($table_name)&&empty($table_id)){
            return '';
        }
        if(isset($table_name)&&$table_name){
            $data = Db::name('system_model')
                ->where('table',$table_name)
                ->field('name,table,engine,tabletype,prefix,is_page')
                ->find();
        }
        if(isset($table_id)&&$table_id){
            $data = Db::name('system_model')
                ->where('id',$table_id)
                ->field('name,table,engine,tabletype,prefix,is_page')
                ->find();
        }
        if(empty($data)){
            return '';
        }
        $field = Db::name('system_field')
            ->where('table',$data['table'])
            ->field('name,field,type,lang,is_null,formtype,table,show,edit,search,total,export,sort,option,join_table')
            ->select()->toArray();
        $newfield = [];
        foreach ($field as $key=>$value){
            if(isset($value['option'])&&$value['option']){
                $option = self::optionToArray($value['option']);
            }else{
                $option = [];
            }
            $value['option'] = $option;
            $newfield[$value['field']] = $value;
        }
        $data['field'] = $newfield;
        return json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    /**获取转化字段类型
     * @param array $field
     * @return array
     */
    public static function conversionField($field=[]){
        foreach ($field as $key=>$value){
            if(in_array($value['formtype'],['lselect','lradio','lcheckbox'])){
                if(isset($value['table'])&&$value['table']){
                    $shuju = Db::name($value['join_table'])
                        ->where('status',1)
                        ->select()->toArray();
                    $value['option'] = $shuju;
                    if(!(isset($value['foreign_key'])&&$value['foreign_key'])){
                        $value['foreign_key'] = 'name';
                    }
                }else{
                    $value['option'] = [];
                }
            }
            $field[$key] = $value;
        }
        return $field;
    }

    /**更新字段
     * @param array $data
     * @return bool
     */
    public static function UpdateField(array  $originaldata=[],array $newdata=[]){
        $table = getDataBaseConfig('prefix').$originaldata['table'];
        $field = $originaldata['field'];
        $newfield = $newdata['field'];

        if($field==$newfield){
            $sql = "alter table `$table` modify column `$field` ";
        }else{
            $sql = "alter table `$table` change `$field` `$newfield` ";
        }

        $type = '';
        if(isset($newdata['type']) && $newdata['type']){
            if(isset($newdata['lang']) && $newdata['lang']  && !in_array($newdata['type'],['mediumtext','tinytext','text','longtext'])){
                $type = $newdata['type']."(".$newdata['lang'].") ";
            }else{
                if($newdata['formtype']=='datetime' || $newdata['formtype']=='month' || $newdata['formtype']=='date'){
                    $type = "BIGINT(20) ";
                }elseif ($newdata['type']=='time'){
                    $type = $newdata['type']." ";
                }else{
                    $type = $newdata['type']." ";
                }
            }
        }
        $sql .= $type;
        $default = '';

        if(strpos($newdata['type'],'text')!==false){
//            $da = $data['default'];
//            $default .= "DEFAULT NULL ";
//            return 1;
        }elseif (in_array($newdata['type'],['varchar'])){
//            return 2;
            $default .= "DEFAULT ";
            $default .= ($newdata['default']??null)?("'".$newdata['default']."' "):"'' ";
        }elseif ((strpos($newdata['type'],'int')!==false)||in_array($newdata['type'],['float'])){
            if(isset($newdata['default'])&&$newdata['default']){
//                return 3;
                $default .= "DEFAULT ".$newdata['default']." ";
            }else{
//                return 4;
                $default .= "DEFAULT 0 ";
            }
        }elseif ($newdata['formtype']=='datetime' || $newdata['formtype']=='month' || $newdata['formtype']=='date'){
            $default .= "DEFAULT NULL ";
        }elseif ($newdata['type']=='time'){
            $default .= "DEFAULT NULL ";
        }else{
            $da = $newdata['default'];
            $default .= "DEFAULT '$da' ";
        }

        $sql .= $default;
        $comment = ''.$newdata["name"];
        if(isset($newdata['formtype'])&&$newdata['formtype']){
            $comment .= "{".$newdata['formtype']."}";
        }
        if(isset($newdata['option'])&&$newdata['option']){
            $comment .= "(".$newdata['option'].")";
        }
        $comment .= '[';
        if(isset($newdata['show'])&&$newdata['show']){
            $comment .= "show:".($newdata['show']??0).',';
        }
        if(isset($newdata['edit'])&&$newdata['edit']){
            $comment .= "edit:".($data['edit']??0).',';
        }
        if(isset($newdata['search'])&&$newdata['search']){
            $comment .= "search:".($newdata['search']??0).',';
        }
        if(isset($newdata['total'])&&$newdata['total']){
            $comment .= "total:".($newdata['total']??0).',';
        }
        if(isset($newdata['export'])&&$newdata['export']){
            $comment .= "export:".($newdata['export']??0).',';
        }
        if(isset($newdata['sort'])&&$newdata['sort']){
            $comment .= "sort:".($newdata['sort']??'').',';
        }
        if(isset($newdata['join_table'])&&$newdata['join_table']){
            $comment .= "join_table:".($newdata['join_table']??'').',';
        }
        if(isset($newdata['default'])&&$newdata['default']){
            $comment .= "default:".($newdata['default']??'').',';
        }
        if(isset($newdata['foreign_key'])&&$newdata['foreign_key']){
            $comment .= "foreign_key:".($newdata['foreign_key']??'').',';
        }
        if(isset($newdata['status'])&&$newdata['status']){
            $comment .= "status:".($newdata['status']??1).',';
        }
        if(isset($newdata['describe'])&&$newdata['describe']){
            $comment .= "describe:".($newdata['describe']??'').',';
        }
        $comment = trim($comment,',');
        $comment .= ']';
//        return $comment;
//        echo $comment;exit;
        $sql .= "COMMENT '$comment'";

        try{
            Db::execute($sql);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage(), 0);
        }
        return true;
    }

    /**添加字段
     * @param array $data
     * @return bool
     */
    public static function AddField(array $data=[]){
        $table = $data['table'];
        $table = getDataBaseConfig('prefix').$table;
        $field = $data['field'];
        $sql = "ALTER TABLE `$table` ADD COLUMN `$field` ";
        $type = '';
        if(isset($data['type'])&&$data['type']){

            if(isset($data['lang'])&&$data['lang'] && !in_array($data['type'],['mediumtext','tinytext','text','longtext'])){
                $type = $data['type']."(".$data['lang'].") ";
            }else{
                if($data['formtype']=='datetime' || $data['formtype']=='month' || $data['formtype']=='date'){
                    $type = "BIGINT(20) ";
                }elseif ($data['type']=='time'){
                    $type = $data['type']." ";
                }else{
                    $type = $data['type']." ";
                }
            }
        }
        $sql .= $type;
        $default = '';

        if(strpos($data['type'],'text')!==false){
//            $da = $data['default'];
//            $default .= "DEFAULT NULL ";
//            return 1;
        }elseif (in_array($data['type'],['varchar'])){
//            return 2;
            $default .= "DEFAULT ";
            $default .= ($data['default']??null)?("'".$data['default']."' "):"'' ";
        }elseif ((strpos($data['type'],'int')!==false)||in_array($data['type'],['float'])){
            if(isset($data['default'])&&$data['default']){
//                return 3;
                $default .= "DEFAULT ".$data['default']." ";
            }else{
//                return 4;
                $default .= "DEFAULT 0 ";
            }
        }elseif ($data['formtype']=='datetime' || $data['formtype']=='month' || $data['formtype']=='date'){
            $default .= "DEFAULT NULL ";
        }elseif ($data['type']=='time'){
            $default .= "DEFAULT NULL ";
        }else{
            $da = $data['default'];
            $default .= "DEFAULT '$da' ";
        }
//        return $default;
        $sql .= $default;
        $comment = ''.$data["name"];
        if(isset($data['formtype'])&&$data['formtype']){
            $comment .= "{".$data['formtype']."}";
        }
        if(isset($data['option'])&&$data['option']){
            $comment .= "(".$data['option'].")";
        }
        $comment .= '[';
        if(isset($data['show'])&&$data['show']){
            $comment .= "show:".($data['show']??0).',';
        }
        if(isset($data['edit'])&&$data['edit']){
            $comment .= "edit:".($data['edit']??0).',';
        }
        if(isset($data['search'])&&$data['search']){
            $comment .= "search:".($data['search']??0).',';
        }
        if(isset($data['total'])&&$data['total']){
            $comment .= "total:".($data['total']??0).',';
        }
        if(isset($data['export'])&&$data['export']){
            $comment .= "export:".($data['export']??0).',';
        }
        if(isset($data['sort'])&&$data['sort']){
            $comment .= "sort:".($data['sort']??'').',';
        }
        if(isset($data['join_table'])&&$data['join_table']){
            $comment .= "join_table:".($data['join_table']??'').',';
        }
        if(isset($data['default'])&&$data['default']){
            $comment .= "default:".($data['default']??'').',';
        }
        if(isset($data['foreign_key'])&&$data['foreign_key']){
            $comment .= "foreign_key:".($data['foreign_key']??'').',';
        }
        if(isset($data['status'])&&$data['status']){
            $comment .= "status:".($data['status']??1).',';
        }
        if(isset($data['describe'])&&$data['describe']){
            $comment .= "describe:".($data['describe']??'').',';
        }
        $comment = trim($comment,',');
        $comment .= ']';
        $sql .= "COMMENT '$comment'";
        try{
            Db::execute($sql);
        }catch (\Exception $e){

            throw new \Exception($e->getMessage(),0);
        }catch (\Throwable $e)
        {
            throw new \Exception($e->getMessage(),0);
        }

        return true;
    }

    /**删除字段
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function DeleteField($data){
        foreach ($data as $value){
            $table = getDataBaseConfig('prefix').$value['table'];
            $field = $value['field'];
            $sql = "ALTER TABLE `$table` DROP COLUMN `$field`";
            try{
                Db::execute($sql);
            }catch (\Exception $e){
                $msg=$e->getMessage();
                if (preg_match("#check that column/key exists#is", $msg, $matches)) {
                    return true;
                }
                throw new \Exception($msg, 0);
            }
        }
        return true;
    }
//    创建表单字段
    public static function create_add_input($value){
        if(isset($value['is_key']) && $value['is_key'])return '';
        $xsname=empty($value['xsname'])?$value['name']:$value['xsname'];
        $xsname="{:fy('".$xsname."')}";
        $str='';
        $required='';
        $label_required=$label_required='';
        if(!isset($value['default'])){
            $value['default'] = '';
        }
        if(isset($value['rule']) && strpos($value['rule'],'require')!==false){
            $required=' lay-verify="required" ';
            $label_required=' required';
        }
        if($value['formtype']=='none'){
            $str .= '<input type="hidden" name="'.$value['field'].'" value="'.$value['default'].'">';
        }else{
            $value['formtype'] = trim($value['formtype']);
            if(!empty($value['grid'])){
                $str .= '<div class="'.$value['grid'].'">';
            }

            $str .= '<div class="layui-form-item">';
            $str .= '<label class="layui-form-label'.$label_required.'">'.$xsname.'</label>';
           if($value['formtype']=='city'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" data-toggle="city-picker" data-level="city" data-placeholder="请选择省/市" id="'.$value['field'].'" name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input" '.$required.'>';
                if(!empty($value['describe'])) if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif($value['formtype']=='district'){

                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" data-toggle="city-picker" data-level="district" data-placeholder="请选择省/市/区" id="'.$value['field'].'" name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input" '.$required.'>';
                if(!empty($value['describe'])) if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='json'){
                $str .= '<div class="layui-input-block">';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '<div class="layui-row">';
                $str .= '<div class="layui-form-mid">{:fy("Key")}：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" value="'.$value['default'].'">';
                $str .= '<div class="layui-form-mid">{:fy("Value")}：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" lay-filter="'.$value['field'].'[value][]" value="'.$value['default'].'">';
                $str .= '<div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>';
                $str .= '</div>';
            }elseif ($value['formtype']=='textarea'){
                $str .= '<div class="layui-input-block">';
                $str .= '<textarea '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-textarea" placeholder="{:fy("Please enter")}'.$xsname.'">'.$value['default'].'</textarea>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='file'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 file" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'">';
                $str .= '<div class="layuimini-upload-btn">';
               $uploadConfig = config('upload');
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="'.$uploadConfig['upload_allow_ext'].'" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='files'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 files" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'">';
                $str .= '<div class="layuimini-upload-btn">';
               $uploadConfig = config('upload');
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="'.$uploadConfig['upload_allow_ext'].'" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='image'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 img" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'">';
                $str .= '<div class="layuimini-upload-btn">';
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,jpeg,gif" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='images'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 imgs" '.$required.'placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'">';
                $str .= '<div class="layuimini-upload-btn">';
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="png,jpg,jpeg,gif" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif($value['formtype']=='video'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 video" '.$required.' placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'">';
                $str .= '<div class="layuimini-upload-btn">';
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="mp4|video" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif (($value['formtype']=='select')){
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
                $value['option']=trim($value['option']);
                if(!empty($value['option'])){
                    $value['option']=explode(',',$value['option']);
                    foreach ($value['option'] as $v){
                        $vv=explode(':',$v);
                        if($vv){
                            $vv[1]=isset($vv[1])?$vv[1]:$vv[0];
                            $selected = $value['default']==trim($vv[0])?' selected':'';
                            $str .= '<option value="'.trim($vv[0]).'"'.$selected.'">'.trim($vv[1]).'</option>';
                        }
                    }
                }

                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='aselect'){
                $name = $value['foreign_key']??'name';
                $name = explode(',',$name);
                $str .= '<div class="layui-input-block">';
                $str .= '<div class="layui-form-select">';
                $str .= '<div class="layui-select-title">';
                $str .= '<select '.$required.' name="'.$value['field'].'" '.$required.' lay-filter="'.$value['field'].'" data-select="'.($value['href']??'ajax/ajaxselect').'" data-fields="'.($value['relationship_primary_key']??'id').','.($value['foreign_key']??'name').'" data-value="{$row[\''.$value['field'].'\']|default=\'\'}">
        </select>';
                $str .= '</div>';
                $str .= '<dl id="'.$value['field'].'" class="layui-anim layui-anim-upbit"><dd lay-value="" class="layui-select-tips" style="text-align: center"><i class="layui-icon layui-icon-loading layui-icon layui-anim layui-anim-rotate layui-anim-loop">{:fy("Please select")}</i></dd></dl>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='treecheckbox'){
                $str .= '<div class="layui-input-block">';
                $str .= '<div id="'.$value['field'].'" class="demo-tree-more" data-url="'.($value['href']??'ajax/ajaxselect').'" data-name="'.($value['foreign_key']??'name').'" data-contentid="'.$value['field'].'" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'"></div>';
                $str .= '</div>';
            }elseif (($value['formtype']=='lselect')){
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
                $str .="{:build_option_input('{$value['join_table']}','{$value['relationship_primary_key']}','{$value['foreign_key']}','{$value['default']}')}";
                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif (($value['formtype']=='selectgroup')){
                $name = $value['foreign_key']??'name';
                $name = explode(',',$name);
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
                $str .= '{foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}';
                $str .= '{if isset($vo[\'child\'])&&count($vo[\'child\'])}';
                $str .= '<optgroup label="{$vo.'.$name[0].'|raw}">';
                $str .= '{foreach $vo[\'child\'] as $k=>$v}';
                $str .= '{if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}';
                $str .= '<option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>';
                $str .= '{else}';
                $str .= '<option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>';
                $str .= '{/if}';
                $str .= '{/foreach}';
                $str .= '{else}';
                $str .= '{if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}';
                $str .= '<option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>';
                $str .= '{else}';
                $str .= '<option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>';
                $str .= '{/if}';
                $str .= '{/if}';
                $str .= '{/foreach}';
                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='aselectgroup'){
                $name = $value['foreign_key']??'name';
                $name = explode(',',$name);
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select aselectgroup" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
//                    $str .= '{foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}';
//                    $str .= '{if isset($vo[\'child\'])&&count($vo[\'child\'])}';
//                    $str .= '<optgroup label="{$vo.'.$name[0].'|raw}">';
//                    $str .= '{foreach $vo[\'child\'] as $k=>$v}';
//                    $str .= '{if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}';
//                    $str .= '<option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>';
//                    $str .= '{else}';
//                    $str .= '<option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>';
//                    $str .= '{/if}';
//                    $str .= '{/foreach}';
//                    $str .= '{else}';
//                    $str .= '{if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}';
//                    $str .= '<option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>';
//                    $str .= '{else}';
//                    $str .= '<option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>';
//                    $str .= '{/if}';
//                    $str .= '{/if}';
//                    $str .= '{/foreach}';
                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='radio'){
                $str .= '<div class="layui-input-block">';
                $value['option']=explode(',',$value['option']);
                foreach ($value['option'] as $v){
                    $vv=explode(':',$v);
                    if($vv){
                        $checked = $value['default']==trim($vv[0])?' checked':'';
                        $str .= '<input type="radio" name="'.$value['field'].'" '.$checked.' lay-filter="'.$value['field'].'" value="'.trim($vv[0]).'" title="'.trim($vv[1]).'">';
                    }

                }
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='lradio'){
                $name = $value['foreign_key']??'name';
                $name = explode(',',$name);
                $str .= '<div class="layui-input-block">';
                $str .= '{foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}';
                $str .= '<input name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="radio" title="{$vo.'.$name[0].'}">';
                $str .= '{/foreach}';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='checkbox'){
                $str .= '<div class="layui-input-block">';
                $value['option']=trim($value['option']);
                if(!empty($value['option'])){
                    $value['option']=explode(',',$value['option']);
                    foreach ($value['option'] as $v){
                        $vv=explode(':',$v);
                        if($vv){
                            $checked = $value['default']==trim($vv[0])?' checked':'';
                            $str .= '<input type="checkbox" name="'.$value['field'].'[]" '.$checked.' lay-filter="'.$value['field'].'[]" lay-skin="primary" value="'.trim($vv[0]).'" title="'.trim($vv[1]).'">';
                        }
                    }
                }
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='lcheckbox'){
                /*  $name = $value['foreign_key']??'name';
                  $name = explode(',',$name);*/
                $str .= '<div class="layui-input-block">';
                $str .="{:build_checkbox_input('{$value['join_table']}','{$value['relationship_primary_key']}','{$value['foreign_key']}','{$value['field']}','{$value['default']}')}";

                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='color'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="hidden" name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="'.$value['default'].'">';
                $str .= '<div id="'.$value['field'].'" class="color"></div>';
                $str .= '</div>';
            }elseif ($value['formtype']=='icon'){
                $str .= '<div class="layui-input-block">';
                $str .= '<div id="'.$value['field'].'" class="icon"></div>';
                $str .= '</div>';
            }elseif ($value['formtype']=='popup_selection'){
               $join_table=PublicUse::UnderlineToHump($value['join_table']);
               $str .= '<div class="layui-input-block">';
               $str .= '<input type="hidden" name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="{$'.$join_table.'.'.$value['relationship_primary_key'].'|default="'.$value['default'].'"}"><a href="javascript:void(0)" data-id="'.$value['field'].'" data-field="'.$value['foreign_key'].','.$value['relationship_primary_key'].'" data-title="'.$xsname.'" data-name="'.$value['foreign_key'].'" open-select="'.$value['href'].'" class="layui-input" >{$'.$join_table.'.'.$value['foreign_key'].'|default="【请点击选择】"}</a>';
               $str .= '</div>';
           }elseif ($value['formtype']=='editor'){
                $str .= '<div class="layui-input-block">';
                $str .= '<textarea '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-textarea editor" style="height: 500px" placeholder="{:fy("Please enter")}'.$xsname.'">'.$value['default'].'</textarea>';
                $str .= '</div>';
            }elseif ($value['formtype']=='password'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="password" id="'.$value['field'].'" name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input" lay-reqtext="{:fy("Please enter")}'.$value['field'].'" placeholder="{:fy("Please enter")}'.$value['field'].'" >';
                $str .= '</div>';
            }elseif ($value['formtype']=='switch'){
//                    echo '<pre>';
//                    print_r($value);
//                    exit;
                $value['option']=explode(',',$value['option']);
                $str .= '<div class="layui-input-block">';
                $str .= '<input name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="switch" value="1" type="checkbox" lay-skin="switch" lay-text="'.($value['option'][1]??'{:fy("Open")}').'|'.($value['option'][0]??'{:fy("Close")}').'" {if $alldata[\'field\'][\''.$value['field'].'\'][\'default\']==1}checked=""{/if}>';
                $str .= '</div>';
            }elseif ($value['formtype']=='datetime'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text" data-date="yyyy-MM-dd HH:mm:ss" name="'.$value['field'].'" class="layui-input datetime" lay-filter="'.$value['field'].'" id="'.$value['field'].'" value="'.$value['default'].'" autocomplete="off">';
                $str .= '</div>';
            }elseif ($value['formtype']=='date'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text" data-date="yyyy-MM-dd" data-date-type="date" name="'.$value['field'].'" class="layui-input date" id="'.$value['field'].'" value="'.$value['default'].'" autocomplete="off" lay-filter="'.$value['field'].'" >';
                $str .= '</div>';
            }elseif ($value['formtype']=='month'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text" data-date="yyyy-MM" data-date-type="month" name="'.$value['field'].'" class="layui-input month" id="'.$value['field'].'" value="'.$value['default'].'" autocomplete="off" lay-filter="'.$value['field'].'">';
                $str .= '</div>';
            }elseif ($value['formtype']=='time'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text" data-date="HH:mm:ss" data-date-type="time" name="'.$value['field'].'" class="layui-input time" id="'.$value['field'].'" value="'.$value['default'].'" autocomplete="off" lay-filter="'.$value['field'].'">';
                $str .= '</div>';
            }else{
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" id="'.$value['field'].'" name="'.$value['field'].'" value="'.$value['default'].'" lay-filter="'.$value['field'].'" class="layui-input" '.$required.'lay-reqtext="{:fy("Please enter")}'.$xsname.'" placeholder="{:fy("Please enter")}'.$xsname.'">';
                if(!empty($value['describe'])) if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';

                $str .= '</div>';
            }
            if(!empty($value['grid'])){
                $str .= '</div>';
            }
            $str .= '</div>';
        }
        return $str;

    }
    public static function create_edit_input($value){
        if(isset($value['is_key']) && $value['is_key'])return '<input type="hidden" name="'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}" lay-filter="'.$value['field'].'">';
        $xsname=empty($value['xsname'])?$value['name']:$value['xsname'];
        $xsname="{:fy('".$xsname."')}";
        $str='';
        $required=$label_required='';
        if(!isset($value['default'])){
            $value['default'] = '';
        }
        if(isset($value['rule']) && strpos($value['rule'],'require')!==false){
            $required=' lay-verify="required" ';
            $label_required=' required';
        }
        if(isset($value['edit_readonly']) && $value['edit_readonly'])$required.=' disabled="disabled" ';
        if($value['formtype']=='none'){
            $str .= '<input type="hidden" lay-filter="'.$value['field'].'" name="'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}">';
        }else{
            if(!empty($value['grid'])){
                $str .= '<div class="'.$value['grid'].'">';
            }
            $str .= '<div class="layui-form-item">';
            $str .= '<label class="layui-form-label'.$label_required.'">'.$xsname.'</label>';
            if($value['formtype']=='city'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" data-toggle="city-picker" data-level="city" data-placeholder="请选择省/市" id="'.$value['field'].'" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input" '.$required.' value="{$row.'.$value['field'].'|default=\'\'}">';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif($value['formtype']=='district'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" data-toggle="city-picker" data-level="district" data-placeholder="请选择省/市/区" id="'.$value['field'].'" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input" '.$required.' value="{$row.'.$value['field'].'|default=\'\'}">';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='json'){
                $str .= '<div class="layui-input-block">';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '{if $row.'.$value['field'].'}';
                $str .= '{foreach $row.'.$value['field'].' as $ke=>$va}';
                $str .= '{if $ke==0}';
                $str .= '<div class="layui-row">';
                $str .= '<div class="layui-form-mid">{:fy("Key")}：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" value="{$va.key}">';
                $str .= '<div class="layui-form-mid">{:fy("Value")}：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" lay-filter="'.$value['field'].'[value][]" value="{$va.value}">';
                $str .= '<div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>';
                $str .= '</div>';
                $str .= '{else}';
                $str .= '<div class="layui-row">';
                $str .= '<div class="layui-form-mid">{:fy("Key")}：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" lay-filter="'.$value['field'].'[key][]" value="{$va.key}">';
                $str .= '<div class="layui-form-mid">{:fy("Value")}：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" lay-filter="'.$value['field'].'[value][]" value="{$va.value}">';
                $str .= '<div class="layui-form-mid"><input type="button" data-type="jian"  class="layui-btn layui-btn-sm layui-btn-primary layui-border-red" value="-"></div>';
                $str .= '</div>';
                $str .= '{/if}';
                $str .= '{/foreach}';
                $str .= '{else}';
                $str .= '<div class="layui-row">';
                $str .= '<div class="layui-form-mid">{:fy("Key")}：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" lay-filter="'.$value['field'].'[key][]" value="">';
                $str .= '<div class="layui-form-mid">{:fy("Value")}：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" lay-filter="'.$value['field'].'[value][]" value="">';
                $str .= '<div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>';
                $str .= '</div>';
                $str .= '{/if}';
                $str .= '</div>';
            }elseif ($value['formtype']=='textarea'){
                $str .= '<div class="layui-input-block">';
                $str .= '<textarea '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-textarea textarea" placeholder="{:fy("Please enter")}'.$xsname.'">{$row.'.$value['field'].'|default=\'\'}</textarea>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='file'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 file" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'" value="{$row.'.$value['field'].'|default=\'\'}">';
                $str .= '<div class="layuimini-upload-btn">';
                $uploadConfig = config('upload');
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="'.$uploadConfig['upload_allow_ext'].'" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='files'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 files" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'" value="{$row.'.$value['field'].'|default=\'\'}">';
                $str .= '<div class="layuimini-upload-btn">';
                $uploadConfig = config('upload');
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="'.$uploadConfig['upload_allow_ext'].'" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='image'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 img" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'" value="{$row.'.$value['field'].'|default=\'\'}">';
                $str .= '<div class="layuimini-upload-btn">';
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,jpeg,gif" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='images'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 imgs" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'" value="{$row.'.$value['field'].'|default=\'\'}">';
                $str .= '<div class="layuimini-upload-btn">';
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="png,jpg,jpeg,gif" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif($value['formtype']=='video'){
                $str .= '<div class="layui-input-block layuimini-upload">';
                $str .= '<input '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input layui-col-xs6 video" placeholder="{:fy("Please")}{:fy("Upload")}'.$xsname.'" value="{$row.'.$value['field'].'|default=\'\'}">';
                $str .= '<div class="layuimini-upload-btn">';
                $str .= '<a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> {:fy("Upload")}</a>';
                $str .= '</div>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='select'){
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
                $value['option']=explode(',',$value['option']);
                foreach ($value['option'] as $v){
                    $vv=explode(':',$v);
                    if($vv){
                        $vv[1]=isset($vv[1])?$vv[1]:$vv[0];
                        $str .= '<option value="'.trim($vv[0]).'" {if isset($row["'.$value['field'].'"])&&$row["'.$value['field'].'"]=="'.trim($vv[0]).'"}selected{/if}>'.trim($vv[1]).'</option>';
                    }

                }
                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif (($value['formtype']=='lselect')){
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
                $str .="{:build_option_input('{$value['join_table']}','{$value['relationship_primary_key']}','{$value['foreign_key']}'".',$row["'.$value["field"].'"])}';
                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif(($value['formtype']=='treecheckbox')){
                $str .= '<div class="layui-input-block">';
                $str .= '<div id="'.$value['field'].'" class="demo-tree-more" data-url="'.($value['href']??'ajax/ajaxselect').'" data-name="'.($value['foreign_key']??'name').'" data-contentid="'.$value['field'].'" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'" data-value="{$row[\''.$value['field'].'\']}"></div>';
                $str .= '</div>';
            }elseif (($value['formtype']=='selectgroup')){
                $name = $value['foreign_key']??'name';
                $name = explode(',',$name);
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-select" lay-search>';
                $str .= '<option value="">{:fy("Please select")}</option>';
                $str .= '{foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}';
                $str .= '{if isset($vo[\'child\'])&&count($vo[\'child\'])}';
                $str .= '<optgroup label="{$vo.'.$name[0].'|raw}">';
                $str .= '{foreach $vo[\'child\'] as $k=>$v}';
                $str .= '{if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}';
                $str .= '<option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>';
                $str .= '{else}';
                $str .= '<option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>';
                $str .= '{/if}';
                $str .= '{/foreach}';
                $str .= '{else}';
                $str .= '{if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}';
                $str .= '<option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>';
                $str .= '{else}';
                $str .= '<option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>';
                $str .= '{/if}';
                $str .= '{/if}';
                $str .= '{/foreach}';
                $str .= '</select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='aselect'){
                $str .= '<div class="layui-input-block">';
                $str .= '<select '.$required.' name="'.$value['field'].'" '.$required.' lay-filter="'.$value['field'].'" data-select="'.($value['href']??'ajax/ajaxselect').'" data-fields="'.($value['relationship_primary_key']??'id').','.($value['foreign_key']??'name').'" data-value="{$row[\''.$value['field'].'\']|default=\'\'}">
        </select>';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='radio'){
                $str .= '<div class="layui-input-block">';
                $value['option']=explode(',',$value['option']);
                foreach ($value['option'] as $v){
                    $vv=explode(':',$v);
                    if($vv){
                        $str .= '<input type="radio" name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="'.trim($vv[0]).'" title="'.trim($vv[1]).'" {if "'.trim($vv[0]).'"==$row.'.$value['field'].'}checked{/if}>';
                    }

                }
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='lradio'){
                $name = $value['foreign_key']??'name';
                $name = explode(',',$name);
                $str .= '<div class="layui-input-block">';
                $str .= '{foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}';
                $str .= '<input name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="radio" title="{$vo.'.$name[0].'}"  {if $vo.'.($value['relationship_primary_key']??'id').'==$row.'.$value['field'].'}checked=""{/if}>';
                $str .= '{/foreach}';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='checkbox'){
                $str .= '<div class="layui-input-block">';
                $value['option']=trim($value['option']);
                if(!empty($value['option'])){
                    $value['option']=explode(',',$value['option']);
                    foreach ($value['option'] as $v){
                        $vv=explode(':',$v);
                        if($vv){
                            $vv[0]=trim($vv[0]);
                            $str .= '<input type="checkbox" name="'.$value['field'].'[]" lay-filter="'.$value['field'].'[]" lay-skin="primary" value="'.$vv[0].'" title="'.trim($vv[1]).'" {if isset($row["'.$value['field'].'"]) && strpos($row["'.$value['field'].'"],"'.$vv[0].'")!==false}checked{/if}>';
                        }
                    }
                }
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='lcheckbox'){
                /*   $name = $value['foreign_key']??'name';
                   $name = explode(',',$name);*/
                $str .= '<div class="layui-input-block">';

                $str .="{:build_checkbox_input('{$value['join_table']}','{$value['relationship_primary_key']}','{$value['foreign_key']}'".",'{$value['field']}',\$row['".$value['field']."'])}";
                /*  $str .= '{foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}';
                  $str .= '<input name="'.$value['field'].'[]" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="checkbox" title="{$vo.'.$name[0].'}"  {if in_array($vo.id,explode(\',\',$row.'.$value['field'].'))}checked=""{/if}>';
                  $str .= '{/foreach}';*/
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }elseif ($value['formtype']=='color'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="hidden" name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="{$row.'.$value['field'].'}">';
                $str .= '<div id="'.$value['field'].'" value="{$row.'.$value['field'].'}" class="color"></div>';
                $str .= '</div>';
            }elseif ($value['formtype']=='icon'){
                $str .= '<div class="layui-input-block">';
                $str .= '<div id="'.$value['field'].'" value="{$row.'.$value['field'].'}" class="icon"></div>';
                $str .= '</div>';
            }elseif ($value['formtype']=='popup_selection'){
                $join_table=PublicUse::UnderlineToHump($value['join_table']);
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="hidden" name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="{$'.$join_table.'.'.$value['relationship_primary_key'].'|default="'.$value['default'].'"}"><a href="javascript:void(0)" data-id="'.$value['field'].'" data-field="'.$value['foreign_key'].','.$value['relationship_primary_key'].'" data-title="'.$xsname.'" data-name="'.$value['foreign_key'].'" open-select="'.$value['href'].'" class="layui-input" >{$'.$join_table.'.'.$value['foreign_key'].'|default="【请点击选择】"}</a>';
                $str .= '</div>';
            }elseif ($value['formtype']=='editor'){
                $str .= '<div class="layui-input-block">';
                $str .= '<textarea '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-textarea editor" style="height: 500px" placeholder="{:fy("Please enter")}'.$xsname.'">{$row.'.$value['field'].'|raw}</textarea>';
                $str .= '</div>';
            }elseif ($value['formtype']=='password'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="password" id="'.$value['field'].'" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input" lay-reqtext="{:fy("Please enter")}'.$value['field'].'" placeholder="{:fy("Please enter")}'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}">';
                $str .= '</div>';
            }elseif ($value['formtype']=='switch'){
                $value['option']=explode(',',$value['option']);
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' name="'.$value['field'].'" lay-filter="'.$value['field'].'" value="1" type="checkbox" class="switch" lay-skin="switch" lay-text="'.($value['option'][1]??'{:fy("Open")}').'|'.($value['option'][0]??'{:fy("Close")}').'" {if $row.'.$value['field'].'==1}checked=""{/if}>';
                $str .= '</div>';
            }elseif ($value['formtype']=='datetime'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" autocomplete="off" data-date="yyyy-MM-dd HH:mm:ss" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input datetime" id="'.$value['field'].'" {if is_numeric($row.'.$value['field'].')}value="{:mydate(\'Y-m-d H:i:s\',$row.'.$value['field'].')}" {else}value="{$row.'.$value['field'].'}"{/if}>';
                $str .= '</div>';
            }elseif ($value['formtype']=='date'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text"  autocomplete="off" data-date="yyyy-MM-dd" data-date-type="date" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input date" id="'.$value['field'].'" {if is_numeric($row.'.$value['field'].')}value="{:mydate(\'Y-m-d\',$row.'.$value['field'].')}" {else}value="{$row.'.$value['field'].'}"{/if}>';
                $str .= '</div>';
            }elseif ($value['formtype']=='month'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text"  autocomplete="off" data-date="yyyy-MM" data-date-type="month" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input month" id="'.$value['field'].'" {if is_numeric($row.'.$value['field'].')}value="{:mydate(\'Y-m\',$row.'.$value['field'].')}" {else}value="{$row.'.$value['field'].'}"{/if}>';
                $str .= '</div>';
            }elseif ($value['formtype']=='time'){
                $str .= '<div class="layui-input-block">';
                $str .= '<input '.$required.' type="text" autocomplete="off" data-date="HH:mm:ss" data-date-type="time" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input time" id="'.$value['field'].'" value="{$row.'.$value['field'].'}">';
                $str .= '</div>';
            }else{
                $str .= '<div class="layui-input-block">';
                $str .= '<input type="text" id="'.$value['field'].'" name="'.$value['field'].'" lay-filter="'.$value['field'].'" class="layui-input" '.$required.'lay-reqtext="{:fy("Please enter")}'.$xsname.'" placeholder="{:fy("Please enter")}'.$xsname.'" value="{$row.'.$value['field'].'|default=\'\'}">';
                if(!empty($value['describe'])) $str .= '<tip>'.fy($value['describe']).'</tip>';
                $str .= '</div>';
            }
            if(!empty($value['grid'])){
                $str .= '</div>';
            }
            $str .= '</div>';
        }
        return $str;
    }
    public static function create_js_col($value){
        $xsname=empty($value['xsname'])?$value['name']:$value['xsname'];
        $xsname="{:fy('".$xsname."')}";
        $str = $width='';
        $color = [
            '#FF0000',
            '#00CC00',
            '#3399CC',
            '#660099',
            '#9966FF',
            '#FF6633',
            '#CCCC99',
        ];

        $formtype = [
            'input',
            'textarea',
            'editor',
            'none',
            'video',
            'checkbox',
            'json',
            'datetime',
            'lcheckbox',
            'lradio',
            'color',
            'selectgroup',
            'treecheckbox',
            'date',
            'time',
            'month'
        ];
        $field=[];
        $field['field']=$value['field'];
        $field['title']=$xsname;
        if(isset($value['open_sort']) && $value['open_sort']){
            $field['sort']=true;
        }
        if($value['search']==1){
            $field['search']=true;
        }else{
            $field['search']=false;
        }
        if(!empty($value['width'])){
            $field['width']=$value['width'];
        }
        if($value['formtype']=='datetime'){
            $field['templet']='ea.table.datetime';
        }elseif($value['formtype']=='date'){
            $field['templet']='ea.table.date';
        }elseif($value['formtype']=='month'){
            $field['templet']='ea.table.month';
        }
        if(in_array($value['formtype'],$formtype)){
            if($value['field']=='id'){
                $field['totalRowText']='合计';

            }else{
                if(in_array($value['formtype'],['lradio','aselectgroup','selectgroup'])){
                    $foreign_key = explode(',',$value['foreign_key']);
                    $newfields = 'get'.str_replace('_','',$value['field']).'field';
                    foreach ($foreign_key as $k=>$vs){
                        $field_names = Db::name('system_field')
                            ->where('table',$value['join_table'])
                            ->where('field',$vs)
                            ->value('name');
                        $field['field']=$newfields.'.'.$vs;
                        $field['title']=$xsname.'.'.$field_names;
                    }

                }elseif($value['search']==1){
                    if($value['formtype']=='checkbox'||$value['formtype']=='lcheckbox'){
                        $field['search']=true;
                        if($value['total']==1){
                            $field['totalRow']=true;
                        }
                    }elseif($value['formtype']=='datetime' || $value['formtype']=='date' || $value['formtype']=='month'){
                        $field['search']='range';
                        if($value['total']==1){
                            $field['totalRow']=true;
                        }
                    }elseif($value['formtype']=='input' && ($value['type']=='tinyint' || $value['type']=='smallint' || $value['type']=='mediumint' || $value['type']=='int' || $value['type']=='integer' || $value['type']=='bigint' || $value['type']=='double' || $value['type']=='float' || $value['type']=='decimal' || $value['type']=='numeric')){
                        $field['search']='between';
                        if($value['total']==1){
                            $field['totalRow']=true;
                        }
                    }else{
                        if($value['total']==1){
                            $field['totalRow']=true;
                        }
                    }
                }else{
                    $field['search']=false;
                    if($value['total']==1){
                        $field['totalRow']=true;
                    }
                }
            }
        }elseif ($value['formtype']=='aselect' || $value['formtype']=='lselect'){
            if($value['search']==1){
//
                $field['search']='select';
                $field['selectList']="{:build_select_list('{$value['join_table']}','{$value['relationship_primary_key']}','{$value['foreign_key']}')}";
            }
        }elseif ($value['formtype']=='radio' || $value['formtype']=='select'){
            if($value['search']==1){
                $field['search']='select';
            }else{
                $field['search']=false;
            }
            $value['option']=explode(',',$value['option']);
            $selectList=[];
            foreach ($value['option'] as $v){
                $vv=explode(':',$v);
                if($vv){
                    $vv[1]=isset($vv[1])?$vv[1]:$vv[0];
                    $selectList[trim($vv[0])]=trim($vv[1]);
                }
            }
            $field['selectList']=$selectList;
            $field['templet']="ea.table.select";
        }elseif($value['formtype']=='switch'){
            $value['option']=explode(',',$value['option']);
            $close="{:fy('Close')}";
            $open="{:fy('Open')}";
            $field['tips']=[$value['option'][0]??$close,$value['option'][1]??$open];
            $field['templet']='ea.table.switch';
            if($value['search']==1){
                $field['search']='select';
                $field['selectList']=[$value['option'][0]??$close,$value['option'][1]??$open];
            }
        }elseif($value['formtype']=='city' || $value['formtype']=='district'){
            if($value['search']==1){
                $field['search']='city';
            }
        }elseif($value['formtype']=='tel'){
            $field['templet']="ea.table.tel";
        }elseif($value['formtype']=='url'){
            $field['templet']="ea.table.url";
        }elseif($value['formtype']=='image' || $value['formtype']=='images'){
            $field['search']=false;
            $field['templet']="ea.table.image";
        }elseif($value['formtype']=='file' || $value['formtype']=='files'){
            $field['search']=false;
            $field['templet']="ea.table.file";
        }elseif($value['formtype']=='popup_selection'){
            $join_table=PublicUse::UnderlineToHump($value['join_table']);
            $field['field']=$join_table.'.'.$value['foreign_key'];

        }

        return json_encode($field,320);
    }






    protected static function  randomColor(){
        $str = '#';
        for ($i=0;$i<6;$i++){
            $randNum = mt_rand(0,15);
            switch ($randNum){
                case 10:$randNum="A";break;
                case 11:$randNum="B";break;
                case 12:$randNum="C";break;
                case 13:$randNum="D";break;
                case 14:$randNum="E";break;
                case 15:$randNum="F";break;
            }
            $str .= $randNum;
        }
        return $str;
    }

}
