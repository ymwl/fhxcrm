<?php

namespace app\api\controller\crm;

use app\api\controller\Authority;
use app\service\ContractStatusConst;
use app\service\CheckStatusConst;

use think\App;
use think\facade\Db;


class ContractReceivablesPlan extends Authority
{

    protected $relationSearch = true;
    /**
     * 验证客户和合同的有效性
     * @param int $customerId 客户ID
     * @param int $contractId 合同ID
     * @param float $planMoney 计划回款金额
     * @return array|bool 成功返回合同信息，失败返回错误信息
     */
    private function validateCustomerAndContract($customerId, $contractId, $planMoney, $excludePlanId = null)
    {
        // 1. 验证客户存在性
        $customer = Db::name('crm_customer')
            ->where('id', $customerId)
            ->where('status', 1)
            ->field('id, name, pr_user, owner_admin_id')
            ->find();
        
        if (!$customer) {
            return ['error' => '客户不存在或已被删除'];
        }

        // 2. 验证合同存在性
        $contract = Db::name('crm_contract')
            ->where('id', $contractId)
            ->field('id, name, numbering, money,return_money, check_status,contract_status, owner_admin_id, customer_id')
            ->find();
        
        if (!$contract) {
            return ['error' => '合同不存在或已被删除'];
        }

        // 3. 验证合同关联的客户是否匹配
        if ($contract['customer_id'] != $customerId) {
            return ['error' => '该合同不属于所选客户'];
        }

        // 4. 验证合同状态（仅"进行中"状态可操作回款计划）
        if ($contract['contract_status'] !=0) {
            return ['error' => '该合同'.(\app\service\CrmContractService::getContractStatus($contract['contract_status'])).'，无法操作回款计划'];
        }

        // 5. 验证合同审核状态（需审核通过）
        if ($contract['check_status'] != 3) {
            $statusText = \app\service\CrmContractService::getCheckStatus($contract['check_status']);
            return ['error' => "该合同状态为【{$statusText}】，无法操作回款计划"];
        }

        // 6. 权限验证（负责人、上级或特定角色）
        $this->modifyPermissions($contract['owner_admin_id']);



        // 7. 验证计划金额（计划总金额 + 本次金额 <= 合同金额）
        //    编辑时排除当前记录，避免和自己比较
        $query = $this->model->where('contract_id', $contractId);
        if ($excludePlanId !== null) {
            $query->where('id', '<>', $excludePlanId);
        }
        $existingPlanTotal = $query->sum('plan_money') ?: 0;
        
        $newTotal = $existingPlanTotal + $planMoney;


        if ($newTotal > $contract['money']) {
            $remaining = $contract['money'] - $existingPlanTotal;
            return ['error' => "计划回款总额超出合同金额。合同金额：{$contract['money']}元，已计划：{$existingPlanTotal}元，剩余可计划：{$remaining}元"];
        }

        // 8. 检查合同是否已结清（仅新增时判断，编辑时不限制）
        if ($excludePlanId === null && $existingPlanTotal >= $contract['money']) {
            return ['error' => '该合同回款计划已全部完成，无需再添加'];
        }
//        增加剩余回款金额


        return ['contract' => $contract, 'customer' => $customer];
    }

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmContractReceivablesPlan();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {

        $fields=cache('crm_contract_receivables_plan_fields');
        if(!$fields){
            $prefix=getDataBaseConfig('prefix');
            $fields=Db::query("SELECT  `field`, `jscol`,`show` FROM `{$prefix}system_field` WHERE `table`='crm_contract_receivables_plan' AND `show`=1 AND `jscol` is not null order BY `sort` ASC,id ASC");
            $field_str=$jscol_str='';
            foreach ($fields as $key=>$value){
                $field_str.=$value['field'].',';
                if($value['show']==1){
                    $jscol_str.=$value['jscol'].',';
                }
            }
            $fields=['field_str'=>trim($field_str,','),'jscol_str'=>trim($jscol_str,',')];
            cache('crm_contract_receivables_plan_fields',$fields);
        }

        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where,$sort) = $this->buildTableParames();
            $scope = $this->request->get('scope',1,'intval');
            if($scope==2){
//                    展示下属的
                $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin);
                if(empty($adminIds)){
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'data'  => ['rows'=>[], 'count'=>0],
                    ]);
                }
                if($adminIds!=='ALL'){
                    $where[] = ['crm_contract_receivables_plan.owner_admin_id', 'in',$adminIds];
                }elseif($adminIds=='ALL'){
//                    展示其他的  不包括自己需要做排除
                    $where[] = ['crm_contract_receivables_plan.owner_admin_id', '<>',$this->admin['admin_id']];
                }

            }elseif($scope==3){
//                    展示下属的和自己的
                $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin,true);
                if(empty($adminIds)){
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'data'  => ['rows'=>[], 'count'=>0],
                    ]);
                }
                if($adminIds!=='ALL'){
                    $where[] = ['crm_contract_receivables_plan.owner_admin_id', 'in',$adminIds];
                }
            }else{
//                    展示自己的
                $where[] = ['crm_contract_receivables_plan.owner_admin_id', '=', $this->admin['admin_id']];
            }

//            返回sql语句的写法
        $count = $this->model->withJoin([
            'crmCustomer' => ['name'],
            'crmContract' => ['name'],
            'ownerAdmin' => ['username']], 'LEFT')->where($where)->count();
        $list=[];
        $overdueMoney = 0;
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


            $list = $this->model->field($field_str)->withJoin([
                'crmCustomer' => ['name'],
                'crmContract' => ['name'],
                'ownerAdmin' => ['username']], 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($sort)
                ->select();

            //                计算逾期未回款金额总和（plan_date已到期且status为0或1的记录）
            $overdueWhere = [];
            foreach ($where as $condition) {
                if (is_array($condition) && isset($condition[0]) && is_string($condition[0])) {
                    $field = $condition[0];
                    // 排除status字段的过滤条件（不含check_status），确保逾期统计不受用户筛选影响
                    if (stripos($field, 'status') !== false && stripos($field, 'check_status') === false) {
                        continue;
                    }
                }
                $overdueWhere[] = $condition;
            }
            $overdueWhere[] = ['crm_contract_receivables_plan.plan_date', '<=', time()];
            $overdueWhere[] = ['crm_contract_receivables_plan.status', 'in', [0, 1]];
            $overdueMoney = $this->model->withJoin([
                'crmCustomer' => ['name'],
                'crmContract' => ['name'],
                'ownerAdmin' => ['username']], 'LEFT')->where($overdueWhere)->sum('plan_money') ?: 0;
        }

        $data = [
            'code'  => 1,
            'msg'   => '',
            'data'  => ['rows'=>$list, 'count'=>$count],
            'overdue_money' => $overdueMoney,
        ];
        return json($data);
    }

    public function add()
    {

        $prefix=getDataBaseConfig('prefix');
        // 排除实际金额、实际日期、状态、计划编号字段（计划编号自动生成）
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`formtype`,`addinput` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract_receivables_plan" AND field NOT IN("actual_money","actual_date","status","plan_no")  order BY `sort` ASC,id ASC');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $this->verifyFields($post,$fields,'crm_contract_receivables_plan');
            
            // 验证必填参数
            if (empty($post['customer_id'])) {
                $this->error('请选择关联客户');
            }
            if (empty($post['contract_id'])) {
                $this->error('请选择关联合同');
            }
            if (empty($post['plan_money']) || $post['plan_money'] <= 0) {
                $this->error('请输入有效的计划回款金额');
            }
            
            // 验证客户、合同、权限和金额
            $validateResult = $this->validateCustomerAndContract(
                $post['customer_id'], 
                $post['contract_id'], 
                $post['plan_money']
            );
            
            if (isset($validateResult['error'])) {
                $this->error($validateResult['error']);
            }
            
            $contract = $validateResult['contract'];
            

                $post=post_convert($post,$fields);
                // 自动生成计划编号：合同编号-序号
                $post['plan_no'] = $this->model->generatePlanNo($post['contract_id']);
                $post['create_username']=$this->admin['username'];
                //谁的客户归谁管理 - 使用合同的负责人
                $post['owner_admin_id']=$contract['owner_admin_id'];
                $post['create_time']= $post['update_time']=time();

                $save = $this->model->save($post);

            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $fields_str='';
        $contract_id=$this->request->param('contract_id',0,'intval');
        $contract_row=[];
        if($contract_id){
            $contract_row=(new \app\common\model\CrmContract())->field('`id`,`name`,`customer_id`,`owner_admin_id`')->withJoin(['crmCustomer' => ['name','pr_user']], 'LEFT')->where('crm_contract.id',$contract_id)->find();
            $this->modifyPermissions($contract_row['owner_admin_id']);
        }
        foreach ($fields as $v){
            if($v['field']=='customer_id' && $contract_row){
                $fields_str.='<div class="layui-col-xs12 layui-col-sm12 layui-col-md6 layui-col-lg6">
        <div class="layui-form-item">
            <label class="layui-form-label">客户名称</label>
            <div class="layui-input-block">
                <input type="hidden" name="customer_id" value="'.$contract_row['customer_id'].'">
                <input type="text" class="layui-input" lay-verify="required" value="'.$contract_row['crmCustomer']['name'].'" readonly>
            </div>
        </div>
        </div>';
            }elseif($v['field']=='contract_id' && $contract_row){
                $fields_str.='<div class="layui-col-xs12 layui-col-sm12 layui-col-md6 layui-col-lg6">
        <div class="layui-form-item">
            <label class="layui-form-label">合同名称</label>
            <div class="layui-input-block">
                <input type="hidden" name="contract_id" value="'.$contract_row['id'].'">
                <input type="text" class="layui-input" lay-verify="required" value="'.$contract_row['name'].'" readonly>
            </div>
        </div>
        </div>';
            }else{
                $fields_str.=trim($v['addinput']);
            }

        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>[]]);
        $this->app->view->engine()->layout($this->layout);





//        $this->assign('contract_row', $contract_row);
        $this->assign('fields_str', $fields_str);
        
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
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit_readonly`,`editinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract_receivables_plan" order BY `sort` ASC,id ASC');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post=$this->param_to_str($post);
            $this->verifyFields($post,$fields,'crm_contract_receivables_plan');
            foreach ($fields as $v){
                if($v['edit_readonly']){
//                    只读的数据无需保存
                    unset($post[$v['field']]);
                }
            }
            $post=post_convert($post,$fields);
            if(isset($post['customer_id']))unset($post['customer_id']);
            if(isset($post['contract_id']))unset($post['contract_id']);
//            plan_no、status、create_time、create_username
            if(isset($post['plan_no']))unset($post['plan_no']);
            if(isset($post['status']))unset($post['status']);
            if(isset($post['create_time']))unset($post['create_time']);
            if(isset($post['create_username']))unset($post['create_username']);

            // 业务验证：复用 validateCustomerAndContract，排除当前记录
            $planMoney = isset($post['plan_money']) ? $post['plan_money'] : $row['plan_money'];
            $validateResult = $this->validateCustomerAndContract(
                $row['customer_id'],
                $row['contract_id'],
                $planMoney,
                $row['id']
            );
            if (isset($validateResult['error'])) {
                $this->error($validateResult['error']);
            }

            // 验证实际金额不超过计划金额
            /*if (!empty($post['actual_money']) && $post['actual_money'] > $planMoney) {
                $this->error('实际回款金额不能超过计划金额');
            }*/

            // 验证实际日期不早于计划日期
            /*$planDate = isset($post['plan_date']) ? $post['plan_date'] : $row['plan_date'];
            if (!empty($post['actual_date']) && !empty($planDate) && $post['actual_date'] < $planDate) {
                $this->error('实际回款日期不能早于计划回款日期');
            }*/

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
        
    }

    
}