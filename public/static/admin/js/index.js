define(["jquery", "easy-admin", "miniAdmin", "miniTab"], function ($, ea, miniAdmin, miniTab) {

    /**
     * 刷新提醒管理菜单角标
     * 通过 AJAX 获取待处理提醒数量，更新左侧菜单中"提醒管理"项的红色角标
     */
    function refreshReminderBadge() {
        $.getJSON(ea.url('crm.reminder/getUnreadCount'), function (res) {
            if (res && res.code == 1) {
                var count = parseInt(res.count) || 0;
                // 查找菜单中包含 reminder/index 的菜单项
                var $menuLink = $('.layuimini-menu-left a[layuimini-href*="reminder/index"]');
                if ($menuLink.length === 0) return;
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
        });
    }

    // 暴露到全局，供 iframe 子页面调用
    window.refreshReminderBadge = refreshReminderBadge;

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
                    $('.license').html(result['msg']);
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
                    }
                }
            });

            ea.listen();
        },
        main: function () {
            miniTab.listen();
            ea.listen();
        },
    };
    return Controller;
});
