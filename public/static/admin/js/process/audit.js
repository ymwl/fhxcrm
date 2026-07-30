define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'process.audit/index',
        add_url: 'process.audit/add',
        edit_url: 'process.audit/edit',
        delete_url: 'process.audit/delete',
        export_url: 'process.audit/export',
        modify_url: 'process.audit/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            var scope = (CONFIG.scope && CONFIG.scope != 1) ? CONFIG.scope : 1;
            ea.init.where['scope'] = scope;
            // 激活对应标签页
            if (scope != 1) {
                $('.layui-tab-title li').removeClass('layui-this');
                $('.layui-tab-title li[data-value="' + scope + '"]').addClass('layui-this');
            }
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh'],
                cols: [[
                    {type: 'checkbox',fixed: true},
                    {field: 'id', title: 'ID', fixed: true, width: 80},
                    {field: 'create_username', title: fy('Originator'), width: 100,search: true,sort: true},
                    {field: 'title', title: fy('Type'), width: 100, search: 'select', selectList: CONFIG.titleLst},
                    {field: 'mess', title: fy('Content'),width: 200 },
                    {field: 'createtime', title: "提交时间",templet:ea.table.datetime,width: 160,sort: true},
                    {field: 'result', title: "审批状态",search:'select', selectList: CONFIG.resultLst},
                    {field: 'result_mess', title: "审批内容",width: 200 },
                    {field: 'reviewer', title: "审批者",sort: true },
                    {field: 'audittime', title: "审批时间" ,templet:ea.table.datetime,sort: true},
                    {width: 120, title: fy('Operate'), templet: ea.table.tool,operat: [[{
                            class: 'layui-btn layui-btn-success layui-btn-xs',
                            method: 'open',
                            text: '审核',
                            title: '{title}',
                            auth: 'audit',
                            url: '{url}&audit_management_id={id}',
                            field:'',
                            extend: '',
                            hidden:function (d) {
                                if(d.is_finish==0 && (d.auditor_group_ids.indexOf(","+CONFIG.ADMIN.group_id+",")>=0 || d.auditor_admin_ids.indexOf(","+CONFIG.ADMIN.admin_id+",")>=0 ) ){
                                    return false;
                                }
                                return true;
                            }
                        }],'delete']},
                ]],done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
                    }
                },where: {scope: scope}
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
