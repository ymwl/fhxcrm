define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.reminder/index',
        delete_url: 'crm.reminder/delete',
    };

    /**
     * 通知父窗口刷新提醒角标
     * 提醒页运行在 iframe 中，需要通过 window.top 调用父窗口的刷新方法
     */
    function notifyBadgeRefresh() {
        try {
            if (window.top && window.top.refreshReminderBadge) {
                window.top.refreshReminderBadge();
            }
        } catch (e) {
            // 跨域限制时静默失败
        }
    }

    var Controller = {
        index: function () {

            var scope = CONFIG.scope || 'pending';
            ea.init.where['scope'] = scope;
            // 激活对应标签页
            if (scope !== 'pending') {
                $('.layui-tab-title li').removeClass('layui-this');
                $('.layui-tab-title li[data-value="' + scope + '"]').addClass('layui-this');
            }

            var cols = [
                {type: 'checkbox'},
                {field: 'id', title: 'Id', width: 70},
                {
                    field: 'type',
                    title: '提醒类型',
                    width: 100,
                    templet: function (res) {
                        var colors = {
                            1: 'blue',
                            2: 'orange',
                            3: 'green',
                            4: 'purple'
                        };
                        return '<span class="layui-badge layui-bg-' + (colors[res.type] || 'gray') + '">' + res.type_text + '</span>';
                    }
                },
                {field: 'title', title: '提醒标题', width: 200},
                {field: 'content', title: '提醒内容', minWidth: 250},
                {
                    field: 'customer_name',
                    title: '关联客户',
                    width: 120,
                    templet: function (res) {
                        if (res.customer_name) {
                            return res.customer_name;
                        }
                        return '-';
                    }
                },
                {
                    field: 'status',
                    title: '状态',
                    width: 80,
                    templet: function (res) {
                        if (res.status === 0) {
                            return '<span class="layui-badge layui-bg-red">待处理</span>';
                        } else if (res.status === 1) {
                            return '<span class="layui-badge layui-bg-orange">已发送</span>';
                        } else if (res.status === 2) {
                            return '<span class="layui-badge layui-bg-green">已读</span>';
                        }
                        return '-';
                    }
                },
                {
                    field: 'remind_time',
                    title: '提醒时间',
                    width: 160,
                    templet: ea.table.datetime,
                    sort: true,
                    search: 'range'
                },
                {field: 'create_time', title: fy('Creation time'), width: 160, templet: ea.table.datetime, sort: true, search: 'range'},
                {
                    width: 180,
                    title: fy('Operate'),
                    templet: ea.table.tool,
                    fixed: 'right',
                    operat: [
                        [{
                            class: 'layui-btn layui-btn-xs layui-btn-primary',
                            method: 'open',
                            text: '详情',
                            auth: 'detail',
                            url: 'crm.reminder/detail',
                            extend: 'data-full="true"',
                            icon: 'fa fa-eye'
                        }],
                        [{
                            class: 'layui-btn layui-btn-xs layui-btn-success',
                            method: 'request',
                            text: '标记已读',
                            auth: 'markRead',
                            url: 'crm.reminder/markRead',
                            hidden: function (data) {
                                return data.status === 2;
                            }
                        }],
                        [{
                            class: 'layui-btn layui-btn-danger layui-btn-xs',
                            method: 'request',
                            text: fy('Delete'),
                            auth: 'delete',
                            url: 'crm.reminder/delete'
                        }]
                    ]
                }
            ];

            // 表格初始化
            ea.table.render({
                elem: init.table_elem,
                id: init.table_render_id,
                url: init.index_url,
                cols: [cols],
                toolbar: [{
                    auth: 'markAllRead',
                    class: 'layui-btn layui-btn-success',
                    text: '全部已读',
                    method: 'request',
                    url: 'crm.reminder/markAllRead',
                    icon: 'fa fa-check-square-o',
                    title: '确定要标记所有提醒为已读吗？'
                }, 'refresh'],
                where: {
                    scope: scope
                }
            });

            ea.listen();

            // 监听 AJAX 完成事件，当标记已读/全部已读/删除操作成功后刷新角标
            $(document).ajaxComplete(function (event, xhr, settings) {
                if (!settings || !settings.url) return;
                var url = settings.url;
                // 匹配标记已读、全部已读、删除接口
                if (url.indexOf('markRead') !== -1 || url.indexOf('markAllRead') !== -1 || url.indexOf('reminder/delete') !== -1) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res && res.code == 1) {
                            // 操作成功，延迟500ms刷新角标（等待数据库更新完成）
                            setTimeout(notifyBadgeRefresh, 500);
                        }
                    } catch (e) {}
                }
            });
        },

        detail: function () {
            ea.listen();

            // 详情页已自动标记已读，通知父窗口刷新角标
            notifyBadgeRefresh();
        }
    };

    return Controller;
});
