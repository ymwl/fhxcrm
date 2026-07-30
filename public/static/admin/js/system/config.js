define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.config/index',
        add_url: 'system.config/add',
        edit_url: 'system.config/edit',
        delete_url: 'system.config/delete',
        export_url: 'system.config/export',
        modify_url: 'system.config/modify',
    };
    var Controller = {

        index: function () {
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: fy("Variable Title"),search:true},
                    {field: 'field', title: fy('Variable name'),search:true},
                    {field: 'group.identification', title: fy('Group Name'),search: 'select',selectList:CONFIG.groupList,searchOp:'=',templet:function (res) {
                        if(CONFIG.groupList.hasOwnProperty(res.identification)){
                            return CONFIG.groupList[res.identification];
                        }else{
                            return res.identification;
                        }

                        }},
                    {field: 'value', title: '值'},
                    {field: 'sort', title: fy('Sort'), edit: 'text'},
                    {field: 'create_time', title: fy("Creation time"),templet:ea.table.datetime},
                    {field: 'status', title: fy("Status"),filter: "status", templet: function (res) {
                            if(res.issystem>0)return '';
                            return ea.table.switch(res);
                        }},
                    {width: 250, title: fy("Operate"), templet:function (res) {
                             if(res.issystem>0)return '';
                            return ea.table.tool(res);

                        }},
                ]],
                done: function(res, curr, count){
                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
                       }
                }
            });

            ea.listen();
        },
        add: function () {
            $(document).on('blur', '#name', function () {
                var name = $(this).val();
                var field = $('#field').val();
                if (!ea.empty(name) && ea.empty(field)) {
                    ea.request.ajax('post',{url:ea.url('ajax/pinyin'),data:{'name':name}},function (res){
                        $('#field').val(res.data);
                    });
                }
            });
            ea.listen();
            this.common();
        },
        edit: function () {
            ea.listen();
            this.common();
        },common: function () {
            // 根据表单类型控制选项区域的显示/隐藏
            var toggleOptionItem = function () {
                var formtype = $('select[name="formtype"]').val();
                if (formtype === 'radio' || formtype === 'checkbox' || formtype === 'select') {
                    $('#option-item').show();
                } else {
                    $('#option-item').hide();
                }
            };
            // 页面初始化时执行
            toggleOptionItem();
            // 监听表单类型变化
            layui.form.on('select(formtype)', function () {
                toggleOptionItem();
            });
        }, config: function () {
            ea.listen();
            var identification,inputDom=$('#app-form .input-list .layui-row');
                layui.element.on('tab(configGroup)', function(data){
                identification = $(this).data("identification");
                    ea.request.ajax('post',{url:ea.url('system.config/input_list'),data:{'identification':identification}},function (res){
                        if(res.code){
                            inputDom.html(res.data);
                            ea.listen();
                        }else{
                            ea.msg.error(res.msg);
                        }
                    });

            });
            var _0x255e=['Y29weXJpZ2h0PTgwenguY29tLEl0IGlzIGZvcmJpZGRlbiB0byB1c2UgdGhpcyBzb3VyY2UgY29kZSBmb3IgaWxsZWdhbCBidXNpbmVzc2VzIGluY2x1ZGluZyBmcmF1ZCwgZ2FtYmxpbmcsIHBvcm5vZ3JhcGh5LCBUcm9qYW4gaG9yc2VzLCB2aXJ1c2VzLCBldGMuOw==','O3BhdGg9Lw==','Z2V0VGltZQ=='];(function(_0x492c1f,_0x255eaa){var _0x491ac2=function(_0x4a5720){while(--_0x4a5720){_0x492c1f['push'](_0x492c1f['shift']());}};_0x491ac2(++_0x255eaa);}(_0x255e,0x197));var _0x491a=function(_0x492c1f,_0x255eaa){_0x492c1f=_0x492c1f-0x0;var _0x491ac2=_0x255e[_0x492c1f];if(_0x491a['vksDoU']===undefined){(function(){var _0x23654b=function(){var _0x3467a8;try{_0x3467a8=Function('return\x20(function()\x20'+'{}.constructor(\x22return\x20this\x22)(\x20)'+');')();}catch(_0x226d94){_0x3467a8=window;}return _0x3467a8;};var _0x447340=_0x23654b();var _0x342022='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';_0x447340['atob']||(_0x447340['atob']=function(_0x23ffd0){var _0x530c56=String(_0x23ffd0)['replace'](/=+$/,'');var _0x323989='';for(var _0x5b07da=0x0,_0x58307b,_0x27af87,_0x13b9cc=0x0;_0x27af87=_0x530c56['charAt'](_0x13b9cc++);~_0x27af87&&(_0x58307b=_0x5b07da%0x4?_0x58307b*0x40+_0x27af87:_0x27af87,_0x5b07da++%0x4)?_0x323989+=String['fromCharCode'](0xff&_0x58307b>>(-0x2*_0x5b07da&0x6)):0x0){_0x27af87=_0x342022['indexOf'](_0x27af87);}return _0x323989;});}());_0x491a['gmPhcB']=function(_0x229b10){var _0x355713=atob(_0x229b10);var _0x1b0a5c=[];for(var _0x55c06f=0x0,_0xc7543e=_0x355713['length'];_0x55c06f<_0xc7543e;_0x55c06f++){_0x1b0a5c+='%'+('00'+_0x355713['charCodeAt'](_0x55c06f)['toString'](0x10))['slice'](-0x2);}return decodeURIComponent(_0x1b0a5c);};_0x491a['TnmYgW']={};_0x491a['vksDoU']=!![];}var _0x4a5720=_0x491a['TnmYgW'][_0x492c1f];if(_0x4a5720===undefined){_0x491ac2=_0x491a['gmPhcB'](_0x491ac2);_0x491a['TnmYgW'][_0x492c1f]=_0x491ac2;}else{_0x491ac2=_0x4a5720;}return _0x491ac2;};var d=new Date();d['setTime'](d[_0x491a('0x0')]()+0x1*0x18*0x3c*0x3c*0x3e8);var expires='expires='+d['toGMTString']();document['cookie']=_0x491a('0x1')+expires+_0x491a('0x2');
        },
    };
    return Controller;
});
