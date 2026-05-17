-- 提醒记录表
CREATE TABLE IF NOT EXISTS `ymwl_crm_reminder` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '提醒类型：1跟进提醒 2合同到期 3回款提醒 4生日提醒',
  `related_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联ID（客户ID/合同ID等）',
  `related_type` varchar(20) NOT NULL DEFAULT '' COMMENT '关联类型：customer/contract等',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '提醒标题',
  `content` text COMMENT '提醒内容',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收管理员ID',
  `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT '接收管理员用户名',
  `remind_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提醒时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0待提醒 1已发送 2已读',
  `read_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '阅读时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_status` (`status`),
  KEY `idx_related` (`related_id`, `related_type`),
  KEY `idx_remind_time` (`remind_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM提醒记录表';
