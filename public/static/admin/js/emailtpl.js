define(["jquery", "easy-admin"], function ($, ea) {
    var form = layui.form;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'emailtpl/index',
        add_url: 'emailtpl/add',
        edit_url: 'emailtpl/edit',
        delete_url: 'emailtpl/delete',
        export_url: 'emailtpl/export',
        modify_url: 'emailtpl/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id',width:80},
                    {field: 'name', title: '名称',width:200},
                    {field: 'tpl_title', title: '模板标题'},
                 /*   {field: 'tpl_content', title: '模板内容'},*/
                    {field: 'status', search: 'select', selectList: ["禁用","启用"], title: '状态', templet: ea.table.switch,width:100},
                    {field: 'sort', title: '排序', edit: 'text',width:100},
                    {field: 'create_time', title: '创建时间', width:150},
                    {width: 150, title: '操作', templet: ea.table.tool},
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
            this.common();
        },
        edit: function () {
            console.log('edit')
            ea.listen();
            this.common();
        },common: function () {
            var type = $('input[name=type]:checked').val();
            if (type) {
                if(type!='clue'){
                    $('.customer_fields').show();
                }
                $('.user_fields').show();
                $('.'+type+'_fields').show();
            }
            form.on('radio(type)', function (data) {
                $('.layui-elem-quote p').hide();
                if(data.value!='clue'){
                    $('.customer_fields').show();
                }
                $('.user_fields').show();
                $('.'+data.value+'_fields').show();

            });
        }
    };
    return Controller;
});