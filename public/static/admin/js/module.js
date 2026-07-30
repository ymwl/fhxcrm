define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'module/index',
        field_url: 'module/field',
        add_url: 'module/add',
        edit_url: 'module/edit',
        delete_url: 'module/delete',
        modify_url: 'module/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'title',  title: '模型名称',width: 120},
                    {field: 'name',  title: '表名',width: 200},
                    {field: 'description',  title: '描述',width: 180},
                    {field: 'status', title: fy('Status'), width: 85, selectList: {0: fy('Close'), 1: fy('Open')}, templet: ea.table.switch},
                    {
                        width: 250,
                        title: fy('Operate'),
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '模型字段',
                                url: init.field_url,
                                method: 'open',
                                auth: 'authorize',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'edit',
                            'delete'
                        ]
                    }
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        authorize: function () {
            var tree = layui.tree;

            ea.request.get(
                {
                    url: window.location.href,
                }, function (res) {
                    res.data = res.data || [];
                    tree.render({
                        elem: '#node_ids',
                        data: res.data,
                        showCheckbox: true,
                        id: 'nodeDataId',
                    });
                }
            );

            ea.listen(function (data) {
                var checkedData = tree.getChecked('nodeDataId');
                var ids = [];
                $.each(checkedData, function (i, v) {
                    ids.push(v.id);
                    if (v.children !== undefined && v.children.length > 0) {
                        $.each(v.children, function (ii, vv) {
                            ids.push(vv.id);
                        });
                    }
                });
                data.node = JSON.stringify(ids);
                return data;
            });

        }
    };
    return Controller;
});