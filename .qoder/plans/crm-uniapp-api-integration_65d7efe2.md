
# CRM UniApp 手机端与后端 API 对接方案

## 项目现状分析

### 架构关系
```
crm_uniapp (手机端) ──HTTP──> api.php ──> app/api/controller/ (JSON API)
                              admin.php ──> app/admin/controller/ (HTML View)
```
- `app/api` 从 `app/admin` 复制而来，控制器结构一致
- `crm_uniapp` 从其他项目复制，config.js 中 baseUrl 指向 `http://crm.laikephp.cn`

### 已完成对接的模块
- 联系人(crm_customer_contacts)：list.vue, addPerson.vue, detail.vue, sendEmail.vue, filter.vue, relevance.vue 已使用 `this.$u.get/post()` 调用 API

### 关键规范（来自项目记忆）
- API 调用必须使用 `this.$u.get/post('url', params)` 显式形式，禁用 `this.$u.api.xxx()` 封装
- API 响应格式：`{code: 1/0, msg: '', data: ...}`
- API 字段类型属性名为 `formtype` 而非 `type`
- 图片路径需拼接 CDN 前缀 `this.vuex_config.upload.cdnurl`
- API 控制器需继承 BaseController，使用 JWT token 认证

---

## 阶段一：环境修复与登录对接（优先级：最高）

**目标**：让手机端能启动、能登录、能访问基础接口

### 任务 1.1：修复 config.js 本地开发地址
- **文件**：`crm_uniapp/common/config.js`
- **问题**：baseUrl 指向 `http://crm.laikephp.cn`，需改为本地开发地址
- **方案**：启用 H5 条件编译中的自动获取域名逻辑，或设置为 `http://localhost:8080`

### 任务 1.2：修复跨域和路由
- **文件**：`public/api.php`（第14行有语法错误 `"'Access-Control-Allow-Credentials` 引号问题）
- **路由**：验证 `/api.php/login/index` POST 登录接口可访问
- **检查**：`app/api/controller/Login.php` 中 `config()` 方法返回的 `upload.cdnurl` 是否正确

### 任务 1.3：验证 JWT 登录流程
- 调用 `login/index` POST → 获取 token + admin_info
- 验证 token 存储到 vuex_token + uni Storage
- 验证 http.interceptor.js 自动注入 token 到请求头
- 验证 `login/config` GET 获取系统配置（upload, theme, tabbar）

### 任务 1.4：清理 API 控制器的视图渲染残留
- **问题**：`app/api` 从 `app/admin` 复制，部分控制器仍调用 `View::assign()`, `View::fetch()`, `display()` 等
- **需要排查的文件**：所有 `app/api/controller/crm/*.php`
- **操作**：GET 请求返回 `$this->success('', '', $data)` 或 `return json()`，POST 请求保留业务逻辑

---

## 阶段二：核心业务模块对接（优先级：高）

**目标**：逐个修复每个业务模块的 CRUD 操作

### 任务 2.1：客户管理(Customer)对接
- **API 控制器**：`app/api/controller/crm/Customer.php`（1136行，核心功能完备）
- **手机端页面**：
  - `pages/client/index.vue` - 客户列表（需验证 index/search/filter）
  - `pages/client/details/index.vue` - 客户详情
  - `pages/client/followUp.vue` - 客户跟进
  - `pages/clues/*.vue` - 线索管理
- **验证点**：列表分页(offset/limit)、搜索过滤(filter/op)、字段类型映射(formtype)

### 任务 2.2：商机管理(Business)对接
- **API 控制器**：`app/api/controller/crm/Business.php`
- **手机端页面**：`pages/business/*.vue`
- **验证点**：关联客户选择、商机阶段流转

### 任务 2.3：合同管理(Contract)对接
- **API 控制器**：`app/api/controller/crm/Contract.php`
- **手机端页面**：`pages/contract/*.vue`

### 任务 2.4：回款管理(Receivables)对接
- **API 控制器**：`app/api/controller/crm/ContractReceivables.php`
- **手机端页面**：`pages/receivables/*.vue`

### 任务 2.5：订单管理(Order)对接
- **API 控制器**：`app/api/controller/crm/Order.php`
- **手机端页面**：查找是否有对应页面

---

## 阶段三：辅助功能对接（优先级：中）

### 任务 3.1：自定义字段系统对接
- **API 控制器**：`app/api/controller/Field.php`, `app/api/controller/Fields.php`
- **关键接口**：`fields/get_fields`（需验证 table 参数格式）
- **手机端组件**：`fa-fields.vue`, `fhx-field-search.vue`, `fhx-field-display.vue`

### 任务 3.2：通用选择器(SelectPage)对接
- **API 路由**：`crm.common/selectpage/model/.../type/...`
- **手机端组件**：`fa-selectpages.vue`

### 任务 3.3：文件上传对接
- **API**：`api/common/upload` 或 `/ajax/upload`
- **验证**：upload 配置中的 cdnurl 与图片显示一致性

### 任务 3.4：用户管理对接
- **手机端页面**：`pages/member/*.vue`
- **验证**：与 admin 端字段对齐（参考 memory: 手机端用户管理字段对齐后台）

---

## 阶段四：功能补全与一致性修复（优先级：中）

### 任务 4.1：待办事项(Backlog)对接
- **手机端页面**：`pages/backlog/*.vue`

### 任务 4.2：发票管理(Invoice)对接
- **手机端页面**：`pages/invoice/*.vue`

### 任务 4.3：产品管理(Product)对接
- **手机端页面**：`pages/product/*.vue`

### 任务 4.4：更多功能(More)对接
- **手机端页面**：`pages/more/*.vue`（签到、个人资料等）

### 任务 4.5：业绩管理(Performance)对接
- **手机端页面**：`pages/performance/*.vue`

---

## 阶段五：测试与质量保障（优先级：中）

### 任务 5.1：API 控制器视图代码清理
- 遍历 `app/api/controller/` 下所有控制器
- 移除 `$this->assignconfig()`, `View::assign()`, `View::fetch()`, `display()` 等方法调用
- GET 请求统一返回 JSON（使用 success/jsonSuccess），POST 保留业务逻辑

### 任务 5.2：手动 API 测试用例
- 使用 curl/Postman 对每个模块的 index/add/edit/delete 逐一测试
- 记录 API 响应格式与前端期望的差异

### 任务 5.3：手机端逐页面验证
- 启动 H5 dev server（`npm run dev:h5`）
- 从登录开始，逐页面验证数据加载、表单提交、列表筛选

### 任务 5.4：数据一致性校验
- 验证字段类型（formtype: text/select/datetime/date/image/images/file/files/textarea/radio/checkbox/popup_selection）
- 验证选项值格式（value:label）
- 验证日期时间格式（时间戳 vs 格式化字符串）

---

## 技术难点与对策

| 难点 | 说明 | 对策 |
|------|------|------|
| API 控制器混合视图代码 | `app/api` 从 admin 复制，残留 HTML 渲染逻辑 | 系统清理 GET 请求中的视图调用，改为返回 JSON |
| 字段名映射不一致 | 前端使用驼峰，后端使用下划线 | 以 `system_field` 表为准，统一使用下划线 |
| 日期时间格式差异 | 后端返回时间戳，前端需要格式化字符串 | 前端 realFieldVal() 已处理，确保 formtype 正确 |
| config.js 多环境 | 硬编码的 URL 导致切换环境困难 | 启用 H5 条件编译自动检测域名 |
| 跨域问题 | CORS 头中引号语法错误 | 修复 `public/api.php` 第14行 |

---

## 实施顺序

```
阶段一（1天）→ 阶段二（3天）→ 阶段三（2天）→ 阶段四（2天）→ 阶段五（2天）
```

每个业务模块的对接按以下子步骤执行：
1. 检查 API 控制器是否存在且正确
2. 清理视图渲染代码（如有）
3. 测试 API 接口（curl）
4. 修改前端页面 API 调用
5. 启动 dev server 验证
