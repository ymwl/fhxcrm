define(["jquery", "easy-admin",'xm-select', "treetable", "iconPickerFa", "autocomplete"], function ($, ea,xmSelect) {
    if (typeof String.prototype.indexOf !== 'function') {
        String.prototype.indexOf = function (str) {
            var i = this.length;
            while (i--) {
                if (this[i] === str) {
                    return i;
                }
            }
            return -1;
        };
    }
    var table = layui.table,
        treetable = layui.treetable,
        iconPickerFa = layui.iconPickerFa,
        autocomplete = layui.autocomplete;

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.fields/index'+parameter,
        add_url: 'system.fields/add'+parameter,
        delete_url: 'system.fields/delete',
        edit_url: 'system.fields/edit'+parameter,
        modify_url: 'system.fields/modify',
    };
    var Controller = {
        index: function () {
            ea.table.render({
                init: init,
                limits:[15,20,30,40,50,60,70,80,90,100],
                toolbar:['refresh','add'],limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                   /* {field: 'id', width: 80, title: 'ID',search:false},*/
                    {field: 'sort',  title: fy('Sort'), edit: 'text',width:100,search:false},
                    {field: 'name',  title: fy('Field Name'),width:150,search:true},
                    {field: 'xsname', width: 150, title: fy('Show')+' '+fy('Name'),search:false, edit: 'text'},
                    {field: 'width', width: 150, title: '宽度',search:false, edit: 'text'},
                    {field: 'field',width:200,  title: fy('Field')},
                    {field: 'list',width:120, title: fy('List display'),filter: "list",  search: false, templet: function (data, option){
                            if(data.status.indexOf('nolist')>-1){
                                return '-';
                            }else{
                                return ea.table.switch(data, option);
                            }
                        }},
                    {field: 'list_sort',width:120, title: '列表排序',filter: "list_sort",  search: false, templet: function (data, option){
                            if(data.status.indexOf('nolist')>-1){
                                return '-';
                            }else{
                                return ea.table.switch(data, option);
                            }
                        }},
                    {field: 'form',width:100, title: '表单显示', filter: "form", search: false, templet:function (data, option){
                if(data.status.indexOf("noedit")!==-1){
                    return '-';
                }else{return ea.table.switch(data, option);
                }
            }},
                    {field: 'search',width:100, title: fy('Search'),  filter: "search",search: false, selectList: {0: fy('Close'), 1:fy('Open')}, templet: function (data, option){
                            if(data.status.indexOf("nosearch")!==-1){
                                return '-';
                            }else{
                             return ea.table.switch(data, option);
                            }
                        }},
                    {field: 'export',width:100, title: '导入导出',  filter: "export",search: false, selectList: {0: fy('Close'), 1: fy('Open')}, templet: function (data, option){
                            if(data.status.indexOf("noimport")!==-1){
                                return '-';
                            }else{
                                return ea.table.switch(data, option);
                            }
                        }},
                    {field: 'require',width:100, title: '必填',  filter: "require",search: false,tips:'是|否', selectList: {0:'否', 1: '是'}, templet: function (data, option){
                            if(data.status.indexOf("norequire")!==-1){
                                return '-';
                            }
                            if (data.rule.indexOf('require') !== -1) {
                                data.require = 1;
                            } else {
                                data.require = 0;
                            }
                            return ea.table.switch(data, option);
                        }},{field: 'unique',width:100, title: '唯一',  filter: "unique",search: false,tips:'是|否', selectList: {0:'否', 1: '是'}, templet: function (data, option){
                            if(data.status.indexOf("nounique")!==-1){
                                return '-';
                            }
                            if (data.rule.indexOf('unique') !== -1) {
                                data.unique = 1;
                            } else {
                                data.unique = 0;
                            }
                            return ea.table.switch(data, option);
                        }},
                    {field: 'edit',width:100, title: '编辑',  filter: "edit",search: false,tips:'是|否',  selectList: {0:'是', 1: '否'}, templet: function (data, option){
                    if(data.field == 'id' || data.field == 'create_time' || data.field == 'update_time' || data.field == 'status' || data.field == 'sort' || data.field == 'issystem' || data.is_key || data.status.indexOf("noedit")!==-1){
                            return '-';
                        }
                            return ea.table.switch(data, option);
                        }},
                    {field: 'create_time', minWidth: 200, title: fy('Creation time'), search: 'range',templet:ea.table.datetime},
                    {
                        width: 150,
                        title: fy('Operate'),
                        templet: function (data, option){
                            if(data.issystem===1){
                                return '-';
                            }else{return ea.table.tool(data, option);
                            }
                        }, operat: ['edit', 'delete'],
fixed:"right"}
                ]],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
if(ea.checkMobile()){
ea.booksTemplet();//填充手机端视图模板
                    }
                }
            });
            // renderTable();
            $('body').on('click', '[data-treetable-refresh]', function () {
                renderTable();
            });
            $('body').on('click', '[data-treetable-delete]', function () {
                var tableId = $(this).attr('data-treetable-delete'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                url = url != undefined ? ea.url(url) : window.location.href;
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    ea.msg.error(fy('Please check the data to be deleted'));
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);
                });
                ea.msg.confirm(fy('Confirm the deletion')+'?', function () {
                    ea.request.post({
                        url: url,
                        data: {
                            id: ids
                        },
                    }, function (res) {
                        ea.msg.success(res.msg, function () {
                            renderTable();
                        });
                    });
                });
                return false;
            });

            ea.listen();
        },
        add: function () {
            iconPickerFa.render({
                elem: '#icon',
                url: PATH_CONFIG.iconLess,
                limit: 12,
                click: function (data) {
                    $('#icon').val('fa ' + data.icon);
                },
                success: function (d) {
                    console.log(d);
                }
            });
            autocomplete.render({
                elem: $('#href')[0],
                url: ea.url('system.menu/getMenuTips'),
                template_val: '{{d.node}}',
                template_txt: '{{d.node}} <span class=\'layui-badge layui-bg-gray\'>{{d.title}}</span>',
                onselect: function (resp) {
                }
            });

            this.common();
            ea.listen();
            this.multiple_select();
        },
        edit: function () {
            iconPickerFa.render({
                elem: '#icon',
                url: PATH_CONFIG.iconLess,
                limit: 12,
                click: function (data) {
                    $('#icon').val('fa ' + data.icon);
                },
                success: function (d) {
                    console.log(d);
                }
            });
            autocomplete.render({
                elem: $('#href')[0],
                url: ea.url('system.menu/getMenuTips'),
                template_val: '{{d.node}}',
                template_txt: '{{d.node}} <span class=\'layui-badge layui-bg-gray\'>{{d.title}}</span>',
                onselect: function (resp) {
                }
            });

            this.common();
            ea.listen();
            this.multiple_select();
        },
        common: function () {
            // 根据表单类型控制选项区域的显示/隐藏
            var toggleOptionItem = function () {
                var formtype = $('select[name="formtype"]').val();
                if (formtype === 'radio' || formtype === 'checkbox' || formtype === 'select') {
                    $('#option-item').show();
                } else {
                    $('#option-item').hide();
                }
            };
            // 根据表单类型控制联动区域的显示/隐藏
            var toggleLinkedField = function () {
                var formtype = $('select[name="formtype"]').val();
                var linkedTypes = ['aselect', 'selectgroup', 'popup_selection', 'lradio', 'lcheckbox', 'lselect', 'lselects', 'treecheckbox'];
                if (linkedTypes.indexOf(formtype) !== -1) {
                    $('#linked-field-area').show();
                } else {
                    $('#linked-field-area').hide();
                }
            };
            // 页面初始化时执行
            toggleOptionItem();
            toggleLinkedField();
            // 监听表单类型变化
            layui.form.on('select(formtype)', function () {
                toggleOptionItem();
                toggleLinkedField();
            });
        },

        multiple_select:function (){
            $(document).on('blur', '#name', function () {
                var name = $(this).val();
                var field = $('#field').val();
                if (!ea.empty(name) && ea.empty(field)) {
                    ea.request.ajax('post',{url:ea.url('ajax/pinyin'),data:{'name':name}},function (res){
                        $('#field').val(res.data);
                    });
                }
            });
            var lang='zn';
            if(CONFIG.LANG=="en-us"){
                lang='en';
            }
            var demo1 = xmSelect.render({
                el: '#multiple-select',
                language: lang,
                data: CONFIG.ruleLst,name:'rule'
            })
        }
    };
    return Controller;
});
