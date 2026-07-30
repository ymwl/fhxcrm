define(["jquery", "easy-admin"], function ($, ea) {
    window.$=$;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'analysis.admin/index',

        detail_url: 'admin.log/detail',
        delete_url: 'admin.log/delete',
        export_url: 'admin.log/export',
        modify_url: 'admin.log/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {field: 'username', title: '业务员', search:true},
                    {field: 'customer_count', title: '客户增量'},
                    {field: 'deal_count', title: '成交数量'},
                    {field: 'deal_rate', title: '成交率'}
                ]],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                }
            });

            require(['plugs/echarts/ymwlechart.min'], function (Ymwlechart) {
                ea.listen();
                var datetimerangeObj=$('#datetimerange');
                layui.laydate.render({
                    elem: '#datetimerange' //指定元素dd
                    ,type: 'datetime'
                    ,trigger: 'click'
                    ,range: true //开启日期范围，默认使用“-”分割
                    ,done: function(value, date, endDate){
                        Ymwlechart.echart.refreshEchart( 'order',datetimerangeObj.attr('data-url'), datetimerangeObj.val(), datetimerangeObj.attr('data-admin_id'),'Coordinate');
                    }
                });
                $("#owner_admin_id").data("eSelect", function(data, obj){
                    datetimerangeObj.attr('data-admin_id', data.admin_id);
                    Ymwlechart.echart.refreshEchart( 'order',datetimerangeObj.attr('data-url'), datetimerangeObj.val(), data.admin_id,'Coordinate');

                });
                // 基于准备好的dom，初始化健康表格数据
                Ymwlechart.echart.loadCoordinateEchart("order", CONFIG.erchart);
                $(document).on("click", ".btn-filter", function () {
                    var range = $(this).attr('data-value');
                    var dateRange=ea.daterange(range);
                    var obj=$(this).parent().find(".datetimerange");
                    obj.val(dateRange);
                    Ymwlechart.echart.refreshEchart(obj.attr('data-charttype'),obj.attr('data-url'),dateRange,obj.attr('data-admin_id'),'Coordinate');
                });
            });


        },
        record: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,url:ea.url('analysis.admin/record'),
                cols: [[
                    {field: 'admin_id', title: '业务员ID', search:true},
                    {field: 'username', title: '业务员', search:true},
                    {field: 'record_count', title: '跟进次数'},
                    {field: 'customer_count', title: '跟进客户数'},
                ]],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                }
            });

            require(['plugs/echarts/ymwlechart.min'], function (Ymwlechart) {
                ea.listen();
                var datetimerangeObj=$('#datetimerange');
                layui.laydate.render({
                    elem: '#datetimerange' //指定元素dd
                    ,type: 'datetime'
                    ,trigger: 'click'
                    ,range: true //开启日期范围，默认使用“-”分割
                    ,done: function(value, date, endDate){
                        Ymwlechart.echart.refreshEchart( 'record',datetimerangeObj.attr('data-url'), datetimerangeObj.val(), datetimerangeObj.attr('data-admin_id'),'Coordinate');
                    }
                });
                $("#owner_admin_id").data("eSelect", function(data, obj){
                    datetimerangeObj.attr('data-admin_id', data.admin_id);
                    Ymwlechart.echart.refreshEchart( 'record',datetimerangeObj.attr('data-url'), datetimerangeObj.val(), data.admin_id,'Coordinate');

                });
                Ymwlechart.echart.loadCoordinateEchart("record", CONFIG.erchart.record);
                $(document).on("click", ".btn-filter", function () {
                    var range = $(this).attr('data-value');
                    var dateRange=ea.daterange(range);
                    var obj=$(this).parent().find(".datetimerange");
                    obj.val(dateRange);
                    Ymwlechart.echart.refreshEchart(obj.attr('data-charttype'),obj.attr('data-url'),dateRange,obj.attr('data-admin_id'),'Coordinate');
                });

                var datetimerangeObj1=$('#datetimerange1');
                layui.laydate.render({
                    elem: '#datetimerange1' //指定元素dd
                    ,type: 'datetime'
                    ,trigger: 'click'
                    ,range: true //开启日期范围，默认使用“-”分割
                    ,done: function(value, date, endDate){
                        Ymwlechart.echart.refreshEchart( 'record_type',datetimerangeObj1.attr('data-url'), datetimerangeObj1.val(), datetimerangeObj1.attr('data-admin_id'),'Pie');
                    }
                });
                $("#owner_admin_id1").data("eSelect", function(data, obj){
                    datetimerangeObj.attr('data-admin_id', data.admin_id);
                    Ymwlechart.echart.refreshEchart( 'record_type',datetimerangeObj1.attr('data-url'), datetimerangeObj1.val(), data.admin_id,'Pie');

                });
                // $(document).on()
                // 基于准备好的dom，初始化健康表格数据
                Ymwlechart.echart.loadPieEchart("record_type", CONFIG.erchart.record_type);
                $(document).on("click", ".btn-filter1", function () {
                    var range = $(this).attr('data-value');
                    var dateRange=ea.daterange(range);
                    datetimerangeObj1.val(dateRange);
                    Ymwlechart.echart.refreshEchart(datetimerangeObj1.attr('data-charttype'),datetimerangeObj1.attr('data-url'),dateRange,datetimerangeObj1.attr('data-admin_id'),'Pie');
                });
            });


        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});