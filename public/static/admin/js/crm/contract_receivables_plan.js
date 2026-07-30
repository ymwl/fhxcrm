define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.contract_receivables_plan/index',
        add_url: 'crm.contract_receivables_plan/add',
        edit_url: 'crm.contract_receivables_plan/edit',
        delete_url: 'crm.contract_receivables_plan/delete',
        export_url: 'crm.contract_receivables_plan/export',
        modify_url: 'crm.contract_receivables_plan/modify',
    };

    var Controller = {

        index: function () {
            var scope = (CONFIG.scope && CONFIG.scope != 1) ? CONFIG.scope : 1;
            ea.init.where['scope'] = scope;
            // 激活对应标签页
            if (scope != 1) {
                $('.layui-tab-title li').removeClass('layui-this');
                $('.layui-tab-title li[data-value="' + scope + '"]').addClass('layui-this');
            }
            var operat={width: 250, title: '操作', fixed: 'right', templet: ea.table.tool}
            cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,operat)
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh','add',
                    [{
                        text: '自定义字段',
                        url: 'system.fields/index?table=crm_contract_receivables_plan',
                        method: 'open',
                        auth: 'fields',
                        class: 'layui-btn layui-btn-warm layui-btn-sm',
                        icon: 'fa fa-cogs ',
                        extend: 'data-full="true"',
                    }]],
                cols:[ cols_fields],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                       if(ea.checkMobile()){
                                               ea.booksTemplet();
                                           }

                    if(res.overdue_money>0){
                        tips=' &nbsp;&nbsp;<span class="layui-font-red">计划回款到期未回款总金额：<strong>'+res.overdue_money+'</strong></span>';
                        $('.layui-table-tool-temp').append(tips);
                    }

                },where: {scope: scope}
            });

            ea.listen();
        },
        add: function () {
            ea.listen(); this.common();
        },
        edit: function () {
            ea.listen(); this.common();
        },common: function () {

            // 获取客户选择框的值
            var customer_id = $('[name="customer_id"]').val();

            // 安全获取 selectPageObject 的方法
            var getSelectPageObj = function() {
                return $('[name="contract_id_text"]').data('selectPageObject');
            };

            // 启用/禁用合同选择框（视觉提示，不阻止事件）
            var toggleContractSelect = function(enable) {
                var $el = $('[name="contract_id_text"]');
                if (enable) {
                    $el.removeClass('layui-disabled').css({'background-color': '', 'cursor': ''});
                } else {
                    $el.addClass('layui-disabled').css({'background-color': '#f2f2f2', 'cursor': 'not-allowed'});
                }
            };

            // 设置 selectPage 请求参数
            var setContractParams = function(cid) {
                var spObj = getSelectPageObj();
                if (spObj && spObj.option) {
                    spObj.option.params = function () {
                        return {custom: {'customer_id': cid}};
                    };
                }
            };

            // 初始化时如果没有客户ID，禁用合同选择
            if (!customer_id || customer_id < 1) {
                setContractParams(0);
                var $contractInput = $('[name="contract_id"]');
                if ($contractInput.length && typeof $contractInput.selectPageClear === 'function') {
                    $contractInput.selectPageClear();
                }
                // 延迟禁用，确保 selectPage 初始化完成
                setTimeout(function() {
                    toggleContractSelect(false);
                }, 100);
            } else {
                setTimeout(function() {
                    toggleContractSelect(true);
                }, 100);
            }

            // 客户选择变更事件
            $(document).on('change', '[name="customer_id"]', function () {
                customer_id = $(this).val();
                console.log('客户ID变更:', customer_id);
                setContractParams(customer_id);
                var $contractInput = $('[name="contract_id"]');
                if ($contractInput.length && typeof $contractInput.selectPageClear === 'function') {
                    $contractInput.selectPageClear();
                }
                // 根据客户选择状态启用/禁用合同选择
                toggleContractSelect(customer_id && customer_id > 0);
            });

            // 关联合同输入框点击事件 - 未选择客户时提示
            $(document).on('click', '[name="contract_id_text"]', function (e) {
                if (!customer_id || customer_id < 1) {
                    ea.msg.error('请先选择关联客户！');
                    return false;
                }
            });

            // 关联合同输入框 mousedown 事件 - 最早触发，可阻止下拉列表显示
            $(document).on('mousedown', '[name="contract_id_text"]', function (e) {
                if (!customer_id || customer_id < 1) {
                    // 阻止默认行为，防止下拉列表打开
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });

            // 关联合同输入框聚焦事件 - 未选择客户时阻止显示列表
            $(document).on('focus', '[name="contract_id_text"]', function (e) {
                // 如果没有选择客户，让输入框失去焦点
                if (!customer_id || customer_id < 1) {
                    var $this = $(this);
                    setTimeout(function() {
                        $this.blur();
                    }, 10);
                    return false;
                }
            });
        },
    };
    return Controller;
});