-- 回款计划表 - 菜单权限配置
-- 插入auth_rule表，用于权限控制和菜单显示
-- 建议挂在【合同管理】菜单下

-- 请先查询确认合同管理菜单的ID，然后替换下面的 @contract_menu_id
-- SELECT id FROM ymwl_auth_rule WHERE title LIKE '%合同%' AND type=1 AND pid=0 LIMIT 1;

-- 删除已存在的菜单（如果重复执行）
DELETE FROM `ymwl_auth_rule` WHERE `href` LIKE 'crm.contract_receivables_plan/%';

-- 插入主菜单（回款计划管理）
-- 注意：请根据实际情况修改 pid 值，建议挂在合同管理下
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `pid`, `type`, `status`, `sort`, `authopen`, `menustatus`, `icon`) 
VALUES ('crm.contract_receivables_plan/index', '回款计划', @contract_menu_id, 1, 1, 100, 1, 1, 'fa fa-calendar-check-o');

-- 获取刚插入的菜单ID
SET @plan_menu_id = LAST_INSERT_ID();

-- 插入子权限（添加）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `pid`, `type`, `status`, `sort`, `authopen`, `menustatus`) 
VALUES ('crm.contract_receivables_plan/add', '添加', @plan_menu_id, 1, 1, 1, 1, 0);

-- 插入子权限（编辑）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `pid`, `type`, `status`, `sort`, `authopen`, `menustatus`) 
VALUES ('crm.contract_receivables_plan/edit', '编辑', @plan_menu_id, 1, 1, 2, 1, 0);

-- 插入子权限（删除）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `pid`, `type`, `status`, `sort`, `authopen`, `menustatus`) 
VALUES ('crm.contract_receivables_plan/delete', '删除', @plan_menu_id, 1, 1, 3, 1, 0);

-- 插入子权限（查看详情）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `pid`, `type`, `status`, `sort`, `authopen`, `menustatus`) 
VALUES ('crm.contract_receivables_plan/detail', '查看', @plan_menu_id, 1, 1, 4, 1, 0);

-- 插入子权限（导出）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `pid`, `type`, `status`, `sort`, `authopen`, `menustatus`) 
VALUES ('crm.contract_receivables_plan/export', '导出', @plan_menu_id, 1, 1, 5, 1, 0);

-- 使用说明：
-- 1. 先查询合同管理菜单ID: SELECT id FROM ymwl_auth_rule WHERE title LIKE '%合同管理%' AND type=1 LIMIT 1;
-- 2. 将 @contract_menu_id 替换为实际ID值
-- 3. 执行此SQL
-- 4. 运行命令生成CRUD代码: php think curd -t crm_contract_receivables_plan -u 1

