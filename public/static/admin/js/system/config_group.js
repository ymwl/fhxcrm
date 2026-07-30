define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.config_group/index',
        add_url: 'system.config_group/add',
        edit_url: 'system.config_group/edit',
        delete_url: 'system.config_group/delete',
        export_url: 'system.config_group/export',
        modify_url: 'system.config_group/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: fy("Group Name")},
                    {field: 'sort', title: fy("Sort"), edit: 'text'},
                    {field: 'create_time', title: fy("Creation time"),templet:ea.table.datetime},
                    {field: 'status', title: fy("Status"), templet: ea.table.switch},
                    {field: 'identification', title: fy("Identification")},
                    {width: 250, title: fy("Operate"), templet: ea.table.tool},
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