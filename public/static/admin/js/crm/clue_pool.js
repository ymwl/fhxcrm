define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.clue/pool',
    };
    cols_fields=[];
    var Controller = {
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
                init: init,
                limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet(init.table_render_id);
                    }
                }
            });
            ea.listen();
        },
    };
    return Controller;
});
