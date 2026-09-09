define(["jquery", "easy-admin"], function ($, ea) {

    /**
     * 插件操作成功后刷新后台左侧菜单
     * 因为 addon 页面运行在 iframe 中，菜单在顶层父窗口，
     * 所以需要通过 window.top 访问父窗口的 layui 模块来刷新菜单。
     */
    function refreshAdminMenu() {
        try {
            var topWindow = window.top;
            if (!topWindow || !topWindow.layui || !topWindow.require) return;
            var topJquery = topWindow.layui.jquery;
            if (!topJquery) return;
            topWindow.require(['miniMenu'], function (topMiniMenu) {
                if (!topMiniMenu) return;
                topJquery.getJSON(ea.url('ajax/initAdmin'), function (data) {
                    if (data && data.menuInfo) {
                        topMiniMenu.render({
                            menuList: data.menuInfo,
                            multiModule: true,
                            menuChildOpen: false
                        });
                    }
                });
            });
        } catch (e) {
            console.log('refreshAdminMenu error:', e.message);
        }
    }

    // 全局 AJAX 拦截：插件 modify/install/uninstall 操作成功后自动刷新菜单
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.url && (
            settings.url.indexOf('addon/modify') !== -1 ||
            settings.url.indexOf('addon/install') !== -1 ||
            settings.url.indexOf('addon/uninstall') !== -1
        )) {
            try {
                var res = xhr.responseJSON;
                if (res && res.code == 1) {
                    refreshAdminMenu();
                }
            } catch (e) {
                // 忽略解析错误
            }
        }
    });

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'addon/index',
        add_url: 'addon/add',
        edit_url: 'addon/edit',
        delete_url: 'addon/delete',
        export_url: 'addon/export',
        modify_url: 'addon/modify',
    };

    var Controller = {

        index: function () {
            ea.init.where['scope']='local';
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                toolbar: ['refresh'/*[{
                    text: '本地安装',
                    url: 'addon/local',
                    method: 'none',
                    auth: 'import',
                    class: 'layui-btn layui-btn-danger layui-btn-sm',
                    icon: 'fa fa-upload ',
                    extend: 'data-upload="local_install" data-upload-number="one" data-upload-exts="zip"',
                }]*/],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'title', title: '名称',search:true},
                    {field: 'description', title: '插件描述',search:true},
                    {field: 'author', title: '作者',search:true},
                    {field: 'version', title: '版本号'},
                    {field: 'status', title: '状态', templet: function (data) {
                        if (data.install == 1) {
                            return ea.table.switch(data);
                        }
                        return '';
                    }, filter: 'status'},
                 /*   {field: 'create_time', title: '创建时间'},*/
                    {width: 250, title: '操作', templet: ea.table.tool,operat:[
                        [{
                            text: '安装',
                            url: 'addon/install?name={name}&no={no}',
                            method: 'post',
                            class: 'layui-btn layui-btn-warm layui-btn-sm',
                            auth: 'install',
                            hidden:function (data) {
                                if (data.install==1) return true;
                                return  false;
                            }
                        }], [{
                                class: 'layui-btn layui-btn-sm layui-btn-primary',
                                method: 'open',
                                text: '设置',
                                auth: 'config',
                                url: 'addon/config?name={name}&no={no}',
                                extend: '', icon: 'fa fa-cog',
                                hidden:function (data) {
                                    console.log(data);
                                    if (data.is_set==1 && data.install==1) return false;
                                    return  true;
                                }
                            }], [{
                                text: '更新',
                                url: 'addon/upgrade?name={name}&no={no}',
                                method: 'none',
                                class: 'layui-btn layui-btn-normal layui-btn-sm addon-upgrade',
                                auth: 'upgrade',
                                hidden:function (data) {
                                    if (data.net_version && data.version!=data.net_version && data.install==1 && !data.build){
                                        return false;
                                    }
                                    if (data.net_version && data.version!=data.net_version && data.install==1 && data.net_build>data.build){
                                        return false;
                                    }
                                    return  true;
                                }
                            }],
                            [{
                                text: '卸载',
                                url: 'addon/uninstall?name={name}',
                                method: 'post',
                                class: 'layui-btn layui-btn-danger layui-btn-sm',
                                auth: 'add',
                                hidden:function (data) {
                                    if (data.install==1) return false;
                                    return  true;
                                }
                            }]
                        ] },
                ]],
                done: function(res, curr, count){

                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                       if(ea.checkMobile()){
                                               ea.booksTemplet();
                                           }
                }
            });

            ea.listen();
        $(document).on('input', 'input[name="local_install"]', function () {
            console.log($(this).val());
        });

        //    data-upload-exts data-request="cloud/update" data-title="更新版本前，请先备份文件和数据库数据，因升级造成的数据问题有操作者承担"
            function upgrade(){
                var index=ea.msg.loading("正在更新中...",2);
                ea.request.post({
                    url: ea.url('cloud/update'),
                }, function (res) {
                    ea.msg.close(index);
                    if (res.code == 0) {
                        admin.msg.error(res.msg);
                        return;
                    }
                    admin.msg.success(res.msg, function () {
                    });
                    if (res.data.version) {
                        $("#version").html(res.data.version);
                    }
                    if (res.data.count>1) {
                        upgrade()
                    }
                })
            }
            $("#upgrade").click(function () {
                ea.msg.confirm("升级前，请务必先完整备份文件与数据库。因升级导致的任何数据问题，责任由操作者自行承担。", function () {
                    upgrade();
                });
            });
            $('body').on('click', '.addon-upgrade', function () {
                url = $(this).attr('data-url');
                ea.msg.confirm("升级前，请务必先完整备份文件与数据库。因升级导致的任何数据问题，责任由操作者自行承担。", function () {
                    upgradeAddons(url);
                });
            });

            function upgradeAddons(url){
                var index=ea.msg.loading("正在更新中...",2);
                ea.request.post({
                    url: ea.url(url),
                }, function (res) {
                    ea.msg.close(index);
                    if (res.code == 0) {
                        admin.msg.error(res.msg);
                        return;
                    }
                    admin.msg.success(res.msg, function () {
                    });
                    if (res.data.count>1) {
                        upgradeAddons(url)
                    }else{
                        layui.table.reload(init.table_render_id);
                    }
                })
            }




        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        }, config: function () {
            ea.listen();
        },
    };
    return Controller;
});