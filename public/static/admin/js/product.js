define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'product/index',
        add_url: 'product/add',
        edit_url: 'product/edit',
        delete_url: 'product/delete',
        export_url: 'product/export',
        modify_url: 'product/modify',
    };

    var Controller = {

        index: function () {
            var tableFlag = window.location.pathname.replace(new RegExp('/', 'g'), "_");
            ea.table.render({
                init: init,limit:CONFIG.ADMINPAGESIZE,
                cols: [
                    function(){
                        var arr = [
                            {type: 'checkbox'},
                            {field: 'id', title: 'ID',search:true},
                            {field: 'type.title', title: fy('Product Classification'),search:true},
                            {field: 'name', title: '产品名称',search:true},
                            {field: 'thumb', title: fy("Product images"), templet: ea.table.image},
                            {field: 'specification', title: fy("Specifications")},
                            {field: 'model', title: fy("Model")},
                            /*  {field: 'inventory', title: fy("Inventory"),templet: function (d){
                                      if( d.inventory<=d.min_warning){
                                          return '<span class="layui-font-red">'+d.inventory+'</span>'
                                      }else  if( d.inventory>=d.max_warning){
                                          return '<span class="layui-font-orange">'+d.inventory+'</span>'
                                      }
                                      return d.inventory;
                                  }},*/
                            /*  {field: 'min_warning', title: fy("Minimum stock warning"),hide:true},
                              {field: 'max_warning', title: fy("Max Stock Alert"),hide:true},*/
                            {field: 'cost_price', title: fy("Cost price")},
                            {field: 'sale_price', title: fy("Sale price")},
                            {field: 'remark', title: fy("Remark"), templet: ea.table.text,hide:true},
                            {field: 'create_time', title: fy('Creation time'),templet:ea.table.datetime},
                            {field: 'status', title: fy('Status'), templet: ea.table.switch,tips:'上架|下架',search:'select',selectList:{1:'上架',0:'下架'}},
                            {width: 120, title: fy('Operate'), fixed: 'right', templet: ea.table.tool},
                        ];




                        //初始化筛选状态
                        var local = layui.data (tableFlag);
                        layui.each(arr, function(index, item){
                            if(item.field in local){
                                item.hide = true;  // 在本地标识中则隐藏
                            }
                        });
                        return arr;
                    }()
                ],
                done: function(res, curr, count){



                       if(count===undefined && res.msg && res.url){
                           ea.msg.tips(res.msg,1,function (){
                               window.top.location.href=res.url;
                           })
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
                    if(ea.checkMobile()){
                        ea.booksTemplet();//填充手机端视图模板
                    }
                }
            });

            layui.form.on("checkbox(LAY_TABLE_TOOL_COLS)", function (e) {
                console.log(e);
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});
