<?php
namespace app\api\controller;
use think\Config;
use think\facade\Db;
use think\facade\Lang;
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
        // 加载登录控制器语言包（从 common/lang 共用目录）
        $this->loadlang('login');
    }
    protected function loadlang($name)
    {
        $name = preg_match("/^([a-zA-Z0-9_\.\/]+)\$/i", $name) ? $name : 'index';
        $lang = Lang::getLangSet();
        $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';
        Lang::load(app()->getBasePath() . 'common/lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
    }

    public function index(){
        if($this->request->isPost()) {
            $data = $this->request->post();
            if($this->system['code']){
                if(!checkCaptcha($data['sid'],$data['captcha'])){
                    return json(['code'=>0,'msg'=>'验证码错误!']);
                }
            }
            $loginfailure=Db::name('login_log')->where(['username'=>$data['username'],'info'=>'Login user name or password error, login failed'])->where('createtime','>=',strtotime(date('Y-m-d')))->count();
            if($loginfailure>6){
                $this->error('您的账号今天登录失败'.$loginfailure.'次，请明天再试');
            }
            $admin = new Admin();
            $return = $admin->login($data,$this->system['code']);
            $ismoblie =$this->request->isMobile();
            $ip =getRealIp();
            $login_side=$ismoblie?2:1;
            Db::name('login_log')->insert([
                'admin_id'=>isset($return['admin']['admin_id'])?$return['admin']['admin_id']:0,
                'username'=>isset($return['admin']['username'])?$return['admin']['username']:$data['username'],
                'ip'=>$ip,
                'login_side'=>$login_side,
                'info'=>$return['msg'],
                'createtime'=>time()
            ]);

            if($return['code']) {
                    $exp=strtotime('+24 hours');
                    $user=$return['admin'];
                    $user['token']=getToken([
                        'admin_id'=>$return['admin']['admin_id'],
                        'username'=>$return['admin']['username'],
                    ], $exp);

                    $user['group_name']=Db::name('auth_group')->where('id','=',$user['group_id'])->value('title');
                    $user['role_name']=Db::name('auth_role')->where('id','=',$user['role_id'])->value('name');
                   $user['avatar']= real_resourse($this->system['domain'],$user['avatar']);
                    $user['expire']=$exp;
                  return json(['code'=>1,'msg'=>'登录成功!','data'=>$user]);
            }else{
                return json(['code'=>0,'msg'=>fy($return['msg'])]);
            }

        }
    }

    public function config(){

//        return app('json')->successful();
        $system=$this->system;
        $http_domain=request()->domain();
        unset($system['license_key']);
        $this->success('配置信息获取成功', '', [
                'login_captcha'=>$this->system['code']=='open',//是否需要验证码，
                'app_id'=>'',//企业微信公众号
                    'upload'=>[
                        "uploadurl"=>$http_domain."/api/common/upload",
                "cdnurl"=> $this->system['domain']?$this->system['domain']:$http_domain,
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
                "storage"=> "local"
                    ]
                ,//上传配置
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
