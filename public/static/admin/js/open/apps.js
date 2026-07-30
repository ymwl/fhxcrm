define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'open.apps/index',
        add_url: 'open.apps/add',
        edit_url: 'open.apps/edit',
        delete_url: 'open.apps/delete',
        export_url: 'open.apps/export',
        modify_url: 'open.apps/modify',
    };

    var Controller = {

        index: function () {
            var url;
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: '名称'},
                    {field: '', title: '通知API',templet:function (res) {
                           url=window.location.protocol + '//' + window.location.host+'/open/index/id/'+res.id+'/token/'+res.token;
                           return '<a class="layuimini-table-url label bg-green" href="' + url + '" target="_blank">' + url + '</a>';
                        }},
                    // {field: 'token', title: '通讯秘钥'},
                    // {field: 'table', title: '对应数据表'},
                    {field: 'status', search: 'select', selectList: ["禁用","启用"], title: '状态', templet: ea.table.switch},
                    {field: 'create_time', title: '创建时间',templet:ea.table.datetime},
                    {width: 250, title: '操作', templet: ea.table.tool,operat: [ [{
                            class: 'layui-btn layui-btn-success layui-btn-xs',
                            method: 'open',
                            text: '对接说明',
                            auth: 'doc',
                            url: 'open.apps/doc',
                            field:'',
                            extend: ''
                        }],'edit', 'delete']},
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
            this.common();
            ea.listen();
        },
        edit: function () {
            this.common();
            ea.listen();
        },common:function (){
            $(document).on('click','.random-char',function (){
                $($(this).attr('data-target')).val(ea.randomChar($(this).attr('data-length')));

            });
        }
    };
    return Controller;
});
