define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'call.call_task/index',
        add_url: 'call.call_task/add',
        delete_url: 'call.call_task/delete',
        cancel_url: 'call.call_task/cancel',
    };

    var Controller = {

        index: function () {

            var typeMap = {1: '外拨', 2: '来电'};
            // 任务状态（来电记录固定为已执行）
            var statusMap = {0: '待执行', 1: '已执行', 2: '已取消', 3: '失败', 4: '超时'};
            // 通话结果（细分结局，call_result 字段）
            var callResultMap = {0: '未知', 1: '已接通', 2: '无人接听', 3: '对方忙线', 4: '被拒接', 5: '网络异常/拨号失败'};

            // 录音列：内嵌播放器 + 下载链接（鉴权 URL，有录音的记录行均可播放/下载）
            var recordCol = {
                'title': '录音',
                'align': 'center',
                'width': 280,
                'templet': function (d) {
                    if (!d.play_url) return '<span style="color:#999">无录音</span>';
                    var html = '<audio controls preload="none" style="height:30px;vertical-align:middle" src="' + d.play_url + '"></audio>';
                    if (d.download_url) {
                        html += ' <a href="' + d.download_url + '" class="layui-btn layui-btn-xs layui-btn-primary" download><i class="fa fa-download"></i> 下载</a>';
                    }
                    return html;
                }
            };

            var operat = {
                'width': 100,
                'title': '操作',
                'fixed': 'right',
                'templet': ea.table.tool,
                'operat': [
                    [{
                        text: '取消',
                        method: 'post',
                        auth: 'cancel',
                        url: 'call.call_task/cancel',
                        class: 'layui-btn layui-btn-xs layui-btn-primary',
                        hidden: function (data) {
                            // 仅外呼任务且状态为待执行时可取消
                            return data.record_type != 1 || data.status != 0;
                        }
                    }],'delete'
                ]
            };

            ea.table.render({
                init: init,
                toolbar: ['refresh', [{
                    text: '发起呼叫',
                    method: 'open',
                    auth: 'add',
                    url: 'call.call_task/add',
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    icon: 'fa fa-phone',
                    extend: '',
                }]],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', align: 'center', width: 70},
                    {field: 'phone', title: '号码', align: 'center', width: 126,search:true},
                    {
                        field: 'contact_info', title: '联系人信息', align: 'center',search: true,
                        templet: function (d) {
                            if (d.url) {
                                return '<a data-open="' + ea.url(d.url) + '" style="color:#1e9fff">' + (d.contact_info || '查看') + '</a>';
                            }
                            return d.contact_info || '-';
                        }
                    },
                    {field: 'record_type', title: '类型', align: 'center',search: 'select',selectList:typeMap, width: 80, templet: function (d) { return typeMap[d.record_type] || '-'; }},
                    {field: 'call_result', title: '拨打结果', align: 'center',search: 'select',selectList:callResultMap, width: 130, templet: function (d) { return callResultMap[d.call_result] || '-'; }},
                    {
                        field: 'status', title: '状态', align: 'center', width: 90,search: 'select',selectList:statusMap, templet: function (d) {
                            return statusMap[d.status] || '-';
                        }
                    },
                    {field: 'result', title: '结果', align: 'center', width: 90},
                    {field: 'duration', title: '时长(秒)', align: 'center', width: 100},
                    {field: 'call_time', title: '通话时间', align: 'center', width: 161, templet:ea.table.datetime},
                    recordCol,
                    {field: 'create_time', title: '发起时间', align: 'center', width: 161,templet:ea.table.datetime},
                    operat
                ]]
            });

            ea.listen();
        },

        add: function () {
            ea.listen();
        },

    };
    return Controller;
});
