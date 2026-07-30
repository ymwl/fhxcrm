var BASE_URL = document.scripts[document.scripts.length - 1].src.substring(0, document.scripts[document.scripts.length - 1].src.lastIndexOf("/") + 1);
window.BASE_URL = BASE_URL;
require.config({
    urlArgs: "v=" + CONFIG.VERSION,
    baseUrl: BASE_URL,
    paths: {
        "lang": [CONFIG.MODULEURL+"/ajax/lang?callback=define&controller="+CONFIG.JSPATH+"&lang="+CONFIG.LANG],
        "jquery": ["plugs/jquery-3.4.1/jquery-3.4.1.min"],
        "citypicker": ["plugs/city-picker/citypicker.min"],
        "layWebupload": ["plugs/webupload/layWebupload.min"],
        "webuploader": ["plugs/webupload/uploader/webuploader"],
        "layui": ["plugs/layui-v2.9/layui.min"],
        "easy-admin": ["plugs/easy-admin/easy-admin.min"],
        "jquery-particleground": ["plugs/jq-module/jquery.particleground.min"],
        "echarts": ["plugs/echarts/echarts.min"],
        "selectPage": ["plugs/lay-module/selectPage/selectpage.min"],
        "echarts-theme": ["plugs/echarts/echarts-theme.min"],
        "xm-select": ["plugs/xm-select/xm-select.min"],
        "miniAdmin": ["plugs/lay-module/layuimini/miniAdmin.min"],
        "miniMenu": ["plugs/lay-module/layuimini/miniMenu.min"],
        "miniTab": ["plugs/lay-module/layuimini/miniTab.min"],
        "miniTheme": ["plugs/lay-module/layuimini/miniTheme.min"],
        "treetable": ["plugs/lay-module/treetable-lay/treetable.min"],
        "tableSelect": ["plugs/lay-module/tableSelect/tableSelect.min"],
        "iconPickerFa": ["plugs/lay-module/iconPicker/iconPickerFa.min"],
        "autocomplete": ["plugs/lay-module/autocomplete/autocomplete.min"],
        "treeGrid": ["plugs/lay-module/extend/treeGrid.min"],
        "vue": ["plugs/vue-2.6.10/vue.min"],
        "qrcode": ["plugs/qrcode/qrcode.min"],
    },
    shim: {
        // qrcodejs 非 AMD 模块，通过 shim 导出全局 QRCode
        "qrcode": { exports: "QRCode" }
    }
});

// 路径配置信息
window.PATH_CONFIG  = {
    iconLess: BASE_URL + "plugs/font-awesome-4.7.0/less/variables.less",
};
// 初始化控制器对应的JS自动加载
if ("undefined" != typeof CONFIG.AUTOLOAD_JS && CONFIG.AUTOLOAD_JS) {
    require([BASE_URL + CONFIG.CONTROLLER_JS_PATH], function (Controller) {
        if (eval('Controller.' + CONFIG.ACTION)) {
            eval('Controller.' + CONFIG.ACTION + '()');
        }
    });
}

