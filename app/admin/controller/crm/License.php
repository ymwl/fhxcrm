<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;


class License extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

    }
    public function index(){

            try {
                $license_name=cache('license_name');
                if($license_name){
                    return json(['code'=>1,'msg'=> $license_name]);
                }
                $key='';
                if(isset($this->system['license_key'])){
                    $key=$this->system['license_key'];
                }
                if(!$key){
                    return json(['code'=>0,'msg'=>'请先配置授权码或秘钥']);
                }

                $version=config('version');
                $host = base64_decode('YXBpLmxhaWtlcGhwLmNvbQ==');
                $param = ['k' => $key,'h'=>$_SERVER['HTTP_HOST'],'v'=>$version['version']];
                $url = 'https://' . $host . '/auth/verify';
                $response=httpRequest($url,'post',$param);

                $res=json_decode($response,true);
                if($res){
                    if(isset($res['data']['status']) && $res['data']['status']==-1){
                        rmdirs(app()->getRootPath().'app/admin/controller/crm');
                    }elseif(isset($res['data']['status']) && $res['data']['status']==0){
                        return json(['code'=>1,'msg'=>'授权使用联系微信:zrwx978']);
                    }elseif(isset($res['data']['status']) && $res['data']['status']==1){
                        cache('license_name',$res['data']['license_name']);
                        return json(['code'=>1,'msg'=>$res['data']['license_name']]);
                    }
                    return json(['code'=>0,'msg'=>$res['msg']]);
                }
                return json(['code'=>0,'msg'=>'获取授权失败','data'=>$response]);

            }catch (\Throwable $t) {
                $this->error($t->getMessage());
            }

    }

    
}