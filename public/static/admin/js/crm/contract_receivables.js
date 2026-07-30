define(["jquery", "easy-admin"], function ($, ea) {
var contract_id = CONFIG.contract_id;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.contract_receivables/index/contract_id/'+contract_id,
        add_url: 'crm.contract_receivables/add/contract_id/'+contract_id,
        edit_url: 'crm.contract_receivables/edit/contract_id/'+contract_id,
        delete_url: 'crm.contract_receivables/delete/contract_id/'+contract_id,
        export_url: 'crm.contract_receivables/export/contract_id/'+contract_id,
        modify_url: 'crm.contract_receivables/modify/contract_id/'+contract_id,
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            var  cols_fields=[
                {type: 'checkbox'},
                {field: 'id', title: 'id', width: 80},
                {field: 'numbering', title: '回款编号',width: 120},
                {field: 'crmCustomer.name', title: '客户',width: 150,search: true},
                {field: 'crmContract.name', title: '合同',width: 150,search: true},
                {field: 'return_time', title: '回款日期',width: 150,search: 'range'},
                {field: 'money', title: '回款金额',width: 100,search: true},
                {field: 'check_status', title: '回款状态',width: 150,search: 'select', selectList:CONFIG.getCheckStatus
                    ,templet:function (res) {
                        //             ['-1'=>'审核未通过','0'=>'待审核','1'=>'草稿','2'=>'审核中','3'=>'审核通过'];
                        if(res.check_status===-1){
                            audit_feedback=res.audit_feedback;
                            if(audit_feedback){
                                return '<span style="color: red">审核未通过【'+audit_feedback+'】</span>';
                            }
                            return '<span style="color: red">审核未通过</span>';
                        }else if(res.check_status===0){
                            return '<span style="color: blue">待审核</span>';
                        }else if(res.check_status===1){
                            return '<span style="color: blue">草稿</span>';
                        }else if(res.check_status===2){
                            return '<span style="color: blue">审核中</span>';
                        }else if(res.check_status===3){
                            return '<span style="color: green">审核通过</span>';
                        }
                    },sort:true},
                {field: 'remark', title: '备注', templet: ea.table.text},
                {field: 'ownerAdmin.username', title: '负责人',width: 120,search: true},
                {field: 'update_time', title: '更新时间'},

            ];
            if(CONFIG.cols_fields){
                cols_fields=cols_fields.concat(CONFIG.cols_fields);
            }
            cols_fields=cols_fields.concat([{width: 200, title: '操作',fixed: 'right', templet: ea.table.tool,operat: [
                    [{
                        class: 'layui-btn layui-btn-xs layui-btn-primary',
                        method: 'open',
                        text: '编辑',
                        auth: 'edit',
                        url: init.edit_url,
                        extend: 'data-full="true"', icon: 'fa fa-edit ',
                        hidden:function (data) {
                            if (CONFIG.getEditStatus.indexOf(String(data.check_status)) !== -1) return false;
                            return true;
                        }
                    }], [{
                        class: 'layui-btn layui-btn-xs',
                        method: 'open',
                        text: fy('详情'),
                        auth: 'desc',
                        url: 'crm.contract_receivables/detail',
                        extend: 'data-full="true"', icon: ''
                    }],  [{
                        class: 'layui-btn layui-btn-danger layui-btn-xs',
                        method: 'request',
                        text: '删除',
                        auth: 'delete',
                        url: init.delete_url,
                        field:'',
                        extend: '',
                        hidden:function (data) {
                            // 可以编辑的可以删除
                            if (CONFIG.getEditStatus.indexOf(String(data.check_status)) !== -1) return false;
                            return  true;
                        }
                    }]
                ]}])

            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh','add',
                    [{
                        text: '自定义字段',
                        url: 'system.fields/index?table=crm_contract_receivables',
                        method: 'open',
                        auth: 'fields',
                        class: 'layui-btn layui-btn-warm layui-btn-sm',
                        icon: 'fa fa-cogs ',
                        extend: 'data-full="true"',
                    }]],
                cols: [cols_fields],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                       if(ea.checkMobile()){
                                               ea.booksTemplet();
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
                }
            });

            ea.listen();
        },
        add: function () {
            $(document).on('click','#create_numbering',function (){
                ea.request.post({
                    url: ea.url('crm.contract_receivables/create_numbering'),
                }, function (res) {
                    if(res.code){
                        $('input[name="numbering"]').val(res.data.numbering);
                    }else{
                        ea.msg.error(res.msg, function () {
                        });
                    }

                });
            });

            // 选择回款计划时自动填充回款金额
            layui.form.on('select(receivables_plan_id)', function(data){
                if (data.value && data.value !== '0') {
                    var $option = $(data.elem).find('option:selected');
                    var planMoney = $option.data('money');
                    if (planMoney !== undefined) {
                        $('input[name="money"]').val(planMoney);
                    }
                }
            });

            ea.listen();
        },
        edit: function () {
            ea.listen();
        },audit: function () {
            ea.listen();
        }
    };
    return Controller;
});