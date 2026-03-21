这个问题不涉及具体代码修改，是基于项目实际情况制定 Git 版本控制策略。让我先了解项目当前的 Git 状态：
项目当前**没有初始化 Git 仓库**。以下是针对此项目的完整版本控制策略：

---

## Git 版本控制策略

### 一、初始化与基础配置

```bash
cd e:\web\crm.laikephp.com
git init

# 创建 .gitignore（排除 runtime/、vendor/、.env 等）
```

推荐的 `.gitignore`：
```
/runtime/*
/vendor/*
/.env
*.log
~$*.docx
CODEBUDD.md
.codebuddy/
composer.lock
```

---

### 二、分支管理策略

```
main (生产环境，稳定版本)
 ├── develop (开发主线)
 │    ├── feature/customer-service (功能分支)
 │    ├── feature/district-field  (功能分支)
 │    └── bugfix/xxx              (修复分支)
 └── hotfix/xxx (紧急修复，从 main 拉出)
```

| 分支类型 | 命名规范 | 说明 |
|---------|---------|------|
| `main` | - | 生产稳定版，只接受 merge |
| `develop` | - | 开发主线，日常开发合并目标 |
| `feature/` | `feature/功能简述` | 新功能开发，完成后合并回 develop |
| `bugfix/` | `bugfix/问题描述` | 非紧急修复，合并回 develop |
| `hotfix/` | `hotfix/问题描述` | 紧急修复，从 main 拉出，修完后合并回 main 和 develop |

---

### 三、提交信息规范

格式：`<type>(<scope>): <描述>`

```
feat(crm): 客户添加支持地区选择字段
fix(api): 修复token过期未正确返回401
refactor(common): param_to_str统一到helper函数
docs: 更新版本控制策略文档
chore: 添加.gitignore文件
```

| type | 含义 |
|------|------|
| `feat` | 新功能 |
| `fix` | 修复 Bug |
| `refactor` | 重构（不改变功能） |
| `docs` | 文档变更 |
| `chore` | 构建/工具变更 |
| `style` | 代码格式调整 |

---

### 四、Tag 标记关键版本

```bash
# 轻量标签
git tag v5.0.2

# 附注标签（推荐，包含提交信息）
git tag -a v5.1.0 -m "新增UniApp端地区选择、客户设置页面优化"

# 推送标签
git push origin v5.1.0

# 查看标签详情
git show v5.1.0
```

建议在以下时机打 Tag：
- 每次正式发布
- 重大功能上线
- 数据库结构变更前

---

### 五、回滚与恢复操作

#### 场景1：修改错误，尚未 commit
```bash
# 查看哪些文件被修改了
git status

# 恢复单个文件到上次提交状态
git checkout -- app/api/controller/crm/Customer.php

# 恢复所有修改
git checkout .
```

#### 场景2：已 commit 但未 push，想撤销
```bash
# 撤销最近一次 commit（保留修改内容）
git reset --soft HEAD~1

# 撤销最近一次 commit（丢弃修改内容，危险！）
git reset --hard HEAD~1
```

#### 场景3：已 push，需要回滚到指定版本
```bash
# 查看提交历史，找到目标版本号
git log --oneline

# 推荐：创建新 commit 来撤销（安全，不影响历史）
git revert <commit-hash>

# 强制方式：直接回退（会改写历史，多人协作时禁用）
git reset --hard <commit-hash>
git push -f origin main
```

#### 场景4：想恢复到某个 Tag
```bash
# 基于标签创建新分支
git checkout -b recover-v5.0.2 v5.0.2

# 或者将 main 强制回到标签状态
git checkout main
git reset --hard v5.0.2
```

#### 场景5：误删文件，从历史恢复
```bash
# 从指定 commit 恢复单个文件
git checkout <commit-hash> -- path/to/file.php
```

#### 场景6：查看任意历史版本代码（只读）
```bash
# 查看某个 commit 的完整文件内容
git show <commit-hash>:app/api/controller/crm/Customer.php

# 临时切换到历史版本查看
git checkout <commit-hash>
# 查看完后回到最新
git checkout main
```

---

### 六、日常核心工作流

```bash
# 1. 开始新功能
git checkout develop
git pull origin develop
git checkout -b feature/district-field

# 2. 开发过程中频繁提交
git add .
git commit -m "feat(crm): district类型字段支持省市区选择"

# 3. 功能完成，合并回 develop
git checkout develop
git merge feature/district-field
git push origin develop

# 4. 发布到生产
git checkout main
git merge develop
git tag -a v5.1.0 -m "v5.1.0 发布"
git push origin main --tags
```

### 七、关键注意事项

1. **`git push -f` 慎用**：强制推送会覆盖远程历史，多人协作时严禁使用
2. **`git reset --hard` 不可逆**：丢弃的修改无法恢复，先确认再执行
3. **生产环境改动前先打 Tag**：确保有恢复点
4. **敏感文件不入库**：`.env`、数据库密码等绝不提交
5. **每日开发结束养成 commit 习惯**：`git add . && git commit -m "chore: daily save"`

需要我帮你在当前项目初始化 Git 仓库并创建 `.gitignore` 吗？