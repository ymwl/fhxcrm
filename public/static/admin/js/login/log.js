define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'login.log/index',
        add_url: 'login.log/add',
        edit_url: 'login.log/edit',
        delete_url: 'login.log/delete',
        export_url: 'login.log/export',
        modify_url: 'login.log/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['delete', 'refresh'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id',width: 80},
                    {field: 'username', title:fy("log on user"),search:true,width: 120},
                    {field: 'ip', title: fy("Sign in")+' IP',search:true},
                    {field: 'info', title: fy("Login Status"),search:true,templet:function (res) {
                            if(res.info!=='Login succeeded' && res.info!=='登录成功!'){
                                return '<span class="red">'+fy(res.info)+'</span>';
                            }else{
                                return fy(res.info);
                            }
                        }},
                    {field: 'login_side', title: fy("Login terminal"),templet:function (res) {
                            if(res.login_side==1){
                                return fy('PC Login');
                            }else if(res.login_side==2){
                                return fy("Mobile login");
                            }else{
                                return fy('Unknown login')
                            }
                    }},
                    {field: 'createtime', title: fy("login time"), templet: function (res,option){
                            if(res.createtime){
                                return layui.util.toDateString(res.createtime * 1000);
                            }
                            return '';
                        }},
                    {width: 250, title: fy('Operate'), templet: ea.table.tool, operat: ['delete']},
                ]],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
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
    };
    return Controller;
});