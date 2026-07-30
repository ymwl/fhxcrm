define(["jquery", "easy-admin","treetable"], function ($, ea) {
    treetable = layui.treetable;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'product.type/index',
        add_url: 'product.type/add',
        edit_url: 'product.type/edit',
        delete_url: 'product.type/delete',
        export_url: 'product.type/export',
        modify_url: 'product.type/modify',
    };

    var Controller = {

        index: function () {
            var renderTable = function () {
                layer.load(2);
                treetable.render({
                    iconIndex:2,
                    treeColIndex: 2,
                    treeSpid: 0,
                    homdPid: 99999999,
                    treeIdName: 'id',
                    treePidName: 'pid',
                    treeLinkage: true,
                    url: ea.url(init.index_url),
                    elem: init.table_elem,
                    id: init.table_render_id,
                    toolbar: '#toolbar',
                    page: false,
                    skin: 'line',
                    isPidData: true ,
                    cols: ea.table.formatCols([
                        function(){
                            var arr = [
                                {type: 'checkbox'},
                                {field: 'id', title: 'ID',sort:false},
                                {field: 'title', title: fy("Category Name"), align: 'left'},
                                {field: 'create_time', title: fy('Creation time'),width: 160,templet:ea.table.datetime},
                                {field: 'sort', title: fy('Sort'), edit: 'text',sort:false},
                                {field: 'update_time', title:fy("Update time"),width: 160,templet:ea.table.datetime},
                                {
                                    width: 220,
                                    title: fy('Operate'),
                                    templet: ea.table.tool,
                                    operat: [
                                        [{
                                            text: fy("Add"),
                                            url: init.add_url,
                                            method: 'open',
                                            auth: 'add',
                                            class: 'layui-btn layui-btn-xs layui-btn-normal',
                                        }, {
                                            text: fy("Edit"),
                                            url: init.edit_url,
                                            method: 'open',
                                            auth: 'edit',
                                            class: 'layui-btn layui-btn-xs layui-btn-success',
                                        }],
                                        'delete'
                                    ]
                                }
                            ];
                            return arr;
                        }()

                    ], init),
                    done: function (res, curr, count) {

                        layer.closeAll('loading');
                        if(count===undefined && res.msg && res.url){
                            ea.msg.tips(res.msg,1,function (){
                                window.top.location.href=res.url;
                            })
                        }
                    }
                });
            };

            renderTable();
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
            ea.table.listenEdit(init, 'currentTable', init.table_render_id, true);
            ea.listen();
        },
        add: function () {
            ea.listen(function (data) {
                return data;
            }, function (res) {
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        },
        edit: function () {
            ea.listen(function (data) {
                return data;
            }, function (res) {
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        },
    };
    return Controller;
});
