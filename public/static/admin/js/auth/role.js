define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'auth.role/index',
        add_url: 'auth.role/add',
        edit_url: 'auth.role/edit',
        delete_url: 'auth.role/delete',
        export_url: 'auth.role/export',
        modify_url: 'auth.role/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: '角色名称'},
                    {field: 'type', search: 'select', selectList: CONFIG.getTypeList, title: '查看权限'},
                    {field: 'status', search: 'select', selectList: ["禁用","启用"], title: '状态', templet: ea.table.switch},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'create_time', title: '创建时间',templet:ea.table.datetime},
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