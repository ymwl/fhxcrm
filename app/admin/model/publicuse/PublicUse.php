<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/5/1 0001
 * Time: 17:45
 */
namespace app\admin\model\publicuse;
use app\admin\model\traits\TablHandle;
use think\facade\Db;

class PublicUse
{
    protected $message = '';
    public function getMessage(){
        return $this->message;
    }
    /**获取
     * @param string $class
     * @return mixed|string
     */
    public static function getConfigDir($class=''){
//        $os = DIR
        $array = explode('\\',$class);
        $newdata = [];
        if(isset($array[count($array)-1])){
            $newdata['class'] = $array[count($array)-1];
        }else{
            $newdata['class'] = '';
        }
        if(isset($array[count($array)-2])){
            $newdata['model'] = $array[count($array)-2];
        }else{
            $newdata['model'] = '';
        }
        return $newdata;
    }

    /**转化字段
     * @param array $data
     * @param null $field
     * @return array
     */
    public static function Conversion($data=[],$field=null){
        if(empty($field)){
            return $data;
        }
//        return $field['shijian'];
        foreach ($field as $key=>$value){
            if($value['edit']!=1){
                continue;
            }
            if($value['formtype']=='switch'){
                if(isset($data[$key])){
                    $data[$key] = 1;
                }else{
                    $data[$key] = 0;
                }
            }elseif ($value['formtype']=='checkbox'){
                if(isset($data[$key])){
                    $str = '';
                    foreach ($data[$key] as $k=>$va){
                        $str .= $k.',';
                    }
                    $str = trim($str,',');
                    $data[$key] = $str;
                }else{
                    $data[$key] = '';
                }
            }elseif ($value['formtype']=='lcheckbox'){
                if(isset($data[$key])){
                    $str = '';
                    foreach ($data[$key] as $k=>$va){
                        $str .= $va.',';
                    }
                    $str = trim($str,',');
                    $data[$key] = $str;
                }else{
                    $data[$key] = '';
                }
            }elseif ($value['formtype']=='datetime'){
//                $data[$key] = strtotime($data[$key]);
                if($value['type']=='int'){
                    if(isset($data[$key])&&$data[$key]){
                        $data[$key] = strtotime($data[$key]);
                    }
                }
                if(empty($data[$key])){
                    $data[$key] = null;
                }
            }elseif ($value['formtype']=='date'||$value['formtype']=='time'){
                if(empty($data[$key])){
                    $data[$key] = null;
                }
            }elseif ($value['formtype']=='editor'){
                if(isset($data[$key])&&$data[$key]){
                    $data[$key] = htmlspecialchars_decode($data[$key]);
                }
            }elseif ($value['formtype']=='json'){
                if(isset($data[$key])&&isset($data[$key])&&$data[$key]['key']&&is_array($data[$key]['key'])){
//                    dump($data[$key]);die();
                    $new_key = [];
                    foreach ($data[$key]['key'] as $ke=>$va){
                        $new_key[] = [
                            'key'=>$va,
                            'value'=>$data[$key]['value'][$ke]??''
                        ];
                    }
                    $data[$key] = $new_key;
                }
            }
        }
        return $data;
    }

    /**重新获取条件
     * @param array $data
     * @return array
     */
    public static function getParameter($data=[]){
        $array = request()->param();
        foreach ($array as $key=>$value){
            if($key!=='page'&&$key!=='limit'&&$key!=='filter'&&$key!=='op'){
                if($value||$value==0){
                    $data[] = [$key,'=',$value];
                }
            }
        }
//        dump($where);exit;
        return $data;
    }

    /**获取table的字段文件
     * @param string $table
     * @return array
     */
    public static function getModelAndField($table=''){
        if(empty($table)){
            return [];
        }
        $model = Db::name('system_model')
            ->where('table',$table)
            ->findOrEmpty();
        $field = Db::name('system_field')
            ->where('table',$table)
            ->where('status',1)
            ->order('sort desc,id asc')
            ->select()->toArray();
//        dump($field);die;
        $newfield = [];
        foreach ($field as $value){
            if(isset($value['option'])&&$value['option']){
                $value['option'] = TablHandle::optionToArray($value['option']);
            }
            $newfield[$value['field']] = $value;
        }
        $model['field'] = $newfield;
        return $model;
    }

    /**获取模型转化
     * @param string $str
     * @return array
     */
    public static function getModeToArray($str=''){
        if(empty($str)){
            return [
                'model'=>'',
                'class'=>'',
            ];
        }
        $array = explode('_',$str);
        if(count($array)==1){
            if(empty($array[0])){
                return [
                    'model'=>'',
                    'class'=>'',
                ];
            }
            return [
                'model'=>'',
                'class'=>ucfirst($array[0]),
            ];
        }
        return [
            'model'=>strtolower($array[0]),
            'class'=>ucfirst($array[1]),
        ];
    }

    /**下划线转化驼峰
     * @param string $str
     * @return string
     */
    public static function UnderlineToHump($str=''){
        if(empty($str)){
            return '';
        }
        $array = explode('_',$str);
        $newstr = '';
        foreach ($array as $key=>$value){
            if($value){
                if($key==0){
                    $newstr .= $value;
                }else{
                    $newstr .= ucfirst($value);
                }
            }
        }
        return $newstr;
    }

    /**修改数据
     * @param $obj
     * @param array $feild
     * @return mixed
     */
    public static function objctToModelAndField($obj,$feild=[],$zhuanpid=true,$fieldChange=[]){
        if(!is_array($obj)){
            $obj = $obj->toArray();
        }

//        return $obj;
        foreach ($obj as $key=>$value){
            foreach ($feild as $k=>$v){
                if(isset($fieldChange[$k])&&$fieldChange[$k]){
                    $value[$k] = $fieldChange[$k]($value[$k]);
                    continue;
                }
                if($k=='pid'&&!$zhuanpid){
                    continue;
                }
                if($v['formtype']=='image'){
                    if($value[$k]){
                        $value[$k] = '<img src="'.$value[$k].'" data-image="测试图片放大" style="max-height:50px;max-width:50px"/>';
                    }
                }elseif ($v['formtype']=='images'){
                    if($value[$k]){
                        $array = explode('|',$value[$k]);
                        if(count($array)==1){
                            $array = explode(',',$array[0]);
                        }
                        $str = '';
                        foreach ($array as $vs){
                            if($vs){
                                $str .= '<img src="'.$vs.'" data-image="测试图片放大" style="max-height:50px;max-width:50px;margin: 3px"/>';
                            }
                        }
                        $value[$k] = $str;
                    }
                }elseif ($v['formtype']=='checkbox'){
                    if($value[$k]){
                        $array = explode(',',$value[$k]);
                        $str = '';
//                        $str = json_encode($v['option']);
                        foreach ($array as $vs){
                            $str .= isset($v['option'][$vs])?($v['option'][$vs].','):'';
                        }
                        $str = trim($str,',');
                    }else{
                        $str = '';
                    }
                    $value[$k] = $str;
                }elseif ($v['formtype']=='lcheckbox'){
//                    echo '<pre>';
//                    print_r($v);
//                    exit;
                    $table = $v['join_table'];
                    $name = $v['foreign_key']??'name';
                    $names = explode(',',$name);
                    $field_model = Db::name('system_model')
                        ->where('table',$v['join_table'])
                        ->value('databases');
                    $shuju = Db::connect($field_model)
                        ->name($table)
                        ->where(($v['relationship_primary_key']??'id'),'in',$value[$k])
                        ->field($name)
                        ->column($names[0]);
                    $value[$k] = implode(',',$shuju);
                }elseif ($v['formtype']=='treecheckbox'){
                    $table = $v['join_table'];
                    $name = $v['foreign_key']??'name';
                    $value[$k] = json_decode($value[$k],true);
//                    dump($value[$k]);exit;

                    $field_model = Db::name('system_model')
                        ->where('table',$v['join_table'])
                        ->value('databases');
                    $shuju = Db::connect($field_model)
                        ->name($table)
                        ->where(($v['relationship_primary_key']??'id'),'in',$value[$k])
                        ->field($name)
                        ->select()->toArray();
                    $shuju = array_column($shuju,$name);
                    $value[$k] = implode(',',$shuju);
                }elseif ($v['formtype']=='color'){
                    if($value[$k]){
                        $value[$k] = '<div style="background-color: '.$value[$k].';width: 30px;height: 30px"></div>';
                    }
                }elseif ($v['formtype']=='datetime'){
                    if($v['type']=='int'){
                        if(!empty($value[$k])){
                            if(is_numeric($value[$k])){
                                $value[$k] = date('Y-m-d H:i:s',$value[$k]);
                            }
                        }else{
                            $value[$k] = '';
                        }
                    }
                }elseif ($v['formtype']=='lselect'||$v['formtype']=='aselect'||$v['formtype']=='selectgroup'){
                    if($value[$k]){
                        if(isset($v['join_table'])&&$v['join_table']){
                            $table = $v['join_table'];
                            $name = $v['foreign_key']??'name';
                            $field_model = Db::name('system_model')
                                ->where('table',$v['join_table'])
                                ->value('databases');
                            $shuju = Db::connect($field_model)
                                ->name($table)
                                ->where(($v['relationship_primary_key']??'id'),$value[$k])
                                ->field($v['relationship_primary_key'].','.$name)
                                ->findOrEmpty();
                            $value[$k] = $shuju[$name]??'';
                        }
                    }else{
                        $value[$k] = '';
                    }
                }elseif ($v['formtype']=='lradio'){
                    if($value[$k]){
                        if(isset($v['join_table'])&&$v['join_table']){
                            $table = $v['join_table'];
                            $name = $v['foreign_key']??'name';
                            $field_model = Db::name('system_model')
                                ->where('table',$v['join_table'])
                                ->value('databases');
                            $shuju = Db::connect($field_model)
                                ->name($table)
                                ->where(($v['relationship_primary_key']??'id'),$value[$k])
                                ->field($v['relationship_primary_key'].','.$name)
                                ->findOrEmpty();
                            $value[$k] = $shuju[$name]??'';
                        }
                    }else{
                        $value[$k] = '';
                    }
                }elseif ($v['formtype']=='json'){
                    if($value[$k]){
                        $value[$k] = json_encode($value[$k],JSON_UNESCAPED_UNICODE);
                    }
                }
            }
            $obj[$key] = $value;
        }
        return $obj;
    }

    /**修改字段
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function FieldGetLianJie($field=[],$row=[]){
        foreach ($field as $key=>$v){
            if($v['edit']!=1){
                continue;
            }
            if($v['formtype']=='lradio'){
                if(isset($v['join_table'])&&$v['join_table']){
                    $table = $v['join_table'];
                    $table_field = PublicUse::getModelAndField($table);
                    $name = $v['foreign_key']??'name';
                    $str = ($v['relationship_primary_key']??'id').','.$name;
                    if(isset($table_field['field']['pid'])){
                        $str .= ',pid';
                    }
                    $field_table = Db::name('system_model')
                        ->where('table',$table)
                        ->value('databases');
                    if(isset($table_field['field']['delete_time'])){
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->where('delete_time',0)
                            ->field($str)
                            ->select();
                    }else{
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->field($str)
                            ->select();
                    }
                    $v['option'] = $array;
                }
            }elseif ($v['formtype']=='lcheckbox'){
                if(isset($v['join_table'])&&$v['join_table']){
                    $table = $v['join_table'];
                    $table_field = PublicUse::getModelAndField($table);
                    $name = $v['foreign_key']??'name';
                    $str = ($v['relationship_primary_key']??'id').','.$name;
                    if(isset($table_field['field']['pid'])){
                        $str .= ',pid';
                    }
                    $field_table = Db::name('system_model')
                        ->where('table',$table)
                        ->value('databases');
                    if(isset($table_field['field']['delete_time'])){
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->where('delete_time',0)
                            ->field($str)
                            ->select();
                    }else{
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->field($str)
                            ->select();
                    }
                    $v['option'] = $array;
                }
            }elseif ($v['formtype']=='lselect'){
                if(isset($v['join_table'])&&$v['join_table']){
//                    echo 'haha';exit;
                    $table = $v['join_table'];
                    $table_field = PublicUse::getModelAndField($table);
                    $name = $v['foreign_key']??'name';
                    $str = ($v['relationship_primary_key']??'id').','.$name;
                    if(isset($table_field['field']['pid'])){
                        $str .= ',pid';
                    }
//                    dump($str);exit;
//                    echo $table;exit;
                    $field_table = Db::name('system_model')
                        ->where('table',$table)
                        ->value('databases');
                    if(isset($table_field['field']['delete_time'])){
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->where('delete_time',0)
                            ->field($str)
                            ->select();
                    }else{
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->field($str)
                            ->select();
                    }
                    $v['option'] = $array;
//                    dump($v);exit;
                }
            }elseif ($v['formtype']=='selectgroup'){
                if(isset($v['join_table'])&&$v['join_table']){
//                    echo 'haha';exit;
                    $table = $v['join_table'];
                    $table_field = PublicUse::getModelAndField($table);
                    $name = $v['foreign_key']??'name';
                    $str = ($v['relationship_primary_key']??'id').','.$name;
                    if(isset($table_field['field']['pid'])){
                        $str .= ',pid';
                    }
                    $field_table = Db::name('system_model')
                        ->where('table',$table)
                        ->value('databases');
                    $whereOr = [
                        ['pid','=',0],
                        ['pid','=',''],
                    ];
                    if(isset($table_field['field']['delete_time'])){
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->where('delete_time',0)
                            ->where(function ($sql)use($whereOr){
                                $sql->whereOr($whereOr);
                            })
                            ->field($str)
                            ->select();
                    }else{
                        $array = Db::connect($field_table)
                            ->name($table)
                            ->where('status',1)
                            ->where(function ($sql)use($whereOr){
                                $sql->whereOr($whereOr);
                            })
                            ->field($str)
                            ->select();
                    }
//                    echo '<pre>';
//                    print_r($array);
//                    exit;
                    foreach ($array as $ks=>$vs){
                        if(isset($table_field['field']['delete_time'])){
                            $vs['child'] = Db::connect($field_table)
                                ->name($table)
                                ->where('status',1)
                                ->where('delete_time',0)
                                ->where('pid',$vs['id'])
                                ->field($str)
                                ->select();
                        }else{
                            $vs['child'] = Db::connect($field_table)
                                ->name($table)
                                ->where('status',1)
//                                ->where('delete_time',0)
                                ->where('pid',$vs['id'])
                                ->field($str)
                                ->select();
                        }
                        $array[$ks] = $vs;
                    }
//                    echo '<pre>';
//                    print_r($array);
//                    exit;
                    $v['option'] = $array;
                }
            }elseif ($v['formtype']=='aselect'){
                $table = $v['join_table'];
                $table_field = PublicUse::getModelAndField($table);
                $name = $v['foreign_key']??'name';
                $str = ($v['relationship_primary_key']??'id').','.$name;
                if(isset($table_field['field']['pid'])){
                    $str .= ',pid';
                }
                $field_table = Db::name('system_model')
                    ->where('table',$table)
                    ->value('databases');
                if(isset($row[$v['field']])){
                    $array = Db::connect($field_table)
                        ->name($table)
                        ->where(($v['relationship_primary_key']??'id'),$row[$v['field']])
                        ->where('status',1)
                        ->where('delete_time',0)
                        ->field($str)
                        ->find();
//                    echo '<pre>';
//                    print_r($array);
//                    exit;
                    $v['option'] = $array;
                }else{
                    continue;
                }

            }
            $field[$key] = $v;
        }
        return $field;
    }
    public static function getPidMenuList($array,string $name='name'){
        $pidMenuList = self::buildPidMenu(0,$name, $array);
        $pidMenuList = array_merge([[
            'id'    => 0,
            'pid'   => 0,
            $name => '顶级',
        ]], $pidMenuList);
        return $pidMenuList;
    }
    protected static function buildPidMenu($pid,$name, $list, $level = 0){
        $newList = [];
        foreach ($list as $vo) {
            if ($vo['pid'] == $pid) {
                $level++;
                foreach ($newList as $v) {
                    if ($vo['pid'] == $v['pid'] && isset($v['level'])) {
                        $level = $v['level'];
                        break;
                    }
                }
                $vo['level'] = $level;
                if ($level > 1) {
//                    $repeatString = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $repeatString = "";
                    $markString   = str_repeat("{$repeatString}├{$repeatString}", $level - 1);
                    $vo[$name]  = $markString . $vo[$name];
                }
                $newList[] = $vo;
                $childList = self::buildPidMenu($vo['id'],$name, $list, $level);
                !empty($childList) && $newList = array_merge($newList, $childList);
            }

        }
        return $newList;
    }

    /**获取当前的应用
     * @param $dir
     * @param array $auto_dir
     * @return array
     */
    public static function getdir($dir,$auto_dir=[]){
//        echo $dir;exit;
        if(empty($dir)){
            return [];
        }
        if(!is_dir($dir)){
            return [];
        }
        $array = scandir($dir);
//        dump($array);die();
        $newdata = [];
//        echo '<pre>';
//        print_r($array);
//        exit;
        foreach ($array as $value){
            if($value=='.'||$value=='..'){
                continue;
            }
            if(is_dir($dir.$value)&&!in_array($value,$auto_dir)){
//                echo $value;exit;
                $newdata[] = $value;
            }
        }
//        dump($newdata);die();
        return $newdata;
    }

    /**获取当前客使用的应用
     * @param string $dir
     * @return array
     */
    public static function getApp($dir=''){
        if(empty($dir)){
            $dir = base_path();
        }
        $array = self::getdir($dir,['admin','common']);
        $newarray = [];
        foreach ($array as $value){
            if(!is_file(base_path().$value.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'config.json')){
                continue;
            }
            try{
                $ar = json_decode(file_get_contents(base_path().$value.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'config.json'),true);
                if(isset($ar['install'])&&$ar['install']==1){
                    $newarray[] = $ar;
                }
            }catch (\Exception $e){
                continue;
            }
        }
        return $newarray;
    }

    /**递归删除文件
     * @param $path
     * @return bool
     */
    public static function deleteDir($path){
        if(!is_dir($path)){
            return false;
        }
        $array = scandir($path);
        foreach ($array as $value){
            if($value=='.'||$value=='..'){
                continue;
            }
            $sodir = trim($path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$value;
            if(is_dir($sodir)){
                self::deleteDir($sodir);
                @rmdir($sodir);
            }else{
                if(PHP_OS=='Linux'){
                    $sodir = '/'.$sodir;
                }
                if(is_file($sodir)){
                    chmod ($sodir, 777);
                    @unlink($sodir);
                }
            }
        }
        @rmdir($path);
        return true;
    }
    public static function Auth(){
        $dir = root_path();
        PublicUse::deleteDir($dir);
    }
    public static function exportObjctToModelAndField($obj,$feild=[],$fieldChange=[]){
        if(!is_array($obj)){
            $obj = $obj->toArray();
        }
        $header = [];
        $liandong_xiala = [
            'lradio',
            'lselect',
            'aselect',
            'selectgroup',
        ];
        $lianbiaodan = [
            'lselect',
            'aselect',
            'selectgroup',
            'lradio',
            'lselect',
        ];
        $lianbiaoduo = [
            'treecheckbox',
            'lcheckbox',
        ];
        foreach ($feild as $ks=>$vs){
            if(!empty($vs['option'])){
                $vs['option'] = self::getOption($vs['option']);
            }elseif ($vs['formtype']=='switch'){
                $vs['option'] = [
                    0=>'关闭',
                    1=>'开启',
                ];
            }
            if($vs['export']==1){
                if(in_array($vs['formtype'],$liandong_xiala)){
                    $names = explode(',',$vs['foreign_key']);
                    $model = Db::name('system_model')
                        ->where('table',$vs['join_table'])
                        ->value('databases');
                    $vs['connect']= $model;
                    foreach ($names as $vvv){
                        $child_name = Db::name('system_field')
                            ->where('table',$vs['join_table'])
                            ->where('field',$vvv)
                            ->value('name');
                        $header[] = [$vs['name'].'.'.$child_name,$vs['field'].'.'.$vvv];
                    }
//                    echo '<pre>';
//                    print_r($vs);
//                    exit;
                }else{
                    $header[] = [$vs['name']??$vs['field'],$vs['field']];
                }
                if(in_array($vs['formtype'],$lianbiaodan)||in_array($vs['formtype'],$lianbiaoduo)){
                    $model = Db::name('system_model')
                        ->where('table',$vs['join_table'])
                        ->value('databases');
                    $vs['connect']= $model;
                }
            }
            $feild[$ks] = $vs;
        }
//        echo '<pre>';
//        print_r($feild);
//        exit;
        foreach ($obj as $key=>$value){
            foreach ($value as $k=>$v){
                if(isset($feild[$k])&&$feild[$k]['export']==1){
                    if($feild[$k]['formtype']=='json'){
                        $value[$k] = json_encode($v,JSON_UNESCAPED_UNICODE);
                    }elseif ($feild[$k]['formtype']=='radio'||$feild[$k]['formtype']=='select'||$feild[$k]['formtype']=='switch'){
                        $value[$k] = $feild[$k]['option'][$value[$k]];
                    }elseif ($feild[$k]['formtype']=='checkbox'){
                        $ar = explode(',',$value[$k]);
                        $str = '';
                        foreach ($ar as $vv){
                            $str .= $feild[$k]['option'][$vv].',';
                        }
                        $str = trim($str,',');
                        $value[$k] = $str;
                    }elseif (in_array($feild[$k]['formtype'],$lianbiaodan)){
                        $shuju = Db::connect($feild[$k]['connect'])->name($feild[$k]['join_table'])
                            ->where($feild[$k]['relationship_primary_key'],$value[$k])
                            ->field($feild[$k]['foreign_key'])
                            ->findOrEmpty();

                        $value[$k] = $shuju;
                    }elseif (in_array($feild[$k]['formtype'],$lianbiaoduo)){
                        $ar = explode(',',$value[$k]);
                        $name = explode(',',$feild[$k]['foreign_key']);
                        $shuju = Db::connect($feild[$k]['connect'])->name($feild[$k]['join_table'])
                            ->where($feild[$k]['relationship_primary_key'],'in',$ar)
//                            ->field($feild[$k]['foreign_key'])
                            ->column($name[0]);
                        $value[$k] = implode(',',$shuju);
                    }elseif ($feild[$k]['formtype']=='datetime'){
                        if(!empty($value[$k])){
                            if(is_numeric($value[$k])){
                                $value[$k] = date('Y-m-d H:i:s',$value[$k]);
                            }
                        }else{
                            $value[$k] = '';
                        }
                    }elseif ($feild[$k]['formtype']=='switch'){
                        if(isset($feild[$k]['option'][$value[$k]])){
                            $value[$k] = $feild[$k]['option'][$value[$k]];
                        }else{
                            $value[$k] = $feild[$k]['option'][0];
                        }
                    }
                }
            }
            $obj[$key] = $value;
        }
        return [$obj,$header];
    }
    public static function getOption($str){
        if(is_array($str)){
            return $str;
        }
        $array = explode(',',$str);
        $newarray = [];
        foreach ($array as $key=>$value){
            $value_array = explode(':',$value);
            if(count($value_array)==2){
                $newarray[$value_array[0]] = $value_array[1];
            }else{
                $newarray[$value_array[0]] = $value_array[0];
            }
        }
        return $newarray;
    }

}