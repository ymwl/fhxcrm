
    var oStorage = {
        _Storage: window.localStorage,
        // 是否支持缓存
        isSupportStorage: function () {
            return this._Storage != undefined ? true : false;
        },
        // 是否有缓存
        hasItem: function (item) {
            return this._Storage.hasOwnProperty(item);
        },
        // 获取缓存key-value形式
        getItem: function (item) {
            // 将json字符串转成对象或数组
            return JSON.parse(this._Storage.getItem(item));
        },
        // 设置缓存key-value形式
        setItem: function (item, val) {
            // 将对象或数组转成json字符串
            return this._Storage.setItem(item, JSON.stringify(val));
        }
    };
// 表格载入成功时执行的函数
    function loadSuccess(tid, field) {
        // tid表示表格id，必须独一为二
        // field表示默认要隐藏的列，没有就填“”
        // 第一次载入时没有缓存，默认隐藏的列存入缓存中
        if( !(oStorage.isSupportStorage() && oStorage.hasItem(tid+"BsTable"))) {
            var aTmpConfig = [field];
            oStorage.setItem(tid+"BsTable",aTmpConfig);
        }

        // 从本地缓存中获取用户配置
        var aUserConfHideCols = oStorage.getItem(tid+"BsTable");
        if(aUserConfHideCols != "") {
            // 遍历用户配置中的数据
            aUserConfHideCols.forEach(function (sField) {
                // 通过hideColumn方法隐藏列
                $("#" + tid).bootstrapTable('hideColumn', sField);
            });
        }
    }
// 切换列时执行的函数 sId表格id,
    function columnSwitch(tid, sId) {
        // tid表示表格id，必须独一为二
        // sId表示特定id

        var aTmpConfig = [];
        // 找到特定id下的所有input框，遍历，i表示索引，v表示元素
        $("#" + sId + " .bootstrap-table ul.dropdown-menu").find("input").each(function (i,v) {

            // 判断未勾选的列，获得字段属性存入定义的数组中
            if(!$(v).is(':checked')) {
                aTmpConfig.push($(v).attr("data-field"));
            }
        });
        // 通过setItem方法，以key-value形式存入缓存中
        oStorage.setItem(tid+"BsTable",aTmpConfig);
    }


