-- 回款计划表 - 自定义字段配置
-- 插入system_field表，用于动态表单和表格列渲染

DELETE FROM `ymwl_system_field` WHERE `table` = 'crm_contract_receivables_plan';

-- 业务字段
INSERT INTO `ymwl_system_field` (`name`, `xsname`, `field`, `type`, `rule`, `msg`, `lang`, `is_null`, `create_time`, `update_time`, `formtype`, `table`, `show`, `open_sort`, `edit`, `search`, `total`, `export`, `sort`, `option`, `join_table`, `default`, `foreign_key`, `describe`, `href`, `relationship_primary_key`, `grid`, `is_key`, `width`, `jscol`, `addinput`, `editinput`) VALUES
('提前提醒天数', '提前天数', 'remind_days', 'tinyint', 'number|between:1,30', '提前天数必须在1-30之间', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'input', 'crm_contract_receivables_plan', 1, 0, 1, 0, 0, 1, 9, '', '', '1', 'name', '', '', 'id', 'layui-col-md6', 0, 100, '{"field":"remind_days","title":"{:fy(\'提前天数\')}","search":false,"width":"100","selectList":null,"templet":"ea.table.text"}', '<div class="layui-col-md6"><div class="layui-form-item"><label class="layui-form-label">{:fy(\'提前天数\')}</label><div class="layui-input-block"><input type="number" name="remind_days" class="layui-input" lay-verify="number|between:1,30" placeholder="请输入提前提醒天数，默认1天" value="{$row.remind_days|default=\'1\'}"></div></div></div>', '<div class="layui-col-md6"><div class="layui-form-item"><label class="layui-form-label">{:fy(\'提前天数\')}</label><div class="layui-input-block"><input type="number" name="remind_days" class="layui-input" lay-verify="number|between:1,30" placeholder="请输入提前提醒天数，默认1天" value="{$row.remind_days|default=\'1\'}"></div></div></div>'),
