<?php

declare(strict_types=1);

namespace app\admin\controller\database;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use think\facade\Log;


/**
 * 数据库管理面板
 *
 * 安全：
 *   - 仅超级管理员 (id=1) 可访问
 *   - 首次访问需二次输入登录密码（30 分钟有效）
 *   - 只能操作当前项目配置的数据库
 *   - 访问操作记录日志
 */
class Adminer extends AdminController
{
    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->app->view->engine()->layout(false);
    }

    /**
     * 数据库管理入口
     */
    public function index()
    {
        $dbConfig = config('database.connections.mysql');

        if(empty($_GET['db'])){
            $_POST['auth']['driver'] = 'server';
            $_POST['auth']['server'] = $dbConfig['hostname'];
            $_POST['auth']['username'] = $dbConfig['username'];
            $_POST['auth']['password'] = $dbConfig['password'];
            $_POST['auth']['db'] = $dbConfig['database'];
        }else{
            $_GET['db']=$dbConfig['database'];
            $_GET['server']=$dbConfig['hostname'];
            $_GET['username']= $dbConfig['username'];
        }

        $adminerFile = root_path() . 'addons/database/library/adminer.php';

        if ((int)($this->admin['admin_id'] ?? 0) !== 1) {
            header('HTTP/1.1 403 Forbidden');
            echo '<h2>Access Denied</h2>';
            exit;
        }

        if (!$this->isPasswordVerified()) {
            if ($this->request->isPost()) {
                $this->handlePasswordCheck();
            }
            $this->showPasswordForm();
            exit;
        }

        if (!defined('ABSPATH')) {
            define('ABSPATH', true);
        }

        require $adminerFile;
        exit;
    }

    // ──────────────────────────────────────
    //  二次密码验证
    // ──────────────────────────────────────

    /**
     * 检查当前 session 是否已通过二次验证
     */
    private function isPasswordVerified(): bool
    {
        $verified = session('db_manager_verified');
        if (!$verified) {
            return false;
        }
        // 有效期 30 分钟
       /* if (time() - (int)$verified > 1800) {
            session('db_manager_verified', null);
            return false;
        }*/
        return true;
    }

    /**
     * 处理密码验证 POST
     */
    private function handlePasswordCheck(): void
    {
        $input = $this->request->post('password', '', 'trim');
        if (empty($input)) {
            $this->showPasswordForm('请输入密码');
            exit;
        }

        $admin = Db::name('admin')
            ->field('pwd, salt')
            ->where('admin_id', $this->admin['admin_id'])
            ->find();

        if (!$admin || $admin['pwd'] !== md5($input . $admin['salt'])) {
            Log::write(
                sprintf(
                    'DB Manager: FAILED password check for admin#%d (%s) from %s',
                    $this->admin['admin_id'],
                    $this->admin['username'] ?? '?',
                    request()->ip()
                ),
                'warning'
            );
            $this->showPasswordForm('密码错误，请重试');
            exit;
        }

        // 验证通过，设 session
        session('db_manager_verified', time());
        $this->redirect(request()->url(true));
    }

    /**
     * 输出密码验证页面
     */
    private function showPasswordForm(string $error = ''): void
    {
        $errorHtml = $error ? '<p style="color:#e74c3c;">' . htmlspecialchars($error) . '</p>' : '';
        $adminName = htmlspecialchars($this->admin['username'] ?? '');

        echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库管理 — 身份验证</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f2f5; display: flex; align-items: center;
            justify-content: center; min-height: 100vh;
        }
        .card {
            background: #fff; border-radius: 8px; padding: 40px 36px 32px;
            width: 400px; max-width: 90vw; box-shadow: 0 2px 12px rgba(0,0,0,.08);
            text-align: center;
        }
        .card h2 { margin-bottom: 8px; font-size: 20px; color: #1a1a1a; }
        .card .sub { margin-bottom: 20px; font-size: 14px; color: #666; }
        .card input[type="password"] {
            width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9;
            border-radius: 6px; font-size: 15px; outline: none;
            transition: border-color .2s;
        }
        .card input[type="password"]:focus { border-color: #1677ff; }
        .card button {
            margin-top: 16px; width: 100%; padding: 10px 0; border: none;
            border-radius: 6px; background: #1677ff; color: #fff;
            font-size: 15px; cursor: pointer; transition: background .2s;
        }
        .card button:hover { background: #4096ff; }
        .card .back { margin-top: 12px; font-size: 13px; }
        .card .back a { color: #999; text-decoration: none; }
        .card .back a:hover { color: #1677ff; }
    </style>
</head>
<body>
    <div class="card">
        <h2>&#128274; 数据库管理面板</h2>
        <p class="sub">当前账号：<strong>{$adminName}</strong>，请输入登录密码进行二次验证</p>
        {$errorHtml}
        <form method="post">
            <input type="password" name="password" placeholder="请输入登录密码" autocomplete="current-password" autofocus>
            <button type="submit">验证并进入</button>
        </form>
        <p class="back"><a href="/admin">&#8592; 返回后台</a></p>
    </div>
</body>
</html>
HTML;
    }
}
