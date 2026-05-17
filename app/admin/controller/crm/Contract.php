<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use think\facade\Request;

/**
 * 合同控制器
 */
class Contract extends AdminController
{

    protected $relationSearch = true;
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\common\model\CrmContract();
        
    }

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
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            if($customer_id){
                $where[]=['crm_contract.customer_id','=',$customer_id];
            }else{
                $scope=$this->request->get('scope', 1,'intval');
                if($scope==2){
//                    展示其他的  不包括自己
                    $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin);
                    if(empty($adminIds)){
                        return json([
                            'code'  => 0,
                            'msg'   => '',
                            'count' => 0,
                            'data'  => [],
                        ]);
                    }
                    if($adminIds!=='ALL'){
                        $where[] = ['crm_contract.owner_admin_id', 'in',$adminIds];
                    }elseif($adminIds=='ALL'){
//                    展示其他的  不包括自己需要做排除
                        $where[] = ['crm_contract.owner_admin_id', '<>',$this->admin['admin_id']];
                    }

                }elseif($scope==3){
//                    展示全部 包括自己
                    $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin,true);
                    if(empty($adminIds)){
                        return json([
                            'code'  => 0,
                            'msg'   => '',
                            'count' => 0,
                            'data'  => [],
                        ]);
                    }
                    if($adminIds!=='ALL'){
                        $where[] = ['crm_contract.owner_admin_id', 'in',$adminIds];
                    }
                }elseif($scope==10){
// 待跟进
                    $where[] = ['crm_contract.next_time', '>', 0];
                    $where[] = ['crm_contract.next_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
                    $where[] = ['crm_contract.owner_admin_id', '=', $this->admin['admin_id']];

                }elseif($scope==11){
// 今天已跟进
                    $where[] = ['crm_contract.last_up_time', '>=', strtotime('today')];
                    $where[] = ['crm_contract.last_up_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
                    $where[] = ['crm_contract.owner_admin_id', '=', $this->admin['admin_id']];

                }elseif($scope==12){
// 从未跟进
                    $where[] = ['crm_contract.last_up_time', '=', 0];
// 限制展示自己的
                    $where[] = ['crm_contract.owner_admin_id', '=', $this->admin['admin_id']];

                }else{
//                   限制展示自己的
                    $where[] = ['crm_contract.owner_admin_id', '=', $this->admin['admin_id']];
                }

            }


           $count = $this->model ->withJoin(['crmCustomer' => ['name'], 'ownerAdmin' => ['username']], 'LEFT')->where($where)->count();
//            下面的查询用说明变量接收
/*
 *
SELECT
	count( crm_contract.id ) AS `total`,
	sum( crm_contract.money ) AS `contractTotalAmount`,
	sum( crm_contract.return_money ) AS `receivedTotalAmount`,
	`crm_customer`.`name` AS `crm_customer__name`,
	`owner_admin`.`username` AS `owner_admin__username`
FROM
	`ymwl_crm_contract` `crm_contract`
	LEFT JOIN `ymwl_crm_customer` `crm_customer` ON `crm_contract`.`customer_id` = `crm_customer`.`id`
	LEFT JOIN `ymwl_admin` `owner_admin` ON `crm_contract`.`owner_admin_id` = `owner_admin`.`admin_id`
WHERE
	( `crm_contract`.`owner_admin_id` = '1' )
	LIMIT 1
 * */

            $contractTotalAmount =  0;
            $receivedTotalAmount =  0;


            $list=[];
            // 接收变量


            if($count){
                $result = $this->model->alias('crm_contract')
                    ->field([
                        'sum(crm_contract.money)' => 'contractTotalAmount',
                        'sum(crm_contract.return_money)' => 'receivedTotalAmount'
                    ])
                    ->join('crm_customer', 'crm_contract.customer_id = crm_customer.id', 'LEFT')
                    ->join('admin', 'crm_contract.owner_admin_id = admin.admin_id', 'LEFT')
                    ->where($where)->where('check_status','=',3)
                    ->find();
                $contractTotalAmount = $result['contractTotalAmount'] ?? 0;
                $receivedTotalAmount = $result['receivedTotalAmount'] ?? 0;
                $list = $this->model
                    ->withJoin(['crmCustomer' => ['name'], 'ownerAdmin' => ['username']], 'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();

            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
                'contractTotalAmount'  => $contractTotalAmount,
                'receivedTotalAmount'  => $receivedTotalAmount,
            ];
            return json($data);
        }

        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query("SELECT `name`,`jscol` FROM `{$prefix}system_field` WHERE `show`=1 AND `table`='crm_contract' AND `jscol` is not null order BY `sort` ASC,id ASC");
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=$v['jscol'].',';
        }

        $this->app->view->engine()->layout(false);
        $fields_str=$this->display(trim($fields_str,','));

        $this->app->view->engine()->layout($this->layout);
        $fields_str=str_replace(['":"{"','"}"'],['":{"','"}'],$fields_str);

        $this->assignconfig('cols_fields',json_decode('['.$fields_str.']',true));

        $this->assignconfig('getCheckStatus', \app\service\CrmContractService::getCheckStatus());
        $this->assignconfig(['customer_id'=>$customer_id]);
        return $this->fetch();
    }

    public function add()
    {
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`editinput`,`formtype`,`addinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract" order BY `sort` ASC,id ASC');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['contract']=$this->param_to_str($post['contract']);
            $rule = [
                'customer_id|必须选择对应客户'    => 'require',
                'money|合同金额'    => 'require',
                'name|合同名称' => 'require|unique:crm_contract',
                'numbering|合同编号' => 'require|unique:crm_contract',
            ];
            $this->validater($post['contract'], $rule);
            $this->verifyFields($post['contract'],$fields,'crm_contract');
            $row_customer=Db::name('crm_customer')->field('id,source')->where([['id','=',$post['contract']['customer_id']],['pr_user','=',$this->admin['username']]])->find();
            if(empty($row_customer['id'])){
                $this->error('合同只能选择自己的客户');
            }
            Db::startTrans();
            try {
                $post['contract']=post_convert($post['contract'],$fields);
                $post['contract']['sign_time'] = $post['contract']['sign_time'] ? strtotime($post['contract']['sign_time']) : null;
                $post['contract']['start_time'] = $post['contract']['start_time'] ? strtotime($post['contract']['start_time']) : null;
                $post['contract']['end_time'] = $post['contract']['end_time'] ? strtotime($post['contract']['end_time']) :null;
                if($post['contract']['start_time'] > $post['contract']['end_time']){
                    throw new \Exception("合同生效时间不能大于结束时间!");
                }
                $post['contract']['create_username'] =$this->admin['username'];
                $post['contract']['owner_admin_id'] =$this->admin['admin_id'];
                $post['contract']['check_status'] =0;
                $post['contract']['source'] =$row_customer['source'];
                $save = $this->model->allowField($this->model->getTableFields())->save($post['contract']);
                $total_price=0;
                if(!empty($post['product'])){
                    $i=1;
                    foreach ($post['product'] as &$row) {
                        unset($row['id']);
                        if(!is_numeric($row['nums']) || $row['nums'] < 1){
                            throw new \Exception('合同记录的产品'.$row['product_extend']['name'].'数量不对，'.$i.'行');
                        }

                        $row_product=Db::name('product')->field('id,name,inventory')->where('id', '=',$row['product_id'])->where('status', '=',1)->find();
                        if(empty($row_product['id'])){
                            throw new \Exception("不存在的产品");
                        }
                        $row['product_extend']=json_encode($row['product_extend'],256);
                        $row['contract_id'] =$this->model->id;
                        ;

                        $total_price=bcadd($total_price,bcsub(bcmul($row['sale_price'],$row['nums'],2),$row['discount'],2),2);
//                        $total_price=$total_price+$row['sale_price']*$row['nums']-$row['discount'];
                        $i++;
                     }
                    Db::name('crm_contract_product')->insertAll($post['product']);
                }


                $audit_management_id=Db::name('audit_management')->insertGetId([
                    'title'=>'合同审核',
                    'mess'=>$this->admin['username'].'添加合同：'.$post['contract']['name'],
//                    'url'=>myurl('admin/crm.order/audit',['id'=>$id]),
                    'url'=>'crm.contract/audit.html?id='.$this->model->id,
                    'createtime'=>time(),
                    'result'=>'To be reviewed',
                    'admin_id'=>$this->admin['admin_id'], 'table_name'=>'crm_contract',
                    'table_id'=>$this->model->id,
                    'show_auth_group_id'=>actiongroup('crm.contract/audit')
                ]);
                $this->model->save(['total_price'=>$total_price,'audit_management_id'=>$audit_management_id]);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            if($save){
                Db::commit();
                $this->success(fy('Save successfully')) ;
            }else{
                Db::rollback();
                $this->error(fy('Save failed'));
            }
        }

        $fields_str='';
        foreach ($fields as $v){
//         remark  替换成 contract[remark]  $v['field']
            $v['addinput']=trim($v['addinput']);
            if($v['addinput']){
                $v['addinput'] = str_replace('"'.$v['field'].'"', '"contract['.$v['field'].']"', $v['addinput']);
                $fields_str.=$v['addinput'];
            }

        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>[]]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);


        $numbering=$this->model->autoNo($this->system['contract_format']);
        $this->assign(['numbering'=>$numbering]);
        return $this->fetch();
    }

    public function edit($id)
    {
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`editinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract" order BY `sort` ASC,id ASC');

        $row = $this->model->field(array_unique(array_merge(array_column($fields, 'field'), ['id','check_status','customer_id'])))->find($id);
        $this->modifyPermissions($row['owner_admin_id']);
        empty($row) && $this->error(fy('The data does not exist'));
        if($row['check_status']>=0){
            $this->error('合同已审核，不能修改');
        }
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['contract']=$this->param_to_str($post['contract']);
            if(isset($post['contract']['numbering'])){
                unset($post['contract']['numbering']);
            }


            $rule = [
                'customer_id|必须选择对应客户'    => 'require',
                'money|合同金额'    => 'require',
                'name|合同名称' => "require|unique:crm_contract,name,{$id}"
            ];
//             unique:users,email,{$id},id
//格式说明：
//
//users: 表名
//email: 字段名
//$id: 要排除的记录 ID
//id: 主键字段名（可选，默认是 id）
            $this->validater($post['contract'], $rule);
            $this->verifyFields($post['contract'], $fields,'crm_contract');
            foreach ($fields as $v){
                if($v['edit_readonly']){
//                    只读的数据无需保存
                    unset($post['contract'][$v['field']]);
                }
            }
            $row_customer=Db::name('crm_customer')->field('id,source')->where([['id','=',$post['contract']['customer_id']],['pr_user','=',$this->admin['username']]])->find();
            if(empty($row_customer['id'])){
                $this->error('合同只能选择自己的客户');
            }
            Db::startTrans();
            try {
                $post['contract']=post_convert($post['contract'],$fields);
                if (isset($post['contract']['numbering']))unset($post['contract']['numbering']);
                $post['contract']['sign_time'] = $post['contract']['sign_time'] ? strtotime($post['contract']['sign_time']) : null;
                $post['contract']['start_time'] = $post['contract']['start_time'] ? strtotime($post['contract']['start_time']) : null;
                $post['contract']['end_time'] = $post['contract']['end_time'] ? strtotime($post['contract']['end_time']) :null;
                if($post['contract']['start_time'] > $post['contract']['end_time']){
                    throw new \Exception("合同生效时间不能大于结束时间!");
                }


                $post['contract']['check_status'] =0;
                $save = $row->allowField($this->model->getTableFields())->save($post['contract']);
                $total_price=0;
//                先删除
                Db::name('crm_contract_product')->where('contract_id', '=',$row->id)->delete();
                if(!empty($post['product'])){
//                    再重新增加
                    $i=1;
                    foreach ($post['product'] as &$v) {
                        if(isset($v['id'])){
                            unset($v['id']);
                        }

                        if(!is_numeric($v['nums']) || $v['nums'] < 1){
                            throw new \Exception('合同记录的产品'.$v['product_extend']['name'].'数量不对，'.$i.'行');
                        }

                        $row_product=Db::name('product')->field('id,name,inventory')->where('id', '=',$v['product_id'])->where('status', '=',1)->find();
                        if(empty($row_product['id'])){
                            throw new \Exception("不存在的产品");
                        }
                        $v['product_extend']=json_encode($v['product_extend'],256);
                        $v['contract_id'] =$row->id;
                        ;

                        $total_price=bcadd($total_price,bcsub(bcmul($v['sale_price'],$v['nums'],2),$v['discount'],2),2);
                        $i++;
                    }
                    Db::name('crm_contract_product')->insertAll($post['product']);
                }


                $audit_management_id=Db::name('audit_management')->insertGetId([
                    'title'=>'合同审核',
                    'mess'=>$this->admin['username'].'编辑合同：'.$post['contract']['name'],
                    'url'=>'crm.contract/audit.html?id='.$row->id,
                    'createtime'=>time(),
                    'result'=>'To be reviewed',
                    'admin_id'=>$this->admin['admin_id'],
                    'table_name'=>'crm_contract',
                    'table_id'=>$row->id,
                    'show_auth_group_id'=>actiongroup('crm.contract/audit')
                ]);
                $row->save(['total_price'=>$total_price,'audit_management_id'=>$audit_management_id]);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            if($save){
                Db::commit();
                $this->success(fy('Save successfully')) ;
            }else{
                Db::rollback();
                $this->error(fy('Save failed'));
            }
        }

        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);


        $crmCustomer=\think\facade\Db::name('crm_customer')->field('c.id,c.name,a.admin_id')->alias('c')->join('admin a','c.pr_user=a.username')->where('c.id','=',$row['customer_id'])->find ();
        $this->assign('crmCustomer', $crmCustomer);
        $this->assign('row', $row);
        return $this->fetch();
    }

    public function audit(){
        //        status 1 审核通过  -1审核不通过
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            $this->error('访问参数非法');
        }
        $row = $this->model->withJoin(['crmCustomer' => ['name'],'crmBusiness' => ['name'], 'ownerAdmin' => ['username']],'LEFT')->where('crm_contract.id',$id)->find();
        empty($row) && $this->error('需要审核的合同不存在');
        if (trim($row['check_status']) > 0 ) {
            $this->error('该合同已经审核过了');

        }
        if($this->request->isAjax()){
            $status= Request::param('status',-1);
            $audit_management_id= $this->request->post('audit_management_id',0);
            if(!$audit_management_id){
                return json(['code' => 0,'msg'=>fy('Parameter error'),'data'=>[]]);
            }
            $result_mess= $this->request->post('result_mess','','trim');

//            加入事务处理过程
            Db::startTrans();
            try {
                if ($status > 0) {
                    //        status 1 审核通过  -1审核不通过
                    $updatearr = [];
                    $updatearr['issuccess'] = 1;
                    $updatearr['success_time'] = time();
//                成功成功的转成客户
                    $updatearr['status'] = 1;
                    Db::name('crm_customer')->where([['id','=' ,$row['customer_id']],['issuccess','=',0]])->update($updatearr);
                    $result = $this->model->where('id', $id)->update(['check_status' => 3, 'audit_feedback' => $result_mess]);
                    $todoDate = ['result' => 'Approved', 'result_mess' => $result_mess, 'is_finish' => 1];
                    //                    对应的商机也需要改成已成交
                    if ($row['business_id']) {
                        Db::name('crm_business')->where('id', $row['business_id'])->update(['status' => 1]);
                    }
                    $msg = ['code' => 1, 'msg' => fy('Submitted successfully'), 'data' => []];

                } else {
                    if($result_mess==''){
                        throw new \Exception('审核不通过必须填写原因', 0);
                    }
                    $result = $this->model->where('id', $id)->update(['check_status' => -1, 'audit_feedback' => $result_mess]);
                    $todoDate = ['result' => 'The audit failed', 'result_mess' => $result_mess, 'is_finish' => 1];
                    $msg = ['code' => 1, 'msg' => fy('Submitted successfully'), 'data' => []];
                }
                if ($todoDate && $result) {
                    $todoDate['audittime'] = time();
                    $todoDate['reviewer'] = $this->admin['username'];
                    Db::name('audit_management')->where('id', '=', $audit_management_id)->update($todoDate);
                } else {
                    $msg = ['code' => 0, 'msg' => fy('Submit failed'), 'data' => []];
                }
                Db::commit();
            }catch (\Exception $e) {
                Db::rollback();
                $msg=$e->getMessage();
                $msg = ['code' => 0, 'msg' =>fy('Save failed').':'.$msg, 'data' => []];
            }

            return json($msg);
        }

        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`editinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract" AND `editinput` is not null order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);
        $this->assign('row', $row);
        return $this->fetch();
    }

    public function desc(){
        //        status 1 审核通过  -1审核不通过
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            $this->error('访问参数非法');
        }
        $row = $this->model->alias('crm_contract')->withJoin(['crmCustomer' => ['name'],'crmBusiness' => ['name'], 'ownerAdmin' => ['username']],'LEFT')->where('crm_contract.id',$id)->find();
        empty($row) && $this->error('需要审核的合同不存在');

        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`editinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract" AND `editinput` is not null order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);
        $crmCustomer=\think\facade\Db::name('crm_customer')->field('c.id,c.name,a.admin_id')->alias('c')->join('admin a','c.pr_user=a.username')->where('c.id','=',$row['customer_id'])->find ();
        $this->assign('crmCustomer', $crmCustomer);
        $this->assign('row', $row);

        // 获取当前合同的回款计划列表
        $receivablesPlans = Db::name('crm_contract_receivables_plan')
            ->where('contract_id', $id)
            ->order('id desc')
            ->select();
        $this->assign('receivablesPlans', $receivablesPlans);

        $this->assign('getCheckStatus', \app\service\CrmContractService::getCheckStatus());
        return $this->fetch();
    }



    public function create_numbering(){
        $numbering=$this->model->autoNo($this->system['contract_format']);
        $this->success('生成成功','',['numbering'=>$numbering]);
    }
//    获取合同对应的产品
    public function product(){
        if($this->request->isAjax()){
            $id=$this->request->get('id');
            if($id){
                $info=Db::name('crm_contract_product')->where('contract_id','=',$id)->order('create_time ASC')->select()->toArray();
                foreach ($info as &$row){
                    $row=$row+ json_decode($row['product_extend'],true);
                    unset($row['product_extend']);
                }
                $this->success(fy('Get successful'),'',$info);
            }

        }
    }

    //    传入商机产品id 删除对应商机产品
    public function delproduct(){
        if($this->request->isAjax()){
            $product_id=$this->request->get('product_id');
            if($product_id){
                $res=Db::name('crm_contract_product')->where('id','=',$product_id)->delete();
                if($res){
                    $this->success(fy('Delete succeeded'));
                }else{
                    $this->success(fy('Delete failed'));
                }

            }

        }
    }

    public function delete(){
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $owner_admin_ids = $this->model->whereIn('id', $id)->column('DISTINCT owner_admin_id');
        $this->modifyPermissions($owner_admin_ids);

        $c = $this->model->whereIn('id', $id)->where('check_status','>',1)->count();
        if($c>0){
            $this->error('当前合同不能删除');
        }
        $list = $this->model->whereIn('id', $id)->select();


        $list->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            foreach ($list as $v) {
                change_success_customer($v->customer_id);
                $v->delete();
                //            如果删除了则没有审核完成的标记为完成
                Db::name('audit_management')->where('table_id',$v->id)->where('is_finish',0)->where('table_name','crm_contract')->update(['is_finish'=>1]);
            }
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
         $this->success('删除成功');

    }

    public function sendEmail($id){
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissions($row['owner_admin_id']);
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
            $emailtpl=\think\facade\Db::name('emailtpl')->field('id,name')->order('sort asc')->where('type','in',['customer','contract'])->select();
            $this->assign('emailtpl',$emailtpl);
            $this->assign('id',$id);
            if(empty($row['email'])){
                $row['email']=\think\facade\Db::name('crm_customer')->where('id',$row['customer_id'])->value('email');
            }
            $this->assign('row',$row);
            return $this->fetch();
        }
    }


    
}