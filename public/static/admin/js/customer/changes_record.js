define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'customer.changes_record/index',
        add_url: 'customer.changes_record/add',
        edit_url: 'customer.changes_record/edit',
        delete_url: 'customer.changes_record/delete',
        export_url: 'customer.changes_record/export',
        modify_url: 'customer.changes_record/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'customer_id', title: '客户对应id'},
                    {field: 'customer_name', title: '客户名称',search:true},
                    {field: 'create_username', title: '操作人',search:true},
                    {field: 'on', title: '操作对象'},
                    {field: 'on_field', title: '操作字段',search:true},
                    {field: 'before_value', title: '操作前值'},
                    {field: 'after_value', title: '操作后值'},
                    {field: 'ip', title: '操作IP',search:true},
                    {field: 'create_time', title: '操作时间',width: 160,templet:ea.table.datetime},
                    {width: 250, title: '操作', templet: ea.table.tool},
                ]],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                       if(ea.checkMobile()){
                                               ea.booksTemplet();
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
