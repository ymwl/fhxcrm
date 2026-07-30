define(["jquery", "easy-admin"], function ($, ea) {

    var customer_id=CONFIG.customer_id;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.customer_contacts/index/customer_id/'+customer_id,
        add_url: 'crm.customer_contacts/add/customer_id/'+customer_id,
        edit_url: 'crm.customer_contacts/edit/customer_id/'+customer_id,
        delete_url: 'crm.customer_contacts/delete/customer_id/'+customer_id,
        export_url: 'crm.customer_contacts/export/customer_id/'+customer_id,
        modify_url: 'crm.customer_contacts/modify/customer_id/'+customer_id,
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            cols_fields=[];
            operat={width: 180, title: '操作','fixed':'right', templet: ea.table.tool,operat:[ /*[{
                    class: 'layui-btn layui-btn-xs layui-btn-primary',
                    method: 'open',
                    text: '写跟进',
                    auth: 'dialogue',
                    url: 'crm.record/dialogue/contacts_id/{id}',
                    extend: 'data-full="true"', icon: 'fa fa-plus ',
                }],[{
                    class: 'layui-btn layui-btn-xs layui-btn-primary',
                    method: 'open',
                    text: '沟通记录',
                    auth: 'record_index',
                    url: 'crm.record/index/contacts_id/{id}',
                    field:'',
                    extend: 'data-full="true"'
                }],*/'edit',[{
                    class: 'layui-btn layui-btn-success layui-btn-xs',
                    method: 'open',
                    text: '发邮件',
                    auth: 'sendEmail',
                    url: 'crm.customer_contacts/sendEmail',
                    field:'',
                    extend: '',
                    hidden:function (data) {
                        if (!data.email) return true;
                        return  false;
                    }
                }],'delete']}
            cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,operat)
            var local = layui.data (tableFlag);
            layui.each(cols_fields, function(index, item){
                if(item.field in local){
                    item.hide = true;  // 在本地标识中则隐藏
                }
            });
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh','delete','export',[{
                    text: '自定义字段',
                    url: 'system.fields/index?table=crm_customer_contacts',
                    method: 'open',
                    auth: 'fields',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    icon: 'fa fa-cogs ',
                    extend: 'data-full="true"',
                }]],
                cols: [ cols_fields],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                    // 记录筛选状态
                    var that = this;
                    that.elem.next().on('mousedown', 'input[lay-filter="LAY_TABLE_TOOL_COLS"]+', function () {
                        var input = $(this).prev()[0];
                        // 此处表名可任意定义
                        layui.data(tableFlag, {
                            key: input.name
                            , value: input.checked
                        })
                    });
                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
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

            ea.listen();
            this.common();
        },common:function () {
        //     禁用data-id="customer_id"的属性open-select
        //     $('[data-id="customer_id"]').removeAttr('open-select');
        },sendEmail: function () {
        ea.listen();
        // Email template selection event
        layui.form.on('select(emailtpl)', function(data){
            var templateId = data.value;
            if (templateId === '') {
            }

            if (templateId) {
                ea.request.ajax('post',{url:ea.url('emailtpl/getTemplate'),data:{'id':templateId,'customer_id': $('input[name="customer_id"]').val(),'customer_contacts_id': $('input[name="customer_contacts_id"]').val()}},function (res){
                    if(res.code){
                        $('input[name="subject"]').val(res.data.tpl_title);
                        $('input[name="type"]').val(res.data.tpl_type);
                        $('textarea[name="email_content"]').html(res.data.tpl_content).trigger('input');
                        // window.UE_STORE['email_content'].setContent(res.data.tpl_content);
                    }else{
                        ea.msg.error(res.msg);
                    }
                });
            }
        });
    },
    };
    return Controller;
});