<?php

namespace app\admin\controller;


use app\admin\service\LicenseService;
use app\common\controller\AdminController;

//技术支持 w w w . 8 0 z x . c o m
class Cloud extends AdminController
{
    public function update(){
        if (!function_exists('fsockopen')) {
            $this->error('本站：PHP环境不支持fsockopen，升级失败');
        }
        // 执行下载文件
        $version=config('version');
        $tempPath=app()->getRuntimePath().'temp';

        if(!is_dir($tempPath)){
            mkdir($tempPath,0777,true);
        }



        set_time_limit(0);

        $domain=$_SERVER['HTTP_HOST'];
        $license=$this->system['license_key'];

        // 升级前置授权校验：密钥与离线授权文件二选一，验证全部失败则禁止执行文件更新
        $licenseService = new LicenseService();
        $auth = $licenseService->buildCloudAuthParams($license);
        if(!$auth['ok']){
            $this->error($auth['msg'] . '，升级已终止');
        }
//        domain={$domain}&license={$license}&build={$version['build']}&php=".PHP_VERSION."&no={$version['no']}
        $data=[
            'domain'=>$domain,
            'license'=>$license,
            'build'=>$version['build'],
            'php'=>PHP_VERSION,
            'no'=>$version['no']
        ];
        // 授权参数：install_id恒定携带，本地验签通过的离线授权文件随license_file上送，服务端按签发记录(bind_install_id)放行升级
        $data = array_merge($data, $auth['params']);
        $res=httpRequest('https://cloud.laikephp.com/index/fhx.version/upgrade','POST',$data);
//        var_dump($res);
        $res_arr=json_decode($res,true);
        if($res_arr){
            if($res_arr['code']==1){
                $url=isset($res_arr['url'])?$res_arr['url']:'';
                if($url){
                    $remoteUrl=strpos($url,'/')===0?config('fhx.api_domain').$url:$url;
//进入文件下载

                    $localFile = $tempPath.DIRECTORY_SEPARATOR.basename($url); // 临时存储路径

//                    var_dump($remoteUrl);
                    $ch = curl_init($remoteUrl);
                    $fp = fopen($localFile, 'wb');

                    if (!$fp) {
                        $this->error('无法创建临时文件');
                    }

                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36');

                    if (curl_exec($ch) === false) {
                        unlink($localFile);
                        $this->error('下载失败: ' . curl_error($ch));
                    }
                    curl_close($ch);
                    fclose($fp);

                    //下载完成执行解压
                    if (!class_exists('ZipArchive')) {
                        $this->error('PHP环境解压类ZipArchive没有开启，升级失败');
                    }

                    $zip = new \ZipArchive;//新建一个ZipArchive的对象
                    /*
                    通过ZipArchive的对象处理zip文件
                    $zip->open这个方法的参数表示处理的zip文件名。
                    如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
                    */
                    if ($zip->open($localFile) === TRUE) {
                        $zip->extractTo(app()->getRootPath());//解压缩到某路径下
                        $zip->close();//关闭处理的zip文件
                        $updateSql=app()->getRootPath().'update/'.$res_arr['version'].'.sql';
                        if(is_file($updateSql)){
                            \tools\Hs::sql($updateSql);
                        }
                        unlink($localFile);
                        $msg='升级完成';
                        if($res_arr['count']>1){
                            $msg="升级到版本{$res_arr['version']}成功";
                        }
                        $this->success($msg,'',['count'=>$res_arr['count'],'version'=>$res_arr['version']]);
                    }else{
                        unlink($localFile);
                        $this->error('打开升级包失败');
                    }

                }
                $this->success($res_arr['msg']);
            }else{
                $this->error($res_arr['msg']);
            }
        }else{
            $this->error($res);
        }

    }
}
