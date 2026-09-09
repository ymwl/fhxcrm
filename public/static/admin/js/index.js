define(["jquery", "easy-admin", "miniAdmin", "miniTab"], function ($, ea, miniAdmin, miniTab) {

    /**
     * 更新头部导航角标显示
     * 数量大于0时显示（超过99显示99+），否则隐藏
     */
    function setHeaderBadge(selector, count) {
        var $badge = $(selector);
        if ($badge.length === 0) return;
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : count).show();
        } else {
            $badge.hide();
        }
    }

    /**
     * 刷新提醒管理菜单角标与头部导航待办/提醒角标
     * 通过 AJAX 获取待处理提醒数量与待办总数，更新角标数字
     */
    function refreshReminderBadge() {
        $.getJSON(ea.url('crm.reminder/getUnreadCount'), function (res) {
            if (res && res.code == 1) {
                var count = parseInt(res.count) || 0;
                var backlog = parseInt(res.backlog) || 0;
                // 查找菜单中包含 reminder/index 的菜单项
                var $menuLink = $('.layuimini-menu-left a[layuimini-href*="reminder/index"]');
                if ($menuLink.length > 0) {
                    var $menuItem = $menuLink.closest('.menu-li, .menu-dd');
                    var $badge = $menuItem.find('.menu-badge');
                    if (count > 0) {
                        if ($badge.length === 0) {
                            // 在 <a> 标签内追加角标
                            $menuLink.css('position', 'relative').append('<span class="menu-badge">' + count + '</span>');
                        } else {
                            $badge.text(count).show();
                        }
                    } else {
                        $badge.remove();
                    }
                }
                // 头部导航待办/提醒角标（待办总数为我的所有待办事项之和）
                setHeaderBadge('#headerTodoBadge', backlog);
                setHeaderBadge('#headerReminderBadge', count);
            }
        });
    }

    // 暴露到全局，供 iframe 子页面调用
    window.refreshReminderBadge = refreshReminderBadge;

    /**
     * 绑定头部导航待办/提醒点击事件
     * 通过 miniTab 打开新标签页（固定标题，避免角标数字混入 tab 标题）
     */
    function bindHeaderNav() {
        $('[data-nav-href]').on('click', function () {
            var href = $(this).attr('data-nav-href');
            var title = $(this).attr('data-title') || $(this).text().trim();
            var loading = layui.layer.load(0, { shade: false, time: 2 * 1000 });
            if (!miniTab.check(href)) {
                miniTab.create({
                    tabId: href,
                    href: href,
                    title: title,
                    isIframe: false,
                    maxTabNum: 28,
                });
            }
            layui.element.tabChange('layuiminiTab', href);
            layui.layer.close(loading);
        });
    }

    var Controller = {
        index: function () {
            var options={};
            options = {
                iniUrl: ea.url('ajax/initAdmin'),    // 初始化接口
                clearUrl: ea.url("index/clear"), // 缓存清理接口
                urlHashLocation: true,      // 是否打开hash定位
                bgColorDefault: 3,      // 主题默认配置
                multiModule: true,          // 是否开启多模块
                menuChildOpen: false,       // 是否默认展开菜单
                loadingTime: 0,             // 初始化加载时间
                pageAnim: true,             // iframe窗口动画
                maxTabNum: 28,              // 最大的tab打开数量
            };
            miniAdmin.render(options);

            // 头部导航待办/提醒点击打开标签页
            bindHeaderNav();

            // 菜单渲染完成后初始化角标，并设置60秒轮询
            setTimeout(refreshReminderBadge, 1500);
            setInterval(refreshReminderBadge, 60000);

            $('.login-out').on("click", function () {
                window.location =ea.url('index/logout');
            });

            // 手机版扫码登录：点击头部按钮弹窗展示登录二维码
            $('.js-mobile-qrcode').on('click', function () {
                require(['qrcode'], function (QRCode) {
                    $.post(ea.url('index/scanLoginToken'), {}, function (res) {
                        if (res.code === 1 && res.data && res.data.qr_url) {
                            layui.layer.open({
                                type: 1,
                                title: '手机版扫码登录',
                                area: ['320px', '400px'],
                                shadeClose: true,
                                content: '<div style="padding:25px;text-align:center;">' +
                                    '<div id="scanLoginQrcode" style="display:inline-block;width:220px;height:220px;"></div>' +
                                    '<p style="margin-top:15px;color:#666;font-size:14px;">5分钟内直接扫一扫即可登录</p>' +
                                    '</div>',
                                success: function () {
                                    // 二维码内容由 qrcodejs 以 canvas 绘制，不拼接 HTML，避免 XSS
                                    new QRCode(document.getElementById('scanLoginQrcode'), {
                                        text: res.data.qr_url,
                                        width: 220,
                                        height: 220,
                                        correctLevel: QRCode.CorrectLevel.M
                                    });
                                }
                            });
                        } else {
                            layui.layer.msg(res.msg || '二维码生成失败');
                        }
                    }, 'json');
                });
            });

            //发送ajax请求，获取用户信息     license
            $.ajax({
                //几个参数需要注意一下
                type: "POST",//方法类型
                dataType: "json",//预期服务器返回的数据类型
                url:ea.url("crm.license/index") ,
                success: function (result) {
                  /*  $('.license').html(result['msg']);
                    // 验证失败时附加离线授权入口（局域网客户全程后台完成）
                    if (result['code'] === 0) {
                        $('.license').append(' <a href="javascript:;" id="licenseApplyCode" style="color:#1e9fff;">复制授权申请码</a> <a href="javascript:;" id="licenseUpload" style="color:#1e9fff;">上传授权文件</a>');
                        // 复制授权申请码：发给服务商离线签发 license.dat
                        $('#licenseApplyCode').on('click', function () {
                            $.post(ea.url('crm.license/applyCode'), {}, function (res) {
                                if (res.code === 1 && res.data && res.data.apply_code) {
                                    layui.layer.open({
                                        type: 1,
                                        title: '授权申请码（复制后发给服务商签发离线授权文件）',
                                        area: ['480px', '300px'],
                                        shadeClose: true,
                                        content: '<div style="padding:15px;"><textarea id="licenseApplyCodeText" class="layui-textarea" style="height:160px;" readonly></textarea><div style="margin-top:10px;text-align:center;"><button type="button" class="layui-btn layui-btn-sm" id="licenseApplyCodeCopy">复制</button></div></div>',
                                        success: function () {
                                            $('#licenseApplyCodeText').val(res.data.apply_code);
                                            $('#licenseApplyCodeCopy').on('click', function () {
                                                $('#licenseApplyCodeText')[0].select();
                                                document.execCommand('copy');
                                                layui.layer.msg('已复制');
                                            });
                                        }
                                    });
                                } else {
                                    layui.layer.msg(res.msg || '获取申请码失败');
                                }
                            }, 'json');
                        });
                        // 上传服务商回传的 license.dat 授权文件（仅限.dat，服务端严格校验后自动转为防下载格式）
                        $('#licenseUpload').on('click', function () {
                            var fileInput = $('<input type="file" accept=".dat" style="display:none;">');
                            fileInput.on('change', function () {
                                if (!this.files || !this.files.length) return;
                                var formData = new FormData();
                                formData.append('file', this.files[0]);
                                $.ajax({
                                    type: 'POST',
                                    url: ea.url('crm.license/uploadLicense'),
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    dataType: 'json',
                                    success: function (res) {
                                        layui.layer.msg(res.msg || '上传完成');
                                        if (res.code === 1) {
                                            setTimeout(function () { window.location.reload(); }, 1200);
                                        }
                                    },
                                    error: function () {
                                        layui.layer.msg('上传失败，请重试');
                                    }
                                });
                            });
                            fileInput.trigger('click');
                        });
                    }*/
                }
            });

            ea.listen();
        },
        main: function () {
            miniTab.listen();
            ea.listen();

            // 待办事项卡片头部总角标：服务端渲染初始值，AJAX 获取 backlog 刷新（60秒轮询，与头部导航角标同源）
            (function refreshMainTodoBadge() {
                var $badge = $('#mainTodoBadge');
                if ($badge.length === 0) return;
                $.getJSON(ea.url('crm.reminder/getUnreadCount'), function (res) {
                    if (res && res.code == 1) {
                        var backlog = parseInt(res.backlog) || 0;
                        if (backlog > 0) {
                            $badge.text(backlog > 99 ? '99+' : backlog).show();
                        } else {
                            $badge.hide();
                        }
                    }
                });
                setTimeout(refreshMainTodoBadge, 60000);
            })();

            // 跟进率进度条（宽度由 data-rate 控制）
            $('.rate-bar-inner').each(function () {
                var rate = parseFloat($(this).attr('data-rate')) || 0;
                $(this).css('width', rate + '%');
            });

            // 系统公告：点击横幅公告条弹出完整富文本（对齐手机端交互）
            $('#welcome-notice').on('click', function () {
                var html = $('#welcome-notice-html').html() || '';
                if (!html) return;
                layui.layer.open({
                    type: 1,
                    title: '系统公告',
                    area: ['560px', ''],
                    maxHeight: 420,
                    skin: 'notice-layer',
                    shadeClose: true,
                    content: '<div class="notice-pop-body">' + html + '</div>'
                });
            });

            // 首页图表初始化（延时等待 iframe 内容渲染完成）
            setTimeout(function () {
                if (!CONFIG.erchart) return;
                require(['plugs/echarts/ymwlechart.min', 'echarts'], function (Ymwlechart, echarts) {
                    // 1. 客户量趋势折线图 + 跟进方式环形图（共享时间段筛选，快捷时间段/指定日期范围 → AJAX 刷新，默认近30天口径一致）
                    if ($('#main_customer-echart').length && CONFIG.erchart && CONFIG.erchart.main_customer) {
                        var customerChart = null;
                        var renderCustomer = function (data) {
                            if (!data) return;
                            if (!customerChart) {
                                customerChart = echarts.init(document.getElementById('main_customer-echart'), 'walden');
                                $(window).on('resize', function () { customerChart.resize(); });
                            }
                            var legend = [], series = [];
                            for (var i in data.data) {
                                legend.push(i);
                                series.push({
                                    name: i,
                                    type: 'line',
                                    smooth: true,
                                    areaStyle: {},
                                    lineStyle: { width: 1.5 },
                                    data: data.data[i]
                                });
                            }
                            customerChart.setOption({
                                title: { text: '', subtext: '' },
                                tooltip: { trigger: 'axis' },
                                legend: { data: legend },
                                grid: { left: 'left', top: 'top', right: '10', bottom: 30 },
                                xAxis: { type: 'category', boundaryGap: false, data: data.date },
                                yAxis: {},
                                series: series
                            }, true);
                        };
                        renderCustomer(CONFIG.erchart.main_customer);

                        // 加载客户量趋势数据：date_range 形如 "2026-07-01 00:00:00 - 2026-07-31 23:59:59"
                        var loadCustomer = function (dateRange) {
                            $.get(ea.url('index/customerEchart'), { date_range: dateRange }, function (res) {
                                if (res.code == 1) {
                                    renderCustomer(res.data.main_customer);
                                    // 标题栏时间段标签更新为短格式 YYYY-MM-DD - YYYY-MM-DD
                                    var parts = dateRange.split(' - ');
                                    $('#main-customer-tag').text((parts[0] || '').split(' ')[0] + ' - ' + (parts[1] || '').split(' ')[0]);
                                } else {
                                    layui.layer.msg(res.msg || '加载失败');
                                }
                            }, 'json');
                        };
                        // 快捷时间段按钮（data-value 与 ea.daterange 关键词一致）
                        $('#main_customer-echart').closest('.layui-card').find('.trend-btn').on('click', function () {
                            var dateRange = ea.daterange($(this).attr('data-value'));
                            $('#main-customer-date').val(dateRange);
                            loadCustomer(dateRange);
                        });
                        // 指定日期范围选择（默认回显最近30天）
                        $('#main-customer-date').val(ea.daterange('last30Days'));
                        layui.laydate.render({
                            elem: '#main-customer-date',
                            type: 'datetime',
                            trigger: 'click',
                            range: true, // 开启日期范围，默认使用“-”分割
                            done: function (value) {
                                if (value) loadCustomer(value);
                            }
                        });
                    }
                    // 2. 线索量趋势折线图（快捷时间段/指定日期范围 → AJAX 刷新，默认近30天口径一致）
                    if ($('#main_clue_customer-echart').length && CONFIG.erchart && CONFIG.erchart.main_clue_customer) {
                        var clueChart = null;
                        var renderClue = function (data) {
                            if (!data) return;
                            if (!clueChart) {
                                clueChart = echarts.init(document.getElementById('main_clue_customer-echart'), 'walden');
                                $(window).on('resize', function () { clueChart.resize(); });
                            }
                            var legend = [], series = [];
                            for (var i in data.data) {
                                legend.push(i);
                                series.push({
                                    name: i,
                                    type: 'line',
                                    smooth: true,
                                    areaStyle: {},
                                    lineStyle: { width: 1.5 },
                                    data: data.data[i]
                                });
                            }
                            clueChart.setOption({
                                title: { text: '', subtext: '' },
                                tooltip: { trigger: 'axis' },
                                legend: { data: legend },
                                grid: { left: 'left', top: 'top', right: '10', bottom: 30 },
                                xAxis: { type: 'category', boundaryGap: false, data: data.date },
                                yAxis: {},
                                series: series
                            }, true);
                        };
                        renderClue(CONFIG.erchart.main_clue_customer);

                        // 加载线索量趋势数据：date_range 形如 "2026-07-01 00:00:00 - 2026-07-31 23:59:59"
                        var loadClue = function (dateRange) {
                            $.get(ea.url('index/clueEchart'), { date_range: dateRange }, function (res) {
                                if (res.code == 1) {
                                    renderClue(res.data.main_clue_customer);
                                    // 标题栏时间段标签更新为短格式 YYYY-MM-DD - YYYY-MM-DD
                                    var parts = dateRange.split(' - ');
                                    $('#main-clue-tag').text((parts[0] || '').split(' ')[0] + ' - ' + (parts[1] || '').split(' ')[0]);
                                } else {
                                    layui.layer.msg(res.msg || '加载失败');
                                }
                            }, 'json');
                        };
                        // 快捷时间段按钮（data-value 与 ea.daterange 关键词一致）
                        $('#main_clue_customer-echart').closest('.layui-card').find('.trend-btn').on('click', function () {
                            var dateRange = ea.daterange($(this).attr('data-value'));
                            $('#main-clue-date').val(dateRange);
                            loadClue(dateRange);
                        });
                        // 指定日期范围选择（默认回显最近30天）
                        $('#main-clue-date').val(ea.daterange('last30Days'));
                        layui.laydate.render({
                            elem: '#main-clue-date',
                            type: 'datetime',
                            trigger: 'click',
                            range: true, // 开启日期范围，默认使用“-”分割
                            done: function (value) {
                                if (value) loadClue(value);
                            }
                        });
                    }
                    // 3. 业绩概况仪表盘（业绩方式/年份/月份切换 → AJAX 刷新完成率）
                    if ($('#achievement-echart').length && CONFIG.achievement) {
                        var achDom = document.getElementById('achievement-echart');
                        var achChart = echarts.init(achDom, 'walden');
                        // 完成率颜色区间（与手机端一致）：≤20%蓝 >20%绿 >80%金
                        var achColor = function (percent) {
                            return percent > 80 ? '#faad14' : (percent > 20 ? '#2fc25b' : '#1890ff');
                        };
                        var achRealPercent = CONFIG.achievement.complete_percent;
                        var achOption = {
                            series: [{
                                type: 'gauge',
                                // 与手机端 uCharts 仪表盘一致：270°表盘、底部开口
                                startAngle: 225,
                                endAngle: -45,
                                min: 0,
                                max: 100,
                                splitNumber: 10,
                                radius: '95%',
                                center: ['50%', '50%'],
                                // 色带：0-20%蓝 20-80%绿 80-100%金（与手机端 categories 一致）
                                axisLine: { lineStyle: { width: 30, color: [[0.2, '#1890ff'], [0.8, '#2fc25b'], [1, '#faad14']] } },
                                // 宽三角指针，颜色随完成率区间变化（与手机端 pointer auto 一致）
                                pointer: { itemStyle: { color: 'auto' }, length: '60%', width: 14 },
                                // 主刻度：白色贯穿色带；次刻度：白色、色带宽度的1/3（与手机端 splitLine 一致）
                                splitLine: { distance: -12, length: 30, lineStyle: { color: '#fff', width: 2 } },
                                axisTick: { distance: -12, splitNumber: 5, length: 10, lineStyle: { color: '#fff', width: 1 } },
                                // 数字刻度位于色带内侧（与手机端 labelOffset 一致）
                                axisLabel: { distance: 5, color: '#666', fontSize: 12 },
                                // 指针根部中心小白点（与手机端一致）
                                anchor: { show: true, showAbove: true, size: 8, itemStyle: { color: '#fff' } },
                                // 中心文字：大号完成率在上、“完成率”小字在下（与手机端 subtitle/title 布局一致）
                                title: { offsetCenter: [0, '50%'], fontSize: 14, color: achColor(achRealPercent) },
                                detail: {
                                    valueAnimation: true,
                                    // 指针封顶100，文字显示真实完成率
                                    formatter: function () { return achRealPercent + '%'; },
                                    offsetCenter: [0, '28%'],
                                    fontSize: 28,
                                    fontWeight: 'bolder',
                                    color: 'auto'
                                },
                                data: [{ value: Math.min(achRealPercent, 100), name: '完成率' }]
                            }]
                        };
                        achChart.setOption(achOption);
                        $(window).on('resize', function () { achChart.resize(); });

                        // 按所选业绩方式/月份重新统计（月份选择器值形如 YYYY-MM，已包含年份；清空则按全年统计）
                        var refreshAchievement = function () {
                            var cfg = $('#achievement-config').val();
                            var year = new Date().getFullYear();
                            var month = 0;
                            var mVal = $('#achievement-month').val();
                            if (mVal) {
                                var mParts = mVal.split('-');
                                year = parseInt(mParts[0]) || year;
                                month = parseInt(mParts[1]) || 0;
                            }
                            $.post(ea.url('index/achievement'), {
                                row_config: cfg,
                                row_year: year,
                                row_month: month
                            }, function (res) {
                                if (res.code == 1) {
                                    achRealPercent = res.data.complete_percent;
                                    achOption.series[0].data[0].value = Math.min(achRealPercent, 100);
                                    // 标题颜色随完成率区间变化（中心标签固定为“完成率”，与手机端一致）
                                    achOption.series[0].title.color = achColor(achRealPercent);
                                    achChart.setOption(achOption);
                                    // 目标前缀随业绩方式变化，避免“目标”指代不明
                                    var targetLabel = { '1': '合同目标', '2': '回款目标', '3': '订单目标' };
                                    $('#achievement-target-label').text(targetLabel[cfg] || '目标');
                                    $('#achievement-yeartarget').text(res.data.yeartarget);
                                    $('#achievement-contract').text(res.data.contract_money);
                                    $('#achievement-receivables').text(res.data.receivables_money);
                                    $('#achievement-order').text(res.data.order_money);
                                }
                            }, 'json');
                        };
                        $('#achievement-config').on('change', refreshAchievement);
                        layui.laydate.render({
                            elem: '#achievement-month',
                            type: 'month',
                            value: String(CONFIG.achievement.year) + '-' + (CONFIG.achievement.month < 10 ? '0' + CONFIG.achievement.month : CONFIG.achievement.month),
                            done: refreshAchievement
                        });
                    }

                    // 3.5 跟进方式占比（跟进来源/月份切换 → AJAX 刷新；无数据时展示空状态提示）
                    if ($('#main_record_type-echart').length && CONFIG.erchart && typeof CONFIG.erchart.main_record_type !== 'undefined') {
                        var rtDom = document.getElementById('main_record_type-echart');
                        var rtChart = null;
                        var renderRecordType = function (typeData) {
                            if (!typeData || typeData.length === 0) {
                                // 空状态：销毁已有实例后显示提示
                                if (rtChart) {
                                    rtChart.dispose();
                                    rtChart = null;
                                }
                                rtDom.innerHTML = '<div class="main-empty" style="height:100%;display:flex;align-items:center;justify-content:center;padding:0;">所选时间段暂无跟进记录</div>';
                                return;
                            }
                            if (!rtChart) {
                                // 清除空状态占位后再初始化
                                rtDom.innerHTML = '';
                                rtChart = echarts.init(rtDom, 'walden');
                            }
                            rtChart.setOption({
                                tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
                                legend: { orient: 'vertical', left: 'left', bottom: 10 },
                                series: [{
                                    name: '跟进方式',
                                    type: 'pie',
                                    radius: ['45%', '70%'],
                                    center: ['50%', '45%'],
                                    avoidLabelOverlap: true,
                                    itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
                                    label: { show: true, formatter: '{b}\n{d}%' },
                                    emphasis: { label: { show: true, fontSize: 14, fontWeight: 'bold' } },
                                    data: typeData
                                }]
                            }, true);
                        };
                        // 窗口缩放自适应（只绑定一次，实例重建后仍生效）
                        $(window).on('resize', function () { if (rtChart) rtChart.resize(); });
                        renderRecordType(CONFIG.erchart.main_record_type);

                        // 月份选择器值（YYYY-MM）转日期范围字符串；未选月份按当前年全年统计（与 placeholder 口径一致）
                        var pad2 = function (n) { return n < 10 ? '0' + n : '' + n; };
                        var buildFollowRange = function () {
                            var year = new Date().getFullYear();
                            var mVal = $('#follow-month').val();
                            if (mVal) {
                                var parts = mVal.split('-');
                                var y = parseInt(parts[0]) || year;
                                var m = parseInt(parts[1]) || 0;
                                if (m > 0) {
                                    var lastDay = new Date(y, m, 0).getDate();
                                    return y + '-' + pad2(m) + '-01 00:00:00 - ' + y + '-' + pad2(m) + '-' + pad2(lastDay) + ' 23:59:59';
                                }
                                return y + '-01-01 00:00:00 - ' + y + '-12-31 23:59:59';
                            }
                            return year + '-01-01 00:00:00 - ' + year + '-12-31 23:59:59';
                        };
                        // 按所选跟进来源/月份重新统计
                        var refreshRecordType = function () {
                            $.get(ea.url('index/recordTypeEchart'), {
                                row_source: $('#follow-config').val(),
                                date_range: buildFollowRange()
                            }, function (res) {
                                if (res.code == 1) {
                                    renderRecordType(res.data.main_record_type);
                                } else {
                                    layui.layer.msg(res.msg || '加载失败');
                                }
                            }, 'json');
                        };
                        $('#follow-config').on('change', refreshRecordType);
                        // 月份选择器：默认留空（全年口径），清空时 done 同样触发刷新
                        layui.laydate.render({
                            elem: '#follow-month',
                            type: 'month',
                            trigger: 'click',
                            done: refreshRecordType
                        });
                    }

                    // 4. 合同|回款|订单 金额与数量趋势（快捷时间段/指定日期范围 → AJAX 刷新，金额左轴、数量右轴）
                    if ($('#trend-echart').length && CONFIG.erchart && CONFIG.erchart.trend) {
                        var trendChart = null;
                        var renderTrend = function (data) {
                            if (!data) return;
                            if (!trendChart) {
                                trendChart = echarts.init(document.getElementById('trend-echart'), 'walden');
                                $(window).on('resize', function () { trendChart.resize(); });
                            }
                            var legend = [], series = [];
                            for (var i in data.data) {
                                legend.push(i);
                                series.push({
                                    name: i,
                                    type: 'line',
                                    smooth: true,
                                    yAxisIndex: i.indexOf('数量') !== -1 ? 1 : 0, // 数量类系列走右轴，金额类走左轴
                                    areaStyle: {},
                                    lineStyle: { width: 1.5 },
                                    data: data.data[i]
                                });
                            }
                            trendChart.setOption({
                                tooltip: {
                                    trigger: 'axis',
                                    formatter: function (params) {
                                        var res = params[0].axisValue + '<br/>';
                                        for (var j = 0; j < params.length; j++) {
                                            var val = Number(params[j].value) || 0;
                                            var isCount = params[j].seriesName.indexOf('数量') !== -1;
                                            res += params[j].marker + params[j].seriesName + '：' + (isCount ? val : val.toLocaleString()) + '<br/>';
                                        }
                                        return res;
                                    }
                                },
                                legend: { data: legend },
                                grid: { left: 70, right: 50, top: 40, bottom: 30 },
                                xAxis: { type: 'category', boundaryGap: false, data: data.date },
                                yAxis: [
                                    { type: 'value', name: '金额', axisLabel: { formatter: function (v) { return v >= 10000 ? (v / 10000) + '万' : v; } } },
                                    { type: 'value', name: '数量', splitLine: { show: false } }
                                ],
                                series: series
                            }, true);
                        };
                        renderTrend(CONFIG.erchart.trend);

                        // 加载趋势数据：date_range 形如 "2026-07-01 00:00:00 - 2026-07-31 23:59:59"
                        var loadTrend = function (dateRange) {
                            $.get(ea.url('index/achievementEchart'), { date_range: dateRange }, function (res) {
                                if (res.code == 1) {
                                    renderTrend(res.data.trend);
                                } else {
                                    layui.layer.msg(res.msg || '加载失败');
                                }
                            }, 'json');
                        };
                        // 快捷时间段按钮（data-value 与 ea.daterange 关键词一致）
                        $('#trend-echart').closest('.layui-card').find('.trend-btn').on('click', function () {
                            var dateRange = ea.daterange($(this).attr('data-value'));
                            $('#trend-date').val(dateRange);
                            loadTrend(dateRange);
                        });
                        // 指定日期范围选择（默认回显最近30天）
                        $('#trend-date').val(ea.daterange('last30Days'));
                        layui.laydate.render({
                            elem: '#trend-date',
                            type: 'datetime',
                            trigger: 'click',
                            range: true, // 开启日期范围，默认使用“-”分割
                            done: function (value) {
                                if (value) loadTrend(value);
                            }
                        });
                    }
                });
            }, 200);
        },
    };

return Controller;
});
