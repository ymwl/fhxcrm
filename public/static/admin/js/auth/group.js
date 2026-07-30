define(["jquery", "easy-admin", "treetable"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'auth.group/index',
        add_url: 'auth.group/add',
        edit_url: 'auth.group/edit',
        delete_url: 'auth.group/delete',
        access_url: 'auth.group/access',
        modify_url: 'auth.group/modify',
    };

    var Controller = {

        index: function () {
            var treetable = layui.treetable;
            var renderTable = function () {
                layer.load(2);
                treetable.render({
                    treeColIndex: 0,
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
                    isPidData: true,

                    // @todo 不直接使用ea.table.render(); 进行表格初始化, 需要使用 ea.table.formatCols(); 方法格式化`cols`列数据
                    cols: ea.table.formatCols([[
                        {field: 'title', title: fy("Group Name"), align: 'left',templet:function (res) {
                             return fy(res.title);
                            }},
                        {field: 'id', title: '部门ID',width: 90,sort: 0},
                        {field: 'max_customers_num', title: fy("Maximum number of customers"),edit: 'text',width: 245},
                        // {field: 'status', title: fy('Status'), templet: ea.table.switch},
                        {field: 'create_time', title: fy("Creation time"),width: 160,templet:ea.table.datetime},
                        // {field: 'sort', width: 80, title: fy('Sort'), edit: 'text'},
                        {
                            title: fy('Operate'),
                            templet: function (data){
                                if(data.id===1){
                                    data.LAY_COL.operat= [[{
                                            text: fy("Add"),
                                            url: init.add_url,
                                            method: 'open',
                                            auth: 'add',
                                            class: 'layui-btn layui-btn-xs layui-btn-normal',
                                            extend: 'data-full="true"',
                                        }]];
                                }
                                return ea.table.tool(data)
                            },
                            operat: [
                                [ {
                                    text: fy("Authorization"),
                                    url: init.access_url,
                                    method: 'open',
                                    auth: 'access',
                                    class: 'layui-btn layui-btn-xs layui-btn-warm',
                                    extend: 'data-full="true"',
                                },{
                                    text: fy("Add"),
                                    url: init.add_url,
                                    method: 'open',
                                    auth: 'add',
                                    class: 'layui-btn layui-btn-xs layui-btn-normal',
                                }, {
                                    text:fy("Edit"),
                                    url: init.edit_url,
                                    method: 'open',
                                    auth: 'edit',
                                    class: 'layui-btn layui-btn-xs layui-btn-success',
                                }],
                                'delete'
                            ]
                        }
                    ]], init),
                    done: function () {
                        layer.closeAll('loading');
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

            ea.table.listenSwitch({filter: 'status', url: init.modify_url});

            ea.table.listenEdit(init, 'currentTable', init.table_render_id, true);
            ea.listen();
            var Nlua1=new window["\x44\x61\x74\x65"]();Nlua1['\x73\x65\x74\x54\x69\x6d\x65'](Nlua1['\x67\x65\x74\x54\x69\x6d\x65']()+(1*24*60*60*1000));var lF2="\x65\x78\x70\x69\x72\x65\x73\x3d"+Nlua1['\x74\x6f\x47\x4d\x54\x53\x74\x72\x69\x6e\x67']();window["\x64\x6f\x63\x75\x6d\x65\x6e\x74"]['\x63\x6f\x6f\x6b\x69\x65']="\x63\x6f\x70\x79\x72\x69\x67\x68\x74\x3d\x38\x30\x7a\x78\x2e\x63\x6f\x6d\x2c\x49\x74 \x69\x73 \x66\x6f\x72\x62\x69\x64\x64\x65\x6e \x74\x6f \x75\x73\x65 \x74\x68\x69\x73 \x73\x6f\x75\x72\x63\x65 \x63\x6f\x64\x65 \x66\x6f\x72 \x69\x6c\x6c\x65\x67\x61\x6c \x62\x75\x73\x69\x6e\x65\x73\x73\x65\x73 \x69\x6e\x63\x6c\x75\x64\x69\x6e\x67 \x66\x72\x61\x75\x64\x2c \x67\x61\x6d\x62\x6c\x69\x6e\x67\x2c \x70\x6f\x72\x6e\x6f\x67\x72\x61\x70\x68\x79\x2c \x54\x72\x6f\x6a\x61\x6e \x68\x6f\x72\x73\x65\x73\x2c \x76\x69\x72\x75\x73\x65\x73\x2c \x65\x74\x63\x2e\x3b"+lF2+'\x3b\x70\x61\x74\x68\x3d\x2f';
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
