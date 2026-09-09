define(["jquery", "easy-admin"], function ($, ea) {

    /**
     * 后台电话拨号：编辑表单 input-tel 输入框 + 列表单元格 cell-tel 号码
     *
     * - 本模块仅由通用公共钩子 Plugin::adminLayoutScripts() 在编辑页/列表页输出加载
     *   (其余页面服务端直接输出空字符串，不会 require 本模块)
     * - DOM ready 后为每个 class="input-tel" 的电话输入框紧贴右侧渲染拨号图标
     * - 列表页通过 document 事件委托监听 class="cell-tel" 号码单元格点击
     *   (layui 表格翻页/搜索/排序会重建单元格 DOM，委托方式无需重复绑定)
     * - 点击后：校验号码 → 确认 → 提交 call.call_task/add 下发外呼任务
     * - 插件卸载时本文件由 AddonFileManager 移回插件目录，require errback 静默失败
     */

    /**
     * 发起呼叫：校验号码 → 确认 → 下发外呼任务
     *
     * @param {string} phone      被叫号码
     * @param {string} controller 来源控制器(crm.customer/crm.customer_contacts/crm.clue)
     * @param {number} id         来源记录ID(编辑页取 URL，列表页取 data-id)
     */
    function submitCall(phone, controller, id) {
        if (!phone) {
            layer.msg('请先填写联系电话');
            return;
        }
        var postData = {phone: phone, controller: controller, id: id};

        layer.confirm('确认向 ' + phone + ' 发起呼叫?', function (index) {
            layer.close(index);

            ea.request.ajax('post', {
                url: ea.url('call.call_task/add'),
                data: postData
            }, function (res) {
                layer.msg(res.msg || '呼叫指令已下发,请留意手机端自动拨号',{
                    time: 3000
                });
            });
        });
    }

    /**
     * 获取当前页面来源信息
     * - controller 取自 CONFIG.CONTROLLER(后台视图注入，如 crm.customer)
     * - id 取自 URL ?id=xx(编辑页携带；列表页由单元格 data-id 覆盖)
     */
    function getPageSource() {
        var controller = (typeof CONFIG !== 'undefined' && CONFIG.CONTROLLER) || '';
        var urlParams = new URLSearchParams(window.location.search);
        var id = parseInt(urlParams.get('id')) || 0;
        return {controller: controller, id: id};
    }

    function init() {
        // 注入一次图标样式
        if (!$('#call-dial-style').length) {
            $('head').append('<style id="call-dial-style">'
                + '.call-dial-btn{position:absolute;right:5px;top:50%;margin-top:-11px;width:22px;height:22px;line-height:22px;text-align:center;color:#1e9fff;cursor:pointer;z-index:2}'
                + '.call-dial-btn:hover{color:#0c84d4}'
                + '.call-dial-btn .fa-phone{font-size:22px}'
                + '.cell-tel{color:#1e9fff;cursor:pointer}'
                + '.cell-tel:hover{color:#0c84d4;text-decoration:underline}'
                + '</style>');
        }

        // 编辑表单：为每个电话输入框渲染图标(字段由 system_field 服务端渲染，class="layui-input input-tel")
        $('.input-tel').each(function () {
            var $input = $(this);
            if ($input.data('call-dial')) return;
            $input.data('call-dial', 1);

            // 图标定位容器：优先 layui-input-block，兜底取直接父级
            var $block = $input.closest('.layui-input-block');
            if (!$block.length) {
                $block = $input.parent();
            }
            $block.css('position', 'relative');
            $input.css('padding-right', '34px');

            $('<span class="call-dial-btn" title="拨号"><i class="fa fa-phone"></i></span>')
                .insertAfter($input)
                .on('click', function (e) {
                    e.stopPropagation();
                    var source = getPageSource();
                    submitCall($.trim($input.val()), source.controller, source.id);
                });
        });

        // 列表单元格：document 事件委托监听 cell-tel 点击(layui 表格单元格动态渲染)
        if (!$(document).data('call-cell-tel')) {
            $(document).data('call-cell-tel', 1);

            // 悬停补充提示(单元格动态渲染，无法初始化时逐个设置 title)
            $(document).on('mouseenter', '.cell-tel', function () {
                if (!$(this).attr('title')) {
                    $(this).attr('title', '点击拨打');
                }
            });

            $(document).on('click', '.cell-tel', function (e) {
                e.stopPropagation();
                var source = getPageSource();
                // 记录 ID 由单元格 data-id 提供(easy-admin.js tel 模板渲染)
                source.id = parseInt($(this).attr('data-id')) || 0;
                submitCall($.trim($(this).text()), source.controller, source.id);
            });
        }
    }

    $(function () { init(); });

    return {init: init};
});
