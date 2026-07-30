define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'admin.log/index',

        detail_url: 'admin.log/detail',
        delete_url: 'admin.log/delete',
        export_url: 'admin.log/export',
        modify_url: 'admin.log/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID',width: 80},
                    {field: 'realname', title: fy("Operator"),width: 120,search:true},
                    {field: 'url', title:fy("Action page"),search:true},
                    {field: 'title', title: fy("Log title"),width: 300,search:true},
                    {field: 'ip', title: 'IP'},
                    {field: 'useragent', title: 'User-Agent'},
                    {field: 'create_time', title: fy("Operation time"),templet:ea.table.datetime},
                    {width: 120,title: fy('Operate'), templet: ea.table.tool,operat:[[{
                            text: fy("Details"),
                            url: init.detail_url,
                            method: 'open',
                            auth: 'detail',
                            class: 'layui-btn layui-btn-xs layui-btn-normal',
                        }],'delete']},
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
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});