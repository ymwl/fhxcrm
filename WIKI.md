# 符号象CRM客户关系管理系统 - Wiki文档

> 版本：v5.1.2 | 构建时间：2026-09-03 | 框架：ThinkPHP 8

---

## 1. 项目概述和架构说明

### 1.1 系统简介

符号象CRM是一套面向中小企业的客户关系管理系统，支持PC后台管理端和移动端（uni-app H5/小程序），涵盖客户管理、商机跟踪、合同订单、回款计划、线索转化、公海池、审批流程、业绩分析等完整CRM业务能力。

### 1.2 技术栈

| 层级 | 技术选型 | 说明 |
|------|----------|------|
| 后端框架 | ThinkPHP 8 | 多应用模式，PHP >= 8.0 |
| ORM | think-orm 3.0 | 模型关联、字段缓存 |
| 数据库 | MySQL (utf8mb4) | 表前缀 `ymwl_` |
| 后台前端 | Layui + RequireJS | 模块化JS加载 |
| 移动端前端 | uni-app + uView UI | H5/微信小程序 |
| 认证 | Session（后台）/ JWT（API） | firebase/php-jwt 7.0 |
| Excel | PhpSpreadsheet + OpenSpout | 导入导出 |
| 邮件 | PHPMailer 7.0 | SMTP发送 |
| 微信 | zoujingli/wechat-developer | 公众号集成 |
| 安全 | voku/anti-xss | XSS过滤 |
| 插件系统 | ymwl/think8-addons | 热插拔扩展 |

### 1.3 多应用架构

系统采用 ThinkPHP 多应用模式（`topthink/think-multi-app`），通过不同入口文件访问不同应用：

```
入口文件          应用            用途
─────────────────────────────────────────────
admin.php    →   admin       →   后台管理端（Layui渲染）
api.php      →   api         →   移动端JSON接口（JWT认证）
index.php    →   index       →   前台首页
install.php  →   install     →   系统安装向导
```

默认应用为 `admin`（`config/app.php` 中 `'default_app'=>'admin'`）。

### 1.4 控制器继承体系

**后台管理端（admin应用）：**

```
think\App (框架)
  └── app\BaseController
        └── app\admin\controller\Common        ← 登录验证、RBAC权限、视图初始化
              └── app\common\controller\AdminController  ← CRUD基类、Curd trait
                    └── 业务控制器 (Customer, Contract, Clue...)
```

- `Common.php`：Session登录检查、权限节点验证（auth_rule + auth_group）、视图变量注入、JS自动加载路径计算
- `AdminController.php`：集成 `Curd` trait 提供标准 index/add/edit/delete/export/modify/selectpage 方法

**移动端API（api应用）：**

```
think\App (框架)
  └── app\api\controller\Common        ← 基础方法、jsonSuccess/jsonError
        └── app\api\controller\Authority  ← JWT Token验证、RBAC权限
              └── 业务API控制器 (crm\Customer, crm\Contract...)
```

- `Authority.php`：从Header读取Token → 单点登录校验（JWT解析 + `admin_token` 表存在性验证）→ 查询admin表 → 权限验证
- JWT签发（`app/api/common.php` 的 `getToken()`）与验证（`app/api/controller/Common.php` 的 `parseToken()`）均使用 `config('app.app_key')` 作为 HS256 密钥；该密钥优先取 `.env` 中的 `APP_KEY`，未配置时回退 `config/app.php` 默认值（php-jwt 7.0 要求密钥长度 >= 32 字节）
- 单点登录校验统一走 `Common::verifyTokenDb()`（`parseToken()` + `admin_token` 表存在性验证），供 `Authority`（HTTP 鉴权）、`Login::checkLogin()`、WSS 握手（`CallWss::auth()`）复用；校验结果经 share 通道缓存 60s（命中与未命中均缓存，避免高频请求打 DB），签发/续签/注销侧通过 `clearSsoTokenCache()` 同步维护缓存；`issueLoginToken()`/`revokeTokenByTokenId()` 位于 `app/api/common.php`
- API控制器复用admin模块的Model类，返回统一JSON格式

### 1.5 核心依赖说明（composer.json）

| 包名 | 版本 | 用途 |
|------|------|------|
| `topthink/framework` | * | ThinkPHP 8 核心框架 |
| `topthink/think-orm` | ^3.0 | 数据库ORM |
| `topthink/think-multi-app` | ^1.1 | 多应用支持 |
| `topthink/think-view` | ^2.0 | 模板引擎 |
| `topthink/think-filesystem` | ^2.0 | 文件系统抽象 |
| `topthink/think-captcha` | ^3.0 | 验证码 |
| `firebase/php-jwt` | 7.0 | JWT Token签发/验证 |
| `phpoffice/phpspreadsheet` | ^1.23 | Excel读写 |
| `openspout/openspout` | ^4.13 | 高性能Excel（大数据导出） |
| `zoujingli/wechat-developer` | ^1.2 | 微信公众号SDK |
| `phpmailer/phpmailer` | ^7.0 | 邮件发送 |
| `voku/anti-xss` | ^4.1 | XSS安全过滤 |
| `ymwl/think8-addons` | ^1.0 | 插件扩展系统 |

---

## 文档导航

本文档已按读者拆分为两份子手册，主文档仅保留项目概述与导航，请按需查阅：

| 读者 | 手册 | 内容范围（对应原章节） |
|------|------|----------------------|
| 业务使用者 / 运维人员 | [用户使用与运维指南](docs/用户使用与运维指南.md) | 功能模块、业务流程、安装部署、运维与常见问题（第 2/3/4/10 章） |
| 二次开发者 / 集成对接方 | [二次开发指南](docs/二次开发指南.md) | 代码结构、数据库设计、API、前后端交互、插件开发（第 5~9 章） |

> 本文档基于符号象CRM v5.1.2 生成，最后更新：2026-09-05
