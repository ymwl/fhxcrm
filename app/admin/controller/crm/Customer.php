<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

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
class Customer extends AdminController
{
    public $sort_by = 'id';
    public $sort_order = 'DESC';

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

    //根据客户ID获取客户信息（联系人、手机号），用于选择客户后自动填充
    public function getCustomerInfo(){
        if($this->request->isAjax()){
            $customer_id = $this->request->post('customer_id',0,'intval');
            if($customer_id<1){
                $this->error(fy('参数错误'));
            }
            $custinfo = Db::name('crm_customer')->field('id,name,contact,phone,area,address')->where('id','=',$customer_id)->find();
            if(empty($custinfo)){
                $this->error(fy('Customer does not exist'));
            }
            $this->success('success','',$custinfo);
        }
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {


        $fields=\tools\Cache::zdy_fields('crm_customer');



        if ($this->request->isAjax()) {
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $scope=$this->request->get('scope', 1,'trim');

            if($scope==2){
//                    展示其他的  不包括自己 owner_admin_id
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }else{
                    $where[] = ['owner_admin_id', '<>', $this->admin['admin_id']];
                }

            }elseif($scope==3){
//                    展示全部 包括自己
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin, true);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
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
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

            }elseif($scope==11){
// 今天已跟进
                $where[] = ['last_up_time', '>=', strtotime('today')];
                $where[] = ['last_up_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

            }elseif($scope==12){
// 从未跟进
                $where[] = ['last_up_time', '=', 0];
// 限制展示自己的
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

            }elseif($scope==20){

                //            展示自己分享给他人的
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
                $where[] = ['share_admin_ids', '<>', ''];

            }elseif($scope==21){
                //                    展示分享给我的
                $where[]=['','exp',\think\facade\Db::raw("FIND_IN_SET('{$this->admin['admin_id']}',share_admin_ids)")];
            }else{
//                   限制展示自己的
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
            }
            $where[]=['status','=',1];
            if($this->system['chjkhdlzhsh']==1){
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
                  /*  if(!in_array('issuccess',$field_str_arr)){
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
                    }*/
                    if(!in_array('id',$field_str_arr)){
                        $field_str='id,'.$field_str;
                    }
                }

                $list = $this->model->field($field_str)
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select()->toArray();

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

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];

            return json($data);
        }

        $jscol_str= $fields['jscol_str'];

        $jscol_str=str_replace(['"已成交"','"未成交"'],['"'.fy('已成交').'"','"'.fy('未成交').'"'],$jscol_str);

        $this->app->view->engine()->layout(false);
        $jscol_str=$this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str=str_replace(['":"{"','"}"'],['":{"','"}'],$jscol_str);
        $this->assignconfig('cols_fields',json_decode('['.$jscol_str.']',true));
        // 传递 scope 给前端，用于激活对应标签页
        $this->assignconfig('scope', $this->request->get('scope', 1, 'trim'));

        return $this->fetch();
    }
    /**
     * @NodeAnotation(title="公海")
     */
    public function seas()
    {
        $where = "`list` = 1 OR `field` IN ('to_gh_time', 'pr_user_bef')";
        $fields = \tools\Cache::zdy_fields('crm_customer', $where);
        if ($this->request->isAjax()) {
            $this->model->autoRecycle($this->system);
            $this->sort_by = 'to_gh_time';
            $this->sort_order = 'ASC';
            
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
            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }

        //技术QQ3623820285

        $jscol_str= $fields['jscol_str'];

        $jscol_str=str_replace(['"已成交"','"未成交"'],['"'.fy('已成交').'"','"'.fy('未成交').'"'],$jscol_str);

        $this->app->view->engine()->layout(false);
        $jscol_str=$this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str=str_replace(['":"{"','"}"'],['":{"','"}'],$jscol_str);
        $this->assignconfig('cols_fields',json_decode('['.$jscol_str.']',true));
        $this->assignconfig('parameter',['scope'=>$this->request->get('scope',1,'intval')]);
        View::assign('grabCountMsg', \app\service\CrmCustomerService::getGrabCount($this->system, $this->admin['admin_id'])['msg']);
        return $this->fetch();
    }

    public function reduplicate(){
        if ($this->request->isAjax()) {
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $list = $this->model->field('`id`,`name`,`phone`,`contact`,`at_user`,`pr_user`,`last_up_time`,`issuccess`,`create_time`,`update_time`')
                ->where($where)
                ->limit(10) //查重复最多显示10条
                ->order($sort)
                ->select();


            foreach ($list as $key => $value) {
                $value['phone'] = mb_substr($value['phone'], 0, 3).'****'. mb_substr($value['phone'], 7, 11);
                $list[$key] = $value;
            }
            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => count($list),
                'data'  => $list,
            ];
            return json($data);
        }
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
        return $this->fetch();
    }
    /**
     * @NodeAnotation(title="成交客户")
     */
    public function issuccess()
    {

        $where = "`list` = 1 OR `field` = 'issuccess'";
        $fields = \tools\Cache::zdy_fields('crm_customer', $where);
        if ($this->request->isAjax()) {
            $this->sort_by = 'success_time';
            $this->sort_order = 'DESC';
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $scope=$this->request->get('scope', 1,'trim');
            if($scope==2){
//                    展示其他的  不包括自己
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }else{
                    $where[] = ['owner_admin_id', '<>', $this->admin['admin_id']];
                }

            }elseif($scope==3){
//                    展示全部 包括自己
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin, true);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }
            }else{
//                   限制展示自己的
                $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
            }
            $where[]=['issuccess','=',1];


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
            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $jscol_str= $fields['jscol_str'];

        $jscol_str=str_replace(['"已成交"','"未成交"'],['"'.fy('已成交').'"','"'.fy('未成交').'"'],$jscol_str);

        $this->app->view->engine()->layout(false);
        $jscol_str=$this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str=str_replace(['":"{"','"}"'],['":{"','"}'],$jscol_str);
        $this->assignconfig('cols_fields',json_decode('['.$jscol_str.']',true));

        $this->assignconfig('parameter',['scope'=>$this->request->get('scope',1,'intval')]);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`addinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `form`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
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
            $this->verifyFields($post,$fields,'crm_customer');
            try {
                $post=post_convert($post,$fields,'add');
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
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['addinput']);
        }

        $fields_str=str_replace(['>已成交<','>未成交<'],['>'.fy('已成交').'<','>'.fy('未成交').'<'],$fields_str);
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>[]]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit`,`editinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `form`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
        $row = $this->model->field(array_unique(array_merge(array_column($fields, 'field'), ['id','owner_admin_id','pr_user','phone','name','contacts_id'])))->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissionsByIds($row['owner_admin_id']);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $this->verifyFields($post,$fields,'crm_customer');



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
        }
        $fields_str='';
        foreach ($fields as $value){
            $fields_str.=trim($value['editinput']);
            if ($this->admin['isphone'] == 0 && $value['formtype']=='tel') {
                $row[$value['field']] = mb_substr($row[$value['field']], 0, 3).'****'. mb_substr($row[$value['field']], 7, 11);
            }
        }
        $fields_str=str_replace(['>已成交<','>未成交<'],['>'.fy('已成交').'<','>'.fy('未成交').'<'],$fields_str);
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);

        $fields=\tools\Cache::zdy_fields('crm_customer_contacts');

        $jscol_str= $fields['jscol_str'];
        $this->app->view->engine()->layout(false);
        $jscol_str=$this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str=str_replace(['":"{"','"}"'],['":{"','"}'],$jscol_str);
        $this->assignconfig('cols_fields',json_decode('['.$jscol_str.']',true));




        $this->assign('id',$id);
        $this->assign('row',$row);
        return $this->fetch();
    }

    public function delete()
    {
        $id = parseIds();
        $this->checkPostRequest();
        $adminIds = $this->model->whereIn('id', $id)->column('DISTINCT owner_admin_id');
        $this->modifyPermissionsByIds($adminIds);

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
        $ids = $this->request->param('ids', $this->request->param('id'));
        $cus_lst=$this->model->field('id,name,owner_admin_id')->where('id','in',$ids)->select();
        if($cus_lst->isEmpty()){
            $this->error(fy("Customer data does not exist"));
        }
//        获取 $cus_lst负责人字段作为数组
        $adminIds=array_column($cus_lst->toArray(),'owner_admin_id');
        $this->modifyPermissionsByIds($adminIds);


        if ($this->request->isAjax()){
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
        }

        $this->assign('cus_lst',$cus_lst);
        View::assign('ids',$ids);

        //查询所有管理员（去除admin）
        $adminResult = Db::name('admin')->where('is_open','=', 1)->field('admin_id,username')->select();
        View::assign('adminResult',$adminResult);

        return $this->fetch();
    }

    /**
     *批量导入客户
     */
    public function import()
    {

        if ($this->request->isPost()) {
            @ini_set("memory_limit",'-1');
            @ini_set('max_execution_time', '0');
            $params = $this->request->post();

            if (!$params['filepath']) {
                $this->error(fy("Parameter error"));
            }
            $filePath = $this->app->getRootPath().'public'.$params['filepath'];

            if (!is_file($filePath)) {
                $this->error(fy("The uploaded file was not found"));
            }

            //实例化reader
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if (!in_array($ext, ['csv', 'xlsx'])) {
                $this->error(fy("Please select EXCEL format to import"));
            }
            if ($ext === 'xlsx') {
                $reader = new \OpenSpout\Reader\XLSX\Reader();
            } else {
                $reader = new \OpenSpout\Reader\CSV\Reader();
            }


            //加载文件
            $insert = 0;
            try {

                $reader->open($filePath);
                /*if (!$PHPExcel = $reader->load($filePath)) {
                    throw new  exception(fy("Failed to get file data"));
                }*/

                $prefix=getDataBaseConfig('prefix');
                $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field` FROM `'.$prefix.'system_field` WHERE (rule <> "" AND `form`=1 AND `table`="crm_customer" AND `editinput` is not null)  order BY `sort` ASC,id ASC');
                $rule=$arr_fields=[];
                foreach ($fields as $v){
                    $name=$v['xsname']?$v['xsname']:$v['name'];
                    $msg=!empty(trim($v['msg']))?'|'.$v['msg']:'|'.$name;
                    $ruleKey=$v['field'].$msg;
                    $rule[$ruleKey]=str_replace('unique','unique:crm_customer',str_replace(',','|',$v['rule']));
                }
//                获取字段对应类型关系
                $fields=Db::query('SELECT `field`,`formtype`,`option`,`lang` FROM `'.$prefix.'system_field` WHERE `export`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
                foreach ($fields as $v){
                    $arr_fields[$v['field']]=$v;
                }

                $allowCustomersNum=$this->model->allowCustomersNum($this->admin);
                if($allowCustomersNum['max_customers_num']>0){
//                大于零说明要验证
                    $yx_c=$allowCustomersNum['yx_c'];
                    if($yx_c<=0){
                        throw new  exception(fy("The maximum number of customers you can currently have is full and cannot be imported"));
                    }
                }

                $fields = [];$insert=0;$sql='';
//                获取第一列
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $index => $row) {
                        $data=[];
                        if(isset($yx_c)){
                            if($yx_c<=0){
                                if($sql){
                                    \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
                                    $sql='';
                                }
                                throw new  exception(fy("The maximum number of customers you can currently have is full and cannot be imported"));
                            }
                        }
                        foreach ($row->getCells() as $column => $cell)  {
                            $val=$cell->getValue();
//                        *客户名称(name)  获取字段
                            $val =trim($val);
                            if($index==1) {
                                preg_match('/\(([a-zA-Z][a-zA-Z0-9_]*)\)$/',$val,$match);
                                if(!empty($match[1])){
                                    $fields[$column] = $match[1];
                                }else{
                                    throw new  exception(fy("Please use the correct import template"));
                                }

                            }else{
                                if(empty($val))continue;
                                $data[$fields[$column]]=$val;
                            }
                        }
                        if(empty(array_filter($data))){
//                        如果为空则直接跳过
                            continue;
                        }
//                    0-线索，1-客户，2-公海，3-删除
                        if($params['pr_user']){
                            $data['pr_user']=$params['pr_user'];
                            $data['owner_admin_id'] = Db::name('admin')->cache('admin_username_'.$params['pr_user'],3600)->where(['username'=>$params['pr_user']])->value('admin_id');
                            $data['status']=1;
                            $data['to_kh_time']=time();
                        }else{
                            $data['pr_user']='';
                            $data['owner_admin_id']=0;
                            $data['status']=2;
                            $data['to_gh_time']=time();
                        }
                        $data['at_user']=$this->admin['username'];
                        $data['update_time']=$data['create_time']=time();

                        try {
                            if($rule){
                                $this->validate($data, $rule);
                            }
                            foreach ($data as $k=>$v){
                                if(isset($arr_fields[$k]['formtype'])){
                                    switch($arr_fields[$k]['formtype']){
                                        case 'datetime':
                                        case 'date':
                                            if(!is_numeric($v)){
                                                $v = str_replace(['年', '月', '日'], ['-', '-', ''], $v);
                                                $data[$k]=strtotime($v);
                                            }
                                            break;
                                        case 'select':
                                        case 'radio':
                                            $selectList=[];
                                            $option=explode(',',$arr_fields[$k]['option']);
                                            if($option){
                                                foreach ($option as $v1){
                                                    $vv=explode(':',$v1);
                                                    if($vv){
                                                        $vv[0]=trim($vv[0]);
                                                        if(empty($vv[1]))$vv[1]=$vv[0];
                                                        $selectList[trim($vv[1])]=$vv[0];
                                                    }
                                                }
                                                if(isset($selectList[$v])){
                                                    $data[$k]= $selectList[$v];
                                                }
                                            }
                                    }
                                }
                            }
                            $temp = Db::name('crm_customer')->fetchSql(true)->save($data).';';
                            if ($temp) {
                                $sql=$sql.$temp;
                                $insert++;
                                if(isset($yx_c)){
                                    $yx_c--;
                                }
                                if($insert%600==0){
                                    flush();
                                    ob_flush();
//                                            每600条执行一遍
                                    \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
                                    $sql='';
                                }

                            }
                        } catch (\Exception $e) {
                            if ($params['skip'] != 1) {
                                throw new  exception($e->getMessage().'，待插入的数据：'.var_export($data,true));
                            }else{
                                \think\facade\Log::write($e->getMessage(),'error');
                                $sql='';
                            }
                        }

                    }
                }
                $reader->close();

                if($sql){
                    \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
                    $sql='';

                }

            } catch (\Exception $e) {

                $this->error($e->getMessage());
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
            if (!$insert) {
                $this->error(fy("No data was imported"));
            }
            $this->success(fy('%s imported successfully',[$insert]));
        }
        return $this->fetch();
    }


    /**
     * 获取导入模板
     * @throws PDOException
     * @throws \think\db\exception\BindParamException
     */
    public function getImportTpl()
    {
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `field`,`name`,`xsname`,`width`,`rule` FROM `'.$prefix.'system_field` WHERE `export`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $i = 0;
        foreach ($fields as $k => $v) {
            $name=$v['xsname']?$v['xsname']:$v['name'];
            if ($i >= 26) {
                $cell = chr(65 + $i / 26 - 1) . chr(65 + $i % 26);
            } else {
                $cell = chr(65 + $i);
            }
            $spreadsheet->getActiveSheet()->getStyle($cell . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('cdf79e');
            $required='';
            if(strpos($v['rule'],'require')!==false)$required='*';
            $sheet->getColumnDimension($cell)->setWidth($v['width'],'px');
            $sheet->setCellValue($cell . '1', $required.$name."({$v['field']})");
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=customertpl.xlsx");
        header('Cache-Control: max-age=0');
        header('Cache-Control: cache, must-revalidate');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        try {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        exit();
    }

    public function export()
    {
        @ini_set("memory_limit", '-1');
        @ini_set('max_execution_time', '0');

        list($page, $limit, $where, $sort) = $this->buildTableParames();
        $scope = $this->request->get('scope', 1, 'trim');

        if($scope==2){
//                    展示其他的  不包括自己
            $adminIds = \app\service\AdminService::getViewAdminIds($this->admin);
            if (empty($adminIds)) {
                return json([
                    'code'  => 1,
                    'msg'   => '',
                    'count' => 0,
                    'data'  => [],
                ]);
            }
            if ($adminIds !== 'ALL') {
                $where[] = ['owner_admin_id', 'in', $adminIds];
            }else{
                $where[] = ['owner_admin_id', '<>', $this->admin['admin_id']];
            }

        }elseif($scope==3){
//                    展示全部 包括自己
            $adminIds = \app\service\AdminService::getViewAdminIds($this->admin, true);
            if(empty($adminIds)){
                $where[] = ['id', 'in',-1];
            }elseif($adminIds !== 'ALL'){
                $where[] = ['owner_admin_id', 'in',$adminIds];
            }
        }elseif($scope==10){
// 待跟进
            $where[] = ['next_time', '>', 0];
            $where[] = ['next_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

        }elseif($scope==11){
// 今天已跟进
            $where[] = ['last_up_time', '>=', strtotime('today')];
            $where[] = ['last_up_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

        }elseif($scope==12){
// 从未跟进
            $where[] = ['last_up_time', '=', 0];
// 限制展示自己的
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

        }elseif($scope==20){

            //            展示自己分享给他人的
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
            $where[] = ['share_admin_ids', '<>', ''];

        }elseif($scope==21){
            //                    展示分享给我的
            $where[]=['','exp',\think\facade\Db::raw("FIND_IN_SET('{$this->admin['admin_id']}',share_admin_ids)")];
        }else{
//                   限制展示自己的
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
        }
        $where[]=['status','=',1];

        // ...（保留原有的 $where 条件设置逻辑，这部分代码未改动）

        $str_fields = '`id`';
        $fields = Db::query('SELECT `field`, `name`, `xsname`, `width`, `rule`, `formtype`, `option` FROM `' . getDataBaseConfig('prefix') . 'system_field` WHERE (`export`=1 OR `list`=1) AND `table`="crm_customer" ORDER BY `sort` ASC, id ASC');

        // 初始化 Spout writer
//        $writer = WriterEntityFactory::createXLSXWriter();
        $writer = new \OpenSpout\Writer\XLSX\Writer();

        $tempPath=$this->app->getRootPath().'public'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;
        if(!is_dir($tempPath)){
            mkdir($tempPath,755,true);
        }
        deleteFilesOlderThanDays($tempPath, 1);
        $tempFile =  date('YmdHis') .'uid'.$this->admin['admin_id'].'_export.xlsx';
        $writer->openToFile($tempPath.$tempFile);


        $str_fields='`id`';
        $cells=[];
        foreach ($fields as $k => $v) {
            $name=$v['xsname']?$v['xsname']:$v['name'];
            $fields[$k]['xsname']=$name;

            if($v['field']!='id'){
                $str_fields=$str_fields.',`'.$v['field'].'`';
            }
            $cells[]=Cell::fromValue($name);
        }

        if($cells){
            $singleRow = new Row($cells);
            $writer->addRow($singleRow);
        }

        // 查询数据并写入文件
        $cursor = $this->model->field(trim($str_fields, ','))->where($where)->order($sort)->cursor();


        foreach ($cursor as $item) {
            $rowData = [];
            foreach ($fields as $field) {
                $value = $item[$field['field']];
                $value = real_field_val($field, $value);
                $rowData[] =Cell::fromValue($value);
            }
            if($rowData){
                $singleRow = new Row($rowData);
                $writer->addRow($singleRow);
            }
        }

        // 关闭 writer 并准备下载
        $writer->close();
        $this->redirect(__MY_PUBLIC__.'/temp/'.$tempFile);

    }


    //移入公海
    public function to_move_gh(){
        //1，获取提交的线索ID 【1,2,3,4,】
        $ids = parseIds();

        if ($this->request->isAjax()){
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
    }
//客户共享
    public function share(){
//        总针对一个id
        $ids = $this->request->param('ids', $this->request->param('id'));

        //查询所有管理员（去除admin）
        $adminResult = Db::name('admin')->where('is_open','=',1)->where('admin_id','<>',$this->admin['admin_id'])->field('admin_id,username')->select();
        View::assign('adminResult',$adminResult);

        if ($this->request->isAjax()){
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

        return $this->fetch();
    }


//取消共享
    public function del_share($id)
    {
        $id = $this->request->param('ids', $id);
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
        return $this->fetch();

    }

    public function sendEmail($id){
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissionsByIds($row['owner_admin_id']);
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
            return $this->fetch();
        }
    }



}
