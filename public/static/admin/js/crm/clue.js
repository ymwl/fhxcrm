define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.clue/index',
        add_url: 'crm.clue/add',
        edit_url: 'crm.clue/edit',
        delete_url: 'crm.clue/delete',
        import_url: 'crm.clue/import',
        export_url: 'crm.clue/export',
        modify_url: 'crm.clue/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    cols_fields=[];
    var Controller = {
        index: function () {
            // 从 CONFIG 读取 URL 中的 scope 参数（支持 PATH_INFO 格式如 /scope/10.html）
            var scope = (CONFIG.scope && CONFIG.scope != 1) ? CONFIG.scope : 1;
            
            ea.init.where['scope'] = scope;
            // 激活对应标签页
            if (scope != 1) {
                $('.layui-tab-title li').removeClass('layui-this');
                $('.layui-tab-title li[data-value="' + scope + '"]').addClass('layui-this');
            }
            var q = {};
            location.search.replace(/([^?&=]+)=([^&]+)/g,function(_,k,v){q[k]=v});

            if(CONFIG.cols_fields){
                if(q.isselect==1){
                    var operat={'width': 90, 'title': fy('Operate'),'fixed':'right',  templet: '<button class="layui-btn layui-btn-xs layui-btn-success select-close" select-close data-id="{{= d.id }}" data-name="{{= d.name}}"><i class="fa fa-check"></i>选择</button>'};
                }else{
                    var operat={'width': 350, 'title': fy('Operate'),'fixed':'right', 'templet': ea.table.tool,operat: [
                            [{
                                class: 'layui-btn layui-btn-xs layui-btn-primary',
                                method: 'open',
                                text: fy('Follow up'),
                                auth: 'record_add',
                                url: 'crm.clue_record/add?clue_id={id}',
                                icon: 'fa fa-commenting-o',
                                extend: '',
                            }],[{
                                class: 'layui-btn layui-btn-xs layui-btn-warm',
                                method: 'request',
                                text: '转为客户',
                                title: fy('Are you sure you want to convert this clue to a customer') + '？',
                                auth: 'toCustomer',
                                url: 'crm.clue/toCustomer',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    // 已转化的不显示
                                    if (data.status == 2) return true;
                                    return false;
                                }
                            }],
                            'edit',
                            [{
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                                method: 'open',
                                text: '发邮件',
                                auth: 'sendEmail',
                                url: 'crm.clue/sendEmail',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    if (!data.email) return true;
                                    return  false;
                                }
                            }],
                            'delete']};
                }
                cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,operat)
            }
            var page=1,limit=CONFIG.ADMINPAGESIZE;
            if(q.isselect==1){
                var toolbar=['refresh'];
            }else{
                var toolbar=['refresh','add','delete','export',[{
                    text: fy('Import'),
                    url: init.import_url,
                    method: 'open',
                    auth: 'import',
                    class: 'layui-btn layui-btn-success layui-btn-sm',
                    icon: 'fa fa-upload ',
                    extend: '',
                }],[{
                    text: '转为客户',
                    url:'crm.clue/toCustomer',
                    method: 'url',
                    auth: 'toCustomer',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    title: '你确定要将已勾选的线索转换为客户吗？',
                    icon: 'fa fa-exchange',
                    extend:'data-checkbox="true"'
                }],[{
                    text: '转移',
                    url:'crm.clue/alterPrUser',
                    method: 'open',
                    auth: 'alterPrUser',
                    class: 'layui-btn layui-btn-primary layui-btn-sm',
                    title:  '您确定要转移这些已勾选的线索吗？',
                    icon: 'fa fa-clock-o',
                    extend:'data-checkbox="true" data-height="350px"'
                }],[{
                    text: fy('To clue pool'),
                    url:'crm.clue/toPool',
                    method: 'url',
                    auth: 'toPool',
                    class: 'layui-btn layui-btn-danger layui-btn-sm',
                    title: fy('Are you sure you want to move the checked clues into the clue pool') + '？',
                    icon: 'fa fa-life-ring',
                    extend:'data-checkbox="true"'
                }],[{
                    text: fy('Custom fields'),
                    url: 'system.fields/index?table=crm_clue',
                    method: 'open',
                    auth: 'fields',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    icon: 'fa fa-cogs ',
                    extend: 'data-full="true"',
                }]];
            }
            var local = layui.data (tableFlag);
            layui.each(cols_fields, function(index, item){
                if(item.field in local){
                    item.hide = true;
                }
            });
            var tableIn=ea.table.render({
                autoSort: false,
                toolbar: toolbar,
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    page=curr;limit=this.limit;
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }

                    if(ea.checkMobile()){
                        ea.booksTemplet();
                    }
                    var that = this;
                    that.elem.next().on('mousedown', 'input[lay-filter="LAY_TABLE_TOOL_COLS"]+', function () {
                        var input = $(this).prev()[0];
                        layui.data(tableFlag, {
                            key: input.name
                            , value: input.checked
                        })
                    });
                },where: {scope: scope}
            });

            ea.listen();

        },
        pool: function () {
            if(CONFIG.cols_fields){
                cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,{'width': 100, 'title': fy('Operate'),toolbar: '#action',fixed: 'right'})
            }
            ea.table.render({
                autoSort: false,
                toolbar: ['refresh',[{
                    text: fy('Claim'),
                    url:'crm.clue_pool/rob',
                    method: 'url',
                    auth: 'rob',
                    class: 'layui-btn layui-btn-success layui-btn-sm',
                    title:  '你确定要领取当前已勾选的线索吗？',
                    icon: 'fa fa-hand-o-down',
                    extend:'data-checkbox="true"'
                }]],
                init: {
                    table_elem: '#currentTable',
                    table_render_id: 'currentTableRenderId',
                    index_url: 'crm.clue/pool',
                },
                limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet('currentTableRenderId');
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
        import: function () {
            ea.listen();
        },
        alterPrUser: function () {
            ea.listen();
        },
        converted: function () {
            if(CONFIG.cols_fields){
                cols_fields=CONFIG.cols_fields;
                /*cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,{'width': 160, 'title': fy('Operate'),fixed: 'right','templet': ea.table.tool,operat: ['edit',
                        'delete']})*/
            }
            ea.table.render({
                autoSort: false,
                toolbar: ['refresh'],
                init: {
                    table_elem: '#currentTable',
                    table_render_id: 'currentTableRenderId',
                    index_url: 'crm.clue/converted',
                },
                limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet('currentTableRenderId');
                    }
                }
            });
            ea.listen();
        },
        sendEmail: function () {
            ea.listen();
            // Email template selection event
            layui.form.on('select(emailtpl)', function(data){
                var templateId = data.value;
                if (templateId === '') {
                    return;
                }

                if (templateId) {
                    ea.request.ajax('post',{url:ea.url('emailtpl/getTemplate'),data:{'id':templateId,'clue_id': $('input[name="clue_id"]').val()}},function (res){
                        if(res.code){
                            $('input[name="subject"]').val(res.data.tpl_title);
                            $('textarea[name="email_content"]').html(res.data.tpl_content).trigger('input');
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