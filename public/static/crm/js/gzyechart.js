define(['echarts','echarts-theme'], function(Echarts, EchartsTheme){
    var Gzyechart = {
        echart: {
            // 将数据加载到数据表中 date = ['1998-02-03','2000-04-04']  data = ['title' => data=[30,40], 'title' => data = ['10','20]]
            loadEchart:function (echartname, date, data) {
                var chartObj = Echarts.init(document.getElementById(echartname+'-echart'), 'walden');
                // 指定图表的配置项和数据
                var legend = [],series = [];
                for (var i in data) {
                    legend.push(i);

                    series.push({
                        name: i,
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {}
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                        data: data[i]
                    });
                }

                var option = {
                    title: {
                        text: '',
                        subtext: ''
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data: legend,
                    },
                    toolbox: {
                        show: false,
                        feature: {
                            magicType: {show: true, type: ['stack', 'tiled']},
                            saveAsImage: {show: true}
                        }
                    },
                    calculable: true,
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: date
                    },
                    yAxis: {},
                    grid: [{
                        left: 'left',
                        top: 'top',
                        right: '10',
                        bottom: 30
                    }],
                    series: series
                };

                // 使用刚指定的配置项和数据显示图表。
                chartObj.setOption(option);

                $(window).resize(function () {
                    chartObj.resize();
                });
            },

            // 时间控件按钮
            initButton: function () {
                $(function(){
                    $(".datetimerange1").data("callback", function (start, end) {
                        var date = start.format(this.locale.format) + " - " + end.format(this.locale.format);
                        $(this.element).val(date);
                        refreshEchart($(this.element).data('type'),$(this.element).data('url'),start.format(this.locale.format), end.format(this.locale.format));
                    });
                    $(document).on("click", ".btn-filter", function () {
                        var label = $(this).text();
                        var obj = $(this).parent().find(".datetimerange").data("daterangepicker");
                        var dates = obj.ranges[label];
                        obj.startDate = dates[0];
                        obj.endDate = dates[1];
                        obj.clickApply();
                    });
                });

                // 刷新echart
                function refreshEchart(charttype,url,start,end)
                {
                    $.ajax({
                        url : url,
                        method : 'get',
                        data : {
                            start_date :start,
                            end_date : end,
                            type: charttype
                        },
                        success : function(data) {
                            if (data.code > 0) {
                                Gzyechart.echart.loadEchart(charttype, data.data[charttype].date, data.data[charttype].data);
                                return ;
                            }
                            layer.msg(data.msg, {'icon':2});
                        },
                    });
                }
            }
        }
    };

    window.Gzyechart = Gzyechart;

    return Gzyechart;
});