define(["jquery", "lang"], function ($,Lang) {
    var form = layui.form,
        layer = layui.layer,
        table = layui.table,
        element = layui.element,
        laytpl = layui.laytpl,
        util = layui.util;
    /* layer.config({
         skin: 'layui-layer-easy'
     });*/
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',//
        upload_url: 'ajax/upload',
        upload_exts: 'doc,gif,ico,icon,jpg,mp3,mp4,p12,pem,png,rar,jpeg,csv,xls,xlsx,pdf',
    };
    var loadedStyles = {}; // 用于记录已加载的样式表
    var titleArray=[],layTableAllChooseHtml='';

    // 安全获取深层属性值，替代 eval
    var getFieldValue = function(data, field) {
        if (!field || data === null || data === undefined) return undefined;
        return field.split('.').reduce(function(obj, key) {
            return (obj !== null && obj !== undefined) ? obj[key] : undefined;
        }, data);
    };

    // HTML 转义，防止 XSS
    var escapeHtml = function(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    };

    // 弹窗尺寸计算
    var calcLayerSize = function($el) {
        var clienWidth = $el.attr('data-width'),
            clientHeight = $el.attr('data-height'),
            dataFull = $el.attr('data-full');
        if (dataFull === 'true') {
            return {width: '100%', height: '100%'};
        }
        if (clienWidth === undefined) {
            clienWidth = $(window).width() >= 1200 ? '1200px' : '100%';
        }
        if (clientHeight === undefined) {
            clientHeight = $(window).height() >= 680 ? '680px' : '100%';
        }
        return {width: clienWidth, height: clientHeight};
    };

    // 节流工具
    var throttleTimer = null;
    var throttledResize = function(fn, delay) {
        delay = delay || 200;
        return function() {
            var args = arguments, ctx = this;
            if (throttleTimer) return;
            throttleTimer = setTimeout(function() {
                throttleTimer = null;
                fn.apply(ctx, args);
            }, delay);
        };
    };
    window.admin = {
        init:{'where':{scope:1}},
        config: {
            shade: [0.02, '#000'],
        },
        laytable:table,
        fy:function () {
            var args = arguments,
                string = args[0],
                i = 1;
            string = string.toLowerCase();
            if (typeof Lang !== 'undefined' && typeof Lang[string] !== 'undefined') {
                if (typeof Lang[string] == 'object')
                    return Lang[string];
                string = Lang[string];
            } else if (string.indexOf('.') !== -1 && false) {
                var arr = string.split('.');
                var current = Lang[arr[0]];
                for (var i = 1; i < arr.length; i++) {
                    current = typeof current[arr[i]] != 'undefined' ? current[arr[i]] : '';
                    if (typeof current != 'object')
                        break;
                }
                if (typeof current == 'object')
                    return current;
                string = current;
            } else {
                string = args[0];
            }
            return string.replace(/%((%)|s|d)/g, function (m) {
                // m is the matched format, e.g. %s, %d
                var val = null;
                if (m[2]) {
                    val = m[2];
                } else {
                    val = args[i];
                    // A switch statement so that the formatter can be extended. Default is %s
                    switch (m) {
                        case '%d':
                            val = parseFloat(val);
                            if (isNaN(val)) {
                                val = 0;
                            }
                            break;
                    }
                    i++;
                }
                return val;
            });
        },daterange:function ($range){
            // 获取当前日期
            var today = new Date();
            if($range == 'last7Days'){
                // 获取最近7天的时间段
                var last7DaysStart = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                var last7DaysEnd = today;
                return  last7DaysRange = formatDate(last7DaysStart) + ' - ' + formatDate(last7DaysEnd);

            }
            if($range == 'last30Days'){
// 获取最近30天的时间段
                var last30DaysStart = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                var last30DaysEnd = today;
                return formatDate(last30DaysStart) + ' - ' + formatDate(last30DaysEnd);

            }
            if($range == 'lastMonth') {
// 获取上月的时间段
                var lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                var lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                return formatDate(lastMonthStart) + ' - ' + formatDate(lastMonthEnd);
            }
            if($range == 'thisMonth') {
// 获取本月的时间段
                var thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                var thisMonthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                return formatDate(thisMonthStart) + ' - ' + formatDate(thisMonthEnd);
            }
// 格式化日期函数
            function formatDate(date) {
                var year = date.getFullYear();
                var month = date.getMonth() + 1;
                var day = date.getDate();
                var hour = date.getHours();
                var minute = date.getMinutes();
                var second = date.getSeconds();
                return year + '-' + padZero(month) + '-' + padZero(day) + ' ' + padZero(hour) + ':' + padZero(minute) + ':' + padZero(second);
            }

// 补零函数
            function padZero(num) {
                return num < 10 ? '0' + num : num;
            }
        },
        url: function (url) {
            if (typeof url !== 'string') return url;
            if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0) return url;  // 增加上http开头的也直接返回
            if(url.substring(0, 1) === "/")return url;
            return CONFIG.MODULEURL + '/' + url;
        },
        attrUrl: function (url) {
            if (typeof url !== 'string') return url;
            if(url.substring(0, 1) === "/")return CONFIG.MY_PUBLIC+url;
            return url;
        },
        headers: function () {
            return {'X-CSRF-TOKEN': window.CONFIG.CSRF_TOKEN};
        },
        parseParams:function (data) {
            //JSON转URL参数
            try {
                var tempArr = [];
                for (var i in data) {
                    var key = encodeURIComponent(i);
                    var value = encodeURIComponent(data[i]);
                    tempArr.push(key + '=' + value);
                }
                return tempArr.join('&');
            } catch (err) {
                return '';
            }
        },
        getFormWhere:function (dataField){
            //获取表单数据对数据进行组装
            var formatFilter = {},
                formatOp = {};
            $.each(dataField, function (key, val) {
                if (val !== '') {
                    formatFilter[key] = val;
                    var op = $(document.getElementById('c-' + key)).attr('data-search-op');
                    op = op || '%*%';
                    formatOp[key] = op;
                }
            });
            where={filter: JSON.stringify(formatFilter), op: JSON.stringify(formatOp)}
            if(init.where){
                for (var key in init.where) {
                    if (init.where.hasOwnProperty(key)) {
                        where[key] = init.where[key];
                    }
                }
            }
            return where;
        },
        //js版empty，判断变量是否为空
        empty: function (r) {
            var n, t, e, f = [void 0, null, !1, 0, "", "0"];
            for (t = 0, e = f.length; t < e; t++) if (r === f[t]) return !0;
            if ("object" == typeof r) {
                for (n in r) if (r.hasOwnProperty(n)) return !1;
                return !0
            }
            return !1
        },
        isNumericString:function (str) {
        return /^[+-]?\d+(\.\d+)?$/.test(str);
    },
        randomChar:function (l) {
            //生成随机字符串
            var x = "123456789poiuytrewqasdfghjklmnbvcxzQWERTYUIPLKJHGFDSAZXCVBNM";
            var tmp = "";
            for (var i = 0; i < l; i++) {
                tmp += x.charAt(Math.ceil(Math.random() * 10000000000) % x.length);
            }
            return tmp;
        },
        strToArray:function (str) {
            return str ? str.replace(/[\s　]+/g, '').split(',') : '';
        },
        loadStyle:function(url) {
            // 如果已经加载过则不再重复加载
            if (loadedStyles[url]) return;
            // 标记为已加载
            loadedStyles[url] = true;
            var link = document.createElement('link')
            link.type = 'text/css'
            link.rel = 'stylesheet'
            link.href = url
            var head = document.getElementsByTagName('head')[0]
            head.appendChild(link)
        },
        checkAuth: function (node, elem) {
            if (CONFIG.IS_SUPER_ADMIN) {
                return true;
            }
            if ($(elem).attr('data-auth-' + node) === '1') {
                return true;
            } else {
                return false;
            }
        },
        parame: function (param, defaultParam) {
            return param !== undefined ? param : defaultParam;
        },
        request: {
            post: function (option, ok, no, ex) {
                return admin.request.ajax('post', option, ok, no, ex);
            },
            get: function (option, ok, no, ex) {
                return admin.request.ajax('get', option, ok, no, ex);
            },
            ajax: function (type, option, ok, no, ex) {
                type = type || 'get';
                option.url = option.url || '';
                option.url = typeof(option.url) === 'undefined' ?'':option.url;
                option.data = typeof(option.data) === 'undefined' ?{}:option.data;

                option.prefix = option.prefix !== undefined ? option.prefix : false;
                option.statusName = option.statusName || 'code';
                option.statusCode = option.statusCode !== undefined ? option.statusCode : 1;
                ok = ok || function (res) {
                };
                no = no || function (res) {
                    var msg = res.msg == undefined ? fy('The returned data is malformed') : res.msg;
                    admin.msg.error(msg);
                    return false;
                };
                ex = ex || function (res) {
                };
                if (option.url == '') {
                    admin.msg.error(fy('The request address cannot be empty'));
                    return false;
                }
                if (option.prefix == true) {
                    option.url = admin.url(option.url);
                }
                var index = admin.msg.loading(fy('Loading'));
                $.ajax({
                    url: option.url,
                    type: type,
                    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                    dataType: "json",
                    headers:admin.headers(),
                    data: option.data,
                    timeout: 60000000,
                    success: function (res) {
                        if(res.code==-200){
                            //    说明需要登录
                            window.top.location.href=res.url;
                        }
                        if (getFieldValue(res, option.statusName) === option.statusCode) {
                            return ok(res);
                        } else {
                            $("input[name='__token__']").val(res.data.token);
                            return no(res);
                        }
                    },
                    error: function (xhr, textstatus, thrown) {
                        if(xhr.responseJSON.msg){
                            var msg =xhr.responseJSON.msg;
                        }else{
                            var msg ='Status:' + xhr.status + '，' + xhr.statusText + '，'+fy('Please try again later')+'！';
                        }
                        admin.msg.error(msg, function () {
                            ex(this);
                        });
                        return false;
                    },
                    complete: function(){
                        admin.msg.close(index);
                        // @todo 刷新csrf-token
                    }
                });
            }
        },
        common: {
            parseNodeStr: function (node) {
                var array = node.split('/');
                $.each(array, function (key, val) {
                    if (key === 0) {
                        val = val.split('.');
                        $.each(val, function (i, v) {
                            val[i] = admin.common.humpToLine(v.replace(v[0], v[0].toLowerCase()));
                        });
                        val = val.join(".");
                        array[key] = val;
                    }
                });
                node = array.join("/");
                return node;
            },
            lineToHump: function (name) {
                return name.replace(/\_(\w)/g, function (all, letter) {
                    return letter.toUpperCase();
                });
            },
            humpToLine: function (name) {
                return name.replace(/([A-Z])/g, "_$1").toLowerCase();
            },
        },
        msg: {
            // 成功消息
            success: function (msg, callback) {
                if (callback === undefined) {
                    callback = function () {
                    }
                }
                var index = layer.msg(msg, {icon: 1, shade: admin.config.shade, scrollbar: false, time: 2000, shadeClose: true}, callback);
                return index;
            },
            // 失败消息
            error: function (msg, callback) {
                if (callback === undefined) {
                    callback = function () {
                    }
                }
                var index = layer.msg(msg, {icon: 2, shade: admin.config.shade, scrollbar: false, time: 4000, shadeClose: true}, callback);
                return index;
            },
            // 警告消息框
            alert: function (msg, callback) {
                var index = layer.alert(msg, {end: callback, scrollbar: false});
                return index;
            },
            // 对话框
            confirm: function (msg, ok, no) {
                var index = layer.confirm(msg, {title: fy('Operation confirmation'), btn: [fy('Confirm'), fy('Cancel')]}, function () {
                    typeof ok === 'function' && ok.call(this);
                }, function () {
                    typeof no === 'function' && no.call(this);
                    self.close(index);
                });
                return index;
            },
            // 消息提示
            tips: function (msg, time, callback) {
                var index = layer.msg(msg, {time: (time || 3) * 1000, shade: this.shade, end: callback, shadeClose: true});
                return index;
            },
            // 加载中提示
            loading: function (msg, callback) {
                var index = msg ? layer.msg(msg, {icon: 16, scrollbar: false, shade: this.shade, time: 0, end: callback}) : layer.load(2, {time: 0, scrollbar: false, shade: this.shade, end: callback});
                return index;
            },
            // 关闭消息框
            close: function (index) {
                return layer.close(index);
            }
        },
        table: {
            render: function (options) {
                if(options.init){
                    for (var key in options.init) {
                        if (options.init.hasOwnProperty(key)) {
                            init[key] = options.init[key];
                        }
                    }
                }
                options.init = options.init || init;
                init.where = options.where || {};

                options.modifyReload = admin.parame(options.modifyReload, false);
                options.elem = options.elem || options.init.table_elem;
                options.id = options.id || options.init.table_render_id;
                options.layFilter = options.id + '_LayFilter';
                options.url = options.url || options.init.index_url;
                options.url =admin.url( options.url);
                options.headers = admin.headers();
                options.page = admin.parame(options.page, true);
                options.search = admin.parame(options.search, true);
                options.skin = options.skin || '';
                options.limit = options.limit || 15;
                options.limits = options.limits || [10, 15, 20, 25, 50, 100,500,1000];
                options.cols = options.cols || [];
                options.defaultToolbar = options.defaultToolbar !== undefined ?options.defaultToolbar :['filter', 'print', {
                    title: fy('Search'),
                    layEvent: 'TABLE_SEARCH',
                    icon: 'layui-icon-search',
                    extend: 'data-table-id="' + options.id + '"'
                }];
                // 判断是否为移动端
                if (admin.checkMobile()) {
                    options.defaultToolbar = !options.search ? ['filter'] : ['filter', {
                        title: fy('Search'),
                        layEvent: 'TABLE_SEARCH',
                        icon: 'layui-icon-search',
                        extend: 'data-table-id="' + options.id + '"'
                    }];
                }

                // 判断元素对象是否有嵌套的
                options.cols = admin.table.formatCols(options.cols, options.init);

                // 初始化表格lay-filter
                $(options.elem).attr('lay-filter', options.layFilter);

                // 初始化表格搜索
                if (options.search === true) {
                    admin.table.renderSearch(options.cols, options.elem, options.id);
                }

                // 初始化表格左上方工具栏
                //layui写法
                if(typeof  options.toolbar ==='object' || typeof  options.toolbar ==='undefined'){
                    options.toolbar = options.toolbar || ['refresh', 'add', 'delete', 'export'];
                    options.toolbar = admin.table.renderToolbar(options.toolbar, options.elem, options.id, options.init);
                }

                // 判断是否有操作列表权限
                options.cols = admin.table.renderOperat(options.cols, options.elem);

                // 统一成功状态码为 1（与后端 BaseController::success 保持一致）
                options.response = $.extend({}, options.response, {statusCode: 1});

                // 初始化表格
                var newTable = table.render(options);

                // 监听表格搜索开关显示
                admin.table.listenToolbar(options.layFilter, options.id);

                // 监听表格开关切换
                admin.table.renderSwitch(options.cols, options.init, options.id, options.modifyReload);

                // 监听表格开关切换
                admin.table.listenEdit(options.init, options.layFilter, options.id, options.modifyReload);
                //QQ 36238 20285 新增监听tab
                element.on('tab(nav-tabs-index)', function(data){
                    var tableId = $(data.elem).attr('nav-tabs-index');
                    var field = $(this).data("field");
                    value=$(this).data("value");
                    init.where[field]=value;
                    admin.init.where[field]=value;
                    if (tableId === undefined || tableId === '' || tableId == null) {
                        tableId = init.table_render_id;
                    }
                    $('[lay-filter="'+tableId+'_filter"]').click()
                });
                // 新增排序
                if(!options.autoSort){
                    table.on('sort('+options.id+'_LayFilter)', function(obj){
                        admin.init.where['sort_by']=obj.field;
                        admin.init.where['sort_order']=obj.type;
                        table.reload(options.id, {
                            initSort: {
                                field:admin.init.where['sort_by'],
                                type: admin.init.where['sort_order']
                            }
                            ,where: admin.init.where
                        });
                    });
                }


                return newTable;
            },
            renderToolbar: function (data, elem, tableId, init) {
                data = data || [];
                var toolbarHtml = '';
                $.each(data, function (i, v) {
                    if (v === 'refresh') {
                        toolbarHtml += ' <button type="button" class="layui-btn layui-btn-sm layuimini-btn-primary" data-table-refresh="' + tableId + '"><i class="fa fa-refresh"></i> </button>\n';
                    } else if (v === 'add') {
                        if (admin.checkAuth('add', elem)) {
                            toolbarHtml += '<button type="button" class="layui-btn layui-btn-normal layui-btn-sm" data-open="' + init.add_url + '" data-title="'+fy('Add')+'"><i class="fa fa-plus"></i>'+fy('Add')+'</button>\n';
                        }
                    } else if (v === 'delete') {
                        if (admin.checkAuth('delete', elem)) {
                            toolbarHtml += '<button type="button" class="layui-btn layui-btn-sm layui-btn-danger" data-url="' + init.delete_url + '" data-table-delete="' + tableId + '"><i class="fa fa-trash-o"></i>'+fy('Delete')+'</button>\n';
                        }
                    } else if (v === 'export') {
                        if (admin.checkAuth('export', elem) && init.export_url) {
                            toolbarHtml += '<button type="button" class="layui-btn layui-btn-sm layui-btn-success easyadmin-export-btn" data-url="' + init.export_url + '" data-table-export="' + tableId + '"><i class="fa fa-file-excel-o"></i> '+fy('Export')+'</button>\n';
                        }
                    } else if (typeof v === "object") {
                        $.each(v, function (ii, vv) {
                            vv.class = vv.class || '';
                            vv.icon = vv.icon || '';
                            vv.auth = vv.auth || '';
                            vv.url = vv.url || '';
                            vv.method = vv.method || 'open';
                            vv.title = vv.title || vv.text;
                            vv.text = vv.text || vv.title;
                            vv.extend = vv.extend || '';
                            vv.checkbox = vv.checkbox || false;
                            if (admin.checkAuth(vv.auth, elem)) {
                                toolbarHtml += admin.table.buildToolbarHtml(vv, tableId);
                            }
                        });
                    }
                });
                return '<div>' + toolbarHtml + '</div>';
            },
            renderSearch: function (cols, elem, tableId) {
                // TODO 只初始化第一个table搜索字段，如果存在多个(绝少数需求)，得自己去扩展
                cols = cols[0] || {};
                var newCols = [];
                var formHtml = '';
                $.each(cols, function (i, d) {
                    // d.field = d.field || false;
                    d.fieldAlias = admin.parame(d.fieldAlias, d.field);
                    d.title = d.title || d.field || '';
                    d.selectList = d.selectList || {};
                    // d.search = admin.parame(d.search, true);
                    d.searchTip = d.searchTip || fy('Please enter') + d.title || '';
                    d.searchValue = d.searchValue || '';
                    d.searchOp = d.searchOp || '%*%';
                    d.timeType = d.timeType || 'datetime';
                    if ( d.search) {
                        switch (d.search) {
                            case true:
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + escapeHtml(d.title) + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + escapeHtml(d.fieldAlias) + '" name="' + escapeHtml(d.fieldAlias) + '" data-search-op="' + escapeHtml(d.searchOp) + '" value="' + escapeHtml(d.searchValue) + '" placeholder="' + escapeHtml(d.searchTip) + '" class="layui-input">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            //    新增城市渲染
                            case 'city':
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + escapeHtml(d.title) + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + escapeHtml(d.fieldAlias) + '" name="' + escapeHtml(d.fieldAlias) + '" data-search-op="' + escapeHtml(d.searchOp) + '" value="' + escapeHtml(d.searchValue) + '" placeholder="' + escapeHtml(d.searchTip) + '" class="layui-input" data-toggle="city-picker">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case  'select':
                                d.searchOp = '=';
                                var selectHtml = '';
                                if(admin.empty(d.selectList) || typeof d.selectList=='string'){
                                    break;
                                }
                                $.each(d.selectList, function (sI, sV) {
                                    var selected = '';
                                    if (sI === d.searchValue) {
                                        selected = 'selected=""';
                                    }
                                    selectHtml += '<option value="' + escapeHtml(sI) + '" ' + selected + '>' + escapeHtml(sV) + '</option>/n';
                                });
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + escapeHtml(d.title) + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<select class="layui-select" id="c-' + escapeHtml(d.fieldAlias) + '" name="' + escapeHtml(d.fieldAlias) + '"  data-search-op="' + escapeHtml(d.searchOp) + '" >\n' +
                                    '<option value="">- '+fy('All')+' -</option> \n' +
                                    selectHtml +
                                    '</select>\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'range':
                                d.searchOp = 'range';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + escapeHtml(d.title) + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + escapeHtml(d.fieldAlias) + '" name="' + escapeHtml(d.fieldAlias) + '"  data-search-op="' + escapeHtml(d.searchOp) + '"  value="' + escapeHtml(d.searchValue) + '" placeholder="' + escapeHtml(d.searchTip) + '" class="layui-input" autocomplete="off">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'between':
                                d.searchOp = 'between';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + escapeHtml(d.title) + '</label>\n' +
                                    '<div class="layui-input-inline between">\n' +
                                    '<input id="c-' + escapeHtml(d.fieldAlias) + '[0]" name="' + escapeHtml(d.fieldAlias) + '[0]"  data-search-op="' + escapeHtml(d.searchOp) + '"  value="' + escapeHtml(d.searchValue) + '" placeholder="开始" class="layui-input" autocomplete="off">\n' +
                                    '- <input id="c-' + escapeHtml(d.fieldAlias) + '[1]" name="' + escapeHtml(d.fieldAlias) + '[1]"  data-search-op="' + escapeHtml(d.searchOp) + '"  value="' + escapeHtml(d.searchValue) + '" placeholder="结束" class="layui-input" autocomplete="off">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'time':
                                d.searchOp = '=';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + escapeHtml(d.title) + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + escapeHtml(d.fieldAlias) + '" name="' + escapeHtml(d.fieldAlias) + '"  data-search-op="' + escapeHtml(d.searchOp) + '"  value="' + escapeHtml(d.searchValue) + '" placeholder="' + escapeHtml(d.searchTip) + '" class="layui-input" autocomplete="off">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            default:
                                formHtml +=d.search
                        }
                        newCols.push(d);
                    }
                });
                if (formHtml !== '') {

                    $(elem).before('<fieldset id="searchFieldset_' + tableId + '" class="table-search-fieldset layui-hide">\n' +
                        '<legend>'+fy('Conditional Search')+'</legend>\n' +
                        '<form class="layui-form layui-form-pane form-search" lay-filter="' + tableId + '_form">\n' +
                        formHtml +
                        '<div class="layui-form-item layui-inline" style="margin-left: 115px">\n' +
                        '<button type="submit" class="layui-btn layui-btn-normal" data-type="tableSearch" data-table="' + tableId + '" lay-submit lay-filter="' + tableId + '_filter"> '+fy('Search')+'</button>\n' +
                        '<button type="reset" class="layui-btn layui-btn-primary" data-table-reset="' + tableId + '"> '+fy('Reset')+'</button>\n' +
                        ' </div>' +
                        '</form>' +
                        '</fieldset>');

                    admin.table.listenTableSearch(tableId);

                    // 初始化form表单
                    form.render();
                    var searchFieldsetId = 'searchFieldset_' + tableId;
                    $.each(newCols, function (ncI, ncV) {
                        var lang='cn';
                        if(CONFIG.LANG=="en-us"){
                            lang='en';
                        }
                        if (ncV.search === 'range') {
                            layui.laydate.render({range: true,'lang':lang, type: ncV.timeType, elem: '#' + searchFieldsetId + ' [name="' + ncV.fieldAlias + '"]'});
                        }
                        if (ncV.search === 'time') {
                            layui.laydate.render({type: ncV.timeType,'lang':lang, elem: '#' + searchFieldsetId + ' [name="' + ncV.fieldAlias + '"]'});
                        }
                    });
                }
            },
            renderSwitch: function (cols, tableInit, tableId, modifyReload) {
                tableInit.modify_url = tableInit.modify_url || false;
                cols = cols[0] || {};
                tableId = tableId || init.table_render_id;
                if (cols.length > 0) {
                    $.each(cols, function (i, v) {
                        v.filter = v.filter || false;
                        if (v.filter !== false && tableInit.modify_url !== false) {
                            admin.table.listenSwitch({filter: v.filter, url: tableInit.modify_url, tableId: tableId, modifyReload: modifyReload});
                        }
                    });
                }
            },
            renderOperat:function(data, elem) {
                // for (dk in data) {
                for (var i=0;i<data.length;i++) {
                    var col = data[i];
                    var operat = col[col.length - 1].operat;
                    if (operat !== undefined) {
                        var check = false;
                        for (key in operat) {
                            var item = operat[key];
                            if (typeof item === 'string') {
                                if (admin.checkAuth(item, elem)) {
                                    check = true;
                                    break;
                                }
                            } else {
                                for (k in item) {
                                    var v = item[k];
                                    if (v.auth !== undefined && admin.checkAuth(v.auth, elem)) {
                                        check = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if (!check) {
                            data[i].pop()
                        }
                    }
                }
                return data;
            },
            buildToolbarHtml: function (toolbar, tableId) {
                var html = '';
                toolbar.class = toolbar.class || '';
                toolbar.icon = toolbar.icon || '';
                toolbar.auth = toolbar.auth || '';
                toolbar.url = toolbar.url || '';
                toolbar.extend = toolbar.extend || '';
                toolbar.method = toolbar.method || 'open';
                toolbar.field = toolbar.field || 'id';
                toolbar.title = toolbar.title || toolbar.text;
                toolbar.text = toolbar.text || toolbar.title;
                toolbar.checkbox = toolbar.checkbox || false;

                var formatToolbar = toolbar;
                formatToolbar.icon = formatToolbar.icon !== '' ? '<i class="' + formatToolbar.icon + '"></i> ' : '';
                formatToolbar.class = formatToolbar.class !== '' ? 'class="' + formatToolbar.class + '" ' : '';
                if (toolbar.method === 'open') {
                    formatToolbar.method = formatToolbar.method !== '' ? 'data-open="' + formatToolbar.url + '" data-title="' + formatToolbar.title + '" ' : '';
                } else if (toolbar.method === 'none'){ // 常用于与extend配合，自定义监听按钮
                    formatToolbar.method = 'data-url="' + formatToolbar.url+'"';
                } else {
                    formatToolbar.method = formatToolbar.method !== '' ? 'data-request="' + formatToolbar.url + '" data-title="' + formatToolbar.title + '" ' : '';
                }
                formatToolbar.checkbox = toolbar.checkbox ? ' data-checkbox="true" ' : '';
                formatToolbar.tableId = tableId !== undefined ? ' data-table="' + tableId + '" ' : '';
                html = '<button type="button"' + formatToolbar.class + formatToolbar.method + formatToolbar.extend + formatToolbar.checkbox +  formatToolbar.tableId + '>' + formatToolbar.icon + formatToolbar.text + '</button>';

                return html;
            },
            buildOperatHtml: function (operat) {
                var html = '';
                operat.class = operat.class || '';
                operat.icon = operat.icon || '';
                operat.auth = operat.auth || '';
                operat.url = operat.url || '';
                operat.extend = operat.extend || '';
                operat.method = operat.method || 'open';
                operat.field = operat.field || 'id';
                operat.title = operat.title || operat.text;
                operat.text = operat.text || operat.title;

                var formatOperat = operat;
                formatOperat.icon = formatOperat.icon !== '' ? '<i class="' + formatOperat.icon + '"></i> ' : '';
                formatOperat.class = formatOperat.class !== '' ? 'class="' + formatOperat.class + '" ' : '';
                if (operat.method === 'open') {
                    formatOperat.method = formatOperat.method !== '' ? 'data-open="' + formatOperat.url + '" data-title="' + formatOperat.title + '" ' : '';
                } else if (operat.method === 'none'){ // 常用于与extend配合，自定义监听按钮
                    formatOperat.method = 'data-url="' +  formatOperat.url+'"';
                } else {
                    formatOperat.method = formatOperat.method !== '' ? 'data-request="' + formatOperat.url + '" data-title="' + formatOperat.title + '" ' : '';
                }
                html = '<a ' + formatOperat.class + formatOperat.method + formatOperat.extend + '>' + formatOperat.icon + formatOperat.text + '</a>';
                return html;
            },
            toolSpliceUrl: function(url, field, data) {
                if(url === undefined || url === ''){return '';}
                url = !url.match(/\{.*?\}/i) ? url + (url.match(/(\?)+/) ? "&"+field+"=" : "?"+field+"=" ) + '{'+field+'}' : url;
                url = url.replace(/\{(.*?)\}/gi, function (matched) {
                    matched = matched.substring(1, matched.length - 1);
                    if (matched.indexOf(".") !== -1) {
                        var temp = data;
                        var arr = matched.split(/\./);
                        for (var i = 0; i < arr.length; i++) {
                            if (typeof temp[arr[i]] !== 'undefined') {
                                temp = temp[arr[i]];
                            }
                        }
                        return typeof temp === 'object' ? '' : temp;
                    }
                    return data[matched];
                });
                return url;
            },
            formatCols: function (cols, init) {
                for (i in cols) {
                    var col = cols[i];
                    for (index in col) {
                        var val = col[index];

                        // 判断是否包含初始化数据
                        if (val.init === undefined) {
                            cols[i][index]['init'] = init;
                        }

                        //解决PHP格式化无法格式化变量问题 技术驱动微信:zrwx978
                        if (val.templet === 'ea.table.date') {
                            cols[i][index]['templet'] = this.date;
                            //凡是时间则参与排序
                            cols[i][index]['sort'] = true;
                        }else if (val.templet === 'ea.table.datetime') {
                            cols[i][index]['templet'] = this.datetime;
                            //凡是时间则参与排序
                            cols[i][index]['sort'] = true;
                        }else if (val.templet === 'ea.table.tel') {
                            cols[i][index]['templet'] = this.tel;
                        }else if (val.templet === 'ea.table.switch') {
                            cols[i][index]['templet'] = this.switch;
                        }else if (val.templet === 'ea.table.select') {
                            cols[i][index]['templet'] = this.select;
                        }else if (val.templet === 'ea.table.image') {
                            cols[i][index]['templet'] = this.image;
                        }else if (val.templet === 'ea.table.file') {
                            cols[i][index]['templet'] = this.file;
                        }else if (val.templet === 'ea.table.url') {
                            cols[i][index]['templet'] = this.url;
                        }
                        // 格式化列操作栏
                        if (val.templet === admin.table.tool && val.operat === undefined) {
                            cols[i][index]['operat'] = ['edit', 'delete'];
                        }

                        // 判断是否包含开关组件
                        if (val.templet === admin.table.switch && val.filter === undefined) {
                            cols[i][index]['filter'] = val.field;
                        }

                        // 判断是否含有搜索下拉列表
                        if (val.selectList !== undefined && val.search === undefined) {
                            cols[i][index]['search'] = 'select';
                        }

                        // 判断是否初始化对齐方式
                        if (val.align === undefined) {
                            cols[i][index]['align'] = 'center';
                        }

                        // 部分字段开启排序 qq3623820285
                        var sortDefaultFields = ['id', 'sort'];
                        if (val.sort === undefined && sortDefaultFields.indexOf(val.field) >= 0) {
                            cols[i][index]['sort'] = true;
                        }

                        // 初始化图片高度
                        if (val.templet === admin.table.image && val.imageHeight === undefined) {
                            cols[i][index]['imageHeight'] = 25;
                        }

                        // 判断是否多层对象
                        if (val.field !== undefined && val.field.split(".").length > 1) {
                            if (val.templet === undefined) {
                                cols[i][index]['templet'] = admin.table.value;
                            }
                        }

                        // 判断是否列表数据转换
                        if (val.selectList !== undefined && val.templet === undefined) {
                            cols[i][index]['templet'] = admin.table.list;
                        }

                    }
                }
                return cols;
            },
            tool: function (data) {
                option=data.LAY_COL;
                option.operat = typeof(option.operat) === 'undefined' ?['edit', 'delete']:option.operat;
                var elem = option.init.table_elem || init.table_elem;
                var html = '';
                $.each(option.operat, function (i, item) {
                    if (typeof item === 'string') {
                        switch (item) {
                            case 'edit':
                                var operat = {
                                    class: 'layui-btn layui-btn-success layui-btn-xs',
                                    method: 'open',
                                    field: 'id',
                                    icon: '',
                                    text: fy('Edit'),
                                    title: fy('Edit the information'),
                                    auth: 'edit',
                                    url: option.init.edit_url,
                                    extend:option.extend || ""
                                };
                                operat.url = admin.table.toolSpliceUrl(operat.url, operat.field, data);
                                if (admin.checkAuth(operat.auth, elem)) {
                                    html += admin.table.buildOperatHtml(operat);
                                }
                                break;
                            case 'delete':
                                var operat = {
                                    class: 'layui-btn layui-btn-danger layui-btn-xs',
                                    method: 'get',
                                    field: 'id',
                                    icon: '',
                                    text: fy('Delete'),
                                    title: fy('Confirm the deletion')+'?',
                                    auth: 'delete',
                                    url: option.init.delete_url,
                                    extend: ""
                                };
                                operat.url = admin.table.toolSpliceUrl(operat.url, operat.field, data);
                                if (admin.checkAuth(operat.auth, elem)) {
                                    html += admin.table.buildOperatHtml(operat);
                                }
                                break;
                        }

                    } else if (typeof item === 'object') {
                        $.each(item, function (i, operat) {

                            if(typeof operat.hidden==='function'){
                                if(operat.hidden(data)){
                                    //结束本次循环
                                    return true;
                                }
                            }
                            operat.class = operat.class || '';
                            operat.icon = operat.icon || '';
                            operat.auth = operat.auth || '';
                            operat.url = operat.url || '';
                            operat.method = operat.method || 'open';
                            operat.field = operat.field || 'id';
                            operat.title = operat.title || operat.text;
                            operat.text = operat.text || operat.title;
                            operat.extend = operat.extend || '';

                            // 自定义表格opreat按钮的弹窗标题风格，extra是表格里的欲加入标题中的字段
                            operat.extra = operat.extra || '';
                            if (data[operat.extra] !== undefined) {
                                operat.title = data[operat.extra] + ' - ' + operat.title;
                            }

                            operat.url = admin.table.toolSpliceUrl(operat.url, operat.field, data);
                            // 支持 title 中的 {字段名} 占位符动态解析（与 url 的 {id} 解析机制一致）
                            operat.title = operat.title.replace(/\{(.*?)\}/gi, function (matched) {
                                matched = matched.substring(1, matched.length - 1);
                                return data[matched] !== undefined ? data[matched] : '';
                            });
                            if (admin.checkAuth(operat.auth, elem)) {
                                html += admin.table.buildOperatHtml(operat);
                            }
                        });
                    }
                });
                return html;
            },
            list: function (data) {
                option=data.LAY_COL;
                option.selectList = option.selectList || {};
                var field = option.field,
                    value = getFieldValue(data, field);
                if (option.selectList[value] === undefined || option.selectList[value] === '' || option.selectList[value] === null) {
                    return value;
                } else {
                    return option.selectList[value];
                }
            },
            image: function (data) {
                option=data.LAY_COL;
                option.imageWidth = option.imageWidth || 100;
                option.imageHeight = option.imageHeight || 30;
                option.imageSplit = option.imageSplit || '|';
                option.imageJoin = option.imageJoin || '<br>';
                option.title = option.title || option.field;
                var field = option.field,
                    title = data[option.title];
                try {
                    var value = getFieldValue(data, field);
                } catch (e) {
                    var value = undefined;
                }
                if (value === undefined || value === null) {
                    return '';
                } else {
                    var values = value.split(option.imageSplit),
                        valuesHtml = [];

                    for(var i = 0; i < values.length; i++) {
                        if(i in values) {
                            valuesHtml.push('<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + admin.attrUrl(values[i]) + '" data-image="' + escapeHtml(title) + '">');
                        }
                    }
                    return valuesHtml.join(option.imageJoin);
                }
            },file: function (data) {
                option=data.LAY_COL;
                option.imageWidth = option.imageWidth || 100;
                option.imageHeight = option.imageHeight || 30;
                option.imageSplit = option.imageSplit || '|';
                option.imageJoin = option.imageJoin || '';
                option.title = option.title || option.field;
                var field = option.field,
                    title = data[option.title];
                try {
                    var value = getFieldValue(data, field);
                } catch (e) {
                    var value = undefined;
                }

                if (value === undefined || value === null || $.trim(value)=='') {
                    return '';
                } else {
                    var values = value.split(option.imageSplit),
                        valuesHtml = [];

                    for(var i = 0; i < values.length; i++) {

                        if(i in values) {
                            // 判断文件后缀
                            var ext = values[i].substring(values[i].lastIndexOf(".") + 1).toLowerCase();
                            if(ext == 'jpg' || ext == 'png' || ext == 'gif' || ext == 'jpeg' || ext == 'ico') {
                                valuesHtml.push('<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + admin.attrUrl(values[i]) + '" data-image="' + escapeHtml(title) + '">');
                            } else {
                                if(ext == 'doc' || ext == 'file' || ext == 'image' || ext == 'mp3' || ext == 'mp4' || ext == 'pdf' || ext == 'ppt' || ext == 'rar' || ext == 'txt' || ext == 'visio' || ext == 'xls' || ext == 'zip' || ext == 'xlsx'){
                                    var icons=CONFIG.MY_PUBLIC+'/static/admin/images/upload-icons/'+ext+'.png';
                                }else{
                                    var icons=CONFIG.MY_PUBLIC+'/static/admin/images/upload-icons/file.png';
                                }
                                valuesHtml.push('<a href="' + admin.attrUrl(values[i])+ '" target="_blank" class="file"><img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="'+icons+'"></a>')
                            }

                        }
                    }
                    return valuesHtml.join(option.imageJoin);
                }
            },
            url: function (data) {
                option=data.LAY_COL;
                var field = option.field,
                    value = getFieldValue(data, field);
                if (value === undefined){
                    return '';
                }
                return '<a class="layuimini-table-url" href="' + escapeHtml(value) + '" target="_blank" class="label bg-green">' + escapeHtml(value) + '</a>';
            },
            switch: function (data) {
                option=data.LAY_COL;
                var field = option.field;
                option.filter = option.filter || option.field || null;
                option.checked = option.checked || 1;
                option.tips = option.tips || fy('Open')+'|'+fy('Close');
                var value = getFieldValue(data, field);
                if (value === undefined) value = undefined;
                var checked = value === option.checked ? 'checked' : '';
                return laytpl('<input type="checkbox" name="' + option.field + '" value="' + data.id + '" lay-skin="switch" lay-text="' + option.tips + '" lay-filter="' + option.filter + '" ' + checked + ' >').render(data);
            },
            price: function (data) {
                option=data.LAY_COL;
                var field = option.field;
                var value = getFieldValue(data, field);
                if (value === undefined) value = undefined;
                return '<span>￥' + value + '</span>';
            },
            percent: function (data) {
                option=data.LAY_COL;
                var field = option.field;
                var value = getFieldValue(data, field);
                if (value === undefined) value = undefined;
                return '<span>' + value + '%</span>';
            },
            icon: function (data) {
                option=data.LAY_COL;
                var field = option.field;
                var value = getFieldValue(data, field);
                if (value === undefined) value = undefined;
                return '<i class="' + value + '"></i>';
            },
            text: function (data) {
                option=data.LAY_COL;
                var field = option.field;
                var value = getFieldValue(data, field);
                if (value === undefined) value = '';
                return '<span class="line-limit-length">' + value + '</span>';
            },
            value: function (data) {
                option=data.LAY_COL;
                var field = option.field;
                var value = getFieldValue(data, field);
                if (value === undefined) value = '';
                return '<span>' + value + '</span>';
            },
            //时间戳转日期
            date: function (data) {
                option=data.LAY_COL;
                var field = option.field, value = '';
                value = getFieldValue(data, field);
                if (value>0){
                    value = util.toDateString(value * 1000, option.format || 'yyyy-MM-dd');
                }else if(value==null || value==0){
                    value = '';
                }
                return '<span>' + value + '</span>';
            },//时间戳转年月
            month: function (data) {
                option=data.LAY_COL;
                var field = option.field, value = '';
                value = getFieldValue(data, field);
                if (value>0){
                    value = util.toDateString(value * 1000, option.format || 'yyyy-MM');
                }else if(value==null || value==0){
                    value = '';
                }
                return '<span>' + value + '</span>';
            },//时间戳转日期时间
            datetime: function (data) {
                option=data.LAY_COL;
                var field = option.field, value = '';
                value = getFieldValue(data, field);
                if (value>0){
                    value = util.toDateString(value * 1000, option.format || 'yyyy-MM-dd HH:mm:ss');
                }else if(value==null || value==0){
                    value = '';
                }
                return '<span>' + value + '</span>';
            },
            //格式拨打电话链接
            tel:function (data){
                option=data.LAY_COL;
                var field = option.field, value = '';
                value = getFieldValue(data, field);
                // 判断是否有* 有*则直接输出
                if (value.indexOf('*')>-1){
                //     找到*
                }else if (value){
                    value = '<a  target="_top" href="tel:'+escapeHtml(value)+'">'+escapeHtml(value)+' </a>';
                }else{
                    value = '';
                }
                return '<span>' + value + '</span>';
            },
            select: function (data) {
                option=data.LAY_COL;
                var field = option.field, value = '';
                value = getFieldValue(data, field);
                value=option.selectList[value]
                if(value===undefined){
                    value=getFieldValue(data, field);
                    if(value===null || value===undefined){
                        value='';
                    }
                }
                return '<span>' + value + '</span>';
            },
            listenTableSearch: function (tableId) {
                //监听搜索
                form.on('submit(' + tableId + '_filter)', function (data) {
                    admin.init.where=admin.getFormWhere(data.field);
                    table.reload(tableId, {
                        page: {
                            curr: 1
                        }
                        , where: admin.init.where
                    }, false);
                    return false;
                });
            },
            listenSwitch: function (option, ok) {
                option.filter = option.filter || '';
                option.url = option.url || '';
                option.field = option.field || option.filter || '';
                option.tableId = option.tableId || init.table_render_id;
                option.modifyReload = option.modifyReload || false;
                form.on('switch(' + option.filter + ')', function (obj) {
                    var checked = obj.elem.checked ? 1 : 0;
                    if (typeof ok === 'function') {
                        return ok({
                            id: obj.value,
                            checked: checked,
                        });
                    } else {
                        var data = {
                            id: obj.value,
                            field: option.field,
                            value: checked,
                        };
                        admin.request.post({
                            url: option.url,
                            prefix: true,
                            data: data,
                        }, function (res) {
                            if (option.modifyReload) {
                                table.reload(option.tableId);
                            }
                        }, function (res) {
                            admin.msg.error(res.msg, function () {
                                table.reload(option.tableId);
                            });
                        }, function () {
                            table.reload(option.tableId);
                        });
                    }
                });
            },
            listenToolbar: function (layFilter, tableId) {
                table.on('toolbar(' + layFilter + ')', function (obj) {

                    // 搜索表单的显示
                    switch (obj.event) {
                        case 'TABLE_SEARCH':
                            var searchFieldsetId = 'searchFieldset_' + tableId;
                            var _that = $("#" + searchFieldsetId);
                            if (_that.hasClass("layui-hide")) {
                                _that.removeClass('layui-hide');
                            } else {
                                _that.addClass('layui-hide');
                            }
                            break;
                    }
                });
            },
            listenEdit: function (tableInit, layFilter, tableId, modifyReload) {
                tableInit.modify_url = tableInit.modify_url || false;
                tableId = tableId || init.table_render_id;
                if (tableInit.modify_url !== false) {
                    table.on('edit(' + layFilter + ')', function (obj) {
                        var value = obj.value,
                            data = obj.data,
                            id = data.id,
                            field = obj.field;
                        var _data = {
                            id: id,
                            field: field,
                            value: value,
                        };
                        admin.request.post({
                            url: tableInit.modify_url,
                            prefix: true,
                            data: _data,
                        }, function (res) {
                            if (modifyReload) {
                                table.reload(tableId);
                            }
                        }, function (res) {
                            admin.msg.error(res.msg, function () {
                                table.reload(tableId);
                            });
                        }, function () {
                            table.reload(tableId);
                        });
                    });
                }
            },
        },
        checkMobile: function () {
            var userAgentInfo = navigator.userAgent;
            var mobileAgents = ["Android", "iPhone", "SymbianOS", "Windows Phone", "iPad", "iPod"];
            var mobile_flag = false;
            //根据userAgent判断是否是手机
            for (var v = 0; v < mobileAgents.length; v++) {
                if (userAgentInfo.indexOf(mobileAgents[v]) > 0) {
                    mobile_flag = true;
                    break;
                }
            }
            var screen_width = window.screen.width;
            //根据屏幕分辨率判断是否是手机
            if (screen_width < 600) {
                mobile_flag = true;
            }
            return mobile_flag;
        },//自定义视图模板
        booksTemplet:function(table_render_id){
            table_render_id=table_render_id?table_render_id:init.table_render_id;
            $('.book-box').remove();
            //用户编号数组
            var html='<div class="book-box">' ,tmp= '';
            if(admin.empty(titleArray)){
                $('.layui-table-box .layui-table-header tr th').each(function (index) {
                    tmp=$.trim($(this).find('div').html());
                    if(tmp.indexOf('lay-filter="layTableAllChoose"')>-1){
                        layTableAllChooseHtml=fy('Select all')+'：'+tmp;
                        $('button[data-table-refresh="currentTableRenderId"]').before(layTableAllChooseHtml);
                        titleArray.push(fy('Choice'));
                    }else{
                        titleArray.push(tmp);
                    }
                });
            }
            if($('.layui-table-tool .layui-table-tool-temp input[lay-filter="layTableAllChoose"]').length<1){
                $('button[data-table-refresh="currentTableRenderId"]').before(layTableAllChooseHtml);
            }
            $('.layui-table-box .layui-table-main table>tbody>tr').each(function (index) {
                data=$(this).data();
                str='';
                for (var prop in data) {
                    str+='data-'+prop+'='+data[prop]+' ';
                }
                html+=' <div class="layui-card tr" '+str+'>';
                $(this).find('td').each(function (index){
                    val=$.trim($(this).find('div').html());
                    lable=titleArray[index];
                    if(!admin.empty(val) && val!=='<span></span>'){
                        if(lable==='<span>'+fy('Operate')+'</span>'){
                            html+='<div class="layui-card-header">'+titleArray[index]+'：'+val+'</div>';
                        }else{
                            data=$(this).data();
                            str='';
                            for (var prop in data) {
                                str+='data-'+prop+'='+data[prop]+' ';
                            }
                            html+='<div class="layui-card-body-item" '+str+'>'+titleArray[index]+'：'+val+'</div>';
                        }

                    }

                });
                html+='</div>';
            });
            if(html==='<div class="book-box">'){
                html+=fy('No data');
            }
            html+='</div>';
            $('.layui-table-box').after(html).hide();
            form.on('checkbox(layTableAllChoose)', function(data){
                if(data.elem.checked){
                    $('input[name="layTableCheckbox"]').prop('checked', true);
                }else{
                    $('input[name="layTableCheckbox"]').prop('checked', false);
                }
            });

            $(document).off('click', '.layui-table-sort i').on('click','.layui-table-sort i',function (e){
                if($(this).hasClass('layui-table-sort-asc')){
                    admin.init.where['sort_order']='asc';
                    $(this).parent().attr('lay-sort','asc')
                }else{
                    admin.init.where['sort_order']='desc';
                    $(this).parent().attr('lay-sort','desc')
                }
                admin.init.where['sort_by']= $(this).parent().parent().attr('data-field');

                table.reload(table_render_id, {
                    initSort:  {
                        field:admin.init.where['sort_by'],
                        type: admin.init.where['sort_order']
                    }
                    ,where: admin.init.where
                });
            });
            form.render('checkbox');
            //     渲染高亮排序字段
            $('[lay-table-id="'+table_render_id+'"]').find('[data-field="'+admin.init.where['sort_by']+'"]').each(function (index) {
                $(this).find('.layui-table-sort').attr('lay-sort',admin.init.where['sort_order']);
            });

        },
        //关闭窗口并回传数据
        close: function (data) {
            var index = parent.Layer.getFrameIndex(window.name);
            var callback = parent.$("#layui-layer" + index).data("callback");
            //再执行关闭
            parent.Layer.close(index);
            //再调用回传函数
            if (typeof callback === 'function') {
                callback.call(undefined, data);
            }
        },
        open: function (title, url, width, height) {
            var shadeClose = arguments[5] ? arguments[5] : false;
            var isResize = arguments[4] ? arguments[4] : true;
            var layerConfig = {
                title: title,
                type: 2,
                area: [width, height],
                content: url,
                maxmin: true,
                moveOut: true,
                shadeClose: shadeClose,
                success: function (layero, index) {
                    var body = layer.getChildFrame('body', index);
                    if (body.length > 0) {
                        $.each(body, function (i, v) {
                            $(v).before('<style>\n' +
                                'html, body {\n' +
                                '    background: #ffffff;\n' +
                                '}\n' +
                                '</style>');
                        });
                    }
                },
                end: function () {
                    $(window).off('resize.adminLayer');
                    index = null;
                }
            };
            if (isResize) {
                var resizeHandler = throttledResize(function () {
                    index && layer.full(index);
                });
                $(window).off('resize.adminLayer').on('resize.adminLayer', resizeHandler);
            }
            var index = layer.open(layerConfig);
            if (admin.checkMobile() || width === undefined || height === undefined) {
                layer.full(index);
            }
            return index;
        },
        urlData: function (url) {
            //    获取url参数
            var urlData = {};
            url=url?url:window.location.href;
            var urlArr = url.split('?');
            if (urlArr.length > 1) {
                var urlParamArr = urlArr[1].split('&');
                for (var i = 0; i < urlParamArr.length; i++) {
                    var urlParam = urlParamArr[i].split('=');
                    urlData[urlParam[0]] = urlParam[1];
                }
            }
            return urlData;
        },
        listen: function (preposeCallback, ok, no, ex) {

            // 监听表单是否为必填项
            admin.api.formRequired();

            // 监听表单提交事件
            admin.api.formSubmit(preposeCallback, ok, no, ex);

            // 初始化图片显示以及监听上传事件
            admin.api.upload();
            // 监听富文本初始化
            admin.api.editor();

            // 监听时间控件生成
            admin.api.date();
            // 初始化layui表单
            form.render();
            admin.api.cityPicker();
            admin.api.fieldList();
            admin.api.selectPage();


            // 表格修改
            $("body").on("mouseenter", ".table-edit-tips", function () {
                var openTips = layer.tips(fy('Click on the row contents to modify it'), $(this), {tips: [2, '#e74c3c'], time: 4000});
            });
            // disabled="disabled"
            $('body').on('click', '[open-select]', function () {
                var $this=$(this);
                if ($this.is('[disabled]')) return;
                var size = calcLayerSize($(this));
                var clienWidth = size.width,
                    clientHeight = size.height,
                    url = $(this).attr('open-select'),
                    external = $(this).attr('data-external') || false;

                var shadeClose = false;
                var isResize =  true;
                var layerConfig = {
                    title: $(this).attr('data-title'),
                    type: 2,
                    area: [clienWidth, clientHeight],
                    content:  admin.url(url),
                    maxmin: true,
                    moveOut: true,
                    shadeClose: shadeClose,
                    success: function (layero, index) {
                        var body = layer.getChildFrame('body', index);
                        if (body.length > 0) {
                            $.each(body, function (i, v) {
                                $(v).before('<style>\n' +
                                    'html, body {\n' +
                                    '    background: #ffffff;\n' +
                                    '}\n' +
                                    '</style>');
                            });
                        }
                        $(layero).data("callback",function (data) {
                            $this.html(data.name);
                            $('[name="'+$this.data('id')+'"]').val(data.id).trigger('change');
                            $('[name="'+$this.data('name')+'"]').val(data.name).trigger('change');
                        });
                    },
                    end: function () {
                        $(window).off('resize.adminSelectLayer');
                        index = null;
                    }
                };
                if (isResize) {
                    var resizeHandler = throttledResize(function () {
                        index && layer.full(index);
                    });
                    $(window).off('resize.adminSelectLayer').on('resize.adminSelectLayer', resizeHandler);
                }
                var index = layer.open(layerConfig);
                if (admin.checkMobile()) {
                    layer.full(index);
                }

            });

            $('body').on('click', '[select-close]', function () {
                var id=$(this).attr('data-id');
                var name=$(this).attr('data-name');
                var index = parent.layui.layer.getFrameIndex(window.name);
                var callback = parent.$("#layui-layer" + index).data("callback");

                //再执行关闭
                parent.layui.layer.close(index);

                //再调用回传函数
                if (typeof callback === 'function') {
                    callback.call(undefined,{id:id,name:name});
                }
            });
            // 监听弹出层的打开
            $('body').on('click', '[data-open]', function () {
                var size = calcLayerSize($(this));
                var clienWidth = size.width,
                    clientHeight = size.height,
                    url = $(this).attr('data-open'),checkbox = $(this).attr('data-checkbox'),
                    external = $(this).attr('data-external') || false;

                if(checkbox === 'true'){
                    tableId = $(this).attr('data-table');
                    tableId = tableId || init.table_render_id;
                    var checkStatus = table.checkStatus(tableId),
                        data = checkStatus.data;
                    if (data.length <= 0) {
                        admin.msg.error(fy('Please check the data to be operated'));
                        return false;
                    }
                    var ids = [];
                    $.each(data, function (i, v) {
                        ids.push(v.id);
                    });
                    if (url.indexOf("?") === -1) {
                        url += '?id=' + ids.join(',');
                    } else {
                        url += '&id=' + ids.join(',');
                    }
                }

                admin.open(
                    $(this).attr('data-title'),
                    external ? url : admin.url(url),
                    clienWidth,
                    clientHeight
                );
            });


            // 放大图片
            $('body').off('click', '[data-image]').on('click', '[data-image]', function () {
                console.log('data-image');
                var title = $(this).attr('data-image'),

                    src = $(this).attr('data-src')?$(this).attr('data-src'):$(this).attr('src'),
                    alt = $(this).attr('alt'),
                    uploadUrl= $(this).next().attr('data-upload-url');

                // 获取this的兄弟节点
                if(!admin.empty(uploadUrl) && admin.url(uploadUrl)!==src){
                    window.open(admin.url(uploadUrl),'_blank');
                    return false;
                }
                var parentDom=$(this).parent();
                if($(this).parent().is('li')){
                    parentDom=$(this).parent().parent();
                }
                var index = 0;
                var $ul = $('<ul></ul>');
                parentDom.find('img').each(function (i, item) {
                    var image = $(item).attr('data-src')?$(item).attr('data-src'):$(item).attr('src');

                    $ul.append($('<li><img src="' + image + '"></li>'));

                    if (image == src) {
                        index = i;
                    }
                });
                admin.loadStyle(CONFIG.MY_PUBLIC+'/static/plugs/viewer/viewer.min.css');
                require(['plugs/viewer/viewer.min'], function (Viewer) {
                    var viewer = new Viewer($ul.get(0), {
                        initialViewIndex: index,
                        title: false,
                        transition: false,
                        hidden: function () {
                            viewer.destroy();
                        },
                    }).show();
                });
                return false;
            });


            // 监听动态表格刷新
            $('body').on('click', '[data-table-refresh]', function () {
                var tableId = $(this).attr('data-table-refresh');
                if (tableId === undefined || tableId === '' || tableId == null) {
                    tableId = init.table_render_id;
                }
                table.reload(tableId);
            });

            // 监听搜索表格重置
            $('body').on('click', '[data-table-reset]', function () {
                var tableId = $(this).attr('data-table-reset');
                if (tableId === undefined || tableId === '' || tableId == null) {
                    tableId = init.table_render_id;
                }
                var where = {filter: '{}', op: '{}'}
                if(init.where){
                    for (var key in init.where) {
                        if (init.where.hasOwnProperty(key)) {
                            where[key] = init.where[key];
                        }
                    }
                }
                admin.init.where = where;
                table.reload(tableId, {
                    page: {
                        curr: 1
                    }
                    ,where:where
                }, false);
            });

            // 监听请求
            $('body').on('click', '[data-request]', function () {
                var title = $(this).attr('data-title'),
                    url = $(this).attr('data-request'),
                    tableId = $(this).attr('data-table'),
                    addons = $(this).attr('data-addons'),
                    checkbox = $(this).attr('data-checkbox'),
                    direct = $(this).attr('data-direct'),
                    field = $(this).attr('data-field') || 'id';

                title = title || fy('Confirm the operation')+'？';

                if (direct === 'true') {
                    admin.msg.confirm(title, function () {
                        window.location.href = url;
                    });
                    return false;
                }

                var postData = {};
                if(checkbox === 'true'){
                    tableId = tableId || init.table_render_id;
                    var checkStatus = table.checkStatus(tableId),
                        data = checkStatus.data;
                    if (data.length <= 0) {
                        admin.msg.error(fy('Please check the data to be operated'));
                        return false;
                    }
                    var ids = [];
                    $.each(data, function (i, v) {
                        ids.push(v[field]);
                    });
                    postData[field] = ids;
                }

                if (addons !== true && addons !== 'true') {
                    url = admin.url(url);
                }
                tableId = tableId || init.table_render_id;
                admin.msg.confirm(title, function () {
                    admin.request.post({
                        url: url,
                        data: postData,
                    }, function (res) {
                        admin.msg.success(res.msg, function () {
                            table.reload(tableId);
                        });
                    })
                });
                return false;
            });
            if($('.colorpicker').length){
                layui.colorpicker.render({
                    elem: '.colorpicker',
                    done: function(color){
                        var target=$(this.elem).attr('data-target');
                        if(target){
                            $(target).val(color);
                        }
                    }
                });
            }
            // 渲染


            // excel导出  QQ315988561修改完善携带搜索参数
            $('body').on('click', '[data-table-export]', function () {


                var tableId = $(this).attr('data-table-export'),
                    field = $(this).attr('data-field') || 'id';
                tableId = tableId || init.table_render_id;
                var ids=[],page = []; // 用于存储所有行的id值
                url = $(this).attr('data-url');
                // 遍历当前页所有行的数据，并将id属性存入ids数组中
                layui.each(table.cache[tableId], function(index, item){
                    page.push(item[field]);
                });
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length > 0) {
                    $.each(data, function (i, v) {
                        ids.push(v[field]);
                    });
                }
                layer.confirm("请选择导出的选项", {
                    title: '导出数据',
                    btn: ["选中项(" + ids.length + "条)", "本页(" + page.length + "条)", "全部页"],
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }
                    , yes: function (index, layero) {
                        if(ids.length<1){
                            admin.msg.error('请先勾选需要导出的数据');
                        }else{
                            submitForm(ids.join(","));
                        }
                        return false;
                    }
                    ,
                    btn2: function (index, layero) {
                        if(page.length<1){
                            admin.msg.error('当前页面没有需要导出的数据');
                        }else{
                            submitForm(page.join(","));
                        }
                        return false;
                    }
                    ,
                    btn3: function (index, layero) {
                        submitForm("all");
                        return false;
                    }
                })
                function submitForm(ids) {
                    var formatOp={},formatFilter={};
                    if(ids!='all'){
                        formatOp['id']='in';
                        formatFilter['id']=ids;
                    }
                    $.each(form.val(tableId + '_form'), function (key, val) {
                        if (val !== '') {
                            formatFilter[key] = val;
                            var op = $(document.getElementById('c-' + key)).attr('data-search-op');
                            op = op || '%*%';
                            formatOp[key] = op;
                        }
                    });
                    var where={filter: JSON.stringify(formatFilter), op: JSON.stringify(formatOp)}
                    if(init.where){
                        for (var key in init.where) {
                            if (init.where.hasOwnProperty(key)) {
                                where[key] = init.where[key];
                            }
                        }
                    }
                    window.open(admin.url(url+'?'+admin.parseParams(where)), '_blank');
                }

            });

            // 数据表格多删除
            $('body').on('click', '[data-table-delete]', function () {
                var tableId = $(this).attr('data-table-delete'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                url = url !== undefined ? admin.url(url) : window.location.href;
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    admin.msg.error(fy('Please check the data to be deleted'));
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);
                });
                admin.msg.confirm(fy('Confirm the deletion')+'？', function () {
                    admin.request.post({
                        url: url,
                        data: {
                            id: ids
                        },
                    }, function (res) {
                        admin.msg.success(res.msg, function () {
                            table.reload(tableId);
                        });
                    });
                });
                return false;
            });

            if($('.js-ajax-btn').length){
                $('body').on('click', '.js-ajax-btn', function (e) {
                    e.preventDefault();
                    var url = $(this).attr('data-url');
                    var refresh = $(this).attr('data-refresh');
                    url = url !== undefined ? admin.url(url) : window.location.href;

                    admin.request.post({
                        url: url,
                    }, function (res) {
                        if(res.code){
                            admin.msg.success(res.msg, function () {
                                if(refresh){
                                    //刷新不提交的写法
                                    window.location.href=window.location.href;
                                    window.location.reload();

                                }
                            });

                        }else{
                            admin.msg.error(res.msg);
                        }
                    });
                    return false;
                });
            }
            if($('.layui-layer-close').length){
            //     增加当前弹窗关闭
                $(document).on('click','.layui-layer-close',function () {
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                });
            }

        },
        api: {
            form: function (url, data, ok, no, ex, refreshTable) {
                if (refreshTable === undefined) {
                    refreshTable = true;
                }
                ok = ok || function (res) {
                    res.msg = res.msg || '';
                    admin.msg.success(res.msg, function () {
                        admin.api.closeCurrentOpen({
                            refreshTable: refreshTable,closeCurrent:true
                        });
                    });
                    return false;
                };
                admin.request.post({
                    url: url,
                    data: data,
                }, ok, no, ex);
                return false;
            },
            closeCurrentOpen: function (option) {
                option = option || {};
                option.refreshTable = option.refreshTable || false;
                option.refreshFrame = option.refreshFrame || false;
                if (option.refreshTable === true) {
                    option.refreshTable = init.table_render_id;
                }
                if(option.closeCurrent===true){
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                }
                if (option.refreshTable !== false) {
                    parent.layui.table.reload(option.refreshTable);
                }
                if (option.refreshFrame) {
                    parent.location.reload();
                }
                return false;
            },
            refreshFrame: function () {
                parent.location.reload();
                return false;
            },
            refreshTable: function (tableName) {
                tableName = tableName || 'currentTable';
                table.reload(tableName);
            },
            formRequired: function () {
                var verifyList = document.querySelectorAll("[lay-verify]");
                if (verifyList.length > 0) {
                    $.each(verifyList, function (i, v) {
                        var verify = $(this).attr('lay-verify');

                        // todo 必填项处理
                        if (verify.indexOf('required') > -1) {
                            var label = $(this).parent().prev();
                            if (label.is('label') && !label.hasClass('required')) {
                                label.addClass('required');
                            }
                            if ($(this).attr('lay-reqtext') === undefined && $(this).attr('placeholder') !== undefined) {
                                $(this).attr('lay-reqtext', $(this).attr('placeholder'));
                            }
                            if ($(this).attr('placeholder') === undefined && $(this).attr('lay-reqtext') !== undefined) {
                                $(this).attr('placeholder', $(this).attr('lay-reqtext'));
                            }
                        }

                    });
                }
            },
            formSubmit: function (preposeCallback, ok, no, ex) {
                var formList = document.querySelectorAll("[lay-submit]");
                // 表单提交自动处理
                if (formList.length > 0) {
                    $.each(formList, function (i, v) {
                        var filter = $(this).attr('lay-filter'),
                            type = $(this).attr('data-type');
                        this.refresh = $(this).attr('data-refresh');
                        this.url = $(this).attr('lay-submit');
                        this.jump = $(this).attr('data-jump');
                        this.noclose = $(this).attr('data-noclose');
                        // 表格搜索不做自动提交
                        if (type === 'tableSearch') {
                            return true;
                        }
                        // 判断是否需要刷新表格
                        if (this.refresh === 'false') {
                            this.refresh = false;
                        } else {
                            this.refresh = true;
                        }
                        // 自动添加layui事件过滤器
                        if (filter === undefined || filter === '') {
                            filter = 'save_form_' + (i + 1);
                            $(this).attr('lay-filter', filter)
                        }
                        if (this.url === undefined || this.url === '' || this.url === null) {
                            this.url = window.location.href;
                        } else {
                            this.url = admin.url(this.url);
                        }
                        form.on('submit(' + filter + ')', function (data) {
                            var dataField = data.field;

                            // 富文本数据处理
                            var editorList = document.querySelectorAll(".editor");
                            if (editorList.length > 0) {
                                /*   $.each(editorList, function (i, v) {
                                       var name = $(this).attr("name");
                                       dataField[name] = CKEDITOR.instances[name].getData();
                                   });
                                   });*/
                            }

                            if (typeof preposeCallback === 'function') {
                                dataField = preposeCallback(dataField);
                            }
                            if(this.noclose==1){
                                this.ok=function (res) {
                                    res.msg = res.msg || '';
                                    admin.msg.success(res.msg, function () {
                                        admin.api.closeCurrentOpen({
                                            refreshTable: this.refresh,closeCurrent:false
                                        });
                                    });
                                    return false;
                                };
                            }
                            //缘明网络新增
                            if(this.jump){
                                this.ok=function (res){
                                    if (res.code == 1){
                                        admin.msg.success(res.msg,function (){
                                            window.location.href=admin.url(this.jump);
                                        });
                                    }else {
                                        admin.msg.error(res.msg);
                                    }
                                }
                            }
                            admin.api.form(this.url, dataField, this.ok || ok, no, ex, this.refresh);

                            return false;
                        });
                    });
                }

            },
            upload: function (container) {
                container = typeof container === 'undefined' ? '.layui-form' : container;
                // var uploadList = document.querySelectorAll("[data-upload]");
                var uploadList = $(container).find("[data-upload]");
                if (uploadList.length > 0) {
                    require(['layWebupload'], function (layWebupload) {
                        $.each(uploadList, function (i, v) {


                            var uploadExts = $(v).attr('data-upload-exts') || init.upload_exts,
                                uploadName = $(v).attr('data-upload'),
                                uploadNumber = $(v).attr('data-upload-number') || 'one',
                                uploadSign = $(v).attr('data-upload-sign') || '|',
                                uploadAccept = $(v).attr('data-upload-accept') || 'file',
                                uploadAcceptMime = $(v).attr('data-upload-mimetype') || '',
                                elem = "input[name='" + uploadName + "']",
                                uploadElem = v,index;
                            layWebuploadIns=layWebupload.render({
                                url: CONFIG.MODULEURL+'/ajax/upload',//上传文件服务器地址，必填
                                fileCheckUrl:CONFIG.MODULEURL+'/UpFiles/md5Check',//文件校验地址
                                checkChunkUrl:CONFIG.MODULEURL+'/UpFiles/md5Check',//文件块校验地址
                                mergeChunksUrl:CONFIG.MODULEURL+'/UpFiles/merge',//文件合并地址
                                size:2*1024*1024*1024,//单个文件大小，有默认值，可不填
                                fileType:uploadExts,//允许上传文件格式,有默认值，可不填
                                fileBoxEle:"#file_table_box",//上传容器
                                elem: v,
                                fileNumLimit:200,//上限500个文件
                                headers:admin.headers(),
                                multiple: uploadNumber !== 'one',
                                start:function(){
                                    index = admin.msg.loading('上传中');
                                },error:function (res){
                                    console.log('res=',res)
                                    admin.msg.close(index);
                                    admin.msg.error(res.msg);
                                    return false;
                                },
                                done: function (res) {
                                    admin.msg.close(index);
                                    if (res.code === 1) {
                                        var url = res.data.url;
                                        if (uploadNumber !== 'one') {
                                            var oldUrl = $(elem).val();
                                            if (oldUrl !== '') {
                                                url = oldUrl + uploadSign + url;
                                            }
                                        }
                                        $(elem).val(url);
                                        $(elem).trigger("input");
                                        admin.msg.success(res.msg);
                                    } else {
                                        admin.msg.error(res.msg);
                                    }
                                    return false;
                                }
                            });


                            // 监听上传input值变化
                            $(elem).bind("input propertychange change", function (event) {
                                var that = this
                                clearTimeout(this.timer)
                                this.timer = setTimeout(function () {
                                    var urlString = $(that).val(),
                                        urlArray = urlString.split(uploadSign),
                                        uploadIcon = $(uploadElem).attr('data-upload-icon') || "file";

                                    var zy_uploadName=uploadName.replace('[','\\[');
                                    zy_uploadName=zy_uploadName.replace(']','\\]');
                                    $('#bing-' + zy_uploadName).remove();
                                    if (urlString.length > 0) {
                                        var parant = $(that).parent('div');
                                        var liHtml = '';
                                        $.each(urlArray, function (i, v) {
                                            let temp=admin.attrUrl(v);
                                            liHtml += '<li><img src="' + temp + '" data-image  onerror="this.src=\'' + BASE_URL + 'admin/images/upload-icons/' + uploadIcon + '.png\';this.onerror=null"><small class="uploads-delete-tip bg-red badge" data-upload-delete="' + uploadName + '" data-upload-url="' + temp + '" data-upload-sign="' + uploadSign + '">×</small></li>\n';
                                        });
                                        parant.after('<ul id="bing-' + uploadName + '" class="layui-input-block layuimini-upload-show">\n' + liHtml + '</ul>');
                                    }
                                }, 1000);

                            });

                            // 非空初始化图片显示
                            if ($(elem).val() !== '') {
                                $(elem).trigger("input");
                            }
                        });

                        // 监听上传文件的删除事件
                        $('body').on('click', '[data-upload-delete]', function () {
                            var uploadName = $(this).attr('data-upload-delete'),
                                deleteUrl = $(this).attr('data-upload-url'),
                                sign = $(this).attr('data-upload-sign');
                            var confirm = admin.msg.confirm(fy('Confirm the deletion')+'？', function () {
                                var elem = "input[name='" + uploadName + "']";
                                var currentUrl = $(elem).val();
                                var url = '';
                                if (currentUrl !== deleteUrl) {
                                    url = currentUrl.search(deleteUrl) === 0 ? currentUrl.replace(deleteUrl + sign, '') : currentUrl.replace(sign + deleteUrl, '');
                                    $(elem).val(url);
                                    $(elem).trigger("input");
                                } else {
                                    $(elem).val(url);
                                    var zy_uploadName=uploadName.replace('[','\\[');
                                    zy_uploadName=zy_uploadName.replace(']','\\]');
                                    $('#bing-' + zy_uploadName).remove();
                                }
                                admin.msg.close(confirm);
                            });
                            return false;
                        });
                    })}
            },
            editor: function (container) {
                // CKEDITOR.tools.setCookie('ckCsrfToken', window.CONFIG.CSRF_TOKEN);
                container = typeof container === 'undefined' ? '.layui-form' : container;
                var editorList = $(container).find(".editor");
                if (editorList.length > 0) {
                    lang= CONFIG.LANG=='zh-cn'?'zh-cn':'en'
                    require([
                        CONFIG.MY_PUBLIC+'/static/ueditor/ueditor.config.min.js',
                        CONFIG.MY_PUBLIC+'/static/ueditor/third-party/zeroclipboard/ZeroClipboard.min.js',
                        CONFIG.MY_PUBLIC+'/static/ueditor/ueditor.all.min.js',
                    ], function (undefined, ZeroClipboard,undefined) {
                        window.ZeroClipboard = ZeroClipboard;
                        var editorOption = {
                            UEDITOR_HOME_URL: CONFIG.MY_PUBLIC+"/static/ueditor/",
                            UEDITOR_ROOT_URL: CONFIG.MY_PUBLIC+"/static/ueditor/",
                            serverUrl: CONFIG.MODULEURL+'/ajax/uploadUeditor',
                            lang: lang,
                            /* toolbars: [["source","undo", "redo", "|", "bold", "italic", "underline", "fontborder", "strikethrough", "superscript", "subscript", "removeformat", "formatmatch", "autotypeset", "blockquote",  "|", "forecolor", "backcolor", "selectall", "cleardoc", "|", "lineheight", "|",   "fontfamily", "fontsize", "|", "link", "unlink", "emotion"]],*/
                            initialContent: "",
                            pageBreakTag: "_ueditor_page_break_tag_",
                            initialFrameWidth: "100%",
                            initialFrameHeight: "200",
                            disabledTableFilter: true,
                            filterMode: false,
                            disabledTableInTable: false,
                            autoFloatEnabled: false,
                            autoHeightEnabled: false,
                            allowDivTransToP: false,
                            autoClearEmptyNode: false,
                            pasteplain: false,
                            removeEmptyTags: false,
                            wordCount: false,
                            filterRules: {} ,
                            disabledInputFilter: true,
                            retainOnlyLabelPasted: false,
                            autoClearinitialContent: false,
                            autoTransWordToList: false,
                            charset: "utf-8",
                        };
                        // 统一存放实例，key = 唯一 id
                        if (!window.UE_STORE) window.UE_STORE = {};

                        editorList.each(function (idx) {
                            var $this = $(this);
                            var name = $this.attr('name') || 'ue_' + idx;
                            // 保证 DOM 有唯一 id
                            var id = $this.attr('id') || 'ueid_' + name + '_' + idx;
                            $this.attr('id', id);

                            // 若已存在先销毁
                            UE.delEditor(id);

                            // 深拷贝配置，防止 toolbars 污染下一个编辑器
                            var opt = $.extend(true, {}, editorOption);
                            var customBars = $this.data('toolbars');
                            if (customBars) opt.toolbars = customBars;

                            var editor = UE.getEditor(id, opt);
                            window.UE_STORE[id] = editor;          // 全局可索引

                            // 双向同步：编辑器 -> textarea
                            editor.addListener('contentChange', function () {
                                $('textarea[name="' + name + '"]').val(this.getContent());
                            });

                            // 双向同步：textarea -> 编辑器（可选）
                            $('textarea[name="' + name + '"]').on('input', function () {
                                console.log('编辑器input触发');
                                editor.ready(function () {
                                    editor.setContent(this.value);
                                }.bind(this));
                            });
                        });
                    });
                }
            },
            select: function () {
                var selectList = document.querySelectorAll("[data-select]");
                $.each(selectList, function (i, v) {
                    var url = $(this).attr('data-select'),
                        selectFields = $(this).attr('data-fields'),
                        value = $(this).attr('data-value'),
                        that = this,
                        html = '<option value=""></option>';
                    var fields = selectFields.replace(/\s/g, "").split(',');
                    if (fields.length !== 2) {
                        return admin.msg.error(fy('Wrong drop-down selection'));
                    }
                    admin.request.get(
                        {
                            url: url,
                            data: {
                                selectFields: selectFields
                            },
                        }, function (res) {
                            var list = res.data;
                            for(var i = 0; i < list.length; i++) {
                                if(i in list) {
                                    val=list[i];
                                    key = val[fields[0]];
                                    if (value !== undefined && key.toString() === value) {
                                        html += '<option value="' + key + '" selected="">' + val[fields[1]] + '</option>';
                                    } else {
                                        html += '<option value="' + key + '">' + val[fields[1]] + '</option>';
                                    }

                                }
                            }
                            $(that).html(html);
                            form.render();
                        }
                    );
                });
            },
            date: function () {
                var dateList = document.querySelectorAll("[data-date]");
                if (dateList.length > 0) {
                    $.each(dateList, function (i, v) {
                        var format = $(this).attr('data-date'),
                            type = $(this).attr('data-date-type'),
                            range = $(this).attr('data-date-range'),min = $(this).attr('data-date-min');
                        if(type === undefined || type === '' || type ===null){
                            type = 'datetime';
                        }
                        var lang='cn';
                        if(CONFIG.LANG=="en-us"){
                            lang='en';
                        }
                        var options = {
                            elem: this,'lang':lang,
                            type: type,trigger:'click'
                        };
                        if (format !== undefined && format !== '' && format !== null) {
                            options['format'] = format;
                        }
                        if (range !== undefined) {
                            if(range === null || range === ''){
                                range = '-';
                            }
                            if(range==='false'){range=false;}
                            options['range'] = range;
                        }
                        if(min !== undefined && min !== '' && min !==null){
                            options['min'] = min;
                        }
                        layui.laydate.render(options);
                    });
                }
            },
            cityPicker:function (){
                //省市区三级联动 "citypicker" 技术支持QQ315988561
                var cityPicker=$('[data-toggle="city-picker"]');
                if(cityPicker.length){
                    require(['citypicker'], function () {
                        cityPicker.citypicker();
                    });
                }
            },fieldList:function (){
                //绑定fieldlist
                if ($(".fieldlist").length) {
                    require(['plugs/dragsort/jquery.dragsort', 'plugs/art-template/dist/template-native'], function (undefined, Template) {
                        //刷新隐藏textarea的值
                        var fieldlistObj=$(".fieldlist");
                        var refresh = function (name) {
                            var data = {};
                            var textarea = $("textarea[name='" + name + "']");
                            var container = $(".fieldlist[data-name='" + name + "']");
                            var template = container.data("template");
                            $.each($("input,select,textarea", container).serializeArray(), function (i, j) {
                                var reg = /\[(\w+)\]\[(\w+)\]$/g;
                                var match = reg.exec(j.name);
                                if (!match)
                                    return true;
                                match[1] = "x" + parseInt(match[1]);
                                if (typeof data[match[1]] == 'undefined') {
                                    data[match[1]] = {};
                                }
                                data[match[1]][match[2]] = j.value;
                            });
                            var result = template ? [] : {};
                            $.each(data, function (i, j) {
                                if (j) {
                                    if (!template) {
                                        if (j.key != '') {
                                            result[j.key] = j.value;
                                        }
                                    } else {
                                        result.push(j);
                                    }
                                }
                            });
                            textarea.val(JSON.stringify(result));
                        };
                        //监听文本框改变事件
                        $(document).on('change keyup changed input', ".fieldlist input,.fieldlist textarea,.fieldlist select", function () {
                            refresh($(this).closest(".fieldlist").data("name"));
                        });
                        //追加控制
                        fieldlistObj.on("click", ".btn-append", function (e, row) {
                            var container = $(this).closest(".fieldlist");
                            var tagName = container.data("tag") || (container.is("table") ? "tr" : "dd");
                            var index = container.data("index");
                            var name = container.data("name");
                            var template = container.data("template");
                            var data = container.data();
                            index = index ? parseInt(index) : 0;
                            container.data("index", index + 1);
                            row = row ? row : {};
                            var vars = {index: index, name: name, data: data, row: row};
                            var html = template ? Template(template, vars) : Template.render(Form.config.fieldlisttpl, vars);
                            $(html).attr("fieldlist-item", true).insertBefore($(tagName + ":last", container));
                            $(this).trigger("appendfieldlist", $(this).closest(tagName).prev());
                        });
                        //移除控制
                        fieldlistObj.on("click", ".btn-remove", function () {
                            var container = $(this).closest(".fieldlist");
                            var tagName = container.data("tag") || (container.is("table") ? "tr" : "dd");
                            $(this).closest(tagName).remove();
                            refresh(container.data("name"));
                        });
                        //渲染数据&拖拽排序
                        fieldlistObj.each(function () {
                            var container = this;
                            var tagName = $(this).data("tag") || ($(this).is("table") ? "tr" : "dd");
                            $(this).dragsort({
                                itemSelector: tagName,
                                dragSelector: ".btn-dragsort",
                                dragEnd: function () {
                                    refresh($(this).closest(".fieldlist").data("name"));
                                },
                                placeHolderTemplate: $("<" + tagName + "/>")
                            });
                            var textarea = $("textarea[name='" + $(this).data("name") + "']");
                            if (textarea.val() == '') {
                                return true;
                            }
                            var template = $(this).data("template");
                            textarea.on("refreshfieldlist", function () {
                                $("[fieldlist-item]", container).remove();
                                var json = {};
                                try {
                                    json = JSON.parse($(this).val());
                                } catch (e) {
                                }
                                $.each(json, function (i, j) {
                                    $(".btn-append,.append", container).trigger('click', template ? j : {
                                        key: i, value: j
                                    });
                                });
                            });
                            textarea.trigger("refreshfieldlist");
                        });
                    });
                }
            },selectPage:function (){
                var selectPageObj = $('[data-toggle="selectPage"]');
                if (selectPageObj.length) {
                    admin.loadStyle(CONFIG.MY_PUBLIC + '/static/plugs/lay-module/selectPage/selectpage.min.css');
                    require(['selectPage'], function () {
                        selectPageObj.each(function () {
                            var $this = $(this);
                            var originalSource = $this.data('source');
                            if (originalSource) {
                                $this.data('source', admin.url(originalSource));
                            }
                            // 初始化插件，并传入覆盖选项
                            var spOptions = {
                                source: $this.data('source'), // 明确指定覆盖后的地址
                                eAjaxSuccess: function (data) {
                                    data.list = typeof data.rows !== 'undefined' ? data.rows : (typeof data.list !== 'undefined' ? data.list : (typeof data.data !== 'undefined' && Array.isArray(data.data) ? data.data : []));
                                    data.totalRow = typeof data.total !== 'undefined' ? data.total : (typeof data.totalRow !== 'undefined' ? data.totalRow : (typeof data.count !== 'undefined' ? data.count : data.list.length));
                                    return data;
                                }
                            };
                            // 支持 data-format-item 自定义展示格式（如 "{part_number} ({model})"）
                            var formatItem = $this.data('format-item');
                            if (formatItem !== undefined) {
                                spOptions.formatItem = formatItem;
                                // selectPage 核心在下拉选择后未使用 formatItem，通过 eSelect 回调手动格式化
                                spOptions.eSelect = function(data, self) {
                                    // 多选模式下输入框应清空，不应设置文本
                                    if (self.option.multiple) {
                                        self.elem.combo_input.val('');
                                        return;
                                    }
                                    if (self.option.formatItem && $.isFunction(self.option.formatItem)) {
                                        try {
                                            var text = self.option.formatItem(data);
                                            self.elem.combo_input.val(text);
                                            self.prop.selected_text = text;
                                        } catch (e) {}
                                    }
                                };
                            }
                            // 支持通过 data-no-result-clean 属性控制无结果时是否清空
                            var noResultClean = $this.data('noResultClean');
                            if (noResultClean !== undefined) {
                                spOptions.noResultClean = !!parseInt(noResultClean);
                            }
                            $this.selectPage(spOptions);
                            // data-format-item 初始化回显格式化（selectPage 核心的 afterInit 未使用 formatItem）
                            if (formatItem !== undefined) {
                                var obj = $this.data('selectPageObject');
                                if (obj) {
                                    var _origAfterInit = obj.afterInit;
                                    obj.afterInit = function(self, data) {
                                        _origAfterInit.call(self, self, data);
                                        // 多选模式下不需要设置输入框文本，原始afterInit已处理标签创建
                                        if (self.option.multiple) return;
                                        if (data && data.length > 0 && self.option.formatItem && $.isFunction(self.option.formatItem)) {
                                            try {
                                                var row = data[0];
                                                var text = self.option.formatItem(row);
                                                self.elem.combo_input.val(text);
                                                self.prop.selected_text = text;
                                            } catch (e) {}
                                        }
                                    };
                                }
                            }
                        });
                    });
                    $(document).on("change", ".sp_input", function () {
                        $(this).closest(".sp_container").find(".sp_hidden").trigger("change");
                    });
                }
            }
        },computeNumber: function(a, type, b) {
            /**
             * 获取数字小数点的长度
             * @param {number} n 数字
             */
            function getDecimalLength(n) {
                var decimal = n.toString().split(".")[1];
                return decimal ? decimal.length : 0;
            }

            /**
             * 修正小数点（兼容IE）
             * @param {number} n 需要修正的数字
             * @param {number} [precision] 精度（默认15）
             */
            function amend(n, precision) {
                // 处理默认参数（IE不支持参数默认值）
                precision = precision === undefined ? 15 : precision;
                // 处理极小值避免科学计数法（IE中0.0000001可能转为"1e-7"）
                var num = Number(n);
                if (Math.abs(num) < 1e-6 || Math.abs(num) >= 1e15) {
                    // 大数/极小数的安全转换：先toFixed再parseFloat
                    return parseFloat(num.toFixed(Math.max(precision, 6)));
                }
                return parseFloat(num.toPrecision(precision));
            }

            var power = Math.pow(10, Math.max(getDecimalLength(a), getDecimalLength(b)));
            var result = 0;

            // 显式修正乘幂后的值（IE需处理浮点溢出）
            a = amend(a * power);
            b = amend(b * power);

            switch (type) {
                case "+":
                    result = (a + b) / power;
                    break;
                case "-":
                    result = (a - b) / power;
                    break;
                case "*":
                    result = (a * b) / (power * power);
                    break;
                case "/":
                    result = a / b;
                    break;
            }

            result = amend(result);

            return {
                result: result,
                next: function(nextType, nextValue) {
                    return admin.computeNumber(result, nextType, nextValue);
                }
            };
        }
    }
    window.fy = window.admin.fy;
    return window.admin;
});

