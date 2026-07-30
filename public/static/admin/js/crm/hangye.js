define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.hangye/index',
        add_url: 'crm.hangye/add',
        edit_url: 'crm.hangye/edit',
        delete_url: 'crm.hangye/delete',
        export_url: 'crm.hangye/export',
        modify_url: 'crm.hangye/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'name', title: fy('Industry category')},
                    {field: 'status', search: 'select', selectList: [fy('Close'),fy('Open')], title: fy('Status'), templet: ea.table.switch},
                    {field: 'sort', title: fy('Sort'), edit: 'text'},
                    {field: 'update_time', title: fy('Update time'),width: 160,templet:ea.table.datetime},
                    {width: 120, title: fy('Operate'), templet: ea.table.tool},
                ]],done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
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
