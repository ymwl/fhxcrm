define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.record/index',
        add_url: 'crm.record/add',
        edit_url: 'crm.record/edit',
        delete_url: 'crm.record/delete',
        export_url: 'crm.record/export',
        modify_url: 'crm.record/modify',
    },util = layui.util;
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    var Controller = {

        index: function () {
            ea.table.render({
                toolbar: ['refresh','delete'],
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'khname', title:fy('Client name'),search:true},
                    {field: 'khphone', title: fy('Client contact number'),search:true},
                    {field: 'pr_user', title: fy('Follower'),search:true},
                    {field: 'record_type', title: fy('Follow up type'),selectList:CONFIG.record_type,search: 'select'},
                    {field: 'content', title: fy('Follow up content'),search:true},

                    {field: 'create_time', title: fy('Follow up time'),sort:true,"search":"range",templet:ea.table.datetime},

                    {field: 'next_time', title: fy('Next follow-up time'),sort:true,"search":"range",templet: function (res,option){
                        if(res.next_time){
                            return util.toDateString(res.next_time * 1000);
                        }
                        return '';
                        }},
                    {field: 'attachs', title: fy('Attachment'),templet: function (res,option){
                        if(res.attachs){
                            return '<a class="layui-btn layui-btn-success layui-btn-xs" data-open="attachs/index?attachs='+res.attachs+'" data-title="'+fy('Follow up')+' '+fy('Attachment')+'">'+fy('Attachment')+'</a>';
                        }else{
                            return '';
                        }
                        }},
                    {width: 100, title: fy('Operate'), templet: ea.table.tool,operat:['delete']},
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
        },add:function (){
            ea.listen('',
                function (res) {
                    ea.msg.success(res.msg, function () {
                        layui.table.reload(init.table_render_id, {page: {curr: 1}});
                    })
                }, function (res) {
                    ea.msg.error(res.msg, function () {
                    });
                });

            ea.table.render({
                toolbar: ['refresh'],
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {field: 'id', title: 'ID',width:70},
                    {field: 'pr_user', title: fy('Follower'),search:true},
                    {field: 'create_time', title:  fy('Follow up time'),search:'range',width:155,templet:ea.table.datetime},
                    {field: 'content', title: fy('Follow up content'),search:true},
                    {field: 'next_time', title: fy('Next follow-up time'),search:'range',templet: ea.table.date,templet:function (res,option){
                        return util.toDateString(res.next_time * 1000);
                        },width:155},
                    {field: 'record_type', title: fy('Follow up type')},
                    {field: 'attachs', title:fy('Attachment'),templet:function (res,option){
                            if(res.attachs){
                                return '<a class="layui-btn layui-btn-success layui-btn-xs" data-open="attachs/index?attachs='+res.attachs+'" data-title="'+fy('Follow up')+' '+fy('Attachment')+'">'+fy('Attachment')+'</a>';
                            }else{
                                return '';
                            }
                        }}
                ]],limit: 5,
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                },where: {scope: 3,customer_id: $('input[name="customer_id"]').val()}
            });

        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});
