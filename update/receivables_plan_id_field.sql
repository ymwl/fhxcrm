-- 回款表关联回款计划字段
-- 为 crm_contract_receivables 表添加 receivables_plan_id 用于关联回款计划

ALTER TABLE `ymwl_crm_contract_receivables`
    ADD COLUMN `receivables_plan_id` INT(11) NULL DEFAULT '0' COMMENT '关联回款计划ID' AFTER `contract_id`;
