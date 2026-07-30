define(["jquery", "easy-admin"], function ($, ea,Vue) {
    var customer_id=CONFIG.customer_id;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.contract/index/customer_id/'+customer_id,
        add_url: 'crm.contract/add/customer_id/'+customer_id,
        edit_url: 'crm.contract/edit',
        delete_url: 'crm.contract/delete',
        export_url: 'crm.contract/export',
        modify_url: 'crm.contract/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var app;
    var Controller = {

        index: function () {
            var q = {};
            location.search.replace(/([^?&=]+)=([^&]+)/g,function(_,k,v){q[k]=v});
            if(q.isselect==1){
                var operat={'width': 90, 'title': fy('Operate'),'fixed':'right',  templet: '<button class="layui-btn layui-btn-xs layui-btn-success select-close" select-close data-id="{{= d.id }}" data-name="{{= d.name}}"><i class="fa fa-check"></i>选择</button>'};
            }else{
                var operat= {width: 250, title: fy('Operate'), templet: ea.table.tool,fixed: 'right',operat: [
                        [{
                            class: 'layui-btn layui-btn-xs layui-btn-primary',
                            method: 'open',
                            text: '编辑',
                            auth: 'edit',
                            url: 'crm.contract/edit',
                            extend: 'data-full="true"', icon: 'fa fa-edit ',
                            hidden:function (data) {
                                // 只有审核未通过和草稿状态的合同才能编辑
                                // CONFIG.getEditStatus = ['-1','1']，使用indexOf兼容IE
                                if (CONFIG.getEditStatus.indexOf(String(data.check_status)) !== -1) return false;
                                return true;
                            }
                        }],[{
                            class: 'layui-btn layui-btn-success layui-btn-xs',
                            method: 'open',
                            text: '发邮件',
                            auth: 'sendEmail',
                            url: 'crm.contract/sendEmail',
                            field:'',
                            extend: '',  hidden:function (res) {
                                if (res.crmCustomer && res.crmCustomer.email) return false;
                                return  true;
                            }
                        }], [{
                            class: 'layui-btn layui-btn-xs',
                            method: 'open',
                            text: fy('详情'),
                            auth: 'desc',
                            url: 'crm.contract/detail',
                            extend: 'data-full="true"', icon: ''
                        }],  [{
                            class: 'layui-btn layui-btn-xs layui-btn-success',
                            method: 'open',
                            text: '回款',
                            auth: 'contract_receivables_add',
                            url: 'crm.contract_receivables/add/contract_id/{id}',
                            field:'',
                            extend: '',
                            hidden:function (res) {
                                //审核通过的合同才有回款功能
                                if (res.check_status==3 && res.return_money <res.money) return false;
                                return  true;
                            }
                        }],[{
                            class: 'layui-btn layui-btn-xs layui-btn-success',
                            method: 'open',
                            text: '计划回款',
                            auth: 'contract_receivables_plan_add',
                            url: 'crm.contract_receivables_plan/add/contract_id/{id}',
                            field:'',
                            extend: '',
                            hidden:function (res) {
                                //审核通过的合同才能添加计划回款功能 + res.return_money < res.money
                                if (res.check_status==3 && res.return_money <res.money) return false;
                                return  true;
                            }
                        }],[{
                            class: 'layui-btn layui-btn-xs layui-btn-success',
                            method: 'open',
                            text: '续签',
                            auth: 'add',
                            url: 'crm.contract/add/pre_contract_id/{id}',
                            field:'',
                            extend: '',
                            hidden:function (res) {
                                if (ea.init.where['scope']=='expiring' || ea.init.where['scope']=='expired') return false;
                                return  true;
                            }
                        }],   [{
                            class: 'layui-btn layui-btn-danger layui-btn-xs',
                            method: 'request',
                            text: '删除',
                            auth: 'delete',
                            url: 'crm.contract/delete',
                            field:'',
                            extend: '',
                            hidden:function (data) {
                                // 可以编辑的合同才能删除
                                if (CONFIG.getEditStatus.indexOf(String(data.check_status)) !== -1) return false;
                                return  true;
                            }
                        }]
                    ]};
            }
            var  cols_fields=[
                {type: 'checkbox'},
                {field: 'id', title: 'Id',width: 70},
                {field: 'name', title: '合同名称',width: 120,sort:true,search:true},
                {field: 'numbering', title: '合同编号',width: 120,search:true},
                {field: 'crmCustomer.name', title: '客户名称',width: 120,sort:true,search:true},
                {field: 'customer_signer', title: '客户签约人',width: 100,sort:true,search:true},
                {field: 'company_signer', title: '公司签约人',width: 100,search:true},
                {field: 'sign_time', title: '签约时间',templet:ea.table.date,width: 120,sort:true,search:'range'},
                {field: 'money', title: '合同金额',width: 100,sort:true,search:true},
                /*  {field: 'total_price', title: '产品总金额',width: 100},*/
                {field: 'return_money', title: '已收到款项',width: 100,sort:true,search:true},
                {field: 'check_status', title: '合同状态',width: 160,search: 'select', selectList:CONFIG.getCheckStatus
                    ,templet:function (res) {
                        //             ['-1'=>'审核未通过','0'=>'待审核','1'=>'草稿','2'=>'审核中','3'=>'审核通过'];
                        if(res.check_status===-1){
                            audit_feedback=res.audit_feedback;
                            if(audit_feedback){
                                return '<span style="color: red">审核未通过【'+audit_feedback+'】</span>';
                            }
                            return '<span style="color: red">审核未通过</span>';
                        }else if(res.contract_status==-1){
                            return '<span style="color: red">已作废</span>';
                        }else if(res.check_status===0){
                            return '<span style="color: blue">待审核</span>';
                        }else if(res.check_status===1){
                            return '<span style="color: blue">草稿</span>';
                        }else if(res.check_status===2){
                            return '<span style="color: blue">审核中</span>';
                        }else if(res.check_status===3 && res.contract_status==1){
                            return '<span style="color: green">已完成</span>';
                        }else if(res.check_status===3){
                            return '<span style="color: green">审核通过</span>';
                        }
                    },sort:true},
                {field: 'start_time', title: '合同生效时间',width: 160,templet:ea.table.datetime,sort:true,search:'range'},
                {field: 'end_time', title: '合同到期时间',width: 160,templet:ea.table.datetime,sort:true,search:'range'},
                {field: 'ownerAdmin.username', title: '负责人',width: 100,search:true},
                {field: 'create_time', title: fy('Creation time'),width: 160,search:'range'},



            ];
            if(CONFIG.cols_fields){
              cols_fields=cols_fields.concat(CONFIG.cols_fields,operat)
            }
            var local = layui.data (tableFlag);
            layui.each(cols_fields, function(index, item){
                if(item.field in local){
                    item.hide = true;  // 在本地标识中则隐藏
                }
            });

            var scope = (CONFIG.scope && CONFIG.scope != 1) ? CONFIG.scope : 1;
            ea.init.where['scope'] = scope;
            // 激活对应标签页
            if (scope != 1) {
                $('.layui-tab-title li').removeClass('layui-this');
                $('.layui-tab-title li[data-value="' + scope + '"]').addClass('layui-this');
            }
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh',
                    [{
                        text: '添加',
                        url: init.add_url,
                        method: 'open',
                        auth: 'add',
                        class: 'layui-btn layui-btn-normal layui-btn-sm',
                        icon: 'fa fa-plus ',
                        extend: 'data-full="true"',
                    }],[{
                        text: '自定义字段',
                         url: 'system.fields/index?table=crm_contract',
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
                    var tips='&nbsp;&nbsp;<span class="contract_money" style="">合同总金额：<strong>'+res.contractTotalAmount+'</strong> &nbsp;&nbsp;已回款金额：<strong class="received_money">'+res.receivedTotalAmount+'</strong>';
                    var no_money=res.contractTotalAmount-res.receivedTotalAmount;
                    if(no_money>0){
                        tips+=' &nbsp;&nbsp;未回款金额：<strong class="no_money">'+no_money+'</strong></span>';
                    }
                    // 放在layui-table-tool-temp类内的最后面
                    $('.layui-table-tool-temp').append(tips);
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
                },where: {scope: scope}
            });

            ea.listen();
        },
        add: function () {

            $(document).on('click','#create_numbering',function (){
                ea.request.post({
                    url: ea.url('crm.contract/create_numbering'),
                }, function (res) {
                    if(res.code){
                        $('input[name="contract[numbering]"]').val(res.data.numbering);
                    }else{
                        ea.msg.error(res.msg, function () {
                        });
                    }

                });
            });
            this.renderPro();
            this.common();
            ea.listen();

        },
        edit: function () {

            this.renderPro();
            this.common();
            ea.listen();

        },audit: function () {
            this.renderPro();
            ea.listen();

        },detail: function () {
        this.renderPro();
        ea.listen();
           /* element.on('tab(tabMore)', function(data){
               var  value=$(this).data("value");
                // let index = data.index;
                if(value && value == 'contract_receivables'){

                }
            });*/
    },
        common: function () {

            //     name="row[customer_id]" 绑定值发生改变的时候触发
            var customer_id=$('[name="contract[customer_id]"]').val();
            $(document).on('change','[name="contract[customer_id]"]',function (){
                customer_id=$(this).val();
                // 动态修改selectPage请求参数
                $('[name="contract[customer_signer]_text"]').data('selectPageObject').option.params = function () {
                    return {custom: {'customer_id':customer_id}};
                };
                $('[name="contract[business_id]_text"]').data('selectPageObject').option.params = function () {
                    return {custom: {'customer_id':customer_id}};
                };
                // 参数变了则清除原来的展示数据
                $('[name="contract[customer_signer]"]').selectPageClear();
                $('[name="contract[business_id]"]').selectPageClear();
            });
            //     [name="contract[customer_signer]_text"] 获取焦点
            $(document).on('focus','[name="contract[customer_signer]_text"],[name="contract[business_id]_text"]',function (){
                if(customer_id<1){
                    ea.msg.error('请先选择客户！');
                }
            });

        },renderPro:function (){
            require(['vue','tableSelect'], function (Vue) {
                app = new Vue({
                    el: '#app',
                    data: {
                        pro_list: [],
                        cost_sum:0.00,sale_sum:0.00
                    },
                    methods:{
                        entryTime(index){
                            if(!this.pro_list[index]['create_time']){
                                this.pro_list[index]['create_time']=(Date.parse(new Date()))/1000;
                            }
                            return layui.util.toDateString(this.pro_list[index]['create_time']*1000, 'yyyy-MM-dd HH:mm');
                        },removePro(index,contract_product_id){
                            //删除数据库对应的商机产品
                            that =this;
                            if(contract_product_id){
                                ea.msg.confirm("是否确定删除?", function () {
                                    ea.request.ajax('get',{url:ea.url('crm.contract/delproduct'),data:{'contract_product_id':contract_product_id}},function (res){
                                        if(res.code){
                                            that.pro_list.splice(index, 1)
                                        }else{
                                            ea.msg.error(fy('Delete failed'));
                                        }
                                    });
                                });
                            }else{
                                that.pro_list.splice(index, 1)
                            }
                        }
                    },computed: {
                        getTotal() {
                            // 获取productList中select为true的数据
                            var proList = this.pro_list
                            // 设置一个值用来存储总价
                            var cost_sum=0,discount_sum=0,sale_sum=0,nums_sum=0;
                            for (let i = 0; i < proList.length; i++) {

                                cost_sum += proList[i].cost_price * proList[i].nums;
                                sale_sum += proList[i].sale_price * proList[i].nums;
                                discount_sum+=parseFloat(proList[i].discount);
                                nums_sum += parseInt(proList[i].nums);
                            }
                            if(sale_sum){
                                real_sale_sum=sale_sum-discount_sum;
                            }else{
                                real_sale_sum=0;
                            }

                            return {

                                cost_sum: cost_sum.toFixed(2),
                                nums_sum: nums_sum,
                                discount_sum: discount_sum.toFixed(2),
                                sale_sum: sale_sum.toFixed(2),
                                real_sale_sum: real_sale_sum.toFixed(2),
                            }
                        },
                    }
                });
                $(document).on('change','[name="contract[business_id]"]',function (){
                    var business_id = $(this).val(); // 获取当前显示的文本
                    if(business_id){
                        ea.request.ajax('get',{url:ea.url('crm.business/product_by_business'),data:{'business_id':business_id}},function (res){
                            app.pro_list=res.data.product;
                            $('[name="contract[money]"]').val(res.data.business.money);
                            $('[name="contract[company_signer]"]').val(res.data.business.ownerAdmin.username);
                            $('[name="contract[company_signer]"]').selectPageRefresh();
                        });
                    }
                });
                id=$('#table-pro').data('id');
        product_ids=$('#table-pro').data('product_ids');
                if(id){
                    //获取合同对应的产品
                    ea.request.ajax('get',{url:ea.url('crm.contract/product'),data:{'id':id}},function (res){
                        app.pro_list=res.data;

                    });
        }else if(product_ids){
            //获取产品对应的产品
            //如何拼接类似 filter: {"id":"product_ids"}   {filter: '{}', op: '{}'}
            // op: {"id":"in"} ,"status":"1"

            ea.request.ajax('get',{url:ea.url('Product/index'),data:{filter: '{"id":"'+product_ids+'","status":"1"}', op: '{"id":"in","status":"="}'}},function (data){


                for (let i = 0; i < data.data.length; i++) {
                    data.data[i].product_id=data.data[i].id;
                    data.data[i].id=0;
                    data.data[i].remark='';
                    data.data[i].nums=1;
                    data.data[i].discount=data.data[i].discount?data.data[i].discount:0;
                }
                app.pro_list=data.data;

            });
                }
                layui.tableSelect.render({
                    elem: "#select-pro",
                    checkedKey: '',
                    searchType: 'more',
                    searchList: [
                        {searchKey: 'name', searchPlaceholder: fy("Please enter")+fy("Product name")},
                    ],
                    table: {
                        url: ea.url('Product/index'),
                        cols: [[
                            {type: 'checkbox'},
                            {field: 'type.title', width:90,title: fy("Product Classification"),templet: function (d){
                                    return '<span>'+d.type.title+'</span>'
                                }},
                            {field: 'name', title: fy("Product name"),width:200 },
                            {field: 'thumb', width:90,title: fy("Product images"), templet: ea.table.image},
                            {field: 'specification', title: fy("Specifications")},
                            {field: 'model', title: fy("Model")},
                            /* {field: 'inventory', title: fy('Inventory'),width:90,templet: function (d){
                                     if( d.inventory<=d.min_warning){
                                         return '<span class="layui-font-red">'+d.inventory+'</span>'
                                     }else  if( d.inventory>=d.max_warning){
                                         return '<span class="layui-font-orange">'+d.inventory+'</span>'
                                     }
                                 }},*/
                            {field: 'cost_price', width:90,title: fy("Cost price")},
                            {field: 'sale_price', width:90,title: fy("Sale price")},
                        ]]
                    },
                    done: function (e, data) {
                        for (let i = 0; i < data.data.length; i++) {
                            data.data[i].product_id=data.data[i].id;
                            data.data[i].id=0;
                            data.data[i].remark='';
                            data.data[i].nums=1;
                            data.data[i].discount=data.data[i].discount?data.data[i].discount:0;
                        }
                        app.pro_list=app.pro_list.concat(data.data);


                    },where: {status: 1}
                })
            });

    },sendEmail: function () {
            ea.listen();
            // Email template selection event
            layui.form.on('select(emailtpl)', function(data){
                var templateId = data.value;
                if (templateId === '') {
                }

                if (templateId) {
                    ea.request.ajax('post',{url:ea.url('emailtpl/getTemplate'),data:{'id':templateId,'customer_id': $('input[name="customer_id"]').val(),'contract_id': $('input[name="contract_id"]').val()}},function (res){
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
