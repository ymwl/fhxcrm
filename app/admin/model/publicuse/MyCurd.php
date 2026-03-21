<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/5/2 0002
 * Time: 16:38
 */
namespace app\admin\model\publicuse;
use app\admin\model\traits\TablHandle;
use Doctrine\Common\Annotations\PhpParser;
use think\facade\Db;

class MyCurd
{
    /**创建控制器
     * @param array $data
     */
    public function create_class($data=[],$name='',$href=''){
        $Hump = PublicUse::getModeToArray($data['table']);
        $class = PublicUse::UnderlineToHump($data['table']);
        $classname = explode('/',$href);
        $classname = explode('.',$classname[0]);
        if(empty($Hump['model'])){
            $file_dir = root_path().'app'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'controller';
        }else{
            $file_dir = root_path().'app'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'controller';
        }
        if(!is_dir($file_dir)){
            chmod(root_path().'app'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'controller',0777);
            mkdir($file_dir,0777,true);
        }
//        dump($classname);die();
        if(!empty($href)){
            if(count($classname)==2){
                if(empty($Hump['model'])){
                    $Hump['model'] = strtolower($classname[0]);
                }
                $Hump['class'] = ucfirst($classname[1]);
                if(!is_dir($file_dir.DIRECTORY_SEPARATOR.strtolower($classname[0]))){
//                    chmod($file_dir.DIRECTORY_SEPARATOR.$classname[0],0777);
                    mkdir($file_dir.DIRECTORY_SEPARATOR.strtolower($classname[0]),0777,true);
                }
                $file_name = $file_dir.DIRECTORY_SEPARATOR.strtolower($classname[0]).DIRECTORY_SEPARATOR.ucfirst($classname[1]).'.php';
//                $classname = $classname[0].DIRECTORY_SEPARATOR.ucfirst($classname[1]);
            }else{
                $Hump['class'] = ucfirst($classname[0]);
                $file_name = $file_dir.DIRECTORY_SEPARATOR.ucfirst($classname[0]).'.php';
            }
        }
        $str = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'controllers.txt');
        $str = str_replace('{{ModelName}}',ucfirst($class),$str);//替换模型名
        if(count($classname)==2){
            $namespace ='app\admin\controller\\'.strtolower($classname[0]).';';
        }else{
            $namespace ='app\admin\controller;';
        }
//        echo $namespace;exit;
//        dump($Hump['class']);die();
        $str = str_replace('{{nameSpaceName}}',$namespace,$str);//替换命名空间
        $str = str_replace('{{titleName}}',$name,$str);//替换注解
        $str = str_replace('{{calss}}',$Hump['class'],$str);//替换注解
        $allowModifyFields = '';
        $noExportFields = '';
        foreach ($data['field'] as $key=>$value){
            //获取可修改的字段
            if($value['formtype']=='switch'){
                $allowModifyFields .= "        '".$value['field']."',".PHP_EOL;
            }
            //获取不可导出字段
            if($value['export']!=1){
                $noExportFields .= "        '".$value['field']."',".PHP_EOL;
            }
        }
        $str = str_replace('{{allowModifyFields}}',$allowModifyFields,$str);//替换可修改字段
        $str = str_replace('{{noExportFields}}',$noExportFields,$str);//替换可修改字段
        //保存文件
//        echo $file_name;exit;
        $bool = file_put_contents($file_name,$str);
        if(!$bool){
            return false;
        }
        return true;
    }

    /**创建模型
     * @param array $data
     */
    public function create_model($data=[]){
        $Hump = PublicUse::UnderlineToHump($data['table']);
//        echo '<pre>';
//        print_r($data);
//        exit;
//        dump($data['field']);exit;
        $haidelete = false;
        $k = 'id';
        $with = '';
        foreach ($data['field'] as $key=>$value){
            if($value['field']=='delete_time'){
                $haidelete = true;
            }
            if($value['is_key']==1){
                $k = $key;
            }
        }
        $jsons = '[';
//        $with =
        foreach ($data['field'] as $value){
            if($value['formtype']=='json'){
                $jsons .='\''.$value['field'].'\',';
            }
            if(in_array($value['formtype'],['lradio','lselect','aselect','aselectgroup','selectgroup'])){
                $join_table = PublicUse::UnderlineToHump($value['join_table']);
                $newclass = ucfirst($join_table);
                $newfield = $value['field'];
                $files = str_replace('_','',$newfield);
                $pk = $value['relationship_primary_key'];
                if(empty($with)){
                    $with .= <<<EOT
public function get{$files}field(){
EOT;
                    $with .= PHP_EOL.'        return $this->hasOne('.$newclass.'::class,"'.$pk.'","'.$newfield.'");'.PHP_EOL.'   }'.PHP_EOL;
                }else{
                    $with .= <<<EOT
    public function get{$files}field(){
EOT;
                    $with .= PHP_EOL.'        return $this->hasOne('.$newclass.'::class,"'.$pk.'","'.$newfield.'");'.PHP_EOL.'   }'.PHP_EOL;
                }

            }
        }
//        dump($with);exit;
        $jsons = rtrim($jsons,',');
        $jsons.= ']';
        if($haidelete){
            $str = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'model.txt');
        }else{
            $str = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'model1.txt');
        }
//        dump($data);exit;
        $str = str_replace('{{ModelName}}',ucfirst($Hump),$str);//替换模型名
        $str = str_replace('{{tableName}}',$data['table'],$str);//替换模型表名
        $str = str_replace('{{ID}}',$k,$str);//替换主键
        $str = str_replace('{{JSON}}',$jsons,$str);//替换主键
        $str = str_replace('{{CONNECTION}}',$data['databases'],$str);//替换连接
        $str = str_replace('{{WITH}}',$with,$str);//替换with
        //保存文件
        $file_dir = root_path().'app'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'model';
        if(!is_dir($file_dir)){
            chmod(root_path().'app'.DIRECTORY_SEPARATOR.'admin',0777);
            mkdir($file_dir,0777,true);
        }
        $file_dir .= DIRECTORY_SEPARATOR.ucfirst($Hump).'.php';
        $bool = file_put_contents($file_dir,$str);
        return $bool;
    }

    /**创建html文件
     * @param array $data
     * @param array $meun
     * @return bool|int
     */
    public function create_html($data=[],$meun=[]){
        if(isset($meun['tablebar'])&&$meun['tablebar']){
            $tablebar = explode(';',htmlspecialchars_decode($meun['tablebar']));
            $newtablebar = [];
            foreach ($tablebar as $value){
                if($value){
                    $newtablebar[] = TablHandle::optionToArray($value);
                }
            }
            $meun['tablebar'] = $newtablebar;
        }else{
            $meun['tablebar'] = [];
        }
        if(isset($meun['right_button'])&&$meun['right_button']){
            $tablebar = explode(';',htmlspecialchars_decode($meun['right_button']));
            $newtablebar = [];
            foreach ($tablebar as $value){
                if($value){
                    $newtablebar[] = TablHandle::optionToArray($value);
                }
            }
            $meun['right_button'] = $newtablebar;
        }else{
            $meun['right_button'] = [];
        }
        $name_array = explode('/',$meun['href']);
        $name_array = explode('.',$name_array[0]);
//        echo '<pre>';
//        print_r($name_array);
//        exit;
        $str = '';
        foreach ($name_array as $value){
            $str .= strtolower($value).DIRECTORY_SEPARATOR;
        }
//        echo '<pre>';
//        print_r($str);
//        exit;
        $str = trim($str,DIRECTORY_SEPARATOR);
        $file_name = root_path().'app'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.$str;
//        echo '<pre>';
//        print_r($file_name);
//        print_r($meun);
//        exit;
        if(!is_dir($file_name)){
            chmod(root_path().'app'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'view',0777);
            mkdir($file_name,0777,true);
        }
//        dump($file_name);die();
        $bool = $this->create_index($data,$file_name,$meun);
        if(!$bool){
            return false;
        }
//        exit;
        $bool = $this->create_add($data,$file_name,$meun);
        if(!$bool){
            return false;
        }
        $bool = $this->create_edit($data,$file_name,$meun);
        if(!$bool){
            return false;
        }
        $bool = $this->create_details($data,$file_name,$meun);
        if(!$bool){
            return false;
        }
        $bool = $this->create_js($data,$file_name,$meun);
//        echo '<pre>';
//        var_dump($bool);
//        exit;
        if(!$bool){
            return false;
        }
        return $bool;
    }

    /**创建index
     * @param array $data
     * @param string $file_dir
     * @return bool|int
     */
    protected function create_index($data=[],$file_dir='',$menu=[]){
//        echo '<pre>';
//        print_r($menu);
//        exit;
        if($data['tabletype']!='ordinary'&&empty($menu['right_button'])){
            $right_button_href = explode('/',$menu['href']);
            $menu['right_button'] = [
              [
                  'desc'=>'添加下级',
                  'href'=>$right_button_href[0].'/add?field=pid',
                  'auth'=>'add',
                  'type'=>'a',
                  'condition'=>'d.status==1',
              ]
            ];
        }
        $k = 'id';
        foreach ($data['field'] as $key=>$value){
            if($value['is_key']==1){
                $k = $key;
            }
        }
        $str = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'index.txt');
        $auth = [];
        $indexAuth = '';
        foreach ($menu['tablebar'] as $value){
            if(!in_array($value['auth'],$auth)){
                $auth[] = $value['auth'];
                $indexAuth .= '               data-auth-'.($value['auth']??'as').'="{:auth(\''.$value['href'].'\')}"'.PHP_EOL;
            }
        }
        $classname = explode('/',$menu['href']);
        foreach ($menu['right_button'] as $value){
            if(!in_array($value['auth'],$auth)){
                $auth[] = $value['auth'];
                $indexAuth .= '               data-auth-'.($value['auth']??'as').'="{:auth(\''.$value['href'].'\')}"'.PHP_EOL;
            }
        }
        $DefaultRightButton = '';
        if(isset($menu['default_right_button'])&&$menu['default_right_button']){
            $RightButton = explode(',',$menu['default_right_button']);
        }else{
            $RightButton = [];
        }
        foreach ($RightButton as $value){
            if($value=='edit'){
                $DefaultRightButton .= '<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$classname[0].'/'.$value.'\')}layui-hide{/if}" data-open="'.$classname[0].'/'.$value.'?'.$k.'={{d.'.$k.'}}" data-title="编辑">编辑</button>'.PHP_EOL;
            }elseif ($value=='delete'){
                $DefaultRightButton .= '<button class="layui-btn layui-btn-danger layui-btn-xs {if !auth(\''.$classname[0].'/'.$value.'\')}layui-hide{/if}" data-request="'.$classname[0].'/'.$value.'?'.$k.'={{d.'.$k.'}}" data-title="删除">删除</button>'.PHP_EOL;
            }elseif ($value=='details'){
                $DefaultRightButton .= '<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$classname[0].'/'.$value.'\')}layui-hide{/if}" data-open="'.$classname[0].'/'.$value.'?'.$k.'={{d.'.$k.'}}" data-title="详情">详情</button>'.PHP_EOL;
            }
        }
        $str = str_replace('{{DefaultRightButton}}',$DefaultRightButton,$str);//替换默认右侧按钮

        $newRightButton = '';
//        dump($menu['right_button']);die();
        foreach ($menu['right_button'] as $value){
            if(strpos($value['href'],'?')!==false){
                $vhref = $value['href'].'&'.$k.'={{d.'.$k.'}}';
            }else{
                $vhref = $value['href'].'?'.$k.'={{d.'.$k.'}}';
            }
            if(isset($value['condition'])){
                if($value['type']=='a'){
                    if(isset($value['data-full'])&&$value['data-full']=='true'){
                        $newRightButton .= '{{# if('.$value['condition'].'){}}<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$value['href'].'\')}layui-hide{/if}" data-open="'.$vhref.'" data-full="true" data-title="'.$value['desc'].'">'.$value['desc'].'</button>{{# } }}'.PHP_EOL;
                    }else{
                        $newRightButton .= '{{# if('.$value['condition'].'){}}<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$value['href'].'\')}layui-hide{/if}" data-open="'.$vhref.'" data-title="'.$value['desc'].'">'.$value['desc'].'</button>{{# } }}'.PHP_EOL;
                    }
                }else{
                    $newRightButton .= '{{# if('.$value['condition'].'){}}<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$value['href'].'\')}layui-hide{/if}" data-request="'.$vhref.'"  data-title="'.$value['desc'].'">'.$value['desc'].'</button>{{# } }}'.PHP_EOL;
                }
            }else{
                if($value['type']=='a'){
                    if(isset($value['data-full'])&&$value['data-full']=='true'){
                        $newRightButton .= '<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$value['href'].'\')}layui-hide{/if}" data-open="'.$vhref.'" data-full="true" data-title="'.$value['desc'].'">'.$value['desc'].'</button>'.PHP_EOL;
                    }else{
                        $newRightButton .= '<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$value['href'].'\')}layui-hide{/if}" data-open="'.$vhref.'" data-title="'.$value['desc'].'">'.$value['desc'].'</button>'.PHP_EOL;
                    }

                }else{
                    $newRightButton .= '<button class="layui-btn layui-btn-normal layui-btn-xs {if !auth(\''.$value['href'].'\')}layui-hide{/if}" data-request="'.$vhref.'" data-title="'.$value['desc'].'">'.$value['desc'].'</button>'.PHP_EOL;
                }
            }
        }
        $str = str_replace('{{RightButton}}',$newRightButton,$str);//替换右侧按钮
        if($menu['feed']==1){
            $style = ".layui-table-cell  {
        height:auto;
        overflow:visible;
        text-overflow:inherit;
        white-space:pre-line;
    }";
        }else{
            $style = "";
        }
        $str = str_replace('{{STYLE}}',$style,$str);//替换是否字符换行
//        echo '<pre>';
//        print_r($menu);
//        exit;
//        echo '<pre>';
//        var_dump($DefaultRightButton);
//        exit;
//        echo $indexAuth;exit;
        $str = str_replace('{{toolBar}}',$indexAuth,$str);//替换toolBar
        $str = str_replace('{{class}}',$classname[0],$str);//替换toolBar
        $bool = file_put_contents($file_dir.DIRECTORY_SEPARATOR.'index.html',$str);
        if(!$bool){
            return $bool;
        }
        return $bool;
    }
    protected function create_add($data=[],$file_dir='',$menu=[]){
//        echo '<pre>';
//        print_r($data['field']);
//        exit;
        $mystr = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'add.txt');
        $str = '';
        foreach ($data['field'] as $key=>$value){
            if($value['formtype']=='none'){
                $str .= '<input type="hidden" name="'.$value['field'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
            }else{
                if($value['edit']!=1){
                    continue;
                }
                $value['formtype'] = trim($value['formtype']);
                $str .= '        <div class="layui-form-item">'.PHP_EOL;
                $str .= '            <label class="layui-form-label">'.$value['name'].'</label>'.PHP_EOL;
                if($value['formtype']=='input'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" id="'.$value['field'].'" name="'.$value['field'].'" class="layui-input" lay-reqtext="请输入'.$value['name'].'" placeholder="请输入'.$value['name'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='json'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" value="">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" value="">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='textarea'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <textarea name="'.$value['field'].'" class="layui-textarea" placeholder="请输入'.$value['name'].'">{$row[\''.$value['field'].'\']|default=\'\'}</textarea>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='file'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 file" placeholder="请上传'.$value['name'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='files'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 files" placeholder="请上传'.$value['name'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='image'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 img" placeholder="请上传'.$value['name'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='images'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 imgs" placeholder="请上传'.$value['name'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif($value['formtype']=='video'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 video" placeholder="请上传'.$value['name'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif (($value['formtype']=='select')){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <option value="{$key}">{$vo|raw}</option>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='aselect'){
//                    echo '<pre>';
//                    print_r($value);
//                    exit;
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    <div class="layui-form-select" style="width: 200px">'.PHP_EOL;
                    $str .= '                        <div class="layui-select-title">'.PHP_EOL;
                    $str .= '                            <input type="text" data-name="'.$name[0].'" class="layui-input aselect" placeholder="请输入'.$value['name'].'" data-url="'.($value['href']??'ajax/ajaxselect').'" autocomplete="off" data-contentid="'.$value['field'].'" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'">'.PHP_EOL;
                    $str .= '                            <input name="'.$value['field'].'" type="hidden" value="">'.PHP_EOL;
                    $str .= '                            <i class="layui-edge"></i>'.PHP_EOL;
                    $str .= '                        </div>'.PHP_EOL;
                    $str .= '                        <dl id="'.$value['field'].'" class="layui-anim layui-anim-upbit"><dd lay-value="" class="layui-select-tips" style="text-align: center"><i class="layui-icon layui-icon-loading layui-icon layui-anim layui-anim-rotate layui-anim-loop">请选择</i></dd></dl>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='treecheckbox'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    <div id="'.$value['field'].'" class="demo-tree-more" data-url="'.($value['href']??'ajax/ajaxselect').'" data-name="'.($value['foreign_key']??'name').'" data-contentid="'.$value['field'].'" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif (($value['formtype']=='lselect')){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.id}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif (($value['formtype']=='selectgroup')){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    {if isset($vo[\'child\'])&&count($vo[\'child\'])}'.PHP_EOL;
                    $str .= '                    <optgroup label="{$vo.'.$name[0].'|raw}">'.PHP_EOL;
                    $str .= '                    {foreach $vo[\'child\'] as $k=>$v}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='aselectgroup'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select aselectgroup" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
//                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
//                    $str .= '                    {if isset($vo[\'child\'])&&count($vo[\'child\'])}'.PHP_EOL;
//                    $str .= '                    <optgroup label="{$vo.'.$name[0].'|raw}">'.PHP_EOL;
//                    $str .= '                    {foreach $vo[\'child\'] as $k=>$v}'.PHP_EOL;
//                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
//                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
//                    $str .= '                    {else}'.PHP_EOL;
//                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
//                    $str .= '                    {/if}'.PHP_EOL;
//                    $str .= '                    {/foreach}'.PHP_EOL;
//                    $str .= '                    {else}'.PHP_EOL;
//                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
//                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
//                    $str .= '                    {else}'.PHP_EOL;
//                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
//                    $str .= '                    {/if}'.PHP_EOL;
//                    $str .= '                    {/if}'.PHP_EOL;
//                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='radio'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'" value="{$key}" type="radio" title="{$vo}"  {if $key==$alldata[\'field\'][\''.$value['field'].'\'][\'default\']}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='lradio'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="radio" title="{$vo.'.$name[0].'}">'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='checkbox'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'[{$key}]" value="{$key}" type="checkbox" title="{$vo}"  {if $key==$alldata[\'field\'][\''.$value['field'].'\'][\'default\']}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='lcheckbox'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'[]" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="checkbox" title="{$vo.'.$name[0].'}">'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='color'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="hidden" name="'.$value['field'].'" value="">'.PHP_EOL;
                    $str .= '                <div id="'.$value['field'].'" class="color"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='icon'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <div id="'.$value['field'].'" class="icon"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='editor'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <textarea name="'.$value['field'].'" class="layui-textarea editor" style="height: 500px" placeholder="请输入'.$value['name'].'"></textarea>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='password'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="password" id="'.$value['field'].'" name="'.$value['field'].'" class="layui-input" lay-reqtext="请输入'.$value['field'].'" placeholder="请输入'.$value['field'].'" value="{$row[\''.$value['field'].'\']|default=\'\'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='switch'){
//                    echo '<pre>';
//                    print_r($value);
//                    exit;
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="switch" value="1" type="checkbox" lay-skin="switch" lay-text="'.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'" {if $alldata[\'field\'][\''.$value['field'].'\'][\'default\']==1}checked=""{/if}>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='datetime'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input datetime" id="'.$value['field'].'" value="">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='date'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input date" id="'.$value['field'].'" value="">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='time'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input time" id="'.$value['field'].'" value="">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }
//                $str .= '            </div>'.PHP_EOL;
                $str .= '        </div>'.PHP_EOL;
            }
        }
        $mystr = str_replace('{{FormContent}}',$str,$mystr);//替换模型名
        $bool = file_put_contents($file_dir.DIRECTORY_SEPARATOR.'add.html',$mystr);
        return $bool;

    }
    protected function create_edit($data=[],$file_dir='',$menu=[]){
        $mystr = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'edit.txt');
        $str = '';
        foreach ($data['field'] as $key=>$value){
            if($value['formtype']=='none'){
                $str .= '<input type="hidden" name="'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
            }else{
                if($value['edit']!=1){
                    continue;
                }
                $str .= '        <div class="layui-form-item">'.PHP_EOL;
                $str .= '            <label class="layui-form-label">'.$value['name'].'</label>'.PHP_EOL;
                if($value['formtype']=='input'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" id="'.$value['field'].'" name="'.$value['field'].'" class="layui-input" lay-reqtext="请输入'.$value['name'].'" placeholder="请输入'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='json'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '                {if $row.'.$value['field'].'}'.PHP_EOL;
                    $str .= '                {foreach $row.'.$value['field'].' as $ke=>$va}'.PHP_EOL;
                    $str .= '                {if $ke==0}'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" value="{$va.key}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" value="{$va.value}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                {else}'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" value="{$va.key}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" value="{$va.value}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="jian"  class="layui-btn layui-btn-sm layui-btn-primary layui-border-red" value="-"></div>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                {/if}'.PHP_EOL;
                    $str .= '                {/foreach}'.PHP_EOL;
                    $str .= '                {else}'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" name="'.$value['field'].'[key][]" value="">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" name="'.$value['field'].'[value][]" value="">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                {/if}'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='textarea'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <textarea name="'.$value['field'].'" class="layui-textarea textarea" placeholder="请输入'.$value['name'].'">{$row.'.$value['field'].'|default=\'\'}</textarea>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='file'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 file" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='files'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 files" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='image'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 img" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='images'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 imgs" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="100" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif($value['formtype']=='video'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 video" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            <div class="layuimini-upload-btn">'.PHP_EOL;
                    $str .= '                <span><a class="layui-btn" data-upload="'.$value['field'].'" data-upload-number="one" data-upload-exts="png,jpg,ico,jpeg,apk,xls,xlsx,txt,doc,docx,ppt,pptx,video" data-upload-icon="image"><i class="fa fa-upload"></i> 上传</a></span>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='select'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <option value="{$key}" {if $key==$row.'.$value['field'].'}selected{/if}>{$vo|raw}</option>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif (($value['formtype']=='lselect')){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" {if $vo.'.($value['relationship_primary_key']??'id').'==$row.'.$value['field'].'}selected{/if}>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '                </div>'.PHP_EOL;
                }elseif ($value['formtype']=='treecheckbox'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    <div id="'.$value['field'].'" class="demo-tree-more" data-url="'.($value['href']??'ajax/ajaxselect').'" data-name="'.($value['foreign_key']??'name').'" data-contentid="'.$value['field'].'" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'" data-value="{$row[\''.$value['field'].'\']}"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif (($value['formtype']=='selectgroup')){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" lay-search>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    {if isset($vo[\'child\'])&&count($vo[\'child\'])}'.PHP_EOL;
                    $str .= '                    <optgroup label="{$vo.'.$name[0].'|raw}">'.PHP_EOL;
                    $str .= '                    {foreach $vo[\'child\'] as $k=>$v}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='aselect'){
//                    echo '<pre>';
//                    print_r($value);
//                    exit;
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    <div class="layui-form-select" style="width: 200px">'.PHP_EOL;
                    $str .= '                        <div class="layui-select-title">'.PHP_EOL;
                    $str .= '                            <input type="text" data-name="'.$name[0].'" class="layui-input aselect" placeholder="请输入'.$value['name'].'" data-url="'.($value['href']??'ajax/ajaxselect').'" autocomplete="off" data-contentid="'.$value['field'].'"  value="{$alldata[\'field\'][\''.$value['field'].'\'][\'option\'][\''.$name[0].'\']|default=\'\'}" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'">'.PHP_EOL;
                    $str .= '                            <input name="'.$value['field'].'" type="hidden" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '                            <i class="layui-edge"></i>'.PHP_EOL;
                    $str .= '                        </div>'.PHP_EOL;
                    $str .= '                        <dl id="'.$value['field'].'" class="layui-anim layui-anim-upbit"><dd lay-value="" class="layui-select-tips" style="text-align: center"><i class="layui-icon layui-icon-loading layui-icon layui-anim layui-anim-rotate layui-anim-loop">请选择</i></dd></dl>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='radio'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'" value="{$key}" type="radio" title="{$vo}"  {if $key==$row.'.$value['field'].'}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='lradio'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="radio" title="{$vo.'.$name[0].'}"  {if $vo.'.($value['relationship_primary_key']??'id').'==$row.'.$value['field'].'}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='checkbox'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'[{$key}]" value="{$key}" type="checkbox" title="{$vo}"  {if in_array($key,explode(\',\',$row.'.$value['field'].'))}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='lcheckbox'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'[]" value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="checkbox" title="{$vo.'.$name[0].'}"  {if in_array($vo.id,explode(\',\',$row.'.$value['field'].'))}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='color'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="hidden" name="'.$value['field'].'" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '                <div id="'.$value['field'].'" value="{$row.'.$value['field'].'}" class="color"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='icon'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <div id="'.$value['field'].'" value="{$row.'.$value['field'].'}" class="icon"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='editor'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <textarea name="'.$value['field'].'" class="layui-textarea editor" style="height: 500px" placeholder="请输入'.$value['name'].'">{$row.'.$value['field'].'|raw}</textarea>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='password'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="password" id="'.$value['field'].'" name="'.$value['field'].'" class="layui-input" lay-reqtext="请输入'.$value['field'].'" placeholder="请输入'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='switch'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" value="1" type="checkbox" class="switch" lay-skin="switch" lay-text="'.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'" {if $row.'.$value['field'].'==1}checked=""{/if}>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='datetime'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input datetime" id="'.$value['field'].'" {if is_numeric($row.'.$value['field'].')}value="{:date(\'Y-m-d H:i:s\',$row.'.$value['field'].')}" {else}value="{$row.'.$value['field'].'}"{/if}>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='date'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input date" id="'.$value['field'].'" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='time'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input time" id="'.$value['field'].'" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }
//                $str .= '            </div>'.PHP_EOL;
                $str .= '        </div>'.PHP_EOL;
            }
        }
        $mystr = str_replace('{{FormContent}}',$str,$mystr);//替换模型名
        $bool = file_put_contents($file_dir.DIRECTORY_SEPARATOR.'edit.html',$mystr);
        return $bool;
    }
    protected function create_details($data=[],$file_dir='',$menu=[]){
        $mystr = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'details.txt');
        $str = '';
        foreach ($data['field'] as $key=>$value){
            if($value['formtype']=='none'){
                $str .= '<input type="hidden" name="'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
            }else{
                if($value['edit']!=1){
                    continue;
                }
                $str .= '        <div class="layui-form-item">'.PHP_EOL;
                $str .= '            <label class="layui-form-label">'.$value['name'].'</label>'.PHP_EOL;
                if($value['formtype']=='input'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" id="'.$value['field'].'" name="'.$value['field'].'" readonly class="layui-input" lay-reqtext="请输入'.$value['name'].'" placeholder="请输入'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='json'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '                {if $row.data}'.PHP_EOL;
                    $str .= '                {foreach $row.data as $ke=>$va}'.PHP_EOL;
                    $str .= '                {if $ke==0}'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" readonly name="'.$value['field'].'[key][]" value="{$va.key}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" readonly name="'.$value['field'].'[value][]" value="{$va.value}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                {else}'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" readonly name="'.$value['field'].'[key][]" value="{$va.key}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" readonly name="'.$value['field'].'[value][]" value="{$va.value}">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="jian"  class="layui-btn layui-btn-sm layui-btn-primary layui-border-red" value="-"></div>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                {/if}'.PHP_EOL;
                    $str .= '                {/foreach}'.PHP_EOL;
                    $str .= '                {else}'.PHP_EOL;
                    $str .= '                    <div class="layui-row">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">键：</div><input class="layui-input layui-input-inline" style="width: 100px" readonly name="'.$value['field'].'[key][]" value="">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid">值：</div><input class="layui-input layui-input-inline" style="width: 150px" readonly name="'.$value['field'].'[value][]" value="">'.PHP_EOL;
                    $str .= '                        <div class="layui-form-mid"><input type="button" data-type="tianjia" data-value="'.$value['field'].'" class="layui-btn layui-btn-sm layui-btn-primary layui-border-blue" value="+"></div>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                {/if}'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='textarea'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <textarea name="'.$value['field'].'" class="layui-textarea textarea" readonly placeholder="请输入'.$value['name'].'">{$row.'.$value['field'].'|default=\'\'}</textarea>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='file'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 file" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='files'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 files" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='image'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 img" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '                {if $row.'.$value['field'].'}'.PHP_EOL;
                    $str .= '            <div>'.PHP_EOL;
                    $str .= '                <img src="{$row.'.$value['field'].'}" style="width: 100px;height: 100px"/>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '            {/if}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='images'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 imgs" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                  $str .= '                {if $row.'.$value['field'].'&&$imgs=explode("|",$row.'.$value['field'].')}'.PHP_EOL;
                    $str .= '            <div>'.PHP_EOL;
                    $str .= '            {foreach $imgs as $img}'.PHP_EOL;
                    $str .= '                <img src="{$img}" style="width: 100px;height: 100px"/>'.PHP_EOL;
                    $str .= '            {/foreach}'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                    $str .= '            {/if}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif($value['formtype']=='video'){
                    $str .= '            <div class="layui-input-block layuimini-upload">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" class="layui-input layui-col-xs6 video" placeholder="请上传'.$value['name'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='select'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" readonly>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <option value="{$key}" {if $key==$row.'.$value['field'].'}selected{/if}>{$vo|raw}</option>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif (($value['formtype']=='lselect')){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" readonly>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" {if $vo.'.($value['relationship_primary_key']??'id').'==$row.'.$value['field'].'}selected{/if}>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '                </div>'.PHP_EOL;
                }elseif (($value['formtype']=='selectgroup')){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <select name="'.$value['field'].'" class="layui-select" readonly>'.PHP_EOL;
                    $str .= '                    <option value="">请选择</option>'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    {if isset($vo[\'child\'])&&count($vo[\'child\'])}'.PHP_EOL;
                    $str .= '                    <optgroup label="{$vo.'.$name[0].'|raw}">'.PHP_EOL;
                    $str .= '                    {foreach $vo[\'child\'] as $k=>$v}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$v.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}" selected>{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$v.'.($value['relationship_primary_key']??'id').'}">{$v.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    {if isset($row[\''.$value['field'].'\'])&&$row[\''.$value['field'].'\']==$vo.'.($value['relationship_primary_key']??'id').'}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}" selected>{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {else}'.PHP_EOL;
                    $str .= '                    <option value="{$vo.'.($value['relationship_primary_key']??'id').'}">{$vo.'.$name[0].'|raw}</option>'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/if}'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                </select>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='aselect'){
//                    echo '<pre>';
//                    print_r($value);
//                    exit;
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    <div class="layui-form-select" style="width: 200px">'.PHP_EOL;
                    $str .= '                        <div class="layui-select-title">'.PHP_EOL;
                    $str .= '                            <input type="text" data-name="'.$name[0].'" class="layui-input aselect" readonly placeholder="请输入'.$value['name'].'" data-url="'.($value['href']??'ajax/ajaxselect').'" autocomplete="off" data-contentid="'.$value['field'].'"  value="{$alldata[\'field\'][\''.$value['field'].'\'][\'option\'][\''.$name[0].'\']|default=\'\'}" data-table="'.$value['join_table'].'" data-relationship="'.($value['relationship_primary_key']??'id').'">'.PHP_EOL;
                    $str .= '                            <input name="'.$value['field'].'" type="hidden" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '                            <i class="layui-edge"></i>'.PHP_EOL;
                    $str .= '                        </div>'.PHP_EOL;
                    $str .= '                        <dl id="'.$value['field'].'" class="layui-anim layui-anim-upbit"><dd lay-value="" class="layui-select-tips" style="text-align: center"><i class="layui-icon layui-icon-loading layui-icon layui-anim layui-anim-rotate layui-anim-loop">请选择</i></dd></dl>'.PHP_EOL;
                    $str .= '                    </div>'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='radio'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'" value="{$key}" type="radio" title="{$vo}" readonly {if $key==$row.'.$value['field'].'}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='lradio'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'" readonly value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="radio" title="{$vo.'.$name[0].'}"  {if $vo.'.($value['relationship_primary_key']??'id').'==$row.'.$value['field'].'}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='checkbox'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'[{$key}]" readonly value="{$key}" type="checkbox" title="{$vo}"  {if in_array($key,explode(\',\',$row.'.$value['field'].'))}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='lcheckbox'){
                    $name = $value['foreign_key']??'name';
                    $name = explode(',',$name);
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                    {foreach $alldata[\'field\'][\''.$value['field'].'\'][\'option\'] as $key=>$vo}'.PHP_EOL;
                    $str .= '                    <input name="'.$value['field'].'[]" readonly value="{$vo.'.($value['relationship_primary_key']??'id').'}" type="checkbox" title="{$vo.'.$name[0].'}"  {if in_array($vo.id,explode(\',\',$row.'.$value['field'].'))}checked=""{/if}>'.PHP_EOL;
                    $str .= '                    {/foreach}'.PHP_EOL;
                    $str .= '                <tip>'.$value['describe'].'</tip>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='color'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="hidden" name="'.$value['field'].'" readonly value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '                <div id="'.$value['field'].'" value="{$row.'.$value['field'].'}" class="color"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='icon'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <div id="'.$value['field'].'" value="{$row.'.$value['field'].'}" class="icon"></div>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='editor'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <textarea name="'.$value['field'].'" readonly class="layui-textarea editor" style="height: 500px" placeholder="请输入'.$value['name'].'">{$row.'.$value['field'].'|raw}</textarea>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='password'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="password" readonly id="'.$value['field'].'" name="'.$value['field'].'" class="layui-input" lay-reqtext="请输入'.$value['field'].'" placeholder="请输入'.$value['field'].'" value="{$row.'.$value['field'].'|default=\'\'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='switch'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input name="'.$value['field'].'" value="1" type="checkbox" class="switch" lay-skin="switch" lay-text="'.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'" {if $row.'.$value['field'].'==1}checked=""{/if}>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='datetime'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" readonly name="'.$value['field'].'" class="layui-input datetime" id="'.$value['field'].'" {if is_numeric($row.'.$value['field'].')}value="{:date(\'Y-m-d H:i:s\',$row.'.$value['field'].')}" {else}value="{$row.'.$value['field'].'}"{/if}>'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='date'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input date" id="'.$value['field'].'" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }elseif ($value['formtype']=='time'){
                    $str .= '            <div class="layui-input-block">'.PHP_EOL;
                    $str .= '                <input type="text" name="'.$value['field'].'" class="layui-input time" id="'.$value['field'].'" value="{$row.'.$value['field'].'}">'.PHP_EOL;
                    $str .= '            </div>'.PHP_EOL;
                }
//                $str .= '            </div>'.PHP_EOL;
                $str .= '        </div>'.PHP_EOL;
            }
        }
        $mystr = str_replace('{{FormContent}}',$str,$mystr);//替换模型名
        $bool = file_put_contents($file_dir.DIRECTORY_SEPARATOR.'details.html',$mystr);
        return $bool;
    }
    protected function create_js($data=[],$file_dir='',$menu=[]){
//        $Hump = PublicUse::getModeToArray($data['table']);
//        $class = PublicUse::UnderlineToHump($data['table']);
        $href = $menu['href'];
        $href = explode('/',$href);
        $href = explode('.',$href[0]);
//        echo '<pre>';
//        print_r($href);
//        exit;
        if(count($href)==2){
            $file_dir = root_path().'public'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.$href[0];
        }else{
            $file_dir = root_path().'public'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'js';
        }
        if(!is_dir($file_dir)){
            chmod(root_path().'public'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'js',0777);
            mkdir($file_dir,0777,true);
        }
//        echo $file_dir;exit;
        if(count($href)==2){
            $file_name = $file_dir.DIRECTORY_SEPARATOR.$href[1].'.js';
        }else{
            $file_name = $file_dir.DIRECTORY_SEPARATOR.$href[0].'.js';
        }
//        echo $file_name;exit;
        if($data['tabletype']=='ordinary'){
            $str = $this->create_js_ordinarys($data,$menu);
        }else{
            $str = $this->create_js_tree($data,$menu);
        }
        $bool = file_put_contents($file_name,$str);
        return $bool;
    }
    public function create_js_ordinarys($data=[],$menu=[]){
        $mystr = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'ordinary.txt');
        $href = explode('/',$menu['href']);
        $ClassHref = $href[0];
        $mystr = str_replace('{{ClassHref}}',$ClassHref,$mystr);//替换类名
        if(isset($menu['default_tablebar'])&&$menu['default_tablebar']){
            $default_tablebar = explode(',',$menu['default_tablebar']);
        }else{
            $default_tablebar = [];
        }
        $DefaultToolBar = '';
        foreach ($default_tablebar as $value){
            $DefaultToolBar .= "'".$value."',";
        }
        $mystr = str_replace('{{DefaultToolBar}}',$DefaultToolBar,$mystr);//替换默认tablebar

        $str = '';
        $totalRow = false;
        $color = [
            '#FF0000',
            '#00CC00',
            '#3399CC',
            '#660099',
            '#9966FF',
            '#FF6633',
            '#CCCC99',
        ];
        $swithch = '';
        $formtype = [
            'input',
            'textarea',
            'editor',
            'none',
            'file',
            'image',
            'files',
            'images',
            'video',
            'checkbox',
            'json',
            'datetime',
            'lselect',
            'lcheckbox',
            'lradio',
            'color',
            'aselect',
            'selectgroup',
            'treecheckbox',
            'date',
            'time',
        ];
        foreach ($data['field'] as $key=>$value){
            if($value['show']==1){
                if(in_array($value['formtype'],$formtype)){
                    if($value['width']==100){
                        $width = '';
                    }else{
                        $width = ',width:'.$value['width'];
                    }
                    if($value['field']=='id'){
                        if($value['search']==1){
                            $str .= '                    {field: \''.$value['field'].'\',  title: \''.$value['name'].'\',totalRowText: \'合计\''.$width.'},'.PHP_EOL;
                        }else{
                            $str .= '                    {field: \''.$value['field'].'\',  title: \''.$value['name'].'\',search:false,totalRowText: \'合计\''.$width.'},'.PHP_EOL;
                        }
                    }else{
                        if(in_array($value['formtype'],['lradio','lselect','aselect','aselectgroup','selectgroup'])){
//                            dump($value);exit;
                            $foreign_key = explode(',',$value['foreign_key']);
                            $newfields = 'get'.str_replace('_','',$value['field']).'field';
                            foreach ($foreign_key as $k=>$vs){
                                $field_names = Db::name('system_field')
                                    ->where('table',$value['join_table'])
                                    ->where('field',$vs)
                                    ->value('name');
//                                if($vs=='phone'){
//                                    dump($value['table']);exit;
//                                }
                                if($value['search']==1){
                                    $str .= '                    {field: \''.$newfields.'.'.$vs.'\', title: \''.$value['name'].'.'.$field_names.'\',search:true'.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$newfields.'.'.$vs.'\', title: \''.$value['name'].'.'.$field_names.'\',search:false'.$width.'},'.PHP_EOL;
                                }
//                                if($k==0){
//                                    if($value['search']==1){
//                                        $str .= '                    {field: \''.$newfields.'.'.$vs.'\', title: \''.$value['name'].'.'.$field_names.'\',search:true'.$width.'},'.PHP_EOL;
//                                    }else{
//                                        $str .= '                    {field: \''.$newfields.'.'.$vs.'\', title: \''.$value['name'].'.'.$field_names.'\',search:false'.$width.'},'.PHP_EOL;
//                                    }
//                                }else{
//                                    $str .= '                    {field: \''.$newfields.'.'.$vs.'\', title: \''.$value['name'].'.'.$field_names.'\',search:false'.$width.'},'.PHP_EOL;
//                                }
                            }
                            continue;
                        }
                        if($value['search']==1){
                            if($value['formtype']=='checkbox'||$value['formtype']=='lcheckbox'){
                                if($value['total']==1){
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false,totalRow: true'.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false'.$width.'},'.PHP_EOL;
                                }
                            }elseif($value['formtype']=='datetime'){
                                if($value['total']==1){
                                    $totalRow = true;
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',totalRow: true,search:\'range\''.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:\'range\''.$width.'},'.PHP_EOL;
                                }
                            }else{
                                if($value['total']==1){
                                    $totalRow = true;
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',totalRow: true'.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\''.$width.'},'.PHP_EOL;
                                }
                            }
                        }else{
                            if($value['total']==1){
                                $totalRow = true;
                                $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false,totalRow: true'.$width.'},'.PHP_EOL;
                            }else{
                                $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false'.$width.'},'.PHP_EOL;
                            }
                        }
                    }
                }elseif ($value['formtype']=='radio'){
                    foreach ($value['option'] as $ks=>$vs){
                        if(isset($color[$ks])){
                            $vs = "<span style='color: ".$color[$ks]."'>{$vs}</span>";
                        }else{
                            $vs = "<span style='color: ".$this->randomColor()."'>{$vs}</span>";
                        }
                        $value['option'][$ks] = $vs;
                    }
                    if($value['search']==1){
                        $str .= '                    {field: \''.$value['field'].'\',  title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:\'select\''.$width.'},'.PHP_EOL;
                    }else{
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:false'.$width.'},'.PHP_EOL;
                    }
                }elseif ($value['formtype']=='select'){
                    foreach ($value['option'] as $ks=>$vs){
                        if(isset($color[$ks])){
                            $vs = "<span style='color: ".$color[$ks]."'>{$vs}</span>";
                        }else{
                            $vs = "<span style='color: ".$this->randomColor()."'>{$vs}</span>";
                        }
                        $value['option'][$ks] = $vs;
                    }
                    if($value['search']==1){
                        $str .= '                    {field: \''.$value['field'].'\',  title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:\'select\''.$width.'},'.PHP_EOL;
                    }else{
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:false'.$width.'},'.PHP_EOL;
                    }
                }elseif($value['formtype']=='switch'){
                    if($value['field']!=='status'){
                        $swithch .= "ea.table.listenSwitch({filter: '".$value['field']."', url: init.modify_url});".PHP_EOL;
                    }
                    if($value['search']==1){
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',  search: \'select\',selectList:{0:\''.($value['option'][0]??'关闭').'\',1:\''.($value['option'][1]??'开启').'\'}, tips: \''.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'\', templet: ea.table.switch'.$width.'},'.PHP_EOL;
                    }else{
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',  search: false, tips: \''.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'\', templet: ea.table.switch'.$width.'},'.PHP_EOL;
                    }
//                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',  search: false, tips: \''.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'\', templet: ea.table.switch},'.PHP_EOL;
                }
            }

        }
        $mystr = str_replace('{{Centent}}',$str,$mystr);//替换字段
        if($totalRow){
            $mystr = str_replace('{{totalRow}}','totalRow: true,',$mystr);//替换开启合计
        }else{
            $mystr = str_replace('{{totalRow}}','',$mystr);//替换开启合计
        }
        $mystr = str_replace('{{SWITHCH}}',$swithch,$mystr);//替换开关监听
//        echo '<pre>';
//        print_r($menu);
//        exit;
        //tablebar
        $str = '';
//        dump($menu['tablebar']);die();
        foreach ($menu['tablebar'] as $value){
            $str .= '                    {'.PHP_EOL;
            $str .= '                        text: \''.$value['desc'].'\','.PHP_EOL;
            $str .= '                        url: \''.$value['href'].'\','.PHP_EOL;
            if($value['type']=='a'){
                $str .= '                        method: \'open\','.PHP_EOL;
            }else{
                $str .= '                        method: \'request\','.PHP_EOL;
            }
            $str .= '                        auth: \''.$value['auth'].'\','.PHP_EOL;
            $str .= '                        class: \'layui-btn layui-btn-success layui-btn-sm\','.PHP_EOL;
            $str .= '                        icon: \'fa fa-hourglass\','.PHP_EOL;
            $str .= '                        checkbox: true,'.PHP_EOL;
            if(isset($value['data-full'])&&$value['data-full']=='true'){
                $str .= '                        extend: \'data-full="true"\','.PHP_EOL;
            }else{
                $str .= '                        extend: \'data-table="\' + init.table_render_id + \'"\','.PHP_EOL;
            }
            $str .= '                    },'.PHP_EOL;
        }

        $mystr = str_replace('{{ToolBar}}',$str,$mystr);//替换tablebar

        if(isset($menu['default_right_button'])&&$menu['default_right_button']){
            $default_right_button = explode(',',$menu['default_right_button']);
        }else{
            $default_right_button = [];
        }
        $DefaultRightButton = '';
        foreach ($default_right_button as $value){
            $DefaultRightButton .= "'".$value."',";
        }
        if(empty($DefaultRightButton)&&empty($menu['right_button'])){
            $right_buttoon = '';
        }else{
            $rigth_width = $menu['width']??300;
            $right_buttoon = <<<EOT
{
                        width: {$rigth_width},
                        title: '操作',
                        templet:"#right_button",
                        fixed:"right"
                    }
EOT;
        }
        $mystr = str_replace('{{DefaultRightButton}}',$DefaultRightButton,$mystr);//替换默认右侧按钮
        //右侧按钮
        $str = '';
        foreach ($menu['right_button'] as $value){
            $str .= '{'.PHP_EOL;
            $str .= '                                text: \''.$value['desc'].'\','.PHP_EOL;
            $str .= '                                url: \''.$value['href'].'\','.PHP_EOL;
            if($value['type']=='a'){
                $str .= '                                method: \'open\','.PHP_EOL;
            }else{
                $str .= '                                method: \'ajax\','.PHP_EOL;
            }
            $str .= '                                auth: \''.$value['auth'].'\','.PHP_EOL;
            $str .= '                                class: \'layui-btn layui-btn-normal layui-btn-xs\','.PHP_EOL;
            $str .= '                            },'.PHP_EOL;
        }
        $mystr = str_replace('{{RightButton}}',$str,$mystr);//替换右侧按钮
        if($data['is_page']==1){
            $mystr = str_replace('{{PAGE}}','true',$mystr);//替换分页
        }else{
            $mystr = str_replace('{{PAGE}}','false',$mystr);//替换分页
        }
        //字段
//        $str .= '                    {type: "checkbox"},'.PHP_EOL;
        $mystr = str_replace('{{right_button}}',$right_buttoon,$mystr);//替换右侧按钮
        return $mystr;
    }
    public function create_js_tree($data=[],$menu=[]){
        $href = explode('/',$menu['href']);
        $class_s = explode('.',$href[0]);
        $strs = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'tree.txt');
        $ClassHref = $href[0];
        $strs = str_replace('{{ClassHref}}',$ClassHref,$strs);//替换类
        $str = '';
        $totalRow = false;
        $color = [
            '#FF0000',
            '#00CC00',
            '#3399CC',
            '#660099',
            '#9966FF',
            '#FF6633',
            '#CCCC99',
        ];
        $swithch = '';
        $formtype = [
            'input',
            'textarea',
            'editor',
            'none',
            'file',
            'image',
            'files',
            'images',
            'video',
            'checkbox',
            'json',
            'datetime',
            'lselect',
            'lcheckbox',
            'lradio',
            'color',
            'aselect',
            'selectgroup',
            'treecheckbox',
            'date',
            'time',
        ];
        foreach ($data['field'] as $key=>$value){
            if($value['show']==1){
                if($value['field']=='id'){
                    continue;
                }
                if(in_array($value['formtype'],$formtype)){
                    if($value['width']==100){
                        $width = '';
                    }else{
                        $width = ',width:'.$value['width'];
                    }
                    if($value['field']=='id'){
                        if($value['search']==1){
                            if($value['total']==1){
                                $totalRow = true;
                                $str .= '                    {field: \''.$value['field'].'\', width: 80, title: \''.$value['name'].'\',totalRowText: \'合计\''.$width.'},'.PHP_EOL;
                            }else{
                                $str .= '                    {field: \''.$value['field'].'\', width: 80, title: \''.$value['name'].'\''.$width.'},'.PHP_EOL;
                            }
                        }else{
                            if($value['total']==1){
                                $totalRow = true;
                                $str .= '                    {field: \''.$value['field'].'\', width: 80, title: \''.$value['name'].'\',search:false,totalRow:true'.$width.'},'.PHP_EOL;
                            }else{
                                $str .= '                    {field: \''.$value['field'].'\', width: 80, title: \''.$value['name'].'\',search:false'.$width.'},'.PHP_EOL;
                            }

                        }
                    }else{
                        $s = "";
                        if($value['field']=='name'||$value['field']=='title'){
                            $s = ",align: 'left'";
                        }
                        if($value['search']==1){
                            if($value['formtype']=='checkbox'||$value['formtype']=='lcheckbox'){
                                if($value['total']==1){
                                    $totalRow = true;
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false,totalRow: true'.$s.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false'.$s.$width.'},'.PHP_EOL;
                                }
                            }elseif($value['formtype']=='datetime'){
                                if($value['total']==1){
                                    $totalRow = true;
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',totalRow: true,search:\'range\''.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:\'range\''.$width.'},'.PHP_EOL;
                                }
                            }else{
                                if($value['total']==1){
                                    $totalRow = true;
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',totalRow: true'.$s.$width.'},'.PHP_EOL;
                                }else{
                                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\''.$s.$width.'},'.PHP_EOL;
                                }
                            }
                        }else{
                            if($value['total']==1){
                                $totalRow = true;
                                $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false,totalRow: true'.$s.$width.'},'.PHP_EOL;
                            }else{
                                $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',search:false'.$s.$width.'},'.PHP_EOL;
                            }
                        }
                    }
                }elseif ($value['formtype']=='radio'){
                    foreach ($value['option'] as $ks=>$vs){
                        if(isset($color[$ks])){
                            $vs = "<span style='color: ".$color[$ks]."'>{$vs}</span>";
                        }else{
                            $vs = "<span style='color: ".$this->randomColor()."'>{$vs}</span>";
                        }
                        $value['option'][$ks] = $vs;
                    }
                    if($value['search']==1){
                        $str .= '                    {field: \''.$value['field'].'\',  title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:\'select\''.$width.'},'.PHP_EOL;
                    }else{
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:false'.$width.'},'.PHP_EOL;
                    }
                }elseif ($value['formtype']=='select'){
                    foreach ($value['option'] as $ks=>$vs){
                        if(isset($color[$ks])){
                            $vs = "<span style='color: ".$color[$ks]."'>{$vs}</span>";
                        }else{
                            $vs = "<span style='color: ".$this->randomColor()."'>{$vs}</span>";
                        }
                        $value['option'][$ks] = $vs;
                    }
                    if($value['search']==1){
                        $str .= '                    {field: \''.$value['field'].'\',  title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:\'select\''.$width.'},'.PHP_EOL;
                    }else{
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',selectList:'.json_encode($value['option'],JSON_UNESCAPED_UNICODE).',search:false'.$width.'},'.PHP_EOL;
                    }
                }elseif($value['formtype']=='switch'){
                    if($value['field']!=='status'){
                        $swithch .= "ea.table.listenSwitch({filter: '".$value['field']."', url: init.modify_url});".PHP_EOL;
                    }
                    if($value['search']==1){
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',  search: \'select\',selectList:{0:\''.($value['option'][0]??'关闭').'\',1:\''.($value['option'][1]??'开启').'\'}, tips: \''.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'\', templet: ea.table.switch'.$width.'},'.PHP_EOL;
                    }else{
                        $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',  search: false, tips: \''.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'\', templet: ea.table.switch'.$width.'},'.PHP_EOL;
                    }
//                    $str .= '                    {field: \''.$value['field'].'\', title: \''.$value['name'].'\',  search: false, tips: \''.($value['option'][1]??'开启').'|'.($value['option'][0]??'关闭').'\', templet: ea.table.switch},'.PHP_EOL;
                }
            }

        }
        $strs = str_replace('{{Centent}}',$str,$strs);//替换字段
        if($totalRow){
            $strs = str_replace('{{totalRow}}','totalRow: true,',$strs);//替换开启合计
        }else{
            $strs = str_replace('{{totalRow}}','',$strs);//替换开启合计
        }
        $strs = str_replace('{{SWITHCH}}',$swithch,$strs);//替换开关监听
        if(isset($menu['default_right_button'])&&$menu['default_right_button']){
            $default_right_button = explode(',',$menu['default_right_button']);
        }else{
            $default_right_button = [];
        }
        $DefaultRightButton = '';
        foreach ($default_right_button as $value){
            $DefaultRightButton .= "'".$value."',";
        }
        if(empty($DefaultRightButton)&&empty($menu['right_button'])){
            $right_buttoon = '';
        }else{
            $rigth_width = $menu['width']??300;
            $right_buttoon = <<<EOT
{
                        width: {$rigth_width},
                        title: '操作',
                        templet:"#right_button",
                        fixed:"right"
                    }
EOT;
        }

        $strs = str_replace('{{right_button}}',$right_buttoon,$strs);//替换字段
        return $strs;
    }
    protected function  randomColor(){
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