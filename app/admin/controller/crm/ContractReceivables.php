<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;
use think\facade\Db;

//合同回款管理  技术支持微信:zrwx978
class ContractReceivables extends AdminController
{
    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmContractReceivables();
        
    }

    public function index()
    {
        $contract_id=$this->request->param('contract_id',0);
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            $scope=$this->request->get('scope', 1,'intval');
            if($contract_id){
                $where[]=['crm_contract_receivables.contract_id','=',$contract_id];
            }else{
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
                        $where[] = ['crm_contract_receivables.owner_admin_id', 'in',$adminIds];
                    }elseif($adminIds=='ALL'){
//                    展示其他的  不包括自己需要做排除
                        $where[] = ['crm_contract_receivables.owner_admin_id', '<>',$this->admin['admin_id']];
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
                        $where[] = ['crm_contract_receivables.owner_admin_id', 'in',$adminIds];
                    }
                }elseif($scope==10){
// 待跟进
                    $where[] = ['crm_contract_receivables.next_time', '>', 0];
                    $where[] = ['crm_contract_receivables.next_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
                    $where[] = ['crm_contract_receivables.owner_admin_id', '=', $this->admin['admin_id']];

                }elseif($scope==11){
// 今天已跟进
                    $where[] = ['crm_contract_receivables.last_up_time', '>=', strtotime('today')];
                    $where[] = ['crm_contract_receivables.last_up_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
                    $where[] = ['crm_contract_receivables.owner_admin_id', '=', $this->admin['admin_id']];

                }elseif($scope==12){
// 从未跟进
                    $where[] = ['crm_contract_receivables.last_up_time', '=', 0];
// 限制展示自己的
                    $where[] = ['crm_contract_receivables.owner_admin_id', '=', $this->admin['admin_id']];

                }else{
//                   限制展示自己的
                    $where[] = ['crm_contract_receivables.owner_admin_id', '=', $this->admin['admin_id']];
                }

            }



            $count = $this->model ->withJoin([
                'crmCustomer' => ['name'],
                'crmContract' => ['name'],
                'ownerAdmin' => ['username']], 'LEFT')->where($where)->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->withJoin([
                        'crmCustomer' => ['name'],
                        'crmContract' => ['name'],
                        'ownerAdmin' => ['username']], 'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($this->sort)
                    ->select();

            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,

            ];
            return json($data);
        }

        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query("SELECT `name`,`jscol` FROM `{$prefix}system_field` WHERE `show`=1 AND `table`='crm_contract_receivables' AND `jscol` is not null order BY `sort` ASC,id ASC");
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=$v['jscol'].',';
        }

        $this->app->view->engine()->layout(false);
        $fields_str=$this->display(trim($fields_str,','));

        $this->app->view->engine()->layout($this->layout);
        $fields_str=str_replace(['":"{"','"}"'],['":{"','"}'],$fields_str);

        $this->assignconfig('cols_fields',json_decode('['.$fields_str.']',true));

        $this->assignconfig('getCheckStatus', $this->model->getCheckStatus());
        $this->assignconfig(['contract_id'=>$contract_id]);
        return $this->fetch();
    }

    protected function verifyFields($post){
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field` FROM `'.$prefix.'system_field` WHERE rule <> "" AND `edit`=1 AND `table`="crm_contract_receivables" AND `editinput` is not null order BY `sort` ASC,id ASC');
        $rule=[];
        foreach ($fields as $v){
            $msg=!empty(trim($v['xsname']))?'|'.fy($v['xsname']):'|'.fy($v['name']);
            $ruleKey=$v['field'].$msg;
            $rule[$ruleKey]=str_replace('unique','unique:crm_contract_receivables',str_replace(',','|',trim($v['rule'],',')));
        }
        if($rule){
            $this->validater($post, $rule);
        }
    }


    public function add()
    {
        $contract_id=$this->request->param('contract_id',0,'intval');
        if($contract_id==0){
            $this->error('提交回款必须选择合同');
        }
        $contract_row=(new \app\admin\model\CrmContract())->field('`id`,`name`,`customer_id`')->withJoin(['crmCustomer' => ['name']], 'LEFT')->where('crm_contract.id',$contract_id)->find();
        if(!$contract_row){
            $this->error('提交回款指定合同不存在');
        }

        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $rule = [
                'customer_id|必须选择对应客户'    => 'require',
                'contract_id|必须选择对应合同'    => 'require',
                'numbering|回款编号'    => 'require|unique:crm_contract_receivables',
                'money|回款金额'    => 'require|number|gt:0',
                'return_time|回款日期'    => 'require',
            ];

            $this->validater($post, $rule);
            $this->verifyFields($post);
            $pr_user=Db::name('crm_customer')->where([['id','=',$post['customer_id']]])->value('pr_user');
            if(empty($pr_user)){
                $this->error('请先设置客户负责人');
            }
            $admin_id=Db::name('admin')->where([['username','=',$pr_user],['is_open','=',1]])->value('admin_id');
            if(empty($admin_id)){
                $this->error('客户负责人不存在');
            }

            try {

                $post=post_convert('crm_contract_receivables',$post);
                $post['check_status'] =0;
                $post['create_username'] =$this->admin['username'];
                $post['owner_admin_id'] =$admin_id;

                $save = $this->model->save($post);

                $audit_management_id=Db::name('audit_management')->insertGetId([
                    'title'=>'回款审核',
                    'mess'=>$this->admin['username'].'添加回款 编号：'.$post['numbering'],
                    'url'=>'crm.contract_receivables/audit?id='.$this->model->id,
                    'createtime'=>time(),
                    'result'=>'To be reviewed',
                    'admin_id'=>$this->admin['admin_id'],
                    'show_auth_group_id'=>actiongroup('crm.contract_receivables/audit')
                ]);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `field`,`addinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract_receivables" AND `addinput` is not null order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['addinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>[]]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);


        $this->assign('contract_row',$contract_row);

        $numbering=$this->model->autoNo($this->system['receivables_format']);
        $this->assign(['numbering'=>$numbering]);
        return $this->fetch();
    }

    public function edit($id)
    {

        $row = $this->model->find($id);
        $this->modifyPermissions($row['owner_admin_id']);
        empty($row) && $this->error(fy('The data does not exist'));
        if($row['check_status']>2){
            $this->error('回款已审核，不能修改');
        }
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            if(isset($post['numbering'])){
                unset($post['numbering']);
            }


            $rule = [
                'customer_id|必须选择对应客户'    => 'require',
                'contract_id|必须选择对应合同'    => 'require',
                'money|回款金额'    => 'require|number|gt:0',
                'return_time|回款日期'    => 'require',
            ];
            $this->validater($post, $rule);
            $this->verifyFields($post);
            Db::startTrans();
            try {
                $post=post_convert('crm_contract_receivables',$post);
                $post['check_status'] =0;

                $save = $row->allowField($this->model->getTableFields())->save($post);
                $total_price=0;



                $audit_management_id=Db::name('audit_management')->insertGetId([
                    'title'=>'回款审核',
                    'mess'=>$this->admin['username'].'编辑回款 编号：'.$row->numbering,
                    'url'=>'crm.contract_receivables/audit?id='.$row->id,
                    'createtime'=>time(),
                    'result'=>'To be reviewed',
                    'admin_id'=>$this->admin['admin_id'],
                    'table_name'=>'crm_contract_receivables',
                    'table_id'=>$row->id,
                    'show_auth_group_id'=>actiongroup('crm.contract_receivables/audit')
                ]);
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

        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`editinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract_receivables" AND `editinput` is not null order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);


        $crmCustomerName=\think\facade\Db::name('crm_customer')->where('id','=',$row['customer_id'])->value('name');
        $crmContractName=\think\facade\Db::name('crm_contract')->where('id','=',$row['contract_id'])->value('name');
        $this->assign('crmCustomerName', $crmCustomerName);
        $this->assign('crmContractName', $crmContractName);
        $this->assign('row', $row);
        return $this->fetch();
    }


    public function create_numbering(){
        $numbering=$this->model->autoNo($this->system['receivables_format']);
        $this->success('生成成功','',['numbering'=>$numbering]);
    }

    public function audit(){
        //        status 1 审核通过  -1审核不通过
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            $this->error('访问参数非法');
        }
        $row = $this->model->withJoin(['crmCustomer' => ['name'],'crmContract' => ['name'], 'ownerAdmin' => ['username']],'LEFT')->where('crm_contract_receivables.id',$id)->find();
        empty($row) && $this->error('需要审核的回款不存在');
        if (trim($row['check_status']) > 2 ) {
            $this->error('该回款已经审核过了');

        }
        if($this->request->isAjax()){
            $status= $this->request->param('status',-1);
            $audit_management_id= $this->request->post('audit_management_id',0);
            if(!$audit_management_id){
                $this->error('参数错误');
            }
            $result_mess= $this->request->post('result_mess','','trim');

//            加入事务处理过程
            Db::startTrans();
            try {
                if ($status > 0) {
//审核通过

                    $sum_return_money=Db::name('crm_contract_receivables')->where('check_status', '=',3)->where('contract_id', '=',$row['contract_id'])->sum('money');
                    Db::name('crm_contract')->where('id', '=', $row['contract_id'])->update(['return_money' =>$sum_return_money]);
                    $result = $this->model->where('id', $id)->update(['check_status' => 3, 'audit_feedback' => $result_mess]);
                    $todoDate = ['result' => 'Approved', 'result_mess' => $result_mess, 'is_finish' => 1];
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
        $row = $this->model->withJoin(['crmCustomer' => ['name'],'crmContract' => ['name'], 'ownerAdmin' => ['username']],'LEFT')->where('crm_contract_receivables.id',$id)->find();

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
        $this->assign('getCheckStatus', $this->model->getCheckStatus());

        return $this->fetch();
    }

    public function delete(){
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $owner_admin_ids = $this->model->whereIn('id', $id)->column('DISTINCT owner_admin_id');
        $this->modifyPermissions($owner_admin_ids);

        $c = $this->model->whereIn('id', $id)->where('check_status','>',1)->count();
        if($c>0){
            $this->error('当前回款不能删除');
        }
        $list = $this->model->whereIn('id', $id)->select();


        $list->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            foreach ($list as $v) {

                $v->delete();
                //如果删除了则没有审核完成的标记为完成
                Db::name('audit_management')->where('table_id',$v->id)->where('is_finish',0)->where('table_name','crm_contract_receivables')->update(['is_finish'=>1]);
            }
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $this->success('删除成功');

    }



}