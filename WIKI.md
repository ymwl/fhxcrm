# 符号象CRM客户关系管理系统 - Wiki文档

> 版本：v5.1.0 | 构建时间：2026-07-25 | 框架：ThinkPHP 8

---

## 目录

1. [项目概述和架构说明](#1-项目概述和架构说明)
2. [主要功能模块介绍](#2-主要功能模块介绍)
3. [系统配置要求和安装部署指南](#3-系统配置要求和安装部署指南)
4. [核心业务流程说明](#4-核心业务流程说明)
5. [代码结构和关键文件解释](#5-代码结构和关键文件解释)
6. [数据库设计和表结构说明](#6-数据库设计和表结构说明)
7. [API接口文档](#7-api接口文档)
8. [前后端交互说明](#8-前后端交互说明)
9. [扩展插件机制](#9-扩展插件机制)
10. [部署和运维注意事项](#10-部署和运维注意事项)

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

- `Authority.php`：从Header读取Token → JWT解析 → 查询admin表 → 权限验证
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

## 2. 主要功能模块介绍

### 2.1 CRM核心业务模块

| 模块 | 控制器 | 路由前缀 | 功能说明 |
|------|--------|----------|----------|
| 客户管理 | `crm\Customer` | crm.customer | 客户CRUD、转移、共享、导入导出、查重、公海回收 |
| 联系人 | `crm\CustomerContacts` | crm.customer_contacts | 联系人CRUD、邮件发送、关联客户 |
| 商机管理 | `crm\Business` | crm.business | 商机CRUD、阶段推进、产品关联 |
| 合同管理 | `crm\Contract` | crm.contract | 合同CRUD、审批、产品关联、到期提醒 |
| 回款管理 | `crm\ContractReceivables` | crm.contract.receivables | 回款CRUD、审批、回款计划 |
| 线索管理 | `crm\Clue` | crm.clues | 线索CRUD、转化为客户、转移、公共池 |
| 线索池 | `crm\CluePool` | crm.clue_pool | 线索公共池领取/分配 |
| 订单管理 | `crm\Order` | crm.order | 订单CRUD、审批、业绩统计 |
| 回款计划 | `crm\ContractReceivablesPlan` | crm.contract_receivables_plan | 计划回款列表、待回款筛选、逾期统计 |
| 公海管理 | `crm\Seas` | crm.seas | 公海池配置、客户回收/领取 |
| 提醒管理 | `crm\Reminder` | crm.reminder | 待办提醒、合同到期、回款提醒、生日提醒 |

### 2.2 跟进与记录模块

| 模块 | 控制器 | 功能说明 |
|------|--------|----------|
| 客户跟进 | `crm\Record` | 客户跟进记录（电话、拜访、微信等） |
| 商机跟进 | `crm\BusinessRecord` | 商机专属跟进记录 |
| 线索跟进 | `crm\ClueRecord` | 线索专属跟进记录 |
| 跟进类型 | `crm\RecordType` | 跟进方式配置（电话/微信/拜访等） |

### 2.3 数据分析模块

| 模块 | 控制器 | 功能说明 |
|------|--------|----------|
| 业绩分析 | `analysis\Admin` | 员工业绩统计、合同/回款/订单分析 |
| 排行榜 | `crm\Rank` | 销售排名（合同金额/回款/订单） |
| 业绩目标 | `crm\Performance` | 团队/个人月度业绩目标设置 |
| 数据看板 | API `crm\Dashboard` | 首页数据概览（仅API端） |

### 2.4 审批与提醒模块

| 模块 | 控制器 | 功能说明 |
|------|--------|----------|
| 审批管理 | `process\Audit` | 合同/回款/订单审批列表、通过/驳回 |
| 提醒管理 | `crm\Reminder` | 待办提醒（跟进/合同到期/回款/生日）、标记已读、全部已读、删除、详情查看 |
| 提醒服务 | `ReminderService` | 自动检查并生成跟进提醒、合同到期提醒、回款提醒、生日提醒 |
| 计划任务 | `index\Cron` | HTTP定时任务入口，执行提醒检查 + 合同到期自动状态变更 |

**提醒类型说明：**

| 类型 | 常量 | 触发条件 | 去重机制 |
|------|------|----------|----------|
| 跟进提醒 | TYPE_FOLLOW_UP=1 | 客户 next_time 在未来1天内 | 同一客户存在待处理提醒则跳过 |
| 合同到期 | TYPE_CONTRACT_EXPIRE=2 | 审核通过且进行中的合同，end_time 在7天内 | 已存在则动态更新剩余天数 |
| 回款提醒 | TYPE_RECEIVABLES=3 | 回款计划状态为计划中/进行中，plan_date - remind_days = 今天（仅查30天内到期计划） | 同一计划存在待处理提醒则跳过 |
| 生日提醒 | TYPE_BIRTHDAY=4 | 联系人 birthday 月日匹配明天 | 同一联系人当年已提醒则跳过 |

**提醒状态：**

| 状态值 | 常量 | 说明 |
|--------|------|------|
| 0 | STATUS_PENDING | 待处理 |
| 1 | STATUS_SENT | 已发送 |
| 2 | STATUS_READ | 已读 |

**关联类型（related_type）：**

| 值 | 说明 | 关联表 |
|----|------|--------|
| customer | 客户 | crm_customer |
| contract | 合同 | crm_contract |
| receivables_plan | 回款计划 | crm_contract_receivables_plan |
| contact | 联系人 | crm_customer_contacts |

**前端标签页（scope参数）：**

| scope值 | 说明 | 后端筛选条件 |
|---------|------|-------------|
| pending | 待处理（默认） | status = 0 |
| read | 已读 | status = 2 |
| all | 全部 | 不限制status |

**计划任务自动处理：**

| 处理项 | 条件 | 操作 |
|--------|------|------|
| 合同到期→已完成 | end_time < 今天 且 check_status=3(审核通过) 且 contract_status=0 | contract_status → 1 |
| 合同到期→已作废 | end_time < 今天 且 check_status≠3(非审核通过) 且 contract_status=0 | contract_status → -1 |

### 2.5 系统管理模块

| 模块 | 控制器 | 功能说明 |
|------|--------|----------|
| 自定义字段 | `system\Fields` | 动态字段配置（system_field表） |
| 模块管理 | `system\Models` | 业务模块注册与管理 |
| 系统配置 | `system\Config` | 全局参数配置（分组管理） |
| 权限管理 | `Auth` | 权限节点(auth_rule)管理 |
| 用户组 | `auth\Group` | 角色组管理、权限分配 |
| 数据角色 | `auth\Role` | 数据范围角色（本人/下属/全部等） |
| 管理员 | `Admin` | 后台用户管理 |
| 操作日志 | `admin\Log` | 管理员操作审计日志 |
| 登录日志 | `login\Log` | 登录记录查询 |

### 2.6 应用集成模块

| 模块 | 控制器 | 功能说明 |
|------|--------|----------|
| 邮件模板 | `Emailtpl` | 邮件HTML模板管理 |
| 邮件日志 | `email\Log` | 邮件发送记录 |
| 产品管理 | `Product` | 产品CRUD |
| 产品分类 | `product\Type` | 产品分类树 |
| 云呼叫 | `open\Apps` | 第三方云呼叫中心集成 |
| 文件上传 | `UpFiles` | 统一文件上传处理 |

### 2.7 插件管理

| 模块 | 控制器 | 功能说明 |
|------|--------|----------|
| 插件管理 | `Addon` | 插件列表、安装/卸载、启用/禁用、在线商店 |

---

## 3. 系统配置要求和安装部署指南

### 3.1 环境要求

| 项目 | 最低要求 | 推荐 |
|------|----------|------|
| PHP | >= 8.0 | 8.1+ |
| MySQL | 5.7+ | 8.0 (utf8mb4) |
| Composer | 2.0+ | 最新版 |
| Web服务器 | Nginx / Apache | Nginx |

**必要PHP扩展：**

```
pdo_mysql, mbstring, curl, openssl, gd, zip, dom, json, fileinfo
```

### 3.2 安装流程

**第一步：获取代码并安装依赖**

```bash
composer install
```

**第二步：配置环境变量**

编辑项目根目录 `.env` 文件：

```ini
APP_DEBUG = 0
APP_DEV = 0

[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = your_database_name
USERNAME = your_db_user
PASSWORD = your_db_password
HOSTPORT = 3306
CHARSET = utf8mb4
PREFIX = ymwl_

[LOG]
CHANNEL = file
```

**第三步：Web服务器配置**

将网站根目录指向 `public/` 目录。

**第四步：访问安装向导**

浏览器访问 `http://your-domain/install.php`，按提示填写数据库信息完成安装。

安装完成后系统自动生成 `config/install.lock` 文件，删除该文件可重新安装。

### 3.3 数据库配置详解

配置文件：`config/database.php`

```php
'connections' => [
    'mysql' => [
        'type'        => 'mysql',
        'hostname'    => '127.0.0.1',
        'database'    => 'crm_laikephp_cn',
        'username'    => 'root',
        'password'    => 'root',
        'hostport'    => '3306',
        'charset'     => 'utf8mb4',
        'prefix'      => 'ymwl_',          // 表前缀
        'fields_strict' => true,           // 严格字段检查
        'fields_cache'  => true,           // 字段缓存（生产环境建议开启）
        'trigger_sql'   => false,          // SQL日志（生产环境关闭）
    ],
],
```

### 3.4 Nginx伪静态配置

参考 `public/htaccess-nginx` 文件，核心规则：

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?s=/$1 last;
        break;
    }
}

# 后台入口
location /admin.php {
    try_files $uri $uri/ /admin.php?s=$uri&$args;
}

# API入口
location /api.php {
    try_files $uri $uri/ /api.php?s=$uri&$args;
}
```

### 3.5 Apache伪静态配置

参考 `public/htaccess-apache`，使用 `.htaccess` 文件：

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php?s=/$1 [QSA,PT,L]
</IfModule>
```

### 3.6 目录权限要求

```
runtime/          ← 777（日志、缓存、临时文件）
public/upload/    ← 777（上传文件存储）
config/           ← 755（配置文件，安装时需写入）
```

### 3.7 应用配置要点

配置文件：`config/app.php`

| 配置项 | 值 | 说明 |
|--------|-----|------|
| `default_app` | admin | 默认访问admin应用 |
| `default_timezone` | Asia/Shanghai | 时区 |
| `default_filter` | trim | 全局输入过滤 |
| `url_html_suffix` | html | URL伪静态后缀 |
| `pageSize` | 15 | 默认分页大小 |
| `auto_clear_logs` | 180 | 自动清理N天前日志 |
| `max_admin_num` | 0 | 管理员数量限制（0不限制） |

### 3.8 上传配置

配置文件：`config/upload.php`

```php
'upload_type'       => 'local',           // 存储方式：local/alioss/qnoss/txcos
'upload_allow_ext'  => 'doc,docx,gif,ico,jpg,mp3,mp4,png,rar,jpeg,csv,xls,xlsx,zip,pdf,avif',
'upload_allow_size' => '204800000',       // 最大200MB
```

支持四种存储方式：本地(local)、阿里云OSS(alioss)、七牛云(qnoss)、腾讯云COS(txcos)。

---

## 4. 核心业务流程说明

### 4.1 销售漏斗全流程

```
线索(Clue) → 转化 → 客户(Customer) → 商机(Business) → 合同(Contract) → 回款(Receivables)
                                              ↓
                                        订单(Order)
```

### 4.2 线索管理流程

1. **录入线索**：手动添加 / 导入 / 外部表单提交
2. **跟进线索**：添加线索跟进记录（`crm\ClueRecord`）
3. **线索转化**：将线索转化为客户（`Clue::converted` 方法）
   - 自动创建客户记录
   - 可选同时创建联系人
   - 标记线索为已转化状态
4. **线索池**：未分配/回收的线索进入公共池（`crm\CluePool`），支持领取/分配

### 4.3 客户管理流程

1. **客户创建**：手动添加 / 线索转化 / 导入
2. **客户查重**：根据名称/电话检测重复（`Customer::reduplicate`）
3. **客户跟进**：添加跟进记录（`crm\Record`），支持多种跟进方式
4. **客户转移**：变更负责人（`alter_pr_user`）
5. **客户共享**：将客户共享给其他同事（`share`）
6. **公海回收**：超时未跟进的客户自动/手动回收到公海池
7. **公海领取**：销售人员从公海池领取客户

### 4.4 商机管理流程

1. **创建商机**：关联客户，设置预计金额和阶段
2. **阶段推进**：商机在不同阶段间推进（初步接触→需求确认→方案报价→谈判→成交/失败）
3. **商机关联**：关联产品、联系人
4. **商机跟进**：独立的商机跟进记录（`crm\BusinessRecord`）

### 4.5 合同与回款流程

1. **创建合同**：关联客户、商机，添加产品明细
2. **合同审批**：提交审批 → 上级审核（通过/驳回）
3. **回款计划**：为合同制定分期回款计划（`ContractReceivablesPlan`）
4. **回款登记**：记录实际回款（`ContractReceivables`）
5. **回款审批**：回款记录需审批确认

### 4.6 审批流程

```
提交审批 → 待审批列表(process\Audit) → 审批操作(通过/驳回) → 更新业务状态(check_status)
```

- `check_status` 字段值：0=待审批，1=已通过，2=已驳回
- 审批权限由 `auth_group` 中的权限节点控制
- 支持合同审批、回款审批、订单审批

### 4.7 数据范围控制（scope）

系统通过 `auth_role` 表的 `type` 字段控制数据可见性：

| auth_role.type | 含义 | 数据范围 |
|----------------|------|----------|
| 0 | 仅本人 | pr_user = 当前用户ID |
| 1 | 直属下属 | parent_id = 当前用户ID 的管理员数据 |
| 2 | 本部门 | group_id = 当前用户组ID |
| 3 | 仅下属部门 | 下属所在部门的所有数据 |
| 4 | 本部门及下属部门 | 本部门 + 所有下属部门数据 |
| 5 | 全部 | 无限制（ALL） |
| sub_departments | 所有下级部门 | 组织架构树中所有下级 |
| self_sub_departments | 本部门及所有下级 | 包含自身部门的完整子树 |

前端传递 `scope` 参数：scope=1(我的)、scope=2(下属)、scope=3(全部)

### 4.8 自定义字段系统工作流

```
system_field表(后台配置) → Fields/get_fields接口(按table+source获取) → 前端动态渲染
```

1. 管理员在后台 `system\Fields` 中配置字段（字段名、类型、是否必填、排序等）
2. 前端通过 `fields/get_fields?table=crm_customer&source=index` 获取字段列表
3. 后台使用 Layui 动态渲染表单/表格列
4. 移动端使用 `fa-fields` 组件渲染表单，`fhx-field-display` 组件渲染详情

**source参数取值：** index(列表)、addForm(添加表单)、editForm(编辑表单)

---

## 5. 代码结构和关键文件解释

### 5.1 项目目录结构

```
crm.laikephp.com/
├── app/                          # 应用代码目录
│   ├── admin/                    # 后台管理应用
│   │   ├── command/              # 命令行工具(Curd生成器、插件管理等)
│   │   ├── controller/           # 后台控制器
│   │   │   ├── crm/              # CRM业务控制器
│   │   │   ├── analysis/         # 数据分析
│   │   │   ├── auth/             # 权限管理
│   │   │   ├── system/           # 系统设置
│   │   │   ├── process/          # 审批流程
│   │   │   ├── product/          # 产品管理
│   │   │   └── *.php             # 顶级控制器(Index,Login,Auth,Field等)
│   │   ├── model/                # 数据模型(41个)
│   │   ├── view/                 # 视图模板(Layui HTML)
│   │   ├── traits/               # Curd trait
│   │   ├── service/              # 后台业务服务（ReminderService等）
│   │   │   └── ReminderService.php # 提醒服务(跟进/合同到期/回款/生日提醒自动生成)
│   │   └── validate/             # 验证器
│   ├── api/                      # 移动端API应用
│   │   ├── controller/           # API控制器(与admin对应，覆盖全部业务模块)
│   │   │   ├── crm/              # CRM业务API(客户/联系人/商机/合同/回款/线索/订单/公海/提醒等)
│   │   │   ├── analysis/         # 数据分析API
│   │   │   ├── auth/             # 权限管理API
│   │   │   ├── system/           # 系统设置API
│   │   │   ├── process/          # 审批流程API
│   │   │   ├── product/          # 产品管理API
│   │   │   └── *.php             # 顶级控制器(Authority/Field/Product/System等)
│   │   └── traits/               # API Curd trait
│   ├── common/                   # 公共模块
│   │   ├── controller/           # AdminController基类
│   │   ├── lang/                 # 语言包(中英文，已从admin/lang迁移)
│   │   ├── model/                # 公共模型(TimeModel、AuditManagement、CrmContract等)
│   │   └── middleware/           # 公共中间件
│   ├── service/                  # 全局业务服务层
│   │   ├── AdminService.php      # 管理员层级、数据权限范围
│   │   ├── CrmCustomerService.php # 客户业务（查重、公海规则）
│   │   ├── CrmContractService.php # 合同业务逻辑
│   │   ├── CrmContractReceivablesService.php # 回款业务逻辑
│   │   ├── CrmClueService.php    # 线索转化逻辑
│   │   ├── SystemFieldService.php # 自定义字段查询与缓存
│   │   └── CheckStatusConst.php  # 审批状态常量
│   ├── common.php                # 全局公共函数
│   └── BaseController.php        # 框架基础控制器
├── config/                       # 配置文件目录
├── extend/                       # 扩展类库(PSR-0自动加载)
│   ├── fhx/                      # 辅助工具(Tree,Pinyin,Leftnav,Qrcode)
│   └── tools/                    # 工具类(Cache,Hs,Excel,System)
├── public/                       # Web根目录
│   ├── static/admin/js/          # RequireJS模块
│   ├── upload/                   # 上传文件存储
│   ├── admin.php                 # 后台入口
│   └── api.php                   # API入口
├── crm_uniapp/                   # uni-app移动端项目
│   ├── pages/                    # 主包页面(首页/客户/商机/线索/数据看板等)
│   ├── packageCrm/pages/         # CRM分包(客户详情/线索/商机/联系人/跟进等)
│   ├── packageDeal/pages/        # 交易分包(合同/订单/回款/回款计划/产品)
│   ├── packageAdmin/pages/       # 管理分包(成员/审批/业绩/更多功能)
│   ├── components/               # 公共组件
│   ├── common/                   # 公共JS(请求封装等)
│   ├── store/                    # Vuex状态管理
│   └── pages.json                # 路由配置
├── addons/                       # 插件目录
├── update/                       # SQL迁移脚本
├── vendor/                       # Composer依赖
└── runtime/                      # 运行时(日志/缓存)
```

### 5.2 关键文件说明

#### `app/common.php` - 全局公共函数

| 函数 | 用途 |
|------|------|
| `auth($uri, $admin)` | 权限检查，返回1(有权限)/0(无权限) |
| `xss_clean($html)` | XSS过滤，支持数组递归处理 |
| `safeInsert($table, $data)` | 安全插入，自动过滤非表字段 |
| `send_email($to, $subject, $content)` | 邮件发送（PHPMailer） |
| `myurl($path)` | URL生成（兼容插件路由） |
| `getRealClientIp()` | 获取CDN后真实客户端IP |
| `savecache($name)` | 更新系统缓存 |
| `real_field_val($field, $value)` | 字段值格式化（时间戳→日期等） |
| `post_convert($post, $fields)` | 表单数据类型转换 |
| `fy($str)` | 多语言翻译函数 |
| `build_select_list($table, $pk, $fk)` | 构建下拉选项列表 |

#### `app/admin/traits/Curd.php` - 后台CRUD复用

提供标准方法：
- `index()`：列表查询（支持filter/op/sort/分页）
- `add()`：添加（POST保存，GET渲染表单）
- `edit($id)`：编辑（POST保存，GET渲染表单）
- `delete()`：删除（支持批量，逗号分隔ID）
- `export()`：Excel导出
- `modify()`：单字段快捷修改
- `selectpage()`：下拉搜索选择（支持远程搜索）

#### `app/api/traits/Curd.php` - API端CRUD复用

与admin版本的区别：
- `index()`：直接返回JSON（code=1），额外支持 `search` 和 `offset` 参数
- `edit()`：GET请求返回行数据JSON（不渲染视图）
- 定义 `$searchFields` 属性控制快速搜索字段

#### `app/service/` - 业务服务层

| 文件 | 职责 |
|------|------|
| `AdminService.php` | 管理员层级关系、数据权限范围计算、下属ID/名称查询 |
| `CrmCustomerService.php` | 客户业务逻辑（查重、公海规则等） |
| `CrmContractService.php` | 合同业务逻辑、合同状态文本转换 |
| `CrmContractReceivablesService.php` | 回款业务逻辑 |
| `CrmClueService.php` | 线索转化逻辑、线索领取次数限制 |
| `SystemFieldService.php` | 自定义字段查询与缓存 |
| `CheckStatusConst.php` | 审批状态常量定义（0待审批/1审批中/2已驳回/3已通过） |

### 5.3 前端JS自动加载机制

后台采用 RequireJS 模块化加载：

1. `Common.php` 的 `viewInit()` 计算当前控制器对应的JS路径
2. 路径规则：`public/static/admin/js/{controller_path}.js`
3. 页面加载时自动执行 `Controller.{action}()` 方法

```javascript
define(["jquery", "easy-admin"], function ($, ea) {
    var Controller = {
        index: function () {
            ea.table.render({ elem: '#currentTable', url: 'crm.customer/index', cols: [[...]] });
            ea.listen();
        },
        add: function () { ea.listen(); },
        edit: function () { ea.listen(); }
    };
    return Controller;
});
```

---

## 6. 数据库设计和表结构说明

### 6.1 表命名规范

- 表前缀：`ymwl_`（代码中通过 `Db::name('表名')` 自动添加）
- CRM业务表：`ymwl_crm_` + 模块名（snake_case）
- 系统表：`ymwl_` + 表名

### 6.2 核心业务表

| 表名（不含前缀） | 说明 | 关键字段 |
|------------------|------|----------|
| `crm_customer` | 客户表 | name, phone, pr_user, status |
| `crm_customer_contacts` | 联系人表 | name, mobile, email, customer_id |
| `crm_business` | 商机表 | name, customer_id, money, status_id |
| `crm_contract` | 合同表 | name, customer_id, money, check_status |
| `crm_receivables` | 回款表 | contract_id, money, check_status |
| `crm_receivables_plan` | 回款计划表 | contract_id, money, receivables_time |
| `crm_clue` | 线索表 | name, phone, pr_user, is_convert |
| `crm_order` | 订单表 | name, customer_id, money, check_status |
| `crm_record` | 跟进记录表 | customer_id, content, record_type |
| `crm_receivables_plan` | 回款计划表 | contract_id, plan_no, plan_money, plan_date, remind_days, status |
| `crm_reminder` | 提醒表 | type, related_id, related_type, title, admin_id, remind_time, status |
| `crm_achievement` | 业绩目标表 | admin_id, month, target_amount |

### 6.3 系统管理表

| 表名（不含前缀） | 说明 | 关键字段 |
|------------------|------|----------|
| `admin` | 管理员表 | username, pwd, salt, group_id, role_id, is_open |
| `auth_rule` | 权限节点表 | href, title, pid, authopen, type |
| `auth_group` | 用户组表 | title, pid, rules(逗号分隔的rule ID) |
| `auth_role` | 数据角色表 | title, type(数据范围类型) |
| `system_field` | 自定义字段表 | table, field, name, formtype, show, edit |
| `system_config` | 系统配置表 | field, value, group_id |
| `module` | 模块注册表 | name, title, status |
| `email_log` | 邮件日志表 | email, title, status, create_time |
| `admin_log` | 操作日志表 | admin_id, href, create_time |

### 6.4 标准字段规范

每个CRM业务表必须包含：

```sql
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
`pr_user` int(11) NOT NULL DEFAULT 0 COMMENT '负责人ID',
`create_user` int(11) NOT NULL DEFAULT 0 COMMENT '创建人ID',
`create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间(时间戳)',
`update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间(时间戳)',
`status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
```

### 6.5 自定义字段表（system_field）

| 字段 | 说明 |
|------|------|
| table | 所属表名（如 `crm_customer`） |
| field | 数据库列名 |
| name | 显示名称 |
| formtype | 表单类型（text/textarea/select/date/datetime/radio/checkbox/file/image/user） |
| show | 列表是否显示 |
| edit | 表单是否可编辑 |
| is_required | 是否必填 |
| option | 选项值（格式：`值:名称,值:名称`） |
| list_sort | 是否开启排序 |

### 6.6 SQL迁移脚本

所有数据库变更脚本存放在 `update/` 目录，包括：
- `5.0.2.sql`：版本升级
- `clue_table.sql` / `clue_field.sql` / `clue_menu.sql`：线索模块
- `clue_pool.sql` / `clue_record.sql` / `clue_converted_menu.sql`：线索池与转化
- `clue_sendemail_menu.sql`：线索邮件菜单
- `receivables_plan_table.sql`：回款计划表结构
- `receivables_plan_field.sql`：回款计划自定义字段
- `receivables_plan_id_field.sql`：回款计划ID关联字段
- `receivables_plan_menu.sql`：回款计划菜单权限
- `reminder_table.sql`：提醒表结构
- `reminder_menu.sql`：提醒菜单权限
- `achievement_table.sql`：业绩目标
- `system_field_indexes.sql`：索引优化

---

## 7. API接口文档

### 7.1 认证机制

- **认证方式**：JWT Token（firebase/php-jwt 7.0）
- **Token传递**：HTTP Header 中携带 `token` 字段
- **Token获取**：登录接口返回
- **过期处理**：Token过期返回 `{code:1, msg:"token已过期", statusCode:401}`

**请求示例：**
```
GET /api.php/crm.customer/index
Header: token: eyJhbGciOiJIUzI1NiIs...
```

### 7.2 基础URL格式

```
http://your-domain/api.php/{控制器路径}/{方法名}
```

控制器路径使用点号分隔多级：
- `crm.customer/index` → `app/api/controller/crm/Customer.php` 的 `index` 方法
- `crm.contract_receivables/add` → `app/api/controller/crm/ContractReceivables.php` 的 `add` 方法
- `fields/get_fields` → `app/api/controller/Fields.php` 的 `get_fields` 方法

### 7.3 统一响应格式

```json
// 列表接口（data直接为数组，非嵌套rows）
{
    "code": 1,
    "msg": "",
    "count": 100,
    "data": [{"id":1,"name":"张三"}, ...],
    "auth": { "edit": 1, "delete": 1 },
    "scopes": { "1": "我的", "2": "下属的", "3": "全部" }
}

// 详情/编辑数据（GET edit）
{
    "code": 1,
    "msg": "success",
    "data": { "id": 123, "name": "张三", ... }
}

// 操作成功
{ "code": 1, "msg": "保存成功" }

// 操作失败
{ "code": 0, "msg": "保存失败，名称不能为空" }

// 认证/权限错误
{ "code": 1, "msg": "Token已过期", "statusCode": 401 }
{ "code": 1, "msg": "您无此操作权限!", "statusCode": 403 }
```

### 7.4 主要接口列表

#### 登录认证

| 接口 | 方法 | 说明 |
|------|------|------|
| `Login/index` | POST | 用户名密码登录，返回Token |
| `Login/out` | POST | 退出登录 |

#### 客户管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.customer/index` | GET | 客户列表（分页+搜索+筛选） |
| `crm.customer/add` | POST | 添加客户 |
| `crm.customer/edit` | GET/POST | GET获取详情，POST保存修改 |
| `crm.customer/delete` | POST | 删除客户 |
| `crm.customer/reduplicate` | GET | 客户查重 |
| `crm.customer/alter_pr_user` | POST | 转移负责人 |
| `crm.customer/share` | POST | 共享客户 |
| `crm.customer/to_move_gh` | POST | 移入公海 |

#### 联系人

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.customer_contacts/index` | GET | 联系人列表 |
| `crm.customer_contacts/add` | POST | 添加联系人 |
| `crm.customer_contacts/edit` | GET/POST | 编辑联系人 |
| `crm.customer_contacts/delete` | POST | 删除联系人 |
| `crm.customer_contacts/sendEmail` | POST | 发送邮件 |

#### 商机管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.business/index` | GET | 商机列表 |
| `crm.business/add` | POST | 添加商机 |
| `crm.business/edit` | GET/POST | 编辑商机 |
| `crm.business/delete` | POST | 删除商机 |
| `crm.business_record/add` | POST | 添加商机跟进 |

#### 合同管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.contract/index` | GET | 合同列表 |
| `crm.contract/add` | POST | 添加合同 |
| `crm.contract/edit` | GET/POST | 编辑合同 |
| `crm.contract/delete` | POST | 删除合同 |

#### 回款管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.contract_receivables/index` | GET | 回款列表 |
| `crm.contract_receivables/add` | POST | 添加回款 |
| `crm.contract_receivables_plan/index` | GET | 回款计划列表 |
| `crm.contract_receivables_plan/add` | POST | 添加回款计划 |

#### 线索管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.clues/index` | GET | 线索列表 |
| `crm.clues/add` | POST | 添加线索 |
| `crm.clues/edit` | GET/POST | 编辑线索 |
| `crm.clues/delete` | POST | 删除线索 |
| `crm.clues/converted` | POST | 线索转化为客户 |
| `crm.clue_pool/index` | GET | 线索池列表 |

#### 订单管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.order/index` | GET | 订单列表 |
| `crm.order/add` | POST | 添加订单 |
| `crm.order/edit` | GET/POST | 编辑订单 |
| `crm.order/delete` | POST | 删除订单 |

#### 审批管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `process.audit/index` | GET | 审批列表 |
| `process.audit/check` | POST | 审批操作（通过/驳回） |

#### 回款计划

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.contract_receivables_plan/index` | GET | 回款计划列表（含逾期统计） |
| `crm.contract_receivables_plan/add` | POST | 添加回款计划 |
| `crm.contract_receivables_plan/edit` | GET/POST | 编辑回款计划 |

#### 提醒管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `crm.reminder/index` | GET | 提醒列表（待提醒/已读） |
| `crm.reminder/markRead` | POST | 标记单条已读 |
| `crm.reminder/markAllRead` | POST | 全部标记已读 |
| `crm.reminder/delete` | POST | 删除提醒 |
| `crm.reminder/getUnreadCount` | GET | 获取未读提醒数量 |
| `crm.reminder/detail` | GET | 提醒详情（自动标记已读） |

#### 系统接口

| 接口 | 方法 | 说明 |
|------|------|------|
| `fields/get_fields` | GET | 获取自定义字段配置 |
| `crm.dashboard/index` | GET | 首页数据看板 |
| `crm.performance/index` | GET | 业绩目标 |
| `analysis.admin/index` | GET | 业绩分析 |

### 7.5 查询参数规范

```
列表查询参数：
├── page           → 页码（从1开始）
├── limit          → 每页数量（默认15，最大1000）
├── search         → 快速搜索（匹配searchFields定义的字段）
├── sort_by        → 排序字段
├── sort_order     → 排序方向（asc/desc）
├── filter         → 高级过滤（JSON字符串）
├── op             → 过滤操作符（JSON字符串）
└── scope          → 数据范围（1我的/2下属/3全部）
```

**请求示例：**
```
GET /api.php/crm.customer/index?page=1&limit=15&search=张三&scope=1&sort_by=create_time&sort_order=desc
```

### 7.6 操作符对照表

| 操作符 | 含义 | 示例 |
|--------|------|------|
| `=` | 等于 | `{"status":"="}` + `{"status":"1"}` |
| `%*%` | 两端模糊 | `{"name":"%*%"}` + `{"name":"张"}` |
| `*%` | 左匹配 | `{"name":"*%"}` + `{"name":"张"}` |
| `%*` | 右匹配 | `{"name":"%*"}` + `{"name":"三"}` |
| `in` | 包含 | `{"id":"in"}` + `{"id":"1,2,3"}` |
| `>` | 大于 | `{"amount":">"}` + `{"amount":"1000"}` |
| `<` | 小于 | `{"amount":"<"}` + `{"amount":"5000"}` |
| `range` | 时间区间 | `{"create_time":"range"}` + `{"create_time":"2025-01-01 - 2025-12-31"}` |

---

## 8. 前后端交互说明

### 8.1 后台管理端（Layui）

#### 数据表格交互

后台使用 Layui table 组件 + easy-admin 封装：

```javascript
ea.table.render({
    elem: '#currentTable',
    url: 'crm.customer/index',
    method: 'get',
    cols: [[
        {type: 'checkbox'},
        {field: 'id', title: 'ID', sort: true},
        {field: 'name', title: '客户名称'},
        {field: 'phone', title: '电话'},
        {width: 250, title: '操作', templet: ea.table.tool, operat: [...]}
    ]],
    // 请求参数自动携带 page, limit, filter, op
});
```

#### 权限按钮控制

视图模板通过 `data-auth-*` 属性控制按钮显隐：

```html
<table id="currentTable"
       data-auth-add="{:auth('crm.customer/add')}"
       data-auth-edit="{:auth('crm.customer/edit')}"
       data-auth-delete="{:auth('crm.customer/delete')}">
</table>
```

JS中读取权限：
```javascript
var authAdd = $('#currentTable').data('auth-add');
if (authAdd) { /* 显示添加按钮 */ }
```

#### selectPage下拉选择组件

用于关联字段选择（如选择客户、联系人）：

```javascript
ea.selectPage({
    elem: '#customer_id',
    url: 'crm.customer/selectpage',
    field: 'name',
    primaryKey: 'id',
    searchField: 'name,phone'
});
```

### 8.2 移动端（uni-app）

#### 请求封装

统一使用 `this.$u.get` / `this.$u.post` 发起请求：

```javascript
// GET请求（data直接为数组）
this.$u.get('crm.customer/index', {
    page: 1, limit: 15, search: '', scope: 1
}).then(res => {
    if (res.code == 1) {
        this.dataList = res.data;  // data直接是数组，无需.rows
    }
});

// POST请求
this.$u.post('crm.customer/add', formData).then(res => {
    if (res.code == 1) {
        uni.showToast({ title: '保存成功' });
    }
});
```

请求拦截器自动在Header中附加Token：
```javascript
header: { 'token': store.state.vuex_token }
```

#### 自定义字段获取

使用全局方法 `this.$getFields(table, source)` 获取字段配置（内部带1小时缓存）：

```javascript
// 获取字段（自动缓存，无需重复请求）
this.$getFields('crm_customer', 'index').then(fields => {
    this.fields = fields;
    this.getList();  // 字段就绪后加载数据
});
```

#### 自定义字段组件

**表单渲染（fa-fields）：**
```vue
<fa-fields :fields="fields" :formData="form" @change="onFieldChange"></fa-fields>
```

**详情展示（fhx-field-display）：**
```vue
<fhx-field-display :fields="fields" :item="item"></fhx-field-display>
```

#### 核心自定义组件

| 组件 | 用途 |
|------|------|
| `fhx-navbar` | 顶部导航栏（替代u-navbar），集成搜索框 |
| `fhx-scope-tabs` | 数据范围标签页切换（我的/下属/全部） |
| `fa-tabbar` | 底部导航栏（仅tabBar页面使用） |
| `fa-fields` | 动态表单字段渲染 |
| `fhx-field-display` | 列表/详情字段展示 |

#### 列表页标准模式

每个列表页包含：
- **分页加载**：page + limit 参数，滚动触底加载更多
- **快速搜索**：500ms防抖，search参数
- **排序功能**：sort_by + sort_order，下拉选择排序字段（由字段 list_sort 配置动态生成）
- **数据范围**：通过 `fhx-scope-tabs` 组件切换 scope（tabBar页通过 `nav_scope` Vuex变量传递）
- **筛选功能**：独立筛选页，tabBar页条件存入 `vuex_filter`（Vuex），非tabBar页存入Storage
- **权限控制**：依赖API返回的 `auth` 字段控制按钮显隐

**分页大小规范（pageSize）：**

全局混入 `store/$u.mixin.js` 已定义计算属性 `pageSize`（优先读取后台系统配置 `admin_pagesize`，默认10），所有页面直接使用 `this.pageSize` 即可。

- **禁止**在页面 `data()` 中重复定义 `pageSize`，否则触发 Vue 警告 `The computed property "pageSize" is already defined in data`
- 页面确需固定的、区别于全局配置的每页条数时，使用其他变量名（如审批列表 `packageAdmin/pages/process/audit/index.vue` 使用 `limitSize: 15`）
- 接口请求参数对象中的 `pageSize` 属性（如 `admin/selectpage` 的参数）不受此限制

#### 移动端分包结构

| 分包 | 目录 | 包含模块 |
|------|------|----------|
| 主包 | `pages/` | 首页、客户列表、商机列表、线索列表、数据看板、登录、筛选 |
| packageCrm | `packageCrm/pages/` | 客户详情/添加/筛选/跟进/共享/邮件/查重/公海、线索详情/添加/筛选/池/转化/邮件、线索跟进、商机详情/添加/筛选/跟进、联系人列表/详情/添加/筛选/邮件/关联、跟进记录、发送信息 |
| packageDeal | `packageDeal/pages/` | 合同列表/添加/详情/筛选/审批、订单列表/添加/详情/筛选/审批、回款列表/添加/详情/筛选/审批、回款计划列表/添加/筛选、产品列表/添加/选择/分类 |
| packageAdmin | `packageAdmin/pages/` | 成员管理/添加/第三方登录、功能列表/图标预览/订阅消息/个人信息/云呼设置、审批管理/审核、业绩快速设置/筛选 |

```javascript
// 滚动加载更多
reachBottom() {
    if (this.lastPage) return;
    this.getList(true, ++this.page);
}
```

#### 筛选条件暂存机制

**tabBar页面（如客户列表 pages/customer/index.vue）：** 使用Vuex `vuex_filter` 存储
```javascript
// 筛选页保存条件到Vuex
this.$u.vuex('vuex_filter', {
    filter: { status: '1' },
    op: { status: '=' }
});

// 列表页读取Vuex筛选条件
const filter = this.vuex_filter;
```

**非tabBar页面（如联系人 packageCrm/pages/contacts/）：** 使用Storage存储
```javascript
// 筛选页保存条件到Storage
uni.setStorageSync('contacts_filter', {
    filter: { status: '1' },
    op: { status: '=' },
    scope: 1,
    scopeName: '我的'
});

// 列表页读取Storage筛选条件
const filter = uni.getStorageSync('contacts_filter');
```

#### scope范围跨tabBar页传递

tabBar页面无法通过URL参数传值，scope切换通过Vuex中转：
```javascript
// 从其他页面跳转并指定scope（如"我的客户"快捷入口）
this.$u.vuex('nav_scope', 1);
uni.switchTab({ url: '/pages/customer/index' });

// 客户列表页onShow中读取nav_scope
if (this.nav_scope) {
    this.scope = this.nav_scope;
    this.$u.vuex('nav_scope', '');  // 用完清空
}
```

#### 未读提醒角标刷新机制

接口：`GET crm.reminder/getUnreadCount`，返回未读提醒数量 `count` 及最新 5 条提醒列表。

统一入口为 `common/fa.mixin.js`（tools mixin）的 `refreshReminderBadge(force)` 方法：

- 请求成功后将未读数写入全局 `vuex_unread_count`（供头部消息通知位置绑定展示）；tabBar 配置包含提醒页时，同步更新 `vuex_config.tabbar.list` 中提醒项的 `count` 角标（fa-tabbar 组件 u-badge 渲染）
- **节流保护**：模块级时间戳全局共享，30 秒内重复调用直接跳过；传入 `force=true` 可跳过节流强制刷新
- 未登录（无 `vuex_token`）时自动跳过，不发请求

**触发时机：**

| 触发点 | 调用方式 | 说明 |
|--------|----------|------|
| `fa-tabbar` 组件 `created` | `refreshReminderBadge()` | 首次进入带底部导航的页面 |
| 5 个 tabBar 页面 `onShow` | `refreshReminderBadge()` | 首页/客户/商机/线索/数据统计，切换 tab 时走 30 秒节流 |
| 提醒列表/详情操作后 | `refreshReminderBadge(true)` | 标记已读、全部已读、删除、详情自动已读后强制立即同步 |

注：`pages/business/index.vue` 与 `pages/presentation/index.vue` 为此引入了 tools mixin，其余 tabBar 页原已引入。

#### Vuex全局状态

| 状态键 | 用途 |
|--------|------|
| `vuex_token` | JWT Token |
| `vuex_config` | 系统配置（字段、菜单等） |
| `vuex_theme` | 主题色配置 |
| `vuex_user` | 当前用户信息 |
| `vuex_filter` | tabBar页面筛选条件暂存 |
| `nav_scope` | 跨tabBar页scope传递中转 |
| `vuex_unread_count` | 未读提醒数量（由 `refreshReminderBadge` 刷新，不持久化） |

#### 权限按钮渲染

```vue
<view class="btn" v-if="itemAuth.edit == 1" @click.stop="editItem(item)">编辑</view>
<view class="btn" v-if="itemAuth.delete == 1" @click.stop="deleteItem(item)">删除</view>
```

### 8.3 后台与移动端权限对等

两端使用完全相同的权限节点（auth_rule.href）：

| 后台模板 | API auth返回 | 前端 v-if |
|----------|--------------|------------|
| `data-auth-add` | `auth.add` | `itemAuth.add == 1` |
| `data-auth-edit` | `auth.edit` | `itemAuth.edit == 1` |
| `data-auth-delete` | `auth.delete` | `itemAuth.delete == 1` |
| `data-auth-sendEmail` | `auth.sendEmail` | `itemAuth.sendEmail == 1` |

---

## 9. 扩展插件机制

### 9.1 插件系统概述

系统基于 `ymwl/think8-addons` 包实现插件扩展，支持插件的安装/卸载、启用/禁用、在线商店下载等功能。

插件管理入口：后台 `Addon` 控制器（`app/admin/controller/Addon.php`）

### 9.2 插件目录结构

```
addons/{plugin_name}/
├── Plugin.php              # 插件主类（生命周期钩子）
├── info.json               # 插件信息配置
├── menu.php                # 菜单注册（可选）
├── install.sql             # 安装时执行的SQL（可选）
├── uninstall.sql           # 卸载时执行的SQL（可选）
├── config.json             # 插件配置项（可选）
├── route.php               # 插件路由（可选）
├── app/                    # 插件控制器（可选）
│   └── admin/controller/
├── library/                # 插件类库（可选）
├── service/                # 插件服务（可选）
├── view/                   # 插件视图（可选）
└── public/                 # 插件静态资源（可选）
```

### 9.3 插件配置文件（info.json）

```json
{
    "name": "database",
    "title": "数据库管理",
    "description": "在线数据库管理工具",
    "website": "https://www.80zx.com/",
    "status": 0,
    "install": 0,
    "author": "zrwx978",
    "version": "1.0.0",
    "build": "2025-07-01 00:00:00",
    "is_set": 0
}
```

| 字段 | 说明 |
|------|------|
| name | 插件标识（唯一，与目录名一致） |
| title | 插件显示名称 |
| description | 插件描述 |
| status | 启用状态（0禁用/1启用） |
| install | 安装状态（0未安装/1已安装） |
| version | 插件版本号 |
| is_set | 是否有配置页 |

### 9.4 插件主类（Plugin.php）

插件主类继承 `think\Addons`，提供四个生命周期钩子：

```php
<?php
namespace addons\database;

use think\Addons;

class Plugin extends Addons
{
    // 插件安装时执行
    public function install() { return true; }

    // 插件卸载时执行
    public function uninstall() { return true; }

    // 插件启用时执行
    public function enabled() { return true; }

    // 插件禁用时执行
    public function disabled() { return true; }
}
```

### 9.5 现有插件说明

#### database - 数据库管理插件

- **功能**：在线数据库管理（基于Adminer），仅超级管理员（id=1）可访问
- **控制器**：`addons/database/app/admin/controller/database/Adminer.php`
- **安全特性**：
  - 首次访问需输入登录密码进行二次验证，验证有效期30分钟
  - 限制只能操作当前项目配置的数据库
  - 记录访问操作日志
- **用途**：数据表浏览、SQL查询、数据导入导出、表结构修改

#### wechat - 微信公众号管理插件

- **功能**：微信公众号菜单管理
- **控制器**：
  - `addons/wechat/app/admin/controller/wechat/Account.php`（公众号配置）
  - `addons/wechat/app/admin/controller/wechat/Menu.php`（菜单管理）
- **依赖**：`zoujingli/wechat-developer` SDK
- **配置**：`addons/wechat/config.json`（存储AppID、AppSecret等）

### 9.6 插件管理操作

通过后台 `Addon` 控制器提供以下操作：

| 操作 | 说明 |
|------|------|
| 本地列表 | 扫描addons目录，同步到数据库 |
| 在线商店 | 从 `cloud.laikephp.com` 获取可用插件列表 |
| 安装 | 执行install.sql + 调用Plugin::install() + 注册菜单 |
| 卸载 | 执行uninstall.sql + 调用Plugin::uninstall() + 移除菜单 |
| 启用/禁用 | 调用Plugin::enabled()/disabled() + 更新状态 |
| 升级 | 下载新版本 + 执行升级SQL |

### 9.7 插件配置（config/addons.php）

```php
return [
    'autoload' => true,    // 自动加载插件
    'hooks'    => [],      // 钩子定义
    'route'    => [],      // 插件路由
    'service'  => [],      // 插件服务
];
```

---

## 10. 部署和运维注意事项

### 10.1 生产环境配置

上线前必须调整的配置：

```ini
# .env 文件
APP_DEBUG = 0
APP_DEV = 0
```

- `APP_DEBUG=0`：关闭调试模式，隐藏错误详情
- `APP_DEV=0`：关闭开发模式，静态资源使用版本号而非时间戳
- `config/database.php` 中 `trigger_sql` 设为 false

### 10.2 日志管理

配置文件：`config/log.php`

- 日志通道：file（文件存储）
- 存储路径：`runtime/{app_name}/log/`
- 最大文件数：120个
- 自动清理：`config/app.php` 中 `auto_clear_logs => 180`（清理180天前日志）

### 10.3 缓存策略

| 缓存类型 | 缓存键 | 说明 |
|----------|--------|------|
| 字段缓存 | `fields_cache` | 数据库表字段结构（生产环境必须开启） |
| 权限缓存 | `rules_{admin_id}` | 管理员权限节点列表 |
| 管理员缓存 | `admin_id_{id}` | 管理员基本信息 |
| 系统配置 | `System` | 全局配置项 |
| 自定义字段 | `{moduleid}_Field` | 模块字段配置 |
| 权限规则 | `auth_rule_{uri}_{id}` | 单个权限节点查询 |

**清除缓存命令：**
```bash
php think clear
```

**需要清除缓存的场景：**
- 修改 system_field 表 → 清除自定义字段缓存
- 修改 auth_rule 表 → 清除 `rules_*` 权限缓存
- 修改系统配置 → 清除 `System` 缓存

### 10.4 安全注意事项

| 安全措施 | 实现方式 |
|----------|----------|
| XSS防护 | `xss_clean()` 函数（voku/anti-xss），所有POST数据过滤 |
| CSRF防护 | 表单Token验证（`CSRF_TOKEN`） |
| SQL注入防护 | ORM参数化查询 + `safeInsert()` 过滤非表字段 |
| 权限控制 | RBAC三表关联（admin + auth_group + auth_rule） |
| 文件上传 | 白名单扩展名 + MIME检查 + 大小限制 |
| 密码存储 | salt + 加密存储 |
| API认证 | JWT Token + 过期机制 |

### 10.5 文件上传安全

配置于 `config/upload.php`：

- 允许扩展名：doc,docx,gif,ico,jpg,mp3,mp4,png,rar,jpeg,csv,xls,xlsx,zip,pdf,avif
- 最大文件：200MB
- MIME白名单检查
- 存储目录：`public/upload/`

### 10.6 数据库备份

- 使用 database 插件提供的在线备份功能
- 建议定期通过mysqldump命令行备份
- 备份文件不应存放在Web可访问目录

### 10.7 版本升级流程

1. 备份数据库和文件
2. 按顺序执行 `update/` 目录中的SQL脚本
3. 替换代码文件
4. 执行 `composer install` 更新依赖
5. 清除缓存：`php think clear`
6. 检查 `config/version.php` 版本号

### 10.8 性能优化建议

| 优化项 | 说明 |
|--------|------|
| 字段缓存 | `fields_cache => true`，避免每次查询表结构 |
| 列表限制 | 默认limit=15，最大1000，防止大查询 |
| 权限缓存 | 权限查询结果缓存，避免重复JOIN |
| OPcache | 生产环境开启PHP OPcache |
| MySQL索引 | 对pr_user、create_time、customer_id等常用查询字段建索引 |
| 静态资源 | 生产环境使用版本号缓存（非时间戳） |
| 日志清理 | 自动清理180天前日志，避免磁盘占满 |

### 10.9 计划任务配置（提醒检查 + 合同到期处理）

提醒系统通过 HTTP 方式的计划任务驱动，由 `app/index/controller/Cron.php` 提供入口。

**访问地址：**
```
http://你的域名/index.php/cron/index?token=your_secure_token_here_2026
```

**安全机制：** 需要 `token` 参数验证，token 值在 `config/app.php` 中的 `cron_token` 配置项设置（上线后务必修改默认值）。

**配置方式（宝塔面板）：**
1. 打开宝塔 → 计划任务 → 添加任务
2. 任务类型：访问URL
3. URL：填写上述地址（含token参数）
4. 执行周期：建议每小时执行一次

**配置方式（在线cron服务）：**
- 使用 https://cron-job.org 等在线服务，设置定时访问URL

**执行内容：**

| 序号 | 检查项 | 说明 |
|------|--------|------|
| 1 | 跟进提醒 | 检查客户 next_time 在未来1天内的记录，生成待办提醒 |
| 2 | 合同到期提醒 | 检查7天内到期的进行中合同，新建或动态更新提醒内容中的剩余天数 |
| 3 | 回款提醒 | 根据每个回款计划的 remind_days 设置，在计划日期前N天触发提醒 |
| 4 | 生日提醒 | 检查明天过生日的联系人（按月日匹配），每年每人只提醒一次 |
| 5 | 清理过期提醒 | 删除创建时间超过180天的所有提醒记录 |
| 6 | 合同状态变更 | 到期合同自动设为已完成（审核通过）或已作废（未审核通过） |

**返回格式（JSON）：**
```json
{
    "code": 0,
    "msg": "success",
    "time": "2026-07-25 10:00:00",
    "data": {
        "follow_up": 3,
        "contract_new": 2,
        "contract_updated": 5,
        "receivables": 1,
        "birthday": 0,
        "cleaned": 10,
        "contract_completed": 1,
        "contract_voided": 0
    },
    "log": ["..."]
}
```

### 10.10 常见问题排查

| 问题 | 排查方向 |
|------|----------|
| 权限不生效 | 检查auth_rule.authopen是否为1，清除rules_*缓存 |
| 自定义字段不显示 | 检查system_field表show/edit字段，清除字段缓存 |
| 上传失败 | 检查目录权限、upload_max_filesize、post_max_size |
| API Token过期 | 检查JWT密钥配置、服务器时间是否同步 |
| 页面空白 | 检查runtime目录权限、PHP错误日志 |
| 插件无法安装 | 检查addons目录权限、info.json格式 |

---

> 本文档基于符号象CRM v5.1.0 生成，最后更新：2026-07-25
