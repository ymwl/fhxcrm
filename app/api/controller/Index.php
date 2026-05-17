<?php

namespace app\api\controller;

use app\BaseController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Index 控制器
 * 处理与登录/登出相关的 API 请求
 */
class Index extends BaseController
{
    /**
     * 退出登录
     * 清除 JWT token（虽然 JWT 是无状态的，前端会清除客户端的 token，
     * 后端返回成功确认，前端在收到成功响应后清除 vuex_token 并跳转到登录页）
     *
     * @return \think\Response
     */
    public function logout()
    {
        // 尝试从请求头获取 token 以记录登出用户信息（可选审计日志）
        $token = $this->request->header('token', '');

        $username = '';
        if (!empty(trim($token))) {
            try {
                JWT::$leeway = 60;
                $data = JWT::decode($token, new Key(config('app.app_key'), 'HS256'));
                $username = $data->username ?? '';
            } catch (\Throwable $e) {
                // token 可能已过期或无效，忽略错误，正常返回退出成功
            }
        }

        // 返回退出成功响应
        // code=1 对应前端 uni-app 中判断 res.code == 1 的逻辑
        return json([
            'code' => 1,
            'msg'  => '退出成功',
            'data' => '',
        ]);
    }
}
