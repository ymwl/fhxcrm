define(['echarts','echarts-theme'], function(Echarts, EchartsTheme){
    console.log(Echarts.version);
    var Ymwlechart = {
        echart: {
            // 将数据加载到数据表中 date = ['1998-02-03','2000-04-04']  data = ['title' => data=[30,40], 'title' => data = ['10','20]]
            loadCoordinateEchart:function (echartname, value) {
                var chartObj = Echarts.init(document.getElementById(echartname+'-echart'), 'walden');
               var date=value.date,data=value.data;

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
                console.log(option)
                // 使用刚指定的配置项和数据显示图表。
                chartObj.setOption(option);

                $(window).resize(function () {
                    chartObj.resize();
                });
            },

            // 时间控件按钮

            refreshEchart:function (charttype,url,dateRange,admin_id,Coordinate){
        $.ajax({
            url : url,
            method : 'get',
            data : {
                date_range :dateRange,
                // type :charttype,
                admin_id :admin_id
            },
            success : function(data) {
                // console.log(charttype)
                console.log(data.data)
                if (data.code > 0) {
                    var fun= 'load'+Coordinate+'Echart';
                    console.log(fun);
                    Ymwlechart.echart[fun](charttype, data.data[charttype]);
                    return ;
                }
                layer.msg(data.msg, {'icon':2});
            },
        });
    },loadPieEchart: function (echartname, data) {
                console.log(data);
                //画饼状图
                option = {
                    title: {
                        text: '',
                        subtext: '',
                        left: 'center'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{c}%'
                    },
                    legend: {
                        orient: 'vertical',
                        left: 'left',
                    },
                    series: [
                        {
                            name: '跟进方式',
                            type: 'pie',
                            radius: '70%',
                            data: data,
                            label: {
                                formatter: '{b}: {@name} ({d}%)'
                            },
                            emphasis: {
                                itemStyle: {
                                    shadowBlur: 10,
                                    shadowOffsetX: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        }
                    ]
                };
                var pie = Echarts.init(document.getElementById(echartname+'-echart'));
                pie.setOption(option);
            },
        }
    };
        return Ymwlechart;
});