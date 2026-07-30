define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.rank/index',
        add_url: 'crm.rank/add',
        edit_url: 'crm.rank/edit',
        delete_url: 'crm.rank/delete',
        export_url: 'crm.rank/export',
        modify_url: 'crm.rank/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: fy('Rank name')},
                    {field: 'status', search: 'select', selectList: [fy('Close'),fy('Open')], title: fy('Status'), templet: ea.table.switch},
                    {field: 'sort', title: fy('Sort'), edit: 'text'},
                    {field: 'create_time', title: fy('Creation time'),width: 160,templet:ea.table.datetime},
                    {width: 120, title: fy('Operate'), templet: ea.table.tool},
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
    };
    return Controller;
});
