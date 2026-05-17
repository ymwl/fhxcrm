<?php

namespace app\api\controller\crm;

use app\api\controller\Authority;

use think\App;
use think\facade\Db;

/**
 * 客户联系人管理 技术支持联系zrwx978
 */
class CustomerContacts extends Authority
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
        $fields=\tools\Cache::zdy_fields('crm_customer_contacts');
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
                        'code'  => 1,
                        'msg'   => '',
                        'data'  => ['rows'=>[], 'count'=>0],
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
                        'code'  => 1,
                        'msg'   => '',
                        'data'  => ['rows'=>[], 'count'=>0],
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
// data-auth-edit="{:auth('crm.customer_contacts/edit')}"
//               data-auth-delete="{:auth('crm.customer_contacts/delete')}"
//               data-auth-record_add="{:auth('crm.record/add')}"
//               data-auth-record_index="{:auth('crm.record/index')}"
//               data-auth-fields="{:auth('system.fields/index')}"
//               data-auth-sendEmail="{:auth('crm.customer_contacts/sendEmail')}"
        $data = [
            'code'  => 1,
            'msg'   => '',
            'auth'=>[
                'edit'=>auth('crm.customer_contacts/edit', $this->admin),
                'delete'=>auth('crm.customer_contacts/delete', $this->admin),
                'sendEmail'=>auth('crm.customer_contacts/sendEmail', $this->admin)
            ],
            'data'  => ['rows'=>$list, 'count'=>$count],
        ];
        return json($data);
        
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
        
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id = 0)
    {
        $id = $id ?: $this->request->param('id', 0, 'intval');
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
        // API模块返回JSON数据供手机端编辑表单使用
        $this->jsonSuccess('', $row);
    }

    public function sendEmail(){
        $param = $this->request->param();
        if(empty($param['customer_contacts_id'])){
            $this->error('请选择联系人');
        }
        $row = $this->model->field('id,customer_id')->find($param['customer_contacts_id']);

        empty($row) && $this->error(fy('The data does not exist'));
        $crmCustomer=\think\facade\Db::name('crm_customer')->field('c.id,c.name,a.admin_id')->alias('c')->join('admin a','c.pr_user=a.username')->where('c.id','=',$row['customer_id'])->find ();
        if(empty($crmCustomer)){
            $this->error('不存在的客户信息!');
        }
        $this->modifyPermissions($crmCustomer['admin_id']);
        if ($this->request->isPost()) {


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
//customer_contacts_id
//6
//customer_id
//19
//type
//customer_contacts
//to_email
//315988561@qq.com
//emailtpl_id
//1
//tpl_name
//公司介绍
//subject
//XXX信息技术有限公司 – 感谢您的关注
//email_content

                send_email($param['to_email'], $param['subject'],$param['email_content'],true,1,$param['type'],$this->admin);

            $this->success('邮件发送成功');
        }
    }



}