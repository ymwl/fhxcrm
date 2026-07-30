define(["jquery", "easy-admin", 'xm-select',"treetable", "iconPickerFa", "autocomplete"], function ($, ea,xmSelect) {

    var table = layui.table,
        treetable = layui.treetable,
        iconPickerFa = layui.iconPickerFa,
        autocomplete = layui.autocomplete
    ;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.models/index',
        add_url: 'system.models/add',
        delete_url: 'system.models/delete',
        edit_url: 'system.models/edit',
        modify_url: 'system.models/modify',
        clieartable: 'system.models/clieartable',
        fieldindex: 'system.fields/index',
        updateTable: 'system.models/updateTable',
    };

    var Controller = {
        index: function () {

            ea.table.render({
                init: init,
                limits:[15,20,30,40,50,60,70,80,90,100],
                toolbar:['refresh',[
                    {
                        text: '表更新',
                        title: '选择表更新',
                        url: 'system.models/update_select_table',
                        method: 'open',
                        auth: 'isdev',
                        class: 'layui-btn layui-btn-success layui-btn-sm',
                        icon: 'fa fa-hourglass',
                        extend: 'data-table="' + init.table_render_id + '"',
                    }
                ],'add','delete'
                ],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID',search:false},
                    {field: 'sort', width: 80, title: fy('Sort'), edit: 'text',search:false},
                    {field: 'name', minWidth: 80, title: '表名',search:true},

                    {field: 'table', minWidth: 80, title: '表名称'},
                    {field: 'engine', minWidth: 80, title: '引擎'},
                    {field: 'tabletype', minWidth: 80, title: '表类型',selectList:{"ordinary":"普通表格","tree":"树状表格"},search: 'select',},
                    {field: 'is_page', title: '开启分页', width: 85, search: false, selectList: {0: fy('Close'), 1: fy('Open')}, templet: ea.table.switch},
                    {field: 'status', title: fy('Status'), width: 85, search: false, selectList: {0: fy('Close'), 1: fy('Open')}, templet: ea.table.switch},
                    {field: 'create_time', minWidth: 80, title: fy('Creation time'),templet:ea.table.datetime},
                    {
                        width: 300,
                        title: fy('Operate'),
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            [
                                {
                                text: '清除表数据',
                                url: init.clieartable,
                                method: 'ajax',
                                auth: 'clears',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            },
                                {
                                    text: '字段',
                                    url: init.fieldindex,
                                    method: 'open',
                                    auth: 'field',field:'table',
                                    extend:'data-full="true"',
                                    class: 'layui-btn layui-btn-normal layui-btn-xs',
                                },
                                {
                                    text: '更新字段',
                                    url: init.updateTable,
                                    method: 'ajax',
                                    auth: 'field',
                                    extend:'data-full="true"',
                                    class: 'layui-btn layui-btn-normal layui-btn-xs',
                                },
                            ],
                            'delete'
                        ],
                        fixed:"right"
                    }
                ]],
            });

            // renderTable();

            $('body').on('click', '[data-treetable-refresh]', function () {
                renderTable();
            });

            $('body').on('click', '[data-treetable-delete]', function () {
                var tableId = $(this).attr('data-treetable-delete'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                url = url != undefined ? ea.url(url) : window.location.href;
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    ea.msg.error(fy('Please check the data to be deleted'));
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);
                });
                ea.msg.confirm(fy('Confirm the deletion')+'?', function () {
                    ea.request.post({
                        url: url,
                        data: {
                            id: ids
                        },
                    }, function (res) {
                        ea.msg.success(res.msg, function () {
                            renderTable();
                        });
                    });
                });
                return false;
            });

            ea.table.listenSwitch({filter: 'status', url: init.modify_url});

            ea.table.listenEdit(init, 'currentTable', init.table_render_id, true);

            ea.listen();
        },
        add: function () {
            iconPickerFa.render({
                elem: '#icon',
                url: PATH_CONFIG.iconLess,
                limit: 12,
                click: function (data) {
                    $('#icon').val('fa ' + data.icon);
                },
                success: function (d) {
                    console.log(d);
                }
            });
            ea.listen(function (data) {
                return data;
            }, function (res) {
                console.log(res);
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        },
        edit: function () {
            iconPickerFa.render({
                elem: '#icon',
                url: PATH_CONFIG.iconLess,
                limit: 12,
                click: function (data) {
                    $('#icon').val('fa ' + data.icon);
                },
                success: function (d) {
                    console.log(d);
                }
            });
            autocomplete.render({
                elem: $('#href')[0],
                url: ea.url('system.menu/getMenuTips'),
                template_val: '{{d.node}}',
                template_txt: '{{d.node}} <span class=\'layui-badge layui-bg-gray\'>{{d.title}}</span>',
                onselect: function (resp) {
                }
            });

            ea.listen(function (data) {
                return data;
            }, function (res) {
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        }
        ,update_select_table:function (){
        ea.request.ajax('post',{url:'get_tables'},function (res){
            var demo1 = xmSelect.render({
                el: '#multiple-select',
                language: 'zn',
                data: res.data,name:'tables'
            })
        })

            ea.listen();
        }
    };
    return Controller;
});