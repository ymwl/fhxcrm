-- 提醒功能菜单和权限配置
-- 执行前请根据实际 pid（父菜单ID）调整

-- 1. 添加菜单规则（假设 CRM 客户管理菜单ID为 338，请根据实际情况调整 pid）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `type`, `status`, `authopen`, `icon`, `condition`, `pid`, `sort`, `addtime`, `menustatus`, `target`) VALUES
('crm.reminder/index', '提醒管理', 1, 1, 1, 'fa fa-bell', '', 338, 50, 0, 1, '_self');

-- 2. 获取刚插入的提醒管理菜单ID（用于子权限）
-- 假设提醒管理菜单ID为 @reminder_menu_id

-- 3. 添加子权限（不显示在菜单上）
INSERT INTO `ymwl_auth_rule` (`href`, `title`, `type`, `status`, `authopen`, `icon`, `condition`, `pid`, `sort`, `addtime`, `menustatus`, `target`) VALUES
('crm.reminder/detail', '提醒详情', 2, 1, 1, '', '', LAST_INSERT_ID(), 0, 0, 0, '_self'),
('crm.reminder/markRead', '标记已读', 2, 1, 1, '', '', LAST_INSERT_ID(), 0, 0, 0, '_self'),
('crm.reminder/markAllRead', '全部已读', 2, 1, 1, '', '', LAST_INSERT_ID(), 0, 0, 0, '_self'),
('crm.reminder/delete', '删除提醒', 2, 1, 1, '', '', LAST_INSERT_ID(), 0, 0, 0, '_self'),
('crm.reminder/getUnreadCount', '获取未读数', 2, 1, 1, '', '', LAST_INSERT_ID(), 0, 0, 0, '_self');
