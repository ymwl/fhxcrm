define(["jquery", "easy-admin"], function ($, ea) {
    table = layui.table;form = layui.form;
    var customer_id=CONFIG.customer_id;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        add_url: 'crm.order/add/customer_id/'+customer_id,
        index_url: 'crm.order/index/customer_id/'+customer_id,
        delete_url: 'crm.order/delete',
        export_url: 'crm.order/export/customer_id/'+customer_id,
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {
        index: function () {
            cols_fields=[];var audit_feedback='';
            if(CONFIG.cols_fields){
                cols_fields=[].concat({'type': 'checkbox'}, {field: 'id', title: 'ID',width:65},
                    {'width': 300, 'title': fy('Operate'), 'templet': ea.table.tool,operat: [
                            [{
                                class: 'layui-btn layui-btn-xs layui-btn-primary',
                                method: 'open',
                                text: fy('编辑'),
                                auth: 'edit',
                                url: 'crm.order/edit',
                                extend: 'data-full="true"', icon: 'fa fa-edit ',
                                hidden:function (data) {
                                    if (data.status==-1 && ea.init.where['scope']==1) return false;
                                    return  true;
                                }
                            }],[{
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                                method: 'open',
                                text: '发邮件',
                                auth: 'sendEmail',
                                url: 'crm.order/sendEmail',
                                field:'',
                                extend: '', hidden:function (data) {

                                    if (ea.init.where['scope']==1) return false;
                                    return  true;
                                }
                            }],
                            [{
                                class: 'layui-btn layui-btn-xs layui-btn-primary',
                                method: 'open',
                                text: fy('审核订单'),
                                auth: 'audit',
                                url: 'crm.order/audit?id={id}&audit_management_id={audit_management_id}',
                                extend: 'data-full="true"', icon: '',
                                hidden:function (data) {
                                    if (data.status==0 && ea.init.where['scope']!=1) return false;
                                    return  true;
                                }
                            }], [{
                                class: 'layui-btn layui-btn-xs',
                                method: 'open',
                                text: fy('详情'),
                                auth: 'desc',
                                url: 'crm.order/detail',
                                extend: 'data-full="true"', icon: ''
                            }],   [{
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                                method: 'request',
                                text: '删除',
                                auth: 'delete',
                                url: 'crm.order/delete',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //分享给我的21没有删除权限
                                    if (data.status>=1) return true;
                                    return  false;
                                }
                            }]]},
                    {field: 'cname', title: fy('Client name'),'width': 150,search:true},
                    {field: 'orderno', title: fy('Order number'),'width': 150,search:true},
                    {field: 'cphone', title: fy('Contact number') ,'width': 150,search:true},
                    {field: 'money', title: fy('amount'),'width': 150},
                    {field: 'freight', title: fy('Freight'),'width': 150},
                    {field: 'pr_user', title: fy('Responsible Person'),'width': 150,hide:false,search:true },
                    {field: 'status', title: fy('Audit status'),'width': 200,templet:function (res) {
                            audit_feedback='';
                            if($.trim(res.audit_feedback)!==''){
                                audit_feedback='【'+res.audit_feedback+'】';
                            }
                            if(res.status==1){
                                return '<span class="green">'+CONFIG.statusList[res.status]+audit_feedback+'</span>';
                            }else if(res.status==-1){
                                return '<span class="red">'+CONFIG.statusList[res.status]+audit_feedback+'</span>';
                            }else{
                                return CONFIG.statusList[res.status];
                            }

                        }
                    ,search: 'select', selectList: CONFIG.statusList  },
                    {field: 'update_time', title: fy('Update time'),'width': 150,templet:ea.table.datetime},CONFIG.cols_fields)
            }
            ea.table.render({
                toolbar: ['refresh','add','export',[{
                    text: fy('Custom fields'),
                    url: 'system.fields/index?table=crm_order',
                    method: 'open',
                    auth: 'fields',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    icon: 'fa fa-cogs ',
                    extend: 'data-full="true"',
                }]],
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
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
        }
        ,
        detail: function () {
            ea.listen();
        },changeyewu:function (){

             //选择客户后自动填充联系人、手机号
            $(document).on('change','input[name="customer_id"]',function (){
                var customerId = $(this).val();
                if(ea.empty(customerId)){
                    return;
                }
                ea.request.post({
                    url: ea.url('crm.customer/getCustomerInfo'),
                    data: {customer_id: customerId}
                }, function (res) {
                    if(res.code && res.data){
                        $('#ccontact').val(res.data.contact || '');
                        $('#cphone').val(res.data.phone || '');
                        $('#address').val((res.data.area || '') + (res.data.address || ''));
                        //表单没有值才赋值，避免覆盖用户已填写的内容
                       /* if(ea.empty($('#ccontact').val())){

                        }
                        if(ea.empty($('#cphone').val())){

                        }
                        if(ea.empty($('#address').val())){

                        }*/
                    }
                });
            });

        },
        add: function () {



            $(document).on('click','#create_orderno',function (){
                ea.request.post({
                    url: ea.url('crm.order/create_orderno'),
                }, function (res) {
                    if(res.code){
                        $('input[name="orderno"]').val(res.data.numbering);
                    }else{
                        ea.msg.error(res.msg, function () {
                        });
                    }

                });
            });

            form.verify({
                orderno_unqiue : function(value, item) {
                    var checkResult=false;
                    $.ajax({
                        url: ea.url("crm.order/verify_orderno"),
                        type: "POST",dataType:"json",
                        data: {orderno:value},
                        async: false,
                        success: function(res) {
                            if (res.code < 0){
                                checkResult=res.msg;
                            }
                        },
                        error: function() {
                        }
                    });
                    if(checkResult){
                        return checkResult;
                    }
                }
            });
            ea.listen(null,function (res) {
                if (res.code == 1){
                    ea.msg.success(res.msg, function () {
                        window.parent.location.reload();
                    });
                }else {
                    ea.msg.error(res.msg);
                }
            });
            Controller.changeyewu();
        },
        edit: function () {

            form.verify({
                orderno_unqiue : function(value, item) {
                    var checkResult=false;
                    $.ajax({
                        url: ea.url("crm.order/verify_orderno"),
                        type: "POST",dataType:"json",
                        data: {orderno:value,id:$('input[name="id"]').val()},
                        async: false,
                        success: function(res) {
                            if (res.code < 0){
                                checkResult=res.msg;
                            }
                        },
                        error: function() {
                        }
                    });
                    if(checkResult){
                        return checkResult;
                    }
                }
            });
            ea.listen(null,function (res) {
                if (res.code == 1){
                    ea.msg.success(res.msg, function () {
                        window.parent.location.reload();
                    });
                }else {
                    ea.msg.error(res.msg);
                }
            });
            Controller.changeyewu();
        },
        sale: function () {
            ea.listen();
        },
        audit: function () {
            ea.listen(null,function (res) {
                if (res.code == 1){
                    ea.msg.success(res.msg,function (){
                        window.parent.location.reload();
                    });
                }else {
                    ea.msg.error(res.msg);
                }
            });
        },sendEmail: function () {
        ea.listen();
        // Email template selection event
        layui.form.on('select(emailtpl)', function(data){
            var templateId = data.value;
            if (templateId === '') {
            }

            if (templateId) {
                ea.request.ajax('post',{url:ea.url('emailtpl/getTemplate'),data:{'id':templateId,'customer_id': $('input[name="customer_id"]').val(),'client_order_id': $('input[name="client_order_id"]').val()}},function (res){
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
    }
    };
    return Controller;
});
