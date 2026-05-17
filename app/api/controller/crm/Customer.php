<?php

namespace app\api\controller\crm;



use app\api\controller\Authority;
use think\App;
use think\facade\Db;
use think\exception\ValidateException;
use think\exception;
use think\facade\Request;
use think\facade\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Cell;



/**
 * @ControllerAnnotation(title="crm_customer")
 */
class Customer extends Authority
{
    public $sort_by = 'id';
    public $sort_order = 'DESC';
    protected $searchFields = 'name|phone';

    /**
     * 允许修改的字段
     * @var array
     */
    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'is_delete',
        'is_auth',
        'title',
        'kh_rank',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmCustomer();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {

        $fields=\tools\Cache::zdy_fields('crm_customer');



        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where,$sort) = $this->buildTableParames();
        $scope=$this->request->get('scope', 1,'intval');

        if($scope==2){
//                    展示其他的  不包括自己
                $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin);
                if(empty($adminName)){
                    return json([
                        'code'  => 1,
                        'msg'   => '','count' => 0,
                        'data'  => [],
                    ]);
                }
                if($adminName!=='ALL'){
                    $where[] = ['pr_user', 'in',$adminName];
                }elseif($adminName=='ALL'){
//                    展示其他的  不包括自己需要做排除
                    $where[] = ['pr_user', '<>',$this->admin['username']];
                }

            }elseif($scope==3){
//                    展示全部 包括自己
                $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin,true);
                if(empty($adminName)){
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => []
                    ]);
                }
                if($adminName!=='ALL'){
                    $where[] = ['pr_user', 'in',$adminName];
                }
            }elseif($scope==10){
// 待跟进
                $where[] = ['next_time', '>', 0];
                if(isset($this->system['daigenjin']) && is_numeric($this->system['daigenjin'])){
                    $where[] = ['next_time', '<', strtotime("+{$this->system['daigenjin']} day 00:00:00")];
                }else{
                    $where[] = ['next_time', '<', strtotime('tomorrow')];
                }

// 待跟进限制展示自己的
                $where[] = ['pr_user', '=', $this->admin['username']];

            }elseif($scope==11){
// 今天已跟进
                $where[] = ['last_up_time', '>=', strtotime('today')];
                $where[] = ['last_up_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
                $where[] = ['pr_user', '=', $this->admin['username']];

            }elseif($scope==12){
// 从未跟进
                $where[] = ['last_up_time', '=', 0];
// 限制展示自己的
                $where[] = ['pr_user', '=', $this->admin['username']];

            }elseif($scope==20){

                //            展示自己分享给他人的
                $where[] = ['pr_user', '=', $this->admin['username']];
                $where[] = ['share_admin_ids', '<>', ''];

            }elseif($scope==21){
                //                    展示分享给我的
                $where[]=['','exp',\think\facade\Db::raw("FIND_IN_SET('{$this->admin['admin_id']}',share_admin_ids)")];
            }else{
//                   限制展示自己的
                $where[] = ['pr_user', '=', $this->admin['username']];
            }
            $where[]=['status','=',1];
            if(!empty($this->system['chjkhdlzhsh'])){
                $where[]=['issuccess','=',0];
            }
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $field_str=empty($fields['field_str'])?'*':$fields['field_str'];
                if (empty($fields['field_str'])){
                    $field_str='*';
                }else{
                    $field_str_arr=explode(',',$fields['field_str']);
                   if(!in_array('issuccess',$field_str_arr)){
                        $field_str='issuccess,'.$field_str;
                    }
                    if(!in_array('create_time',$field_str_arr)){
                        $field_str='create_time,'.$field_str;
                    }
                    if(!in_array('last_up_time',$field_str_arr)){
                        $field_str='last_up_time,'.$field_str;
                    }
                    if(!in_array('to_kh_time',$field_str_arr)){
                        $field_str='to_kh_time,'.$field_str;
                    }
                    if(!in_array('id',$field_str_arr)){
                        $field_str='id,'.$field_str;
                    }
                }

                $list = $this->model->field($field_str)
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select()->toArray();


                if ($this->admin['isphone'] == 0 && $list) {
                    foreach ($list as $key => $value) {
                        foreach ($fields['tels'] as $tel_key => $tel_value){
                            if($value[$tel_value]){
                                $value[$tel_value] = mb_substr($value[$tel_value], 0, 3).'****'. mb_substr($value[$tel_value], 7, 11);
                            }
                        }
                        $list[$key] = $value;
                    }
                }
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
                'scopes'=>['1'=>'我的','2'=>'下属的','3'=>'全部']
            ];

            return json($data);
    }
    /**
     * @NodeAnotation(title="公海")
     */
    public function seas()
    {
        $where = "`show` = 1 OR `field` IN ('to_gh_time', 'pr_user_bef')";
        $fields = \tools\Cache::zdy_fields('crm_customer', $where);
        $this->model->autoRecycle($this->system);
        $this->sort_by = 'to_gh_time';
        $this->sort_order = 'ASC';
        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where,$sort) = $this->buildTableParames();
        $where[]=['status','=',2];
        $count = $this->model
            ->where($where)
            ->count();
        $list=[];
        if($count){
            $field_str=empty($fields['field_str'])?'*':$fields['field_str'];
            if (empty($fields['field_str'])){
                $field_str='*';
            }else{
                $field_str_arr=explode(',',$fields['field_str']);
                if(!in_array('id',$field_str_arr)){
                    $field_str='id,'.$field_str;
                }
            }
            $list = $this->model->field($field_str)
                ->where($where)
                ->page($page, $limit)
                ->order($sort)
                ->select();
        }

        if ($this->admin['group_id'] != 1) {
            foreach ($list as $key => $value) {
                foreach ($fields['tels'] as $tel_key => $tel_value){
                    if($value[$tel_value]){
                        $value[$tel_value] = mb_substr($value[$tel_value], 0, 3).'****'. mb_substr($value[$tel_value], 7, 11);
                    }
                }
                $list[$key] = $value;
            }
        }
        return json([
            'code'  => 1,
            'msg'   => '',
            'data'  => ['rows' => $list, 'count' => $count],
        ]);

        //技术QQ3623820285

        $jscol_str= $fields['jscol_str'];
        $jscol_str=str_replace(['"已成交"','"未成交"'],['"'.fy('已成交').'"','"'.fy('未成交').'"'],$jscol_str);
        $this->app->view->engine()->layout(false);
        $jscol_str=$this->display(trim($jscol_str,','));
        $this->app->view->engine()->layout($this->layout);
        $jscol_str=str_replace(['":"{"','"}"'],['":{"','"}'],$jscol_str);
        $this->assignconfig('cols_fields',json_decode('['.$jscol_str.']',true));
        $this->assignconfig('parameter',['scope'=>$this->request->get('scope',1,'intval')]);
        View::assign('grabCountMsg',$this->getGrabCount()['msg']);
        
    }

    public function reduplicate(){
        if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();
            $list = $this->model->field('`id`,`name`,`phone`,`contact`,`at_user`,`pr_user`,`last_up_time`,`issuccess`,`create_time`,`update_time`')
                ->where($where)
                ->limit(10) //查重复最多显示10条
                ->order($this->sort)
                ->select();


            foreach ($list as $key => $value) {
                $value['phone'] = mb_substr($value['phone'], 0, 3).'****'. mb_substr($value['phone'], 7, 11);
                $list[$key] = $value;
            }
            return json([
                'code'  => 1,
                'msg'   => '',
                'data'  => ['rows' => $list, 'count' => count($list)],
            ]);
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query("SELECT `name`,`jscol` FROM `{$prefix}system_field` WHERE `table`='crm_customer' AND `field` IN ('name','phone','contact','at_user','pr_user','last_up_time','issuccess','create_time','update_time') order BY `sort` ASC,id ASC");
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=$v['jscol'].',';
        }

        $fields_str=str_replace(['"已成交"','"未成交"'],['"'.fy('已成交').'"','"'.fy('未成交').'"'],$fields_str);
//
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display(trim($fields_str,','));

        $this->app->view->engine()->layout($this->layout);
//        $fields_str=str_replace(['":"{:',')}"'],['":{:',')}'],$fields_str);
        $fields_str=str_replace(['":"{"','"}"'],['":{"','"}'],$fields_str);

//        var_dump(json_decode('['.$fields_str.']',true));
        $this->assignconfig('cols_fields',json_decode('['.$fields_str.']',true));
        
    }
    /**
     * @NodeAnotation(title="成交客户")
     */
    public function issuccess()
    {
        $where = "`show` = 1 OR `field` = 'issuccess'";
        $fields = \tools\Cache::zdy_fields('crm_customer', $where);
        $this->sort_by = 'success_time';
            $this->sort_order = 'DESC';
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $where[]=['issuccess','=',1];
            if($this->admin['group_id']>1){
                $where[] = ['pr_user', '=', $this->admin['username']];
            }

            $count = $this->model
                ->where($where)
                ->count();$list=[];
            if($count){
                $field_str=empty($fields['field_str'])?'*':$fields['field_str'];
                if (empty($fields['field_str'])){
                    $field_str='*';
                }else{
                    $field_str_arr=explode(',',$fields['field_str']);
                    if(!in_array('id',$field_str_arr)){
                        $field_str='id,'.$field_str;
                    }
                }

                $list = $this->model->field($field_str)
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
                if ($this->admin['isphone'] == 0) {
                    foreach ($list as $key => $value) {
                        foreach ($fields['tels'] as $tel_key => $tel_value){
                            if($value[$tel_value]){
                                $value[$tel_value] = mb_substr($value[$tel_value], 0, 3).'****'. mb_substr($value[$tel_value], 7, 11);
                            }
                        }
                        $list[$key] = $value;
                    }
                }
            }

            return json([
                'code'  => 1,
                'msg'   => '','count' => $count,
                'data'  => $list,
            ]);
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`jscol` FROM `'.$prefix.'system_field` WHERE `show`=1 AND `table`="crm_customer" AND `jscol` is not null order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){

            $fields_str.=$v['jscol'].',';
        }
        $fields_str=str_replace(['"已成交"','"未成交"'],['"'.fy('已成交').'"','"'.fy('未成交').'"'],$fields_str);


        $this->app->view->engine()->layout(false);
        $fields_str=$this->display(trim($fields_str,','));
        $this->app->view->engine()->layout($this->layout);
        $fields_str=str_replace(['":"{"','"}"'],['":{"','"}'],$fields_str);
        $this->assignconfig('cols_fields',json_decode('['.$fields_str.']',true));
        $this->assignconfig('parameter',['scope'=>$this->request->get('scope',1,'intval')]);
        
    }


    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {

        if ($this->request->isPost()) {
            $allowCustomersNum=$this->model->allowCustomersNum($this->admin);
            if($allowCustomersNum['max_customers_num']>0){
//                对于零说明要验证
                if($allowCustomersNum['yx_c']<=0){
                    $this->error(fy("The maximum number of customers you have is full and you can't add client information"));
                }
            }
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $prefix=getDataBaseConfig('prefix');
            $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`addinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
            $this->verifyFields($post,$fields,'crm_customer');
            try {
                $post=post_convert($post,$fields);
                $post['at_user']=$this->admin['username'];
                $post['pr_user']=$this->admin['username'];
                $post['owner_admin_id']=$this->admin['admin_id'];
                $post['create_time']= $post['update_time']=$post['to_kh_time']=time();
                $post=array_intersect_key($post, array_flip($this->model->getTableFields()));
                $id = $this->model->insertGetId($post);
                if($id){
                    $contacts_id=Db::name('crm_customer_contacts')->insertGetId([
                        'customer_id'=>$id,
                        'contact'=>!empty($post['contact'])?$post['contact']:$post['name'],
                        'phone'=>isset($post['phone'])?$post['phone']:'',
                        'email'=>isset($post['email'])?$post['email']:'',
                        'wechat'=>isset($post['wechat'])?$post['wechat']:'',
                        'create_username'=>$this->admin['username'],
                        'owner_admin_id'=>$this->admin['admin_id'],
                        'create_time'=>$post['create_time'],
                        'update_time'=>$post['create_time'],
                    ]);
                    if($contacts_id){
                        Db::name('crm_customer')->where('id','=',$id)->update(['contacts_id'=>$contacts_id]);
                    }
                }
            } catch (\Exception $e) {
                $msg=$e->getMessage();

                if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                    
                    $msg = "包含【{$matches[1]}】的记录已存在";
                }elseif (preg_match("/Data too long for column '(.+)' at row/is", $msg, $matches)) {
                    $msg = "字段【{$matches[1]}】长度不足";
                }
                $this->error(fy('Save failed').':'.$msg);
            }
            $id ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`editinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
        $row = $this->model->field(array_unique(array_merge(array_column($fields, 'field'), ['id','pr_user','phone','name','contacts_id'])))->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissionsByName($row['pr_user']);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $this->verifyFields($post,$fields,'crm_customer');
//            针对多选无值赋空
            $fields_name=[];
            foreach ($fields as $v){
                $name=!empty($v['xsname'])?$v['xsname']:$v['name'];
                $fields_name[$v['field']]=$name;
            }
            Db::startTrans();
            try {
                $post=post_convert($post,$fields);
                $post['update_time']=time();
                if(!empty($this->system['customer_record_fields'])){
//                    不为空则记录变动的值
                    $record_fields=explode(',',$this->system['customer_record_fields']);
                    $record=[];
                    $record['customer_id']=$row['id'];
//                    转字符串
                    $record['customer_name']=$row['name'];
                    $record['create_admin_id']=$this->admin['admin_id'];
                    $record['create_username']=$this->admin['username'];
                    $record['create_time']=time();
                    $record['ip']=getRealIp();
                    $sql='';

                    foreach ($record_fields as $v){
                        if(isset($post[$v]) && $post[$v]!=$row[$v]){
                            $record['on']=$fields_name[$v];
                            $record['on_field']=$v;
                            $record['before_value']=$row[$v];
                            $record['after_value']=$post[$v];
                            $sql.=Db::name('customer_changes_record')->fetchSql(true)->insert($record).';';
                        }
                    }
                    if($sql){
                        \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
                    }
                }
                if($row['contacts_id']){
                    $contacts_data=[];
                    $contacts_data['update_time']=time();
                    if(isset($post['contact'])){
                        $contacts_data['contact']=$post['contact'];
                    }
                    if(isset($post['phone'])){
                        $contacts_data['phone']=$post['phone'];
                    }
                    if(isset($post['email'])){
                        $contacts_data['email']=$post['email'];
                    }
                    if(isset($post['wechat'])){
                        $contacts_data['wechat']=$post['wechat'];
                    }
                    Db::name('crm_customer_contacts')->where('id','=',$row['contacts_id'])->update($contacts_data);
                }

                $save = $row->save($post);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $msg=$e->getMessage();
                if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                    $msg = fy('A record containing %s already exists',[$matches[1]]);

                }elseif (preg_match("/Data too long for column '(.+)' at row/is", $msg, $matches)) {
                    $msg = fy('Fields %s Insufficient length',[$matches[1]]);
                }
                $this->error(fy('Save failed').':'.$msg);
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }else{

            foreach ($fields as $value){
                if ($this->admin['isphone'] == 0 && $value['formtype']=='tel') {
                    $row[$value['field']] = mb_substr($row[$value['field']], 0, 3).'****'. mb_substr($row[$value['field']], 7, 11);
                }
            }
            $this->success('success','',$row);
        }

    }

    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $pr_users = $this->model->whereIn('id', $id)->column('DISTINCT pr_user');
        $this->modifyPermissionsByName($pr_users);

        $row = $this->model->whereIn('id', $id)->select();

        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }



    //客户转移，变更负责人
    public function alter_pr_user(){
        //1，获取提交的线索ID 【1,2,3,4,】
//        获取客户ID
        $ids = $this->request->param('id');
        $cus_lst=$this->model->field('id,name,pr_user')->where('id','in',$ids)->select();
        if($cus_lst->isEmpty()){
            $this->error(fy("Customer data does not exist"));
        }
//        获取 $cus_lst负责人字段作为数组
        $pr_user_arr=array_column($cus_lst->toArray(),'pr_user');
        $this->modifyPermissionsByName($pr_user_arr);


        $username = $this->request->param('username','','trim');
            $type = $this->request->param('type',1,'intval');
            if(empty($username)){
                $this->error(fy("The person in charge must choose"));
            }
            $idsArr = explode(",",$ids);

            $count = 0;

            $username_arr= explode(',',$username);//转成数组
            $admin_count=count($username_arr);//管理员数量
            $i=0;//
            foreach ($idsArr as  $value){
                if ($type==2){
                    //随机分配客户
                    $username=$username_arr[mt_rand(0, $admin_count - 1)];
                }else{
                    //平均分配
                    $username=$username_arr[$i];
                    $i==($admin_count-1)?$i=0:$i++;
                }

                $max_customers_num=Db::name('admin')->alias('a')->cache('max_customers_num_'.$username)
                    ->join(config('database.connections.mysql.prefix').'auth_group ag','a.group_id = ag.id','left')
                    ->where(['a.username'=>$username])
                    ->value('ag.max_customers_num');
                if( $max_customers_num > 0){
                    //            验证业务员的最大容量

                    $cz_c=Db::name('crm_customer')->whereRaw('pr_user=:username AND status=1',['username'=>$username])->count();
                    if($cz_c>=$max_customers_num){
                        $this->error($username."最大拥有的客户数量已满,限制" . $max_customers_num . "条,成功转移{$count}条!");
                    }
                }

                $data['pr_user_bef'] = Db::name('crm_customer')->where(['id'=>$value])->value('pr_user');
                $data['owner_admin_id'] = Db::name('admin')->cache('admin_username_'.$username,1200)->where(['username'=>$username])->value('admin_id');
                $data['pr_user'] = $username;
                $data['to_kh_time'] = time();
                $insertAll = Db::name('crm_customer')->where(['id'=>$value])->update($data);
                if ($insertAll){
                    $count ++;
                }
            }
            if ($count > 0){
                $this->success(fy("Transfer %s customers successfully",[$count]));
            }else{
                $this->error(fy("Failed"));
            }

        //查询所有管理员（去除admin）
        $adminResult = Db::name('admin')->where('group_id','<>', 1)->field('admin_id,username')->select();
        View::assign('adminResult',$adminResult);
    }












    //移入公海
    public function to_move_gh(){
        //1，获取提交的线索ID 【1,2,3,4,】
        $ids = Request::param('id');

        $count = 0;
        foreach ($ids as $value){
                $data['pr_user_bef'] = Db::name('crm_customer')->where(['id'=>$value])->value('pr_user');
                if(empty($data['pr_user_bef'])){
                    $data['pr_user_bef']='';
                }
                $data['pr_user'] = '';
                $data['owner_admin_id'] = 0;

                $data['to_gh_time'] = time();
                $data['status'] = 2;//0-线索，1客户，2公海
                $result = Db::name('crm_customer')->where(['id'=>$value])->update($data);
                if ($result){
                    $count ++;
                }
            }
            if ($count > 0){
                $this->success($count.'个客户移入公海成功！');
            }else{
                $this->error(fy('Failed'));
            }
    }
//客户共享
    public function share(){
//        总针对一个id
        $ids = $this->request->param('id');

        //查询所有管理员（去除admin）
        $adminResult = Db::name('admin')->where('admin_id','<>',$this->admin['admin_id'])->field('admin_id,username')->select();
        View::assign('adminResult',$adminResult);

        if ($this->request->isPost()){
            $share_admin_ids = $this->request->post('share_admin_ids','','trim');
            if(empty($share_admin_ids)){
                $this->error(fy("No colleagues selected to share"));
            }
            $this->model->where('id','in',$ids)->update(['share_admin_ids'=>$share_admin_ids]);
            $this->success(fy("Successfully shared"));
        }
//        只针对一个客户进行分享
        $cus_lst=$this->model->field('id,name,share_admin_ids')->where('id','in',$ids)->find();
        if(empty($cus_lst)){
            $this->error(fy("Customer data does not exist"));
        }
        View::assign('cus_lst',$cus_lst);
        View::assign('ids',$ids);

        
    }


//取消共享
    public function del_share($id)
    {
        $this->checkPostRequest();
        $row = $this->model->field('id,share_admin_ids')->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $save = Db::name('crm_customer')->whereIn('id', $id)->update(['share_admin_ids'=>'']);
        } catch (\Exception $e) {
            $this->error(fy("Unsharing failed"));
        }
        $save ? $this->success(fy("Cancel sharing successfully")) : $this->error(fy("Unsharing failed"));
    }

    public function success_bgshow(){
        $this->sort_by = 'success_time';
        $this->sort_order = 'DESC';
//        $this->system['customer_big_fields']
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::name('system_field')->field('field,name,xsname,width,rule,formtype,option')->where('field','in',$this->system['customer_big_fields'])->where('table','=','crm_customer')->order('sort asc,id asc')->column('field,name,xsname,width,rule,formtype,option','field');
//
        list($page, $limit, $where,$sort) = $this->buildTableParames();
        $kwd=$this->request->param('kwd','','trim');
        if($kwd){
            $where[]=['name','like','%'.$kwd.'%'];
        }
//        $where[]=['status','=',1];
        $where[]=['issuccess','=',1];
        $field_str=$this->system['customer_big_fields'];
        if(!in_array('id',explode(',',$field_str))){
            $field_str='id,'.$field_str;
        }


        $cursor=$this->model->field($field_str)
            ->where($where)
            ->order($sort)->limit(1000)->cursor();
        $this->assign('list', $cursor);
        $this->assign('customer_big_fields', $this->system['customer_big_fields']);
        $this->assign('fields', $fields);
        $this->assign('isphone',$this->admin['isphone']);
        $this->assign('system',$this->system);
        $this->assign('kwd',$kwd);
        $this->app->view->engine()->layout(false);
        

    }

    public function sendEmail($id){
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissionsByName($row['pr_user']);
        if ($this->request->isPost()) {
            $param = $this->request->param();
            
            // 验证输入参数
            $validate = new \think\Validate([
                'to_email' => 'require|email',
                'subject' => 'require|max:200',
                'email_content' => 'require'
            ], [
                'to_email.require' => '收件邮箱不能为空',
                'to_email.email' => '收件邮箱格式不正确',
                'subject.require' => '邮件标题不能为空',
                'subject.max' => '邮件标题不能超过200字符',
                'email_content.require' => '邮件内容不能为空'
            ]);
            
            if (!$validate->check($param)) {
                $this->error($validate->getError());
            }
            
            try {
                send_email($param['to_email'], $param['subject'],$param['email_content'],true,1,'customer');
            } catch (\Exception $e) {
                $this->error('邮件发送失败：' . $e->getMessage());
            }
            $this->success('邮件发送成功');
        }else{
//            emailtpl
            $emailtpl=\think\facade\Db::name('emailtpl')->field('id,name')->order('sort asc')->where('type','customer')->select();
            $this->assign('emailtpl',$emailtpl);
            $this->assign('id',$id);
            $this->assign('row',$row);
            
        }
    }



}
