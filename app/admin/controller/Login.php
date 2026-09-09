<?php
namespace app\admin\controller;
use think\facade\Db;
use think\facade\Lang;
use think\facade\View;
use app\BaseController;
use app\admin\model\Admin;
use think\captcha\facade\Captcha;
class Login extends BaseController
{

    public $admin=[];
    public function initialize(){
        $this->admin=session('admin');
        if (!empty($this->admin)) {
            $this->redirect(myurl('index/index'));
        }
        View::assign('system',$this->system);
    }

    public function index(){
        $thisController =  $this->request->controller();
        list($thisControllerArr, $jsPath) = [explode('.', $thisController), null];
        foreach ($thisControllerArr as $vo) {
            empty($jsPath) ? $jsPath = parse_name($vo) : $jsPath .= '/' . parse_name($vo);
        }
        $name = preg_match("/^([a-zA-Z0-9_\.\/]+)\$/i", $jsPath) ? $jsPath : 'index';
        $lang = Lang::getLangSet();
        $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';
        Lang::load(app()->getBasePath() . 'common/lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
        if($this->request->isPost()) {
            $check = $this->request->checkToken('__token__');
            if(false === $check) {
                $this->error('无效的token，请重试');
            }
            $data = $this->request->post('',[],'trim');
            if(empty($data['username']) || empty($data['password'])){
                $this->error( fy('Account number and password must be filled in'));
            }

//            判断登录账号今天是否有多次登录失败记录
            $loginfailure=Db::name('login_log')->where(['username'=>$data['username'],'info'=>'Login user name or password error, login failed'])->where('createtime','>=',strtotime(date('Y-m-d')))->count();
            if($loginfailure>6){
                $this->error('您的账号今天登录失败'.$loginfailure.'次，请明天再试');
            }
            if($this->system['code']){
                if(!isset($data['vercode']) || !captcha_check($data['vercode'])){
                    $this->error( fy('Login verification code error'));
                }
            }
            $admin = new Admin();
            $return = $admin->login($data);
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
            if($return['code']){
                session('admin',$return['admin']);
                if(empty($data['remember'])){
                    cookie('username','');
                    cookie('password','');
                }else{
                    cookie('username',$data['username'],315360000);
                    cookie('password',cpEncode($data['password'],config('app.cookie_crypt_key'),315360000),315360000);
                }

                session('referer',null);
                $this->success(fy($return['msg']));

            }else{

                $this->error( fy($return['msg']));
            }

        }else{

            return \think\facade\View::fetch();
        }
    }
    public function verify(){
        ob_clean();
        return Captcha::create();
    }
}
