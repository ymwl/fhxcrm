
define(['webuploader'], function (webUploader)
{
    var $ = layui.$
        // ,webUploader= layui.webuploader
        ,element = layui.element
        ,table=layui.table
        ,layer=layui.layer
        ,rowData=[]//保存上传文件属性集合,添加table用
        ,fileSize=100*1024*1024//默认上传文件大小
        ,chunkSize=5*1024*1024//默认文件片段
        ,fileType='doc,docx,pdf,xls,xlsx,ppt,pptx,gif,jpg,jpeg,bmp,png,rar,zip'
        ,headers
        ,nowtime=0;
    var index=0;
    var fileAllNum=0;
    var fileAllSize=0;
    var successNum=0;
    var successSize=0;
    var  percentages = {}; // 所有文件的进度信息，key为file id
    var fList=[];
    var fileBoxEle="#fileBoxEle";
    //加载样式
    layui.link(CONFIG.MY_PUBLIC+'/static/plugs/webupload/uploader/webuploader.css');

    var Class = function (options) {

        this.options=options;
        this.register(this);
        this.init(this);
        this.events(this);
    };
    Class.prototype.init=function(that){

        options=that.options;
        if(!that.strIsNull(options.size)){
            fileSize=options.size
        }
        if(!that.strIsNull(options.chunkSize)){
            chunkSize=options.chunkSize;
        }
        if(!that.strIsNull(that.options.fileType)){
            fileType=that.options.fileType;
        }
        this.createUploader(options,that,fileType,fileSize,chunkSize);
    };

    Class.prototype.createUploader=function(options,that,fileType,fileSize,chunkSize){
        this.options.elementHtml = $(options.elem).html();
        console.log('that.buildFileType(fileType)=',that.buildFileType(fileType));
        this.uplaod = webUploader.create({
            // 不压缩image
            resize: false,
            // swf文件路径
            swf: 'uploader/Uploader.swf',
            // 默认文件接收服务端。
            server: options.url,
            pick: {
                id: options.elem,//指定选择文件的按钮容器，不指定则不创建按钮。注意 这里虽然写的是 id, 不仅支持 id, 还支持 class, 或者 dom 节点。
                multiple :options.multiple //开启文件多选
            },
            // fileSingleSizeLimit:fileSize,//单个文件大小
            //接收文件类型--自行添加options


            accept:[{
                title: 'file',
                extensions: fileType,
                mimeTypes: that.buildFileType(fileType)
            }],
            // 单位字节，如果图片大小小于此值，不会采用压缩。512k  512*1024，如果设置为0，原图尺寸大于设置的尺寸就会压缩；如果大于0，只有在原图尺寸大于设置的尺寸，并且图片大小大于此值，才会压缩
            compress: false,auto:true,
            fileNumLimit : options.fileNumLimit,//验证文件总数量, 超出则不允许加入队列,默认值：undefined,如果不配置，则不限制数量
            fileSizeLimit : 100*1024*1024*1024, //1kb=1024*1024,验证文件总大小是否超出限制, 超出则不允许加入队列。
            fileSingleSizeLimit:fileSize, //验证单个文件大小是否超出限制, 超出则不允许加入队列。
            chunked:true,//是否开启分片上传
            threads:1,
            chunkSize:chunkSize,//如果要分片，每一片的文件大小
            prepareNextFile:false//在上传当前文件时，准备好下一个文件,请设置成false，不然开启文件多选你浏览器会卡死
            ,duplicate :true
        });
// UploadAccept回调函数
        this.uplaod.onUploadAccept=function (file, response){
            console.log('UploadAccept', file, response);
            // 在这里可以对服务端返回的 JSON 进行自定义处理
            if(!response.code){
                options.error(response);
                return false;
            }else{
                return true;
            }
         /*   if (response.code === 0) {
                console.log('上传成功');
            } else {
                console.log('上传失败');
            }*/

        }
        this.uplaod.onError = function (code) {
            var msg='';
            switch (code) {
                case "Q_TYPE_DENIED":
                    msg='不允许上传此类文件!';
                    break;
                case "Q_EXCEED_NUM_LIMIT":
                    msg = "超过允许上传的文件数据";
                    break;
                case "F_DUPLICATE":
                    msg = "文件重复添加！";
                    break;
                case "F_EXCEED_SIZE":
                    msg=fy("The single uploaded one is too large")+'! <br>'+fy("Maximum support")+' '+that.formatFileSize(fileSize)+'!';
                    break;
            }
            if (options.error) {
                options.error({msg:msg});
            }else{
                alert(code);
            }

        };


    };

    Class.prototype.formatFileSize=function(size){
        var fileSize =0;
        if(size/1024>1024){
            var len = size/1024/1024;
            fileSize = len.toFixed(2) +"MB";
        }else if(size/1024/1024>1024){
            var len = size/1024/1024;
            fileSize = len.toFixed(2)+"GB";
        }else{
            var len = size/1024;
            fileSize = len.toFixed(2)+"KB";
        }
        return fileSize;
    };

    Class.prototype.buildFileType=function (type) {
        var ts = type.split(',');
        var ty='';

        for(var i=0;i<ts.length;i++){
            ty=ty+ "."+ts[i]+",";
        }
        return  ty.substring(0, ty.length - 1)
    };

    Class.prototype.strIsNull=function (str) {
        if(typeof str == "undefined" || str == null || str == "")
            return true;
        else
            return false;
    };

    Class.prototype.events=function (that) {
        //当文件添加进去
        that.uplaod.on('fileQueued', function( file ){
            //console.log("file:",file);
            // var fileSize = that.formatFileSize(file.size);
            element.render('progress');
        });

        //监听进度条,更新进度条信息
        that.uplaod.on( 'uploadProgress', function( file, percentage ) {
            $(that.options.elem).prop("disabled", true).find(".webuploader-pick").html('<i class="fa fa-upload"></i> 上传'+(percentage * 100).toFixed(0)+'%');
        });


        /**上传之前**/
        that.uplaod.on('uploadBeforeSend', function( block, data, headers ) {

            nowtime=new Date().getTime();
            data.fileMd5 = block.file.fileMd5;
            //block.file.chunks = block.chunks;//当前文件总分片数量
            //console.log("block:",block);
            data.contentType=block.file.type;
            data.chunks = block.file.chunks;
            data.zoneTotalMd5= block.file.fileMd5;
            data.zoneMd5=block.zoneMd5;
            data.zoneTotalCount=block.chunks;
            data.zoneNowIndex=block.chunk;
            data.zoneTotalSize=block.total;
            data.zoneStartSize=block.start;
            data.zoneEndSize=block.end;
            /* if(that.isEmpty(that.options.headers)){
                 headers=that.options.headers;
             }*/

            // headers.Authorization=that.options.headers.Authorization;
        });
        /**从文件队列移除**/
        that.uplaod.on('fileDequeued', function( file ) {
            fileAllNum--;
            fileAllSize-=file.size;
            delete percentages[ file.id ];
        });

        //移除上传的文件
        table.on('tool(extend-uploader-form)', function(obj){
            var data = obj.data;
            if(obj.event === 'del'){
                rowData=that.removeArray(rowData,data.fileId);
                fList=that.removeArray(fList,data.fileId);
                that.uplaod.removeFile(data.fileId,true);

                obj.del();
                //通知后台删除文件
                //console.log("data:",data);
                //console.log("data.fileId:",data.fileId);
            }else if(obj.event=== 'stop'){//暂停、继续
                // if(){
                //
                // }
                that.uplaod.stop(true);

            }
        });

        //开始上传
        $("#extent-button-uploader").click(function () {
            that.uploadToServer();
        });

        //当文件上传成功时触发。file {ArchivesFile} File对象, response {Object}服务端返回的数据
        that.uplaod.on('uploadSuccess',function(file,response){
            /*  if(response.success){
                  that.setTableBtn(file.id,'正在校验文件...');
              }*/
        })
        //所有文件上传成功后
        that.uplaod.on('uploadFinished',function(){//成功后
            // $(that.options.elem).text("开始上传");
            // $("#extent-button-uploader").removeClass('layui-btn-disabled');
        });

    };

    Class.prototype.reloadData=function(data){
        layui.table.reload('extend-uploader-form',{
            data : data
        });
    };

    Class.prototype.register=function (that) {
        options = that.options;

        headers=options.headers||{};
        var fileCheckUrl=options.fileCheckUrl;//检测文件是否存在url
        var checkChunkUrl=options.checkChunkUrl;//检测分片url
        var mergeChunksUrl=options.mergeChunksUrl;//合并文件请求地址

        //监控文件上传的三个时间点(注意：该段代码必须放在WebUploader.create之前)
        //时间点1：:所有分块进行上传之前（1.可以计算文件的唯一标记;2.可以判断是否秒传）
        //时间点2： 如果分块上传，每个分块上传之前（1.询问后台该分块是否已经保存成功，用于断点续传）
        //时间点3：所有分块上传成功之后（1.通知后台进行分块文件的合并工作）
        // 卸载
        webUploader.Uploader.unRegister('beforeSendFile');
        webUploader.Uploader.register({
            "before-send-file":"beforeSendFile",
            "before-send":"beforeSend",
            "after-send-file":"afterSendFile",
            name: 'beforeSendFile',
        },{
            //时间点1：:所有分块进行上传之前调用此函数
            beforeSendFile:function(file){//利用md5File（）方法计算文件的唯一标记符
                //创建一个deffered
                that.options.start();
                var deferred = webUploader.Deferred();
                //1.计算文件的唯一标记，用于断点续传和秒传,获取文件前20m的md5值，越小越快，防止碰撞，把文件名文件大小和md5拼接作为文件唯一标识
                (new webUploader.Uploader()).md5File( file )
                    // 及时显示进度
                    .progress(function(percentage) {
                        console.log('Percentage:', percentage);
                    })
                    // 完成
                    .then(function(val) {
                        // console.log('md5 result:', val);
                        file.fileMd5=val;
                        $.ajax({
                            type:"POST",
                            url:fileCheckUrl,
                            headers:headers,
                            data:{
                                checkType:1,
                                contentType:file.type,
                                ext:file.ext,
                                zoneTotalMd5:val
                            },
                            dataType:"json",
                            success:function(response){
                                if(response.exist){
                                    that.uplaod.skipFile(file);
                                    $(that.options.elem).prop("disabled", false).find(".webuploader-pick").html(that.options.elementHtml);

                                    that.options.done(response);
                                    successNum++;
                                    successSize+=file.size;
                                    //如果存在，则跳过该文件，秒传成功
                                    fList.push(response.data);
                                    deferred.reject();
                                }else{
                                    deferred.resolve();
                                }
                            }
                        });
                    });

                //返回deffered
                return deferred.promise();
            },
            //时间点2：如果有分块上传，则 每个分块上传之前调用此函数
            //block:代表当前分块对象
            beforeSend:function(block){//向后台发送当前文件的唯一标记，用于后台创建保存分块文件的目录
                //1.请求后台是否保存过当前分块，如果存在，则跳过该分块文件，实现断点续传功能
                var deferred = webUploader.Deferred();
                //请求后台是否保存完成该文件信息，如果保存过，则跳过，如果没有，则发送该分块内容
                (new webUploader.Uploader()).md5File(block.file,block.start,block.end).progress(function(percentage){
                }).then(function(val){
                    block.zoneMd5=val;

                    $.ajax({
                            type:"POST",
                            url:checkChunkUrl,
                            headers:headers,
                            data:{
                                checkType:2,
                                //总的md5
                                zoneTotalMd5:block.file.fileMd5,
                                //块的md5
                                zoneMd5:val
                            },
                            dataType:"json",
                            success:function(response){
                                if(response.exist){
                                    //分块存在，跳过该分块
                                    deferred.reject();
                                }else{
                                    //分块不存在或者不完整，重新发送该分块内容
                                    deferred.resolve();
                                }
                            }
                        }
                    );
                });
                return deferred.promise();
            },
            //时间点3：所有分块上传成功之后调用此函数
            afterSendFile:function(file){//前台通知后台合并文件
                //1.如果分块上传，则通过后台合并所有分块文件
                //请求后台合并文件
                console.log('所有分块上传成功之后调用此函数');
                console.log(that);
                $.ajax({
                    type:"POST",
                    url:mergeChunksUrl,
                    headers:headers,
                    data:{'filemd5':file.fileMd5},
                    dataType:"JSON",
                    success:function(resdata){
                        if(resdata.code){
                            that.uplaod.skipFile(file);
                            that.setTableBtn(file.id,'上传成功');
                            $(that.options.elem).prop("disabled", false).find(".webuploader-pick").html(that.options.elementHtml);
                            that.options.done(resdata);
                            successNum++;
                            successSize+=file.size;
                            var data=resdata.data.fileInfo;
                            fList.push(data);
                        }
                    }
                });
            }
        });
    };

    /***
     * 注意更改了table列的位置,或自行新增了表格,请自行在这修改
     */
    Class.prototype.getTableHead=function (field) {
        //获取table头的单元格class,保证动态设置table内容后单元格不变形
        var div = $("#extend-uploader-form").next().find('div[class="layui-table-header"]');
        var div2 = div[0];
        var table = $(div2).find('table');
        var td = table.find('th[data-field="'+field+'"]').find('div').attr('class');
        return td;
    };

    Class.prototype.setTableBtn=function (fileId,val) {
        var td = this.getTableHead('oper');
        //获取操作栏,修改其状态
        var table = $("#extend-uploader-form").next().find('div[class="layui-table-body layui-table-main"]').find('table');
        var pro = table.find('td[data-field="progress"]');
        for(var i=0;i<pro.length;i++){
            var d = $(pro[i]).attr('data-content');
            if(d==fileId ){
                var t = $(pro[i]).next();
                t.empty();
                t.append('<div class="'+td+'"><a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="ok">'+val+'</a></div>')
            }
        }
    };

    Class.prototype.uploadToServer=function () {
        if(rowData.length<=0){
            layer.msg('没有上传的文件', {icon: 5});
            return;
        }
        $("#extent-button-uploader").text("正在上传，请稍等...");
        $("#extent-button-uploader").addClass('layui-btn-disabled');
        that.uplaod.upload();
    };

    Class.prototype.removeArray=function (array,fileId) {
        for(var i=0;i<array.length;i++){
            if(array[i].fileId==fileId){
                array.splice(i,1);
            }
        }
        return array;
    };

    Class.prototype.getData=function () {
        var files= [];
        for(var i=0;i<fList.length;i++){
            if(this.isEmpty(fList[i])){
                files.push(fList[i]);
            }
        }
        var obj = {};
        files = files.reduce(function(item, next) {//去重，第二次上传在获取时，会重复
            obj[next.md5Value] ? '' : obj[next.md5Value] = true && item.push(next);
            return item;
        }, []);
        return files;
    }
    Class.prototype.isEmpty=function (value){
        if (value != null && value != undefined && value != "") {
            return true;
        }
        if(parseInt(value)==0){//等于0不算空值系列,可能 ""==0也为true，所以转成数字来比较
            return true;
        }
        return false;
    }
    Class.prototype.clearData=function () {
        fList=[];
        rowData=[];
    }
    Class.prototype.setFileType=function (fileType) {
        this.options.fileType=fileType;
        this.register(this);
        this.init(this);
        this.events(this);
    }

    var layWebupload = {
        render:function (options) {
            var inst = new Class(options);
            return inst;
        }
    };
    return layWebupload;
});