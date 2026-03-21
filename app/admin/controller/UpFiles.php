<?php
namespace app\admin\controller;
use think\facade\View;
use think\facade\Db;
use think\Request;
use app\BaseController;
use think\facade\Env;
class UpFiles extends Common
{






    public function md5Check(){
//        checkType: 1
//contentType: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
//zoneTotalMd5: 16fca17e7228e887282e483032d8c790
//        ext
        if($this->request->isPost()){
            $post=$this->request->post();
            if($post['checkType']==1){
//                整个文件验证
                $info=\think\facade\Db::name('system_uploadfile')->field('url,file_path,upload_type')->where('md5','=',$post['zoneTotalMd5'])->where('file_ext','=',$post['ext'])->find();
//
                if($info && file_exists(public_path().$info['file_path'])){
                    return json(['code'=>1,'msg'=>fy('Get successful'),'exist'=>1,'data'=>$info]);
                }else{
                    return json(['code'=>1,'msg'=>'文件不存在','exist'=>0]);
                }
            }elseif($post['checkType']==2){
//                分片验证
                $info=\think\facade\Db::name('system_zone_uploadfile')->field('url,file_path,upload_type')->where('total_md5','=',$post['zoneTotalMd5'])->where('md5','=',$post['zoneMd5'])->find();
                if($info && file_exists(public_path().$info['file_path'])){
                    return json(['code'=>1,'msg'=>fy('Get successful'),'exist'=>1,'data'=>$info]);
                }else{
                    return json(['code'=>1,'msg'=>'文件不存在','exist'=>0]);
                }
            }

        }

    }
    public function merge(){
        if($this->request->isPost()){
            $filemd5=$this->request->post('filemd5');
            $info=\think\facade\Db::name('system_zone_uploadfile')->field('url,file_ext,file_path,upload_type,chunk_count,total_md5,mime_type,original_name')->where('total_md5','=',$filemd5)->order('chunk_index ASC')->select();
            if(!$info){
                return json(['code'=>0,'msg'=>'需要合成的文件信息不存在']);
            }

            $count=count($info);
            $types = '.gif|.jpeg|.png|.bmp|.jpg';  //定义检查的图片类型
            $rootDir=\think\facade\Filesystem::getDiskConfig('public','root');
            if(strpos($types, $info[0]['file_ext']) !== false){
                $path='upload'.DIRECTORY_SEPARATOR.'images';
            } else {
                $path='upload'.DIRECTORY_SEPARATOR.'attach';
            }
            $put_path=$path.DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR;
            if(!is_dir($rootDir.DIRECTORY_SEPARATOR.$put_path)){
                mkdir($rootDir.DIRECTORY_SEPARATOR.$put_path,0755,true);
            }
            $save_path=$put_path.$filemd5.'.'.$info[0]['file_ext'];

            if( $count==$info[0]['chunk_count']){
                $url=str_replace(DIRECTORY_SEPARATOR, '/', DIRECTORY_SEPARATOR.$save_path);
                if(!file_exists(public_path().$save_path) ){
                    $fp = fopen(public_path().$save_path, "ab");

                    foreach ($info as $v){
                        $tmp_file = public_path().$v['file_path'];
                        $handle = fopen($tmp_file, "rb");
                        fwrite($fp, fread($handle, filesize($tmp_file)));
                        fclose($handle);
                        unset($handle);
                        unlink($tmp_file);//合并完毕的文件就删除
                    }

                    \think\facade\Db::name('system_zone_uploadfile')->where('total_md5','=',$filemd5)->delete();
                    Db::name('system_uploadfile')->insert(
                        [
                            'upload_type'   => 'local',
                            'original_name' => $info[0]['original_name'],
                            'mime_type' => $info[0]['mime_type'],
                            'file_ext'      => $info[0]['file_ext'],
                            'url'           => $url,
                            'file_path'           => $url,
                            'create_time'   => time(),
                            'admin_id'   => $this->admin['admin_id'],
                            'md5'   =>  $filemd5
                        ]);
                }


               return json(['code'=>1,'msg'=>'上传成功','data'=>['url'=>$url]]);
            }else{
                return json(['code'=>0,'msg'=>'块缺失']);
            }
        }else{
            return json(['code'=>0,'msg'=>'请求不合法']);
        }

    }
}
