define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'email.log/index',
        add_url: 'email.log/add',
        edit_url: 'email.log/edit',
        delete_url: 'email.log/delete',
        export_url: 'email.log/export',
        modify_url: 'email.log/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh','delete'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id',width:60},
                    {field: 'type', title: '类型',width:150,search: 'select', selectList: CONFIG.typeList},
                    {field: 'email', title: '收件箱',width:150,search: true},
                    {field: 'title', title: '发送标题',search: true},
                   /* {field: 'content', title: '发送内容',search: true},*/
                    {field: 'create_username', title: '操作者',width:100,search: true},
                    {field: 'status', title: '状态','width': 80,templet:function (res) {
                           if(res.status=='success'){
                                return '<span class="green">'+CONFIG.statusList[res.status]+'</span>';
                            }else{
                               return '<span class="red">'+CONFIG.statusList[res.status]+'</span>';
                            }

                        }
                        ,search: 'select', selectList: CONFIG.statusList ,width:100 },
                    {field: 'notes', title: '备注',width:120},
                    {field: 'create_time', title: '发送时间',width:150},
                    {width: 150, title: '操作', templet: ea.table.tool,operat: [ [{
                            class: 'layui-btn layui-btn-xs',
                            method: 'open',
                            text: fy('详情'),
                            auth: 'edit',
                            url: init.edit_url,
                            extend: '', icon: ''
                        }],'delete']},
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