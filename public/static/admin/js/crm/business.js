define(["jquery", "easy-admin"], function ($, ea) {
    var customer_id=CONFIG.customer_id;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.business/index/customer_id/'+customer_id,
        add_url: 'crm.business/add/customer_id/'+customer_id,
        edit_url: 'crm.business/edit',
        delete_url: 'crm.business/delete',
        export_url: 'crm.business/export',
        modify_url: 'crm.business/modify',
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
                toolbar: ['refresh',
                    [{
                        text: fy("Add"),
                        url: init.add_url,
                        method: 'open',
                        auth: 'add',
                        class: 'layui-btn layui-btn-normal layui-btn-sm',
                        icon: 'fa fa-plus ',
                        extend: 'data-full="true"',
                    }],
                    'delete'],
                cols: [function (){
                    var arr=[
                        {type: 'checkbox'},
                        {field: 'id', title: 'id',width: 60},
                        {field: 'crmCustomer.name', title:fy("Client name"),search: true,width: 150},
                        {field: 'name', title: fy("Opportunity Name"),width: 150},
                        {field: 'money', title: fy("Budget amount"),width: 100},
                        {field: 'total_price', title: fy("Total sales amount"),width: 100},
                        {field: 'next_time', title: fy("Next contact time"),width: 142,templet:function (d){
                                return layui.util.toDateString(d.next_time*1000, 'yyyy-MM-dd HH:mm');
                            },search: 'range'},
                        {field: 'is_end', search: 'select', selectList:CONFIG.getIsEndList, title: fy('Status')},
                        {field: 'deal_time', title: fy("Estimated transaction date"),templet:function (d){
                                return layui.util.toDateString(d.deal_time*1000, 'yyyy-MM-dd HH:mm');
                            },width: 142},
                    //     	`last_up_records` VARCHAR(600) NOT NULL DEFAULT '' COMMENT '最后跟进记录' COLLATE 'utf8mb4_unicode_ci',
                        // 	`last_up_time` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT '最后跟进时间',
                    /*    {field: 'create_username', title: fy("Created by"),search: true},*/
                        {field: 'last_up_time', title: '最后跟进时间',templet:function (d){
                                return layui.util.toDateString(d.last_up_time*1000, 'yyyy-MM-dd HH:mm');
                            },sort: true,width: 142},
                        {field: 'last_up_records', title: '最后跟进记录',search: true},
                        {field: 'ownerAdmin.username', title: fy("Responsible Person"),search: true},
                       /* {field: 'create_time', title: fy('Creation time'),templet:ea.table.datetime},*/
                        {width: 250, title: fy('Operate'),'fixed':'right', templet: ea.table.tool,operat: [
                                [{
                                    class: 'layui-btn layui-btn-xs layui-btn-primary',
                                    method: 'open',
                                    text: fy("Write follow-up"),
                                    auth: 'record_add',
                                    url: 'crm.business_record/add?business_id={id}&customer_id={customer_id}',
                                    extend: 'data-full="true"', icon: 'fa fa-plus'
                                }],[{
                                    text: fy("Edit"),
                                    url: init.edit_url,
                                    method: 'open',
                                    auth: 'edit',
                                    class: 'layui-btn layui-btn-xs layui-btn-success',
                                    extend: 'data-full="true"',
                                }],
                                'delete']},
                    ]
                    //初始化筛选状态
                    var local = layui.data (tableFlag);
                    layui.each(arr, function(index, item){
                        if(item.field in local){
                            item.hide = true;  // 在本地标识中则隐藏
                        }
                    });
                    return arr;
                }()],
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
                },where: {scope: scope}
            });

            ea.listen();
        },
        add: function () {
            this.renderPro();

        },
        edit: function () {
            this.renderPro();

        },renderPro:function (){
            require(['vue','tableSelect'], function (Vue) {
                var app = new Vue({
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
                        },removePro(index,business_product_id){
                            //删除数据库对应的商机产品
                            that =this;
                            if(business_product_id){
                                ea.msg.confirm("是否确定删除?", function () {
                                    ea.request.ajax('get',{url:ea.url('crm.business/delproduct_by_business'),data:{'business_product_id':business_product_id}},function (res){
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
                business_id=$('#table-pro').data('business_id');
                if(business_id){
                    //获取商机对应产品
                    ea.request.ajax('get',{url:ea.url('crm.business/product_by_business'),data:{'business_id':business_id}},function (res){
                        app.pro_list=res.data.product;

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
                // Vue 挂载完成后再初始化 layui 组件，避免 v-cloak/display:none 导致日期组件失效
                Vue.nextTick(function () {
                    ea.listen();
                });
            });

        }
    };
    return Controller;
});
