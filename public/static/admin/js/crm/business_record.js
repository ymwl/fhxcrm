define(["jquery", "easy-admin","vue"], function ($, ea,Vue) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.business_record/index',
        add_url: 'crm.business_record/add',
        edit_url: 'crm.business_record/edit',
        delete_url: 'crm.business_record/delete',
        export_url: 'crm.business_record/export',
        modify_url: 'crm.business_record/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            ea.table.render({
                toolbar: ['refresh','delete'],
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'business_name', title: '跟进商机',search:true},
                    {field: 'create_username', title: fy('Follower'),search:true},
                    {field: 'create_time', title: fy('Follow up time'),search:'range',templet: ea.table.date},
                    {field: 'content', title: fy('Follow up content'),search:true},
                    {field: 'next_time', title: fy('Next follow-up time'),search:'range',templet: ea.table.date},
                    {field: 'record_type', title: fy('Follow up type'),search:true},
                    {field: 'attachs', title: fy("Attachment"),templet:function (res,option){
                            if(res.attachs){
                                return '<a class="layui-btn layui-btn-success layui-btn-xs" data-open="attachs/index?attachs='+res.attachs+'" data-title="'+fy('Follow up')+' '+fy('Attachment')+'">'+fy('Attachment')+'</a>';
                            }else{
                                return '';
                            }
                        }},
                    {width: 250, title: fy('Operate'), templet: ea.table.tool,operat:['delete']},
                ]],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                },where: {scope: 1}
            });

            ea.listen();
        },
        add: function () {
            this.renderPro();
            ea.listen('',
                function (res) {
                    ea.msg.success(res.msg, function () {
                        layui.table.reload(init.table_render_id, {page: {curr: 1}});
                        // location.reload();
                    });
                }, function (res) {
                    ea.msg.error(res.msg, function () {
                    });
                });
        },
        edit: function () {
            ea.listen();
        },renderPro:function (){
            business_id=$('#table-pro').data('business_id');
            ea.table.render({
                toolbar: ['refresh'],
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {field: 'id', title: 'ID'},
                    {field: 'create_username', title: fy('Follower'),search:true},
                    {field: 'create_time', title:  fy('Follow up time'),search:'range',templet: ea.table.date},
                    {field: 'content', title: fy('Follow up content'),search:true},
                    {field: 'next_time', title: fy('Next follow-up time'),search:'range',templet: ea.table.date},
                    {field: 'record_type', title: fy('Follow up type')},
                    {field: 'attachs', title:fy('Attachment'),templet:function (res,option){
                            if(res.attachs){
                                return '<a class="layui-btn layui-btn-success layui-btn-xs" data-open="attachs/index?attachs='+res.attachs+'" data-title="'+fy('Follow up')+' '+fy('Attachment')+'">'+fy('Attachment')+'</a>';
                            }else{
                                return '';
                            }
                        }}
                ]],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                },where: {scope: 1,business_id: business_id}
            });
            // 修改成相关合同获取 还需要增加绑定id
            ea.table.render({
                toolbar: ['refresh'],
                init: {
                    table_elem: '#contractTable',
                    table_render_id: 'contractTableRenderId',
                    index_url: 'crm.contract/index',
                    add_url: 'crm.contract/add',
                    edit_url: 'crm.contract/edit',
                    delete_url: 'crm.contract/delete',
                    export_url: 'crm.contract/export',
                    modify_url: 'crm.contract/modify',
                },limit:CONFIG.ADMINPAGESIZE,
                //还需要增加绑定id

                cols: [[
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
                    {field: 'start_time', title: '合同生效时间',width: 160,templet:ea.table.datetime,sort:true,search:'range'},
                    {field: 'end_time', title: '合同到期时间',width: 160,templet:ea.table.datetime,sort:true,search:'range'},
                    {field: 'ownerAdmin.username', title: '负责人',width: 100,search:true},
                    {field: 'create_time', title: fy('Creation time'),width: 160,search:'range'}
                ]],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                },where: {scope: 1,business_id: business_id}
            });

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
                            ea.request.ajax('get',{url:ea.url('crm.business/delproduct_by_business'),data:{'business_product_id':business_product_id}},function (res){
                                if(res.code){
                                    that.pro_list.splice(index, 1)
                                }else{
                                    ea.msg.error(fy('Delete failed'));
                                }
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

            if(business_id){
                //获取商机对应产品
                ea.request.ajax('get',{url:ea.url('crm.business/product_by_business'),data:{'business_id':business_id}},function (res){
                    app.pro_list=res.data.product;

                });
            }

        }
    };
    return Controller;
});
