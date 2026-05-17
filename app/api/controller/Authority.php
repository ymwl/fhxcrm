<?php

namespace app\api\controller;

use think\facade\Db;

class Authority extends Common
{
    public $admin = [];
    public $auto_record_log=1;

    protected function initialize()
    {

        try {

            define('CONTROLLER', strtolower($this->request->controller()));
            define('ACTION', strtolower($this->request->action()));
            // 自动加载当前控制器对应的语言包（从 common/lang 共用目录）
            $this->loadlang(CONTROLLER);

            if(in_array(ACTION,$this->noNeedLogin) || in_array('*', $this->noNeedLogin)){

            }else{
                $token = $this->request->header('token', '');
                if (empty(trim($token))) {
                    throw new \Exception('请先登录', 401);
                }
                try {
                    $sessionInfo = $this->parseToken($token);
                }catch (\Throwable $t){
                    throw new \Exception('token已过期', 401);
                }



                if (!$sessionInfo) {
                    throw new \Exception('token已过期', 401);
                }
//                `admin_id`,`username`,`salt`,`realname`,`pwd`,`group_id`,`avatar`,`isphone`,`is_open`,`role_id`,phone,email,wechat
                $this->admin  = Db::name('admin')->field('`admin_id`,`username`,`realname`,`group_id`,`avatar`,`isphone`,`is_open`,`role_id`,phone,email,wechat')->where('admin_id', $sessionInfo->admin_id)->cache('admin_id' . $sessionInfo->admin_id, 300)->find();
                if ($this->admin['is_open'] < 1) {
                    throw new \Exception('当前用户已禁用，请联系网站管理员!', 401);
                }

                //权限管理
                //当前操作权限ID
                $group_id = \think\facade\Db::name('admin')->cache('admin_id_' . $this->admin['admin_id'])->where('admin_id', '=', $this->admin['admin_id'])->value('group_id');
                $path=CONTROLLER.'/'.ACTION;
                $this->HrefId = \think\facade\Db::name('auth_rule')->whereRaw('LOWER(`href`)=:href AND authopen=1', ['href' => $path])->value('id');
                if ($group_id != 1 && $this->HrefId) {


                    //当前管理员权限
                    $map['a.admin_id'] = $this->admin['admin_id'];
                    $prefix = getDataBaseConfig('prefix');

                    $rules = Db::name('admin')->alias('a')->cache('rules_' . $this->admin['admin_id'])
                        ->join($prefix . 'auth_group ag', 'a.group_id = ag.id', 'left')
                        ->where($map)
                        ->value('ag.rules');
                    $this->adminRules = explode(',', $rules);
                    if ($this->HrefId) {
                        if (!in_array($this->HrefId, $this->adminRules)) {
                            throw new \Exception('您无此操作权限!', 403);
                        }
                    }
                }
                if($this->auto_record_log && $this->HrefId && $this->request->isPost()){
                    \app\common\model\AdminLog::record($this->admin,$path,$this->HrefId);
                }
            }

            $this->cache_model = ['System'];
            foreach ($this->cache_model as $r) {
                if (!cache($r)) {
                    savecache($r);
                }
            }

        } catch (\Throwable $t) {
            $this->jsonError($t->getMessage(),[],$t->getCode());

        }

    }
}
