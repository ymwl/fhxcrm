<?php
namespace app\api\controller;
use think\Config;
use think\facade\Db;
use think\facade\View;
use app\BaseController;
use app\admin\model\Admin;
use think\captcha\facade\Captcha;
class Login extends BaseController
{
    private $cache_model,$system;
    public $admin=[];
    public function initialize(){
//        check_cors_request();
        $this->cache_model=['System'];
        foreach($this->cache_model as $r){
            savecache($r);
        }
        $this->system = cache('System');

    }
    public function index(){
        if($this->request->isPost()) {
            $data = $this->request->post();
            if($this->system['code']){
                if(!checkCaptcha($data['sid'],$data['captcha'])){
                    return json(['code'=>0,'msg'=>'验证码错误!']);
                }
            }
            $user=Db::name('admin')->field('`admin_id`,`username`,`realname`,`salt`,`pwd`,`group_id`,`avatar`,`isphone`,`is_open`,`email`')->where('username',$data['username'])->find();
            if($user) {
                if($user['is_open']<1){
                    return json(['code'=>0,'msg'=>'当前用户已禁用，请联系网站管理员!']);
                }
                if ($user['is_open']==1 && $user['pwd'] == md5($data['password'].$user['salt'])){
                    unset($user['pwd']);
                    unset($user['salt']);
                    unset($user['is_open']);
                    $exp=strtotime('+24 hours');
                    $user['token']=getToken([
                        'admin_id'=>$user['admin_id'],
                        'username'=>$user['username'],
                    ], $exp);

                    $user['group_name']=Db::name('auth_group')->where('id','=',$user['group_id'])->value('title');
                   $user['avatar']= real_resourse($this->system['domain'],$user['avatar']);
                    $user['expire']=$exp;
                  return json(['code'=>1,'msg'=>'登录成功!','data'=>$user]);

                }else{
                    return json(['code'=>0,'msg'=>'用户名或者密码错误!']);


                }
            }else{
                return json(['code'=>0,'msg'=>'不存在的登录用户!']);
            }

        }
    }

    public function config(){

//        return app('json')->successful();
        $this->success('配置信息获取成功', '', [
                'login_captcha'=>$this->system['code']=='open',//是否需要验证码，
                'app_id'=>'',//企业微信公众号
                'config'=>['upload'=>[   "uploadurl"=>real_resourse($this->system['domain'],"/api/common/upload"),
                "cdnurl"=> $this->system['domain'],
                "savekey"=>"/upload/{year}{mon}{day}/{filemd5}{.suffix}",
                "maxsize"=>"10mb",
                "mimetype"=> "jpg,png,bmp,jpeg,gif,webp,zip,rar,xls,xlsx,wav,mp4,mp3,webm,pdf",
                "multiple"=> false,
                "chunking"=> false,
                "chunksize"=>2097152,
               "fullmode"=> false,
               "thumbstyle"=> "",
                "bucket"=>"local",
                "multipart"=> [],
                "storage"=> "local"]],//上传配置
                'system'=>$this->system,
                'themeconfig'=>["navbar"=> [
                    "titleColor"=> "#fff",
                    "bgColor"=> [
                        "background"=> "#336699"
                    ],
                    "backIconColor"=> "#fff",
                    "backTextStyle"=> [
                        "color"=> "#fff"
                    ],
                    "titleSize"=> "35",
                    "isshow"=> true
                ],
                    "theme"=> [
                        "color"=> "#336699",
                        "bgColor"=> "#ffffff",
                        "title"=> $this->system['name'],
                        "logo"=> trim($this->system['domain'],'/').$this->system['web_logo'],
                        "discription"=> $this->system['site_abbr']
                    ],
                    "tabbar"=> [
                        "color"=> "#999",
                        "selectColor"=> "#000",
                        "bgColor"=> "#ffffff",
                        "height"=> "100",
                        "borderTop"=> "#66ffff",
                        "iconSize"=> "40",
                        "list"=> [
                            [
                                "image"=> real_resourse($this->system['domain'],"/static/crm/img/home.png"),
                                "selectedImage"=> real_resourse($this->system['domain'],"/static/crm/img/home-active.png"),
                                "text"=> "首页",
                                "path"=> "/pages/index/index",
                                "count"=> 0,
                                "isDot"=> false,
                                "badgeColor"=> "#336699",
                                "badgeBgColor"=> "#ffffff"
                            ],
                            [
                                "image"=> real_resourse($this->system['domain'],"/static/crm/img/client.png"),
                                "selectedImage"=> real_resourse($this->system['domain'],"/static/crm/img/client-active.png"),
                                "text"=> "客户",
                                "path"=> "/pages/client/index",
                                "count"=> 0,
                                "isDot"=> false,
                                "badgeColor"=> "#374486",
                                "badgeBgColor"=> "#ffffff"
                            ],
                            [
                                "image"=> real_resourse($this->system['domain'],"/static/crm/img/business.png"),
                                "selectedImage"=> real_resourse($this->system['domain'],"/static/crm/img/business-active.png"),
                                "text"=> "商机",
                                "path"=> "/pages/business/index",
                                "count"=> 0,
                                "isDot"=> false,
                                "badgeColor"=> "#374486",
                                "badgeBgColor"=> "#ffffff"
                            ],
                            [
                                "image"=> real_resourse($this->system['domain'],"/static/crm/img/clue.png"),
                                "selectedImage"=> real_resourse($this->system['domain'],"/static/crm/img/clue-active.png"),
                                "text"=> "线索",
                                "path"=> "/pages/clues/list",
                                "count"=> 0,
                                "isDot"=> false,
                                "badgeColor"=> "#374486",
                                "badgeBgColor"=> "#ffffff"
                            ],
                            [
                                "image"=> real_resourse($this->system['domain'],"/static/crm/img/data.png"),
                                "selectedImage"=> real_resourse($this->system['domain'],"/static/crm/img/data-active.png"),
                                "text"=> "数据",
                                "path"=> "/pages/presentation/index",
                                "count"=> 0,
                                "isDot"=> false,
                                "badgeColor"=> "#374486",
                                "badgeBgColor"=> "#ffffff"
                            ]
                        ],
                        "isshow"=> true
                    ]],//主题设置
                'payConfig'=>[],//在线收款配置
            ]
        );
    }
}
