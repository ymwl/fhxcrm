<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/19
 * Time: 16:56
 */
namespace app\admin\model\publicuse;

use think\facade\Db;
use think\facade\Filesystem;
use think\Image;

class UpField
{
    protected $config =[
        'serverurl'=>'/upload',

    ];
    public function __construct($config=[])
    {
        if($config){
            foreach ($config as $key=>$value){
                $this->config[$key] = $value;
            }
        }
    }

    /**静态
     * @param string $type
     * @param bool $iskey
     * @param bool $getid
     * @param null $path
     * @return array
     */
    public static function addFields($type='图片',$iskey=false,$path=null){
        $file = request()->file();
        $obj = new UpField();
        $newshuju = [];
        if(!empty($file)){
            foreach ($file as $key=>$value){
                if($iskey){
                    $newshuju[$key] = $obj->addField($value,$path);
                }else{
                    $newshuju[] = $obj->addField($value,$path);
                }
            }
        }
        return $newshuju;
    }

    /**添加数据
     * @param array $data
     * @param string $type
     * @param null $fenmian
     * @return array
     */
    public function getId($data=[],$type='图片',$fenmian=null){
        if(is_assoc($data)){
//            echo 1;exit;
            $data['type'] = $type;
            if($type!=='图片'&&$fenmian){
                $data['fenmian'] = $fenmian;
            }
            if(!($data['quan_path']??null)){
                $data['quan_path'] = $data['path'];
                if(!strstr($data['path'],'http')){
                    $data['quan_path'] = request()->domain().$data['path'];
                }
            }
            $data['create_time'] = time();
            $data['status'] = 1;
            if($data['id'] = Db::name('enclosure')->insertGetId($data)){
                return $data;
            }
            return $data;
        }else{
//            echo 2;exit;
            $newdata = [];
            foreach ($data as $value){
                $value['type'] = $type;
                if($type!=='图片'&&$fenmian){
                    $value['fenmian'] = $fenmian;
                }
                if(!($value['quan_path']??null)){
                    $value['quan_path'] = $value['path'];
                    if(!strstr($value['path'],'http')){
                        $value['quan_path'] = request()->domain().$value['path'];
                    }
                }
                $value['create_time'] = time();
                $value['status'] = 1;
//                $newdata[] = $value;
                if($value['id'] = Db::name('enclosure')->insertGetId($value)){
                    $newdata[] = $value;
                }
            }
//            echo '<pre>';
//            print_r($newdata);
//            exit;
            return $newdata;
        }
    }

    /**添加数据
     * @param $file
     * @param string $path
     * @return mixed
     */
    public function addField($file,$path=''){
        $width = request()->param('width',null);
        $heigth = request()->param('heigth',null);
        if(is_array($file)){
            $newdata = [];
            foreach ($file as $value){
                $data['yuan_name'] ='';
                $data['path'] = $this->config['serverurl'];
                if(empty($path)){
                    $savename = Filesystem::disk('public')->putFile('',$value);
                }else{
                    $savename = Filesystem::disk('public')->putFile($path,$value);
                }
                $data['new_name'] = str_replace(date('Ymd').'\\','',$savename);
                if(empty($path)){
                    $data['path'] = str_replace('\\','/','/'.$savename);
                    $data['quan_path'] = request()->domain().str_replace('\\','/','/'.$savename);
                }else{
                    $data['path'] = str_replace('\\','/','/'.$savename);
                    $data['quan_path'] = request()->domain().str_replace('\\','/','/'.$savename);
                }
                $newdata[] = $data;
            }

            $data = $newdata;
        }else{
            $data['yuan_name'] ='';
            $data['path'] = $this->config['serverurl'];
            if(empty($path)){
                $savename = Filesystem::disk('public')->putFile('',$file);
            }else{
                $savename = Filesystem::disk('public')->putFile($path,$file);
            }
            $data['new_name'] = str_replace(date('Ymd').'\\','',$savename);
            if(empty($path)){
                $data['path'] = str_replace('\\','/','/'.$savename);
                $data['quan_path'] = request()->domain().str_replace('\\','/','/'.$savename);
            }else{
                $data['path'] = str_replace('\\','/','/'.$savename);
                $data['quan_path'] = request()->domain().str_replace('\\','/','/'.$savename);
            }
        }
        //压缩
        if($width||$heigth){
            if(is_file('.'.$data['path'])){
                $image = Image::open('.'.$data['path']);
                if($width&&empty($heigth)){
                    $image->thumb($width,$width)->save('.'.$data['path']);
                }
                if(empty($width)&&$heigth){
                    $image->thumb($heigth,$heigth)->save('.'.$data['path']);
                }
                if($width&&$heigth){
                    $image->thumb($width,$heigth,Image::THUMB_CENTER)->save('.'.$data['path']);
                }
            }
        }
        return $data;
    }
    public function base64UpFile($files,$path=''){
        $width = request()->param('width',null);
        $heigth = request()->param('heigth',null);
        $file = $files['base64'];
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/',$file,$result)){
            $types = explode('.',$files['name']);
            $type = end($types);
            $file_name = uniqid().'.'.$type;
            if(!is_dir('.'.rtrim($this->config['serverurl'],'/').'/'.date('Ymd',time()))){
                chmod('.'.rtrim($this->config['serverurl'],'/').'/',0777);
                mkdir('.'.rtrim($this->config['serverurl'],'/').'/'.date('Ymd',time()));
            }
            if($path){
                $path =$this->config['serverurl'].'/'.trim($path,'/').'/'.date('Ymd',time()).'/'.$file_name;
            }else{
                $path =$this->config['serverurl'].'/'.date('Ymd',time()).'/'.$file_name;
            }
            $str =explode(',',$file);
            if(file_put_contents('.'.$path,base64_decode($str[1]))){
                $data['path'] = $path;
                $data['yuan_name'] = $files['name'];
                $data['new_name'] = $file_name;
                //压缩
                if($width||$heigth){
                    if(is_file('.'.$data['path'])){
                        $image = Image::open('.'.$data['path']);
                        if($width&&empty($heigth)){
                            $image->thumb($width,$width)->save('.'.$data['path']);
                        }
                        if(empty($width)&&$heigth){
                            $image->thumb($heigth,$heigth)->save('.'.$data['path']);
                        }
                        if($width&&$heigth){
                            $image->thumb($width,$heigth,Image::THUMB_CENTER)->save('.'.$data['path']);
                        }
                    }
                }
                return $data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}