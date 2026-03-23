<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;
use think\facade\Db;

/**
 * 客户联系人管理 技术支持联系zrwx978
 */
class CustomerContacts extends AdminController
{
    protected $relationSearch = true;
    protected $selectpageFields = '`id`,`contact`,`phone`';

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmCustomerContacts();
        
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        $customer_id=$this->request->param('customer_id',0);
//        判断当前的登录者是否有操作权限
        if($customer_id){
            $pr_user=\think\facade\Db::name('crm_customer')->where('id','=',$customer_id)->value('pr_user');
            if(empty($pr_user)){
                $this->error('当前客户不存在或已移入公海！');
            }
            $this->modifyPermissionsByName($pr_user);
        }

        $fields=cache('crm_customer_contacts_fields');
        if(!$fields){
            $prefix=getDataBaseConfig('prefix');
            $fields=Db::query("SELECT  `field`, `jscol`,`show` FROM `{$prefix}system_field` WHERE `table`='crm_customer_contacts' AND `show`=1 AND `jscol` is not null order BY `sort` ASC,id ASC");
            $field_str=$jscol_str='';
            foreach ($fields as $key=>$value){
                $field_str.=$value['field'].',';
                if($value['show']==1){
                    $jscol_str.=$value['jscol'].',';
                }
            }
            $fields=['field_str'=>trim($field_str,','),'jscol_str'=>trim($jscol_str,',')];
            cache('crm_customer_contacts_fields',$fields);
        }

        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            if($customer_id){
                $where[]=['crm_customer_contacts.customer_id','=',$customer_id];
            }else{
                $scope = $this->request->get('scope',1,'intval');
                if($scope==2){
//                    展示下属的
                    $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin);
                    if(empty($adminName)){
                        return json([
                            'code'  => 0,
                            'msg'   => '',
                            'count' => 0,
                            'data'  => [],
                        ]);
                    }
                    if($adminName!=='ALL'){
                        $where[] = ['crm_customer.pr_user', 'in',$adminName];
                    }elseif($adminName=='ALL'){
//                    展示其他的  不包括自己需要做排除
                        $where[] = ['crm_customer.pr_user', '<>',$this->admin['username']];
                    }

                }elseif($scope==3){
//                    展示下属的和自己的
                    $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin,true);
                    if(empty($adminName)){
                        return json([
                            'code'  => 0,
                            'msg'   => '',
                            'count' => 0,
                            'data'  => [],
                        ]);
                    }
                    if($adminName!=='ALL'){
                        $where[] = ['crm_customer.pr_user', 'in',$adminName];
                    }
                }else{
//                    展示自己的
                    $where[] = ['crm_customer.pr_user', '=', $this->admin['username']];
                }
            }
//            返回sql语句的写法
            $count = $this->model->withJoin(['crmCustomer' => ['name','pr_user']],'LEFT')->where($where)->count();
            $list=[];
            if($count){
                $field_str=empty($fields['field_str'])?'*':$fields['field_str'];
                if (empty($fields['field_str'])){
                    $field_str='*';
                }else{
                    if(!in_array('id',explode(',',$fields['field_str']))){
                        $field_str='id,'.$field_str;
                    }
                    if(!in_array('customer_id',explode(',',$fields['field_str']))){
                        $field_str='customer_id,'.$field_str;
                    }
                }

                $list = $this->model->field($field_str)->withJoin(['crmCustomer' => ['name','pr_user']],'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
                if ($this->admin['isphone'] == 0 && $list) {
                    foreach ($list as $key => $value) {
                        $value['phone'] = mb_substr($value['phone'], 0, 3).'****'. mb_substr($value['phone'], 7, 11);
                        $list[$key] = $value;
                    }
                }
            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $jscol_str= $fields['jscol_str'];
        $this->app->view->engine()->layout(false);
        $jscol_str=$this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str=str_replace(['":"{"','"}"'],['":{"','"}'],$jscol_str);
        $this->assignconfig('cols_fields',json_decode('['.$jscol_str.']',true));
        $this->assignconfig(['customer_id'=>$customer_id]);
        return $this->fetch();
    }

    public function add()
    {
        $customer_id=$this->request->param('customer_id',0);
        $customer_id=$customer_id+0;
        if(empty($customer_id)){
            $this->error('客户联系人必须在客户详情页面添加');
        }
        $crmCustomer=\think\facade\Db::name('crm_customer')->field('c.id,c.name,a.admin_id')->alias('c')->join('admin a','c.pr_user=a.username')->where('c.id','=',$customer_id)->find ();
        if(empty($crmCustomer)){
            $this->error('不存在的客户信息!');
        }
        $this->modifyPermissions($crmCustomer['admin_id']);
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`formtype`,`addinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_customer_contacts" order BY `sort` ASC,id ASC');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $this->verifyFields($post,$fields,'crm_customer_contacts');
            try {
                $post=post_convert($post,$fields);
                $post['create_username']=$this->admin['username'];
                //谁的客户归谁管理
                $ower_admin_id=$crmCustomer['admin_id'];
                $post['owner_admin_id']=$ower_admin_id;
                $post['create_time']= $post['update_time']=time();
                $post['customer_id']=$customer_id;
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败:'.$e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['addinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>[],'crmCustomer'=>$crmCustomer]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');


        $crmCustomer=\think\facade\Db::name('crm_customer')->field('c.id,c.name,a.admin_id')->alias('c')->join('admin a','c.pr_user=a.username')->where('c.id','=',$row['customer_id'])->find ();
        if(empty($crmCustomer)){
            $this->error('不存在的客户信息!');
        }
        $this->modifyPermissions($crmCustomer['admin_id']);
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`editinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_customer_contacts" order BY `sort` ASC,id ASC');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $this->verifyFields($post,$fields,'crm_customer_contacts');
            foreach ($fields as $v){
                if($v['edit_readonly']){
//                    只读的数据无需保存
                    unset($post[$v['field']]);
                }
            }
                $post=post_convert($post,$fields);
                if(isset($post['customer_id']))unset($post['customer_id']);
                $post['owner_admin_id']=$crmCustomer['admin_id'];
                $post['update_time']=time();
                $save = $row->save($post);

            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$row,'crmCustomer'=>$crmCustomer]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);
//        $this->assign('row', $row);
        return $this->fetch();
    }

    public function sendEmail($id){
        $row = $this->model->find($id);

        empty($row) && $this->error(fy('The data does not exist'));
        $crmCustomer=\think\facade\Db::name('crm_customer')->field('c.id,c.name,a.admin_id')->alias('c')->join('admin a','c.pr_user=a.username')->where('c.id','=',$row['customer_id'])->find ();
        if(empty($crmCustomer)){
            $this->error('不存在的客户信息!');
        }
        $this->modifyPermissions($crmCustomer['admin_id']);
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
                send_email($param['to_email'], $param['subject'],$param['email_content'],true,1,$param['type']);
            } catch (\Exception $e) {
                $this->error('邮件发送失败：' . $e->getMessage());
            }
            $this->success('邮件发送成功');
        }else{
//            emailtpl
            $emailtpl=\think\facade\Db::name('emailtpl')->field('id,name')->order('sort asc')->where('type','in',['customer','customer_contacts'])->select();
            $this->assign('emailtpl',$emailtpl);
            $this->assign('id',$id);
            $this->assign('row',$row);
            return $this->fetch();
        }
    }



}