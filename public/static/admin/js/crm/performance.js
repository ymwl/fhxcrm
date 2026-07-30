define(["jquery", "easy-admin"], function ($, ea) {
    table = layui.table;form = layui.form;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        add_url: 'crm.order/add',
        index_url: 'crm.order/index',
        delete_url: 'crm.order/del',
        personindex_url: 'crm.order/personindex',
        export_url: 'crm.order/export',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {
        analytics:function (){
            init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                add_url: 'crm.order/add',
                index_url: 'crm.performance/analytics',
                export_url: 'device/export',
            };
            var myDate = new Date();
            var year=myDate.getFullYear(); //获取完整的年份(4位,1970-????)
            var month=myDate.getMonth()+1; //获取当前月份(0-11,0代表1月)
            var cur_date=year+'年'+month+'日'
            ea.table.render({
                toolbar: ['refresh'],
                init: init, limit: Number.MAX_VALUE,page: false,
            defaultToolbar:['filter',{
                    title: fy('Custom printing'),
                    layEvent: 'DIY_PRINT',
                    icon: 'layui-icon-print',
                }],
                cols: [[
                    {search:'<div class="layui-form-item layui-inline"><label class="layui-form-label">'+fy('month')+'</label><div class="layui-input-inline"><input id="c-month" name="month" data-date data-date-type="month" class="layui-input" data-search-op="=" value="'+year+'-'+month+'" /></div>',hide:true},
                    {field: 'username', title: fy('salesman'),search:true},

                    {field: 'order_target', title: '月订单目标', sort: true},
                    {field: 'order_rate', title: '订单完成率', sort: true, templet: function (res){
                            if(res.order_rate == -1) return '目标未设置';
                            return res.order_rate ? res.order_rate.toFixed(2) + '%' : '0%';
                        }},
                    {field: 'order_money', title: '订单金额', sort: true},
                    {field: 'order_count', title: '订单量', sort: true},

                    {field: 'contract_target', title: '月合同目标', sort: true},
                    {field: 'contract_rate', title: '合同完成率', sort: true, templet: function (res){
                            if(res.contract_rate == -1) return '目标未设置';
                            return res.contract_rate ? res.contract_rate.toFixed(2) + '%' : '0%';
                        }},
                    {field: 'contract_money', title: '合同金额', sort: true},
                    {field: 'contract_count', title: '合同量', sort: true},

                    {field: 'receivables_target', title: '月回款目标', sort: true},
                    {field: 'receivables_rate', title: '回款完成率', sort: true, templet: function (res){
                            if(res.receivables_rate == -1) return '目标未设置';
                            return res.receivables_rate ? res.receivables_rate.toFixed(2) + '%' : '0%';
                        }},
                    {field: 'receivables_money', title: '回款金额', sort: true},
                    {field: 'receivables_count', title: '回款量', sort: true}

                ]],

                done: function(res, curr, count){
                    if(res.cur_date){
                        cur_date=res.cur_date;
                    }
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                }
            });

            // 监听表格排序事件，将排序参数传递给后端
            table.on('sort(currentTableRenderId)', function(obj){
                var filterData = {};
                var monthVal = $('#c-month').val();
                if(monthVal) filterData.month = monthVal;
                ea.table.reload('currentTableRenderId', {
                    where: {
                        filter: JSON.stringify(filterData),
                        field: obj.field,
                        order: obj.type
                    }
                });
            });

            ea.listen();
            table.on('toolbar(currentTableRenderId_LayFilter)', function (obj) {
                // 搜索表单的显示
                switch (obj.event) {
                    case 'DIY_PRINT':
                        //自定义打印处理
                        var f = ["<style>", "body{font-size: 12px; color: #666;}", "table{width: 100%; border-collapse: collapse; border-spacing: 0;}", "th,td{line-height: 20px; padding: 9px 15px; border: 1px solid #ccc; text-align: left; font-size: 12px; color: #666;text-align: center;}", "a{color: #666; text-decoration:none;}", "*.layui-hide{display: none}", "</style>"].join("");
                        var v=$($(".layui-table-header").html());
                        v.append($(".layui-table-main table").html());
                        v.find("th.layui-table-patch").remove(), v.find(".layui-table-col-special").remove();
                        var h = window.open("Print_window", "_blank");
                        h.document.write(f +'<h1 style="text-align: center;">'+cur_date+'员工业绩表<h1>'+ $(v).prop("outerHTML"));
                        h.document.close();
                        h.print();
                        h.close();
                }
            });
            $('.table-search-fieldset').removeClass('layui-hide');
        },

        /**
         * 业绩设置列表
         */
        achievement: function () {
            init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'crm.performance/achievement',
            };
            var myDate = new Date();
            var curYear = myDate.getFullYear();

            ea.table.render({
                toolbar: ['refresh', [{
                    text: '批量设置',
                    url: 'crm.performance/batchAchievement',
                    icon: 'fa fa-plus',
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    auth: 'batch',
                    method: 'open',
                    extend: '',
                }]],
                init: init,
                limit: Number.MAX_VALUE,
                page: false,
                cols: [[
                    {field: 'search_year', title: '', search: '<div class="layui-form-item layui-inline"><label class="layui-form-label">年份</label><div class="layui-input-inline"><input id="c-year" name="year" data-date data-date-type="year" class="layui-input" data-search-op="=" value="'+curYear+'" /></div></div>', hide: true},
                    {field: 'admin.username', title: '员工', width: 160, search: '<div class="layui-form-item layui-inline"><label class="layui-form-label">员工</label><div class="layui-input-inline"><input id="c-admin_name" name="admin_name" class="layui-input" data-search-op="like" placeholder="请输入员工姓名" /></div></div>'},
                    {field: 'year', title: '年份', width: 80},
                    {field: 'config_text', title: '业绩方式', width: 200},
                    {field: 'yeartarget', title: '全年目标', width: 120},
                    {field: 'january', title: '1月', width: 90},
                    {field: 'february', title: '2月', width: 90},
                    {field: 'march', title: '3月', width: 90},
                    {field: 'april', title: '4月', width: 90},
                    {field: 'may', title: '5月', width: 90},
                    {field: 'june', title: '6月', width: 90},
                    {field: 'july', title: '7月', width: 90},
                    {field: 'august', title: '8月', width: 90},
                    {field: 'september', title: '9月', width: 90},
                    {field: 'october', title: '10月', width: 90},
                    {field: 'november', title: '11月', width: 90},
                    {field: 'december', title: '12月', width: 90},
                    {
                        width: 150, title: '操作', fixed: 'right', templet: ea.table.tool, operat: [[{
                            class: 'layui-btn layui-btn-success layui-btn-xs',
                            method: 'open',
                            text: fy('Edit'),
                            auth: 'edit',
                            url: 'crm.performance/achievementEdit',
                            field: '',
                            extend: '',
                        }],[{
                            class: 'layui-btn layui-btn-danger layui-btn-xs',
                            method: 'request',
                            text: fy('Delete'),
                            auth: 'delete',
                            url: 'crm.performance/achievementDelete',
                            field: '',
                            extend: ''
                        }]]
                    }
                ]],
                done: function (res, curr, count) {
                    if (count === undefined && res.msg && res.url) {
                        ea.msg.tips(res.msg, 1, function () {
                            window.top.location.href = res.url;
                        });
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet('currentTableRenderId');
                    }
                }
            });
            ea.listen();
            $('.table-search-fieldset').removeClass('layui-hide');
        },

        /**
         * 批量业绩设置表单
         */
        batchAchievement: function () {
            // 每种业绩方式的平均分配按钮
            $(document).on('click', '.btn-average', function () {
                var config = $(this).data('config');
                var yeartarget = parseFloat($('input[name="yeartarget_' + config + '"]').val());
                if (!yeartarget || yeartarget <= 0) {
                    ea.msg.error('请先输入全年目标金额');
                    return;
                }
                var avg = (yeartarget / 12).toFixed(2);
                var months = ['january','february','march','april','may','june','july','august','september','october','november','december'];
                for (var i = 0; i < months.length; i++) {
                    $('input[name="' + months[i] + '_' + config + '"]').val(avg);
                }
            });

            ea.listen();
        },

        /**
         * 编辑业绩记录
         */
        achievementEdit: function () {
            // 平均分配按钮
            $(document).on('click', '#btn-average', function () {
                var yeartarget = parseFloat($('input[name="yeartarget"]').val());
                if (!yeartarget || yeartarget <= 0) {
                    ea.msg.error('请先输入全年目标金额');
                    return;
                }
                var avg = (yeartarget / 12).toFixed(2);
                var months = ['january','february','march','april','may','june','july','august','september','october','november','december'];
                for (var i = 0; i < months.length; i++) {
                    $('input[name="' + months[i] + '"]').val(avg);
                }
            });

            ea.listen();
        },

        /**
         * 删除业绩记录
         */
        achievementDelete: function () {
            ea.listen();
        }
    };
    return Controller;
});

