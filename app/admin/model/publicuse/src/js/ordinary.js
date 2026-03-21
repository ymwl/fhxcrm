define(["jquery", "easy-admin", "treetable", "iconPickerFa", "autocomplete"], function ($, ea) {

    var table = layui.table,
        treetable = layui.treetable,
        iconPickerFa = layui.iconPickerFa,
        colorpicker = layui.colorpicker,
        laydate = layui.laydate,
        autocomplete = layui.autocomplete;

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'ces.ces/index'+parameter,
        add_url: 'ces.ces/add'+parameter,
        delete_url: 'ces.ces/delete',
        edit_url: 'ces.ces/edit',
        modify_url: 'ces.ces/modify',
        export_url: 'ces.ces/export',
    };
    var tree = layui.tree;
    var newmaowenke = new Newmaowenke(tree,ea,iconPickerFa,colorpicker,autocomplete,treetable,laydate);
    var Controller = {
        index: function () {

            ea.table.render({
                init: init,
                cellMinWidth:100,
                limits:[15,20,30,40,50,60,70,80,90,100],

                toolbar:['refresh',[

                ],'add','delete',
                ],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id',  title: 'ID',search:false,totalRowText: '合计',width:80},
                    {field: 'name', title: '单行文本',search:false,width:150},
                    {field: 'sort', title: fy('Sort'),search:false,width:150},
                    {field: 'yearys', title: '年月日时分秒',search:false},
                    {field: 'nianyue', title: '年月',search:false},
                    {field: 'times', title: '时间',search:false},
                    {field: 'danxuankuang', title: '单选框',selectList:["<span style='color: #FF0000'>你好<\/span>","<span style='color: #00CC00'>你不好<\/span>","<span style='color: #3399CC'>非常不好<\/span>"],search:false},
                    {field: 'duoxuankuang', title: '多选框',search:false},
                    {field: 'xialakuang', title: '下拉框',selectList:["<span style='color: #FF0000'>你好<\/span>","<span style='color: #00CC00'>你不好<\/span>","<span style='color: #3399CC'>非常不好<\/span>"],search:false},
                    {field: 'getldanxuankuangfield.name', title: 'l单选框.名称',search:false},
                    {field: 'getldanxuankuangfield.phone', title: 'l单选框.手机',search:false},
                    {field: 'lliandongduoxuan', title: '联动多选框',search:false},
                    {field: 'getlliandongxialafield.name', title: '联动下拉框.名称',search:false},
                    {field: 'getlliandongxialafield.phone', title: '联动下拉框.手机',search:false},
                    {field: 'getlliandongxialazufield.name', title: '联动下拉组.名称',search:false},
                    {field: 'getlliandongxialazufield.phone', title: '联动下拉组.手机',search:false},
                    {field: 'ajaxliandongshuzhuangduoxuan', title: 'ajax联动树状多选',search:false},
                    {field: 'getajaxliandongxialakuangfield.name', title: 'ajax联动下拉框.名称',search:false},
                    {field: 'getajaxliandongxialakuangfield.phone', title: 'ajax联动下拉框.手机',search:false},
                    {field: 'create_time', title: fy('Creation time'),search:'range',width:200,templet:ea.table.datetime},
                    {field: 'status', title: fy('Status'),  search: false, tips: '开启|关闭', templet: ea.table.switch,width:200},

                    {
                        width: 200,
                        title: '操作',
                        templet:"#right_button",
                    }
                ]],
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
                    ea.msg.error('请勾选需要删除的数据');
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);
                });
                ea.msg.confirm('确定删除？', function () {
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
            ea.table.listenSwitch({filter: 'status', url: init.modify_url});

            ea.table.listenEdit(init, 'currentTable', init.table_render_id, true);

            ea.listen();
        },
        add: function () {
            //解决树状多选框
            newmaowenke.treemore()


            var time = 0;
            //解决json数组
            newmaowenke.AddJson();
            //解决ajax_select
            newmaowenke.aselect();
            newmaowenke.AddIcon();
            newmaowenke.AddDateTime();
            newmaowenke.AddDate();
            newmaowenke.AddTime();
            ea.listen(function (data) {
                return data;
            }, function (res) {
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        },
        edit: function () {
            newmaowenke.treemore()


            var time = 0;
            //解决json数组
            newmaowenke.AddJson();
            //解决ajax_select
            newmaowenke.aselect();
            newmaowenke.AddIcon();
            newmaowenke.AddDateTime();
            newmaowenke.AddDate();
            newmaowenke.AddTime();
            ea.listen(function (data) {
                console.log(data)
                return data;
            }, function (res) {
                console.log(res);
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        },
        details:function(){
            newmaowenke.treemore()


            var time = 0;
            //解决json数组
            newmaowenke.AddJson();
            //解决ajax_select
            newmaowenke.aselect();
            newmaowenke.AddIcon();
            newmaowenke.AddDateTime();
            newmaowenke.AddDate();
            newmaowenke.AddTime();
            ea.listen(function (data) {
                console.log(data)
                return data;
            }, function (res) {
                console.log(res);
                ea.msg.success(res.msg, function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    parent.$('[data-treetable-refresh]').trigger("click");
                });
            });
        }
    };
    return Controller;
});
