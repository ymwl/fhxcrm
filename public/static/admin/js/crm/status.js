define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.status/index',
        add_url: 'crm.status/add',
        edit_url: 'crm.status/edit',
        delete_url: 'crm.status/delete',
        export_url: 'crm.status/export',
        modify_url: 'crm.status/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: fy('Status Name')},
                    {field: 'sort', title: fy('Sort'), edit: 'text'},
                    {field: 'status', search: 'select', selectList: [fy('Close'),fy('Open')], title: fy('Status'), templet: ea.table.switch},
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
