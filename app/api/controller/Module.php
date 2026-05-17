<?php
namespace app\api\controller;
use think\App;
use think\facade\View;
use think\facade\Db;
use think\facade\Request;
use app\api\controller\Authority;
use think\Model;

class Module extends Authority
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\Module();
    }
    function initialize()
    {
        parent::initialize();
        $field_pattern = [
            ['name'=>'defaul','title'=>'默认'],
            ['name'=>'email','title'=>'电子邮件'],
            ['name'=>'url','title'=>'网址'],
            ['name'=>'date','title'=>'日期'],
            ['name'=>'number','title'=>'有效的数值'],
            ['name'=>'digits','title'=>'数字'],
            ['name'=>'creditcard','title'=>'信用卡号码'],
            ['name'=>'equalTo','title'=>'再次输入相同的值'],
            ['name'=>'ip4','title'=>'IP'],
            ['name'=>'mobile','title'=>'手机号码'],
            ['name'=>'zipcode','title'=>'邮编'],
            ['name'=>'qq','title'=>'QQ'],
            ['name'=>'idcard','title'=>'身份证号'],
            ['name'=>'chinese','title'=>'中文字符'],
            ['name'=>'cn_username','title'=>'中文英文数字和下划线'],
            ['name'=>'tel','title'=>'电话号码'],
            ['name'=>'english','title'=>'英文'],
            ['name'=>'en_num','title'=>'英文数字和下划线'],
        ];
        View::assign('pattern', json_encode($field_pattern,true));
    }
    public function index(){
        if($this->request->isAjax()) {
            $pageSize =input('limit',config('app.pageSize'),'intval');
            $sort_by = input('sort_order') ? input('sort_by') : 'id';
            $sort_order = input('sort_order') ? input('sort_order') : 'asc';
            $list = \think\facade\Db::name('module')->order($sort_by.' '.$sort_order)
                ->paginate($pageSize)
                ->toArray();
            return json(['code'=>0,'msg'=>fy('Get successful').'!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1]);
        }else{
            return View::fetch();
        }
    }
    public function edit($id){
        if($this->request->isPost()){
            $data = $this->request->post();
            unset($data['name']);
            if(Db::name('module')->update($data)!==false){
                savecache('Module');
                return json(['code'=>1,'url'=>myurl('index'),'msg'=>fy("Modification succeeded")]);
            }else{
                return json(['code'=>0,'url'=>myurl('index'),'msg'=>'修改失败!']);
            }
        }else{
            $map['id'] = input('param.id');
            $info = \think\facade\Db::name('module')->field('*')->where($map)->find();
            View::assign('title',lang('edit').lang('module'));
            View::assign('info', json_encode($info,true));
            $this->app->view->engine()->layout(false);
            return View::fetch();
        }
    }
    public function add(){
        if($this->request->isPost()){
            //获取数据库所有表名
            $tables = Db::getTables();


            //组装表名
            $prefix = config('database.connections.mysql.prefix');
            $tablename = $prefix.input('post.name');
            //判断表名是否已经存在
            if(in_array($tablename,$tables)){
                $result['code'] = 0;
                $result['info'] = '该表已经存在！';
                return json($result);
            }
            $name = ucfirst(input('post.name'));
            $data=$this->request->post();
            $emptytable=$data['emptytable'];
            unset($data['emptytable']);
            $data['type'] = 1;

            $moduleid = \think\facade\Db::name('module')->insertGetId($data);
            if(empty($moduleid)){
                $result['code'] = 0;
                $result['msg'] = '添加模型失败！';
                return json($result);
            }
            $comment=input('post.description',input('post.title'));
            if($emptytable=='0'){
                Db::execute("CREATE TABLE `".$tablename."` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
			  `catid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
			  `userid` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
			  `username` varchar(40) NOT NULL DEFAULT '' COMMENT '用户名',
			  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '标题',
			  `title_style` varchar(225) NOT NULL DEFAULT '' COMMENT '标题样式',
			  `thumb` varchar(225) NOT NULL DEFAULT '' COMMENT '缩略图',
			  `keywords` varchar(120) NOT NULL DEFAULT '' COMMENT '关键词',
			  `description` mediumtext NOT NULL COMMENT '描述',
			  `content` mediumtext NOT NULL  COMMENT '内容',
			  `template` varchar(40) NOT NULL DEFAULT '' COMMENT '模板', 
			  `posid` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '推荐位',
			  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
			  `recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '允许评论',
			  `readgroup` varchar(100) NOT NULL DEFAULT '' COMMENT '访问权限',
			  `readpoint` smallint(5) NOT NULL DEFAULT '0' COMMENT '阅读权限',
			  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
			  `hits` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点击',
			  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
			  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
			  PRIMARY KEY (`id`),
			  KEY `status` (`id`,`status`,`sort`),
			  KEY `catid` (`id`,`catid`,`status`),
			  KEY `sort` (`id`,`catid`,`status`,`sort`)
			) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'catid', '栏目', '', '1', '1', '6', '', '必须选择一个栏目', '', 'catid', '','1','', '1', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'title', '标题', '', '1', '1', '80', '', '标题必须为1-80个字符', '', 'title', 'array (\n  \'thumb\' => \'1\',\n  \'style\' => \'1\',\n  \'size\' => \'55\',\n)','1','',  '2', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'keywords', '关键词', '', '0', '0', '80', '', '', '', 'text', 'array (\n  \'size\' => \'55\',\n  \'default\' => \'\',\n  \'ispassword\' => \'0\',\n  \'fieldtype\' => \'varchar\',\n)','1','',  '3', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'description', 'SEO简介', '', '0', '0', '0', '', '', '', 'textarea', 'array (\n  \'fieldtype\' => \'mediumtext\',\n  \'rows\' => \'4\',\n  \'cols\' => \'55\',\n  \'default\' => \'\',\n)','1','',  '4', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'content', '内容', '', '0', '0', '0', '', '', '', 'editor', 'array (\n  \'toolbar\' => \'full\',\n  \'default\' => \'\',\n  \'height\' => \'\',\n  \'showpage\' => \'1\',\n  \'enablekeylink\' => \'0\',\n  \'replacenum\' => \'\',\n  \'enablesaveimage\' => \'0\',\n  \'flashupload\' => \'1\',\n  \'alowuploadexts\' => \'\',\n)','1','',  '5', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'createtime', '发布时间', '', '1', '0', '0', 'date', '', 'createtime', 'datetime', '','1','',  '6', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'status', '状态', '', '0', '0', '0', '', '', '', 'radio', 'array (\n  \'options\' => \'发布|1\r\n定时发布|0\',\n  \'fieldtype\' => \'tinyint\',\n  \'numbertype\' => \'1\',\n  \'labelwidth\' => \'75\',\n  \'default\' => \'1\',\n)','1','','7', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'recommend', '允许评论', '', '0', '0', '1', '', '', '', 'radio', 'array (\n  \'options\' => \'允许评论|1\r\n不允许评论|0\',\n  \'fieldtype\' => \'tinyint\',\n  \'numbertype\' => \'1\',\n  \'labelwidth\' => \'\',\n  \'default\' => \'\',\n)','1','', '8', '0', '0')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'readpoint', '阅读收费', '', '0', '0', '5', '', '', '', 'number', 'array (\n  \'size\' => \'5\',\n  \'numbertype\' => \'1\',\n  \'decimaldigits\' => \'0\',\n  \'default\' => \'0\',\n)','1','', '9', '0', '0')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'hits', '点击次数', '', '0', '0', '8', '', '', '', 'number', 'array (\n  \'size\' => \'10\',\n  \'numbertype\' => \'1\',\n  \'decimaldigits\' => \'0\',\n  \'default\' => \'0\',\n)','1','',  '10', '0', '0')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'readgroup', '访问权限', '', '0', '0', '0', '', '', '', 'groupid', 'array (\n  \'inputtype\' => \'checkbox\',\n  \'fieldtype\' => \'tinyint\',\n  \'labelwidth\' => \'85\',\n  \'default\' => \'\',\n)','1','', '11', '0', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'posid', '推荐位', '', '0', '0', '0', '', '', '', 'posid', '','1','', '12', '1', '1')");
                Db::execute("INSERT INTO `".$prefix."field` (`moduleid`,`field`,`name`,`tips`,`required`,`minlength`,`maxlength`,`pattern`,`errormsg`,`class`,`type`,`setup`,`ispost`,`unpostgroup`,`sort`,`status`,`issystem`) VALUES ( '".$moduleid."', 'template', '模板', '', '0', '0', '0', '', '', '', 'template', '','1','', '13', '1', '1')");

            }else{
                Db::execute("CREATE TABLE `".$tablename."` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
			  `at_userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者用户ID',
			  `at_username` varchar(40) NOT NULL DEFAULT '' COMMENT '创建者用户名',
			  `up_userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新者用户ID',
			  `up_username` varchar(40) NOT NULL DEFAULT '' COMMENT '更新者用户名',
			  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
			  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=".config('database.connections.mysql.charset').' COMMENT="'.$comment.'"');
            }
            if ($moduleid  !==false) {
                savecache('Module');
                $result['code'] = 1;
                $result['msg'] = '添加模型成功！';
                $result['url'] = myurl('index');
                return json($result);
            }
        }else{
            View::assign('title',lang('add').lang('module'));
            View::assign('info','null');
            return View::fetch('form');
        }
    }
    //模型状态
    public function moduleState(){
        $id=input('post.id');
        $status=input('post.status');
        if(Db::name('module')->where('id='.$id)->update(['status'=>$status])!==false){
            return json(['status'=>1,'msg'=>fy("Setting succeeded")]);
        }else{
            return json(['status'=>0,'msg'=>fy("Setting failed")]);
        }
    }
    //删除模型
    function del() {
        $id =input('param.id');
        $r = \think\facade\Db::name('module')->find($id);
        if(!empty($r)){
            $tablename = config('database.connections.mysql.prefix').$r['name'];

            $m = \think\facade\Db::name('module')->delete($id);
            if($m){
                Db::execute("DROP TABLE IF EXISTS `".$tablename."`");
                \think\facade\Db::name('Field')->where(array('moduleid'=>$id))->delete();
            }
        }
        savecache('Module');
        return json(['code'=>1,'msg'=>fy('Delete succeeded').'！']);
    }

    /****************************模型字段******************************/
    public function field(){
        if($this->request->isPost()){
            $nodostatus = array('catid','title','status','createtime');
            $sysfield = array('catid','userid','username','title','thumb','keywords','description','posid','status','createtime','url','template');

            $list = \think\facade\Db::name('field')->where("moduleid=".input('param.id'))->order('sort asc,id asc')->select()->toArray();
            foreach ($list as $k=>$v){
                if($v['status']==1){
                    if(in_array($v['field'],$nodostatus)){
                        $list[$k]['disable']=2;
                    }else{
                        $list[$k]['disable']=0;
                    }
                }else{
                    $list[$k]['disable']=1;
                }

                if(in_array($v['field'],$sysfield)){
                    $list[$k]['delStatus']=1;
                }else{
                    $list[$k]['delStatus']=0;
                }
            }
            View::assign('list', $list);
            return json(['code'=>0,'msg'=>fy('Get successful').'!','data'=>$list,'rel'=>1]);
        }else{
            return View::fetch();
        }
    }
    //修改状态
    public function fieldStatus(){
        $map['id']=input('post.id');
        //判断当前状态情况
        $field = \think\facade\Db::name('field');
        $status=$field->where($map)->value('status');
        if($status==1){
            $data['status'] = 0;
        }else{
            $data['status'] = 1;
        }
        $field->where($map)->update($data);
        return json($data);
    }
    //添加字段
    public function fieldAdd(){
        if($this->request->isAjax()){
            if(input('isajax')) {
                View::assign(input('get.'));
                View::assign($this->request->post());
                $name = \think\facade\Db::name('module')->where(array('id' => input('moduleid')))->value('name');
                if (input('name')) {
                    $files = Db::getTableFields(config('database.connections.mysql.prefix') . $name);
                    if(isset($files['type'][input('name')])){
                        $fieldtype = $files['type'][input('name')];
                    }else{
                        $fieldtype = '';
                    }
                    View::assign('fieldtype', $fieldtype);
                    return view('fieldType');
                } else {
                    return view('fieldAddType');
                }
            }else{
                $data = $this->request->post();
                $fieldName=$data['field'];
                $prefix=config('database.connections.mysql.prefix');
                $name = \think\facade\Db::name('module')->where(array('id'=>$data['moduleid']))->value('name');
                $tablename=$prefix.$name;
                $Fields=Db::getTableFields($tablename);
                if(in_array($fieldName,$Fields)){
                    $result['msg'] = '字段名已经存在！';
                    $result['code'] = 0;
                    return json($result);
                }
                $addfieldsql =$this->get_tablesql($data,'add');
                if(isset($data['setup'])) {
                    $data['setup'] = array2string($data['setup']);
                }
                if($data['pattern']=='?'){
                    $data['pattern'] = 'defaul';
                }else{
                    $pattern= explode(':',$data['pattern']);
                    $data['pattern'] = $pattern[1];
                }
                if(empty($data['class'])){
                    $data['class'] = $data['field'];
                }
                $model = \think\facade\Db::name('field');
                if ($model->strict(false)->insert($data) !==false) {
                    savecache('Field',$data['moduleid']);
                    if(is_array($addfieldsql)){
                        foreach($addfieldsql as $sql){
                            Db::execute($sql);
                        }
                    }else{
                        Db::execute($addfieldsql);
                    }
                    $result['msg'] = fy("Successfully added").'！';
                    $result['code'] = 1;
                    $result['url'] = myurl('field',array('id'=>input('post.moduleid')));
                    return json($result);
                } else {
                    $result['msg'] = fy("Add failed").'！';
                    $result['code'] = 0;
                    return json($result);
                }
            }
        }else{
            $moduleid =input('moduleid');
            View::assign('moduleid',$moduleid);
            View::assign('title',lang('add').lang('field'));
            View::assign('info','null');
            return View::fetch('fieldForm');
        }
    }
    //编辑字段
    public function fieldEdit(){
        if($this->request->isAjax()){
            $data = $this->request->post();
            unset($data['oldfield']);
            $oldfield = input('oldfield');
            $fieldName=$data['field'];
            $name = \think\facade\Db::name('module')->where(array('id'=>$data['moduleid']))->value('name');
            $prefix=config('database.connections.mysql.prefix');
            if($this->_iset_field($prefix.$name,$fieldName) && $oldfield!=$fieldName){
                $result['msg'] = '字段名重复！';
                $result['code'] = 0;
                return json($result);
            }

            $editfieldsql =$this->get_tablesql($this->request->post(),'edit');
            if($data['setup']){
                $data['setup']=array2string($data['setup']);
            }
            if(!empty($data['unpostgroup'])){
                $data['setup'] = implode(',',$data['unpostgroup']);
            }
            if($data['pattern']=='?'){
                $data['pattern'] = 'defaul';
            }else{
                $pattern= explode(':',$data['pattern']);
                $data['pattern'] = $pattern[1];
            }
            if(empty($data['class'])){
                $data['class'] = $data['field'];
            }




            $model = \think\facade\Db::name('field');
            if (false !== $model->update($data)) {
                savecache('Field',$data['moduleid']);
                if(is_array($editfieldsql)){
                    foreach($editfieldsql as $sql){
                        Db::execute($sql);
                    }
                }else{
                    Db::execute($editfieldsql);
                }
                $result['msg'] = fy("Modification succeeded");
                $result['code'] = 1;
                $result['url'] = myurl('field',array('id'=>input('post.moduleid')));
                return json($result);
            } else {
                $result['msg'] = '修改失败！';
                $result['code'] = 0;
                return json($result);
            }
        }else{
            $model = \think\facade\Db::name('field');
            $id = input('param.id');
            if(empty($id)){
                $result['msg'] = '缺少必要的参数！';
                $result['code'] = 0;
                return json($result);
            }
            $fieldInfo = $model->where(array('id'=>$id))->find();
            if($fieldInfo['setup']) $fieldInfo['setup']=string2array($fieldInfo['setup']);
            View::assign('info',json_encode($fieldInfo,true));
            View::assign('title',lang('edit').lang('field'));
            View::assign('moduleid', input('param.moduleid'));
            return View::fetch('fieldForm');
        }
    }
    //字段排序
    public function listOrder(){
        $model =\think\facade\Db::name('field');
        $data = $this->request->post();
        if($model->update($data)!==false){
            return json(['msg' => '操作成功！','url'=>myurl('field',array('id'=>input('post.moduleid'))), 'code' => 1]);
        }else{
            return json(['code'=>0,'msg'=>'操作失败！']);
        }
    }

    function fieldDel() {
        $id=input('id');
        $r = \think\facade\Db::name('field')->find($id);
        if($r['issystem']){
            $this->error('系统字段无法删除！');
        }
        \think\facade\Db::name('field')->delete($id);
        $moduleid = $r['moduleid'];

        $field = $r['field'];

        $prefix=config('database.connections.mysql.prefix');
        $name = \think\facade\Db::name('module')->where(array('id'=>$moduleid))->value('name');
        $tablename=$prefix.$name;

        Db::execute("ALTER TABLE `$tablename` DROP `$field`");

        return json(['code'=>1,'msg'=>fy('Delete succeeded').'！']);
    }


    public function get_tablesql($info,$do){
        $comment = $info['name'];
        $fieldtype = $info['type'];
        if(isset($info['setup']['fieldtype'])){
            $fieldtype=$info['setup']['fieldtype'];
        }
        $moduleid = $info['moduleid'];
        $default = '';
        if(isset($info['setup']['default'])){
            $default=$info['setup']['default'];
        }
        $field = $info['field'];
        $prefix = config('database.connections.mysql.prefix');
        $name = \think\facade\Db::name('module')->where(array('id'=>$moduleid))->value('name');
        $tablename=$prefix.$name;
        $maxlength = intval($info['maxlength']);
        $minlength = intval($info['minlength']);
        $numbertype = '';
        if(isset($info['setup']['numbertype'])){
            $numbertype=$info['setup']['numbertype'];
        }

        $isnull = $info['required']==0?'NULL':"NOT NULL";
        if($do=='add'){
            $do = ' ADD ';
        }else{
            $oldfield = $info['oldfield'];
            $do =  " CHANGE `".$oldfield."` ";
        }
        switch($fieldtype) {
            case 'varchar':
                if(!$maxlength){$maxlength = 255;}
                $maxlength = min($maxlength, 255);
                $sql = "ALTER TABLE `$tablename` $do `$field` VARCHAR( $maxlength ) $isnull DEFAULT '$default' COMMENT '$comment'";
                break;
            case 'title':
                $thumb = $info['setup']['thumb'];
                $style = $info['setup']['style'];
                if(!$maxlength){$maxlength = 255;}
                $maxlength = min($maxlength, 255);
                $sql[] = "ALTER TABLE `$tablename` $do `$field` VARCHAR( $maxlength ) $isnull DEFAULT '$default' COMMENT '$comment'";


                if(!$this->_iset_field($tablename,'thumb')){
                    if($thumb==1) {
                        $sql[] = "ALTER TABLE `$tablename` ADD `thumb` VARCHAR( 100 ) $isnull DEFAULT '' COMMENT '缩略图'";
                    }
                }else{
                    if($thumb==0) {
                        $sql[] = "ALTER TABLE `$tablename` drop column `thumb`";
                    }
                }

                if(!$this->_iset_field($tablename,'title_style')){
                    if($style==1) {
                        $sql[] = "ALTER TABLE `$tablename` ADD `title_style` VARCHAR( 100 )$isnull DEFAULT '' COMMENT '标题样式'";
                    }
                }else{
                    if($style==0) {
                        $sql[] = "ALTER TABLE `$tablename` drop column `title_style`";
                    }
                }
                break;
            case 'catid':
                $sql = "ALTER TABLE `$tablename` $do `$field` SMALLINT(5) UNSIGNED $isnull DEFAULT '0' COMMENT '$comment'";
                break;

            case 'number':
                $decimaldigits = $info['setup']['decimaldigits'];
                $default = $decimaldigits == 0 ? intval($default) : floatval($default);
                $sql = "ALTER TABLE `$tablename` $do `$field` ".($decimaldigits == 0 ? 'INT' : 'decimal( 10,'.$decimaldigits.' )')." ".($numbertype ==1 ? 'UNSIGNED' : '')."  $isnull DEFAULT '$default'  COMMENT '$comment'";
                break;

            case 'tinyint':
                if(!$maxlength) $maxlength = 3;
                $maxlength = min($maxlength,3);
                $default = intval($default);
                $sql = "ALTER TABLE `$tablename` $do `$field` TINYINT( $maxlength ) ".($numbertype ==1 ? 'UNSIGNED' : '')." $isnull DEFAULT '$default'  COMMENT '$comment'";
                break;


            case 'smallint':
                $default = intval($default);
                $sql = "ALTER TABLE `$tablename` $do `$field` SMALLINT ".($numbertype ==1 ? 'UNSIGNED' : '')." $isnull DEFAULT '$default' COMMENT '$comment'";
                break;

            case 'int':
                $default = intval($default);
                $sql = "ALTER TABLE `$tablename` $do `$field` INT ".($numbertype ==1 ? 'UNSIGNED' : '')." $isnull DEFAULT '$default' COMMENT '$comment'";
                break;

            case 'mediumint':
                $default = intval($default);
                $sql = "ALTER TABLE `$tablename` $do `$field` INT ".($numbertype ==1 ? 'UNSIGNED' : '')." $isnull DEFAULT '$default' COMMENT '$comment'";
                break;

            case 'mediumtext':
                $sql = "ALTER TABLE `$tablename` $do `$field` MEDIUMTEXT $isnull COMMENT '$comment'";
                break;

            case 'text':
                $sql = "ALTER TABLE `$tablename` $do `$field` TEXT $isnull COMMENT '$comment'";
                break;

            case 'posid':
                $sql = "ALTER TABLE `$tablename` $do `$field` TINYINT(2) UNSIGNED $isnull DEFAULT '0' COMMENT '$comment'";
                break;

            //case 'typeid':
            //$sql = "ALTER TABLE `$tablename` $do `$field` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'";
            //break;

            case 'datetime':
                $sql = "ALTER TABLE `$tablename` $do `$field` INT(11) UNSIGNED $isnull DEFAULT '0' COMMENT '$comment'";
                break;

            case 'editor':
                $sql = "ALTER TABLE `$tablename` $do `$field` TEXT $isnull COMMENT '$comment'";
                break;

            case 'image':
                $sql = "ALTER TABLE `$tablename` $do `$field` VARCHAR( 80 ) $isnull DEFAULT '' COMMENT '$comment'";
                break;

            case 'images':
                $sql = "ALTER TABLE `$tablename` $do `$field` MEDIUMTEXT $isnull COMMENT '$comment'";
                break;

            case 'file':
                $sql = "ALTER TABLE `$tablename` $do `$field` VARCHAR( 80 ) $isnull DEFAULT '' COMMENT '$comment'";
                break;

            case 'files':
                $sql = "ALTER TABLE `$tablename` $do `$field` MEDIUMTEXT $isnull COMMENT '$comment'";
                break;
            case 'template':
                $sql = "ALTER TABLE `$tablename` $do `$field` VARCHAR( 80 ) $isnull DEFAULT '' COMMENT '$comment'";
                break;
            case 'linkage':
                $sql = "ALTER TABLE `$tablename` $do `$field` VARCHAR( 80 ) $isnull DEFAULT '' COMMENT '$comment'";
                break;
        }
        return $sql;
    }
    protected function _iset_field($table,$field){
        $fields = \think\facade\Db::getTableFields($table);
        return array_search($field,$fields);
    }

}