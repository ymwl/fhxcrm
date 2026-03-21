<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\facade\Cache;
use app\BaseController;
use app\Request;
use think\Config;
use think\captcha\Captcha as CaptchaLib;
use think\facade\Env;

/**
 * 图片验证码
 * Class Captcha
 */
class Yzm extends BaseController
{
    public function initialize(){
    }
    public function c()
    {
        var_dump(config('app.app_key'));
    }

    public function index()
    {
        $sid=$this->request->param('sid');
        if (!$sid){
            $sid=app('session')->getId();
            $this->success('会话ID获取成功','',['sid'=>$sid]);
        }else{
            app('session')->setId($sid);
        }
        ob_clean();
        $rep = captcha();
        $key = app('session')->get('captcha.key');

        Cache::set('key.cap.' . $sid, $key, 300);

        return $rep;
    }


}