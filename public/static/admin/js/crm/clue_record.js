define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.clue_record/index',
        add_url: 'crm.clue_record/add',
        delete_url: 'crm.clue_record/delete',
    };
    var Controller = {
        index: function () {
            ea.init.where['scope']=1;
            var q = {};
            location.search.replace(/([^?&=]+)=([^&]+)/g,function(_,k,v){q[k]=v});

            ea.table.render({
                init: init,
                limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh','delete'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', width: 70},
                    {field: 'clue_name', title: fy('Clue name'), width: 150, search: true},
                    {field: 'content', title: fy('Content'), width: 250,search: true},
                    {field: 'record_type', title: fy('Follow-up type'), width: 100,search: true},
                    {field: 'pr_user', title: fy('Follow-up by'), width: 100,search: true},
                    {field: 'create_time', title: fy('Follow-up time'), width: 160, templet: ea.table.datetime},
                    {field: 'next_time', title: fy('Next follow-up'), width: 160, templet: ea.table.datetime},
                    {field: 'attachs', title: fy('Attachment'),templet: function (res,option){
                            if(res.attachs){
                                return '<a class="layui-btn layui-btn-success layui-btn-xs" data-open="attachs/index?attachs='+res.attachs+'" data-title="'+fy('Follow up')+' '+fy('Attachment')+'">'+fy('Attachment')+'</a>';
                            }else{
                                return '';
                            }
                        }},
                    {width: 100, title: fy('Operate'), templet: ea.table.tool},
                ]],
                where: {clue_id: q.clue_id || 0, scope: 1},
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
    };
    return Controller;
});
