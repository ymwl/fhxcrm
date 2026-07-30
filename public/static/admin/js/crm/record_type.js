define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.record_type/index',
        add_url: 'crm.record_type/add',
        edit_url: 'crm.record_type/edit',
        delete_url: 'crm.record_type/delete',
        export_url: 'crm.record_type/export',
        modify_url: 'crm.record_type/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: fy('Type name')},
                    {field: 'sort', title: fy('Sort'), edit: 'text'},
                    {field: 'status', search: 'select', selectList: [fy('Close'),fy('Open')], title: fy('Status'), templet: ea.table.switch},
                    {width: 120, title: fy('Operate'), templet: ea.table.tool},
                ]],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                }
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
