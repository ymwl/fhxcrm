-- 回款计划表
-- 用于记录合同的计划回款安排，区别于crm_contract_receivables（实际回款记录）
CREATE TABLE `ymwl_crm_contract_receivables_plan` (
                                                      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                                                      `customer_id` INT(11) NULL DEFAULT '0' COMMENT '客户ID{popup_selection}[show:1,edit:0,search:1,export:1,sort:1,join_table:crm_customer,foreign_key:name]',
                                                      `contract_id` INT(11) NULL DEFAULT '0' COMMENT '合同ID{aselect}[show:1,edit:0,search:1,export:1,sort:2,join_table:crm_contract,foreign_key:name]',
                                                      `plan_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '计划编号{input}[show:1,edit:1,search:1,sort:3]' COLLATE 'utf8mb4_unicode_ci',
                                                      `plan_money` DECIMAL(18,2) NOT NULL DEFAULT '0.00' COMMENT '计划回款金额{input}[show:1,edit:1,search:0,sort:4,total:1]',
                                                      `plan_date` BIGINT(20) NULL DEFAULT '0' COMMENT '计划回款日期{date}[show:1,edit:0,search:1,export:1,sort:5,foreign_key:name]',
                                                      `actual_money` DECIMAL(18,2) NULL DEFAULT '0.00' COMMENT '实际回款金额{input}[show:1,edit:1,search:0,sort:6,total:1]',
                                                      `actual_date` BIGINT(20) NULL DEFAULT '0' COMMENT '实际回款日期{date}[show:1,edit:0,search:1,export:1,sort:7,foreign_key:name]',
                                                      `status` TINYINT(4) NULL DEFAULT '0' COMMENT '回款状态{select}(0:计划中,1:进行中,2:已回款,3:逾期)[show:1,edit:0,search:1,export:1,sort:8,foreign_key:name]',
                                                      `remark` TEXT NULL DEFAULT NULL COMMENT '备注{textarea}[show:0,edit:1,search:0,sort:100]' COLLATE 'utf8mb4_unicode_ci',
                                                      `remind_days` TINYINT(4) NULL DEFAULT '1' COMMENT '提前提醒天数{input}[show:1,edit:0,export:1,sort:9,default:1,foreign_key:name,describe:提前多少天提醒]',
                                                      `owner_admin_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '负责人ID',
                                                      `create_username` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '创建人' COLLATE 'utf8mb4_unicode_ci',
                                                      `create_time` BIGINT(20) NULL DEFAULT '0' COMMENT '创建时间{datetime}[show:1,search:1,export:1,sort:104,foreign_key:name]',
                                                      `update_time` BIGINT(16) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
                                                      PRIMARY KEY (`id`) USING BTREE,
                                                      INDEX `customer_id` (`customer_id`) USING BTREE,
                                                      INDEX `contract_id` (`contract_id`) USING BTREE,
                                                      INDEX `owner_admin_id` (`owner_admin_id`) USING BTREE,
                                                      INDEX `plan_date` (`plan_date`) USING BTREE,
                                                      INDEX `status` (`status`) USING BTREE
)
    COMMENT='回款计划表'
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
ROW_FORMAT=DYNAMIC
;


