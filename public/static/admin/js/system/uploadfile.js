define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.uploadfile/index',
        add_url: 'system.uploadfile/add',
        edit_url: 'system.uploadfile/edit',
        delete_url: 'system.uploadfile/delete',
        modify_url: 'system.uploadfile/modify',
        export_url:'system.uploadfile/export',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,toolbar: ['refresh','delete'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'upload_type', minWidth: 80, title: fy("Storage location"), search: 'select', selectList: {'local': fy("local"), 'alioss': fy("Alibaba Cloud"), 'qnoss': fy("Qiniu Cloud"), ',txcos': fy("Tencent Cloud")}},
                    {field: 'url', minWidth: 80, search: false, title: fy("Picture information"), templet: ea.table.file},
                    {field: 'url', minWidth: 120, title: fy("Save address"), templet: ea.table.url},
                    {field: 'original_name', minWidth: 80, title: fy("Original file name")},
                    {field: 'mime_type', minWidth: 80, title: 'mime'+' '+fy("Type")},
                    {field: 'file_ext', minWidth: 80, title: fy("File suffix")},
                    {field: 'create_time', minWidth: 80, title: fy("Upload time"), search: 'range',templet:ea.table.datetime},
                    {width: 120, title: fy('Operate'), templet: ea.table.tool, operat: ['delete']}
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});
