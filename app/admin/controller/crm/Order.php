<?php
namespace app\admin\controller\crm;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class Order extends AdminController {

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmOrder();

    }

    //订单列表
    public function index(){
        $customer_id=$this->request->param('customer_id',0);
//        判断当前的登录者是否有操作权限
        if($customer_id){
            $owner_admin_id=\think\facade\Db::name('crm_customer')->where('id','=',$customer_id)->value('owner_admin_id');
            if(empty($owner_admin_id)){
                $this->error('当前客户不存在或已移入公海！');
            }
            $this->modifyPermissionsByIds($owner_admin_id);
        }
        if($this->request->isAjax()){
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            if($customer_id){
                $where[]=['customer_id','=',$customer_id];
            }
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
            }else{
//                   限制展示自己的
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
            }
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query("SELECT `name`,`jscol` FROM `{$prefix}system_field` WHERE `list`=1 AND `table`='crm_order' AND `jscol` is not null order BY `sort` ASC,id ASC");
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=$v['jscol'].',';
        }

        $this->app->view->engine()->layout(false);
        $fields_str=$this->display(trim($fields_str,','));

        $this->app->view->engine()->layout($this->layout);
        $fields_str=str_replace(['":"{"','"}"'],['":{"','"}'],$fields_str);

        $this->assignconfig('cols_fields',json_decode('['.$fields_str.']',true));
        $adminResult = Db::name('admin')->where('is_open','=', 1)->field('admin_id,username')->select();
        View::assign('adminResult',$adminResult);

        $this->assignconfig('statusList',$this->model->getStatusList());
        $this->assignconfig(['customer_id'=>$customer_id]);
        return $this->fetch();
    }

    //新建订单号
    public function add(){
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`addinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `form`=1 AND `table`="crm_order" order BY `sort` ASC,id ASC');
        if($this->request->isPost()){

            $data=$this->request->post();
            $data=$this->param_to_str($data);
            $this->verifyFields($data,$fields,'crm_order');

            $data['owner_admin_id']=$this->admin['admin_id'];
            $data['pr_user']=$this->admin['username'];

            $data['create_time']=$data['update_time'] = time();
            $data['status'] = 0;


            Db::startTrans();
            try {
                $data=post_convert($data,$fields,'add');
            $crmClientOrderModel=new \app\admin\model\CrmOrder();
               $customerInfo= \think\facade\Db::name('crm_customer')->field('name,phone')->where('id','=',$data['customer_id'])->find();
               if(empty($customerInfo)){
                   $this->error('Customer does not exist');
               }
                $data['cname'] = $customerInfo['name'];
                $data=array_intersect_key($data, array_flip($crmClientOrderModel->getTableFields()));
            $id=$crmClientOrderModel->insertGetId($data);

            if ($id){
                $audit_management_id=Db::name('audit_management')->insertGetId([
                    'title'=>'Order review',
                    'event'=>'Add Order',
                    'mess'=>$this->admin['username'].'添加订单'.$data['orderno'],
                    'url'=>'crm.order/audit.html?id='.$id,
                    'createtime'=>time(),
                    'result'=>'To be reviewed',
                    'create_username'=>$this->admin['username'],
                    'create_admin_id'=>$this->admin['admin_id'],
                    'table_name'=>'crm_order',
                    'table_id'=>$id,
                    'auditor_group_ids'=>actiongroup('crm.order/audit'),
                ]);
                $this->model->where('id','=',$id)->update(['audit_management_id'=>$audit_management_id]);
                $msg = ['code' => 1,'msg'=>fy('Submitted successfully').'！','data'=>[]];

            }else{
                $msg = ['code' => 1,'msg'=>fy('Submit failed').'！','data'=>[]];

            }
                Db::commit();
            return json($msg);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
        }
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['addinput']);
        }


        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>[]]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);


        $userlist = \think\facade\Db::name('admin')->where('is_open','=', 1)->field('admin_id,username')->select();
        View::assign('userlist',$userlist);
        View::assign('admin',$this->admin);

        $customer_id=$this->request->param('customer_id',0,'intval');
        $row_customer=[];
        if($customer_id){
            $row_customer=\think\facade\Db::name('crm_customer')->field('name,phone,owner_admin_id,contact,area,address')->where('id','=', $customer_id)->find();
            if(!empty($row_customer['owner_admin_id']) && $row_customer['owner_admin_id']>0){
                $this->modifyPermissionsByIds($row_customer['owner_admin_id']);
            }
        }
        $orderno=$this->model->autoNo($this->system['order_format']);
        $this->assign(['orderno'=>$orderno]);
        $this->assign(['row_customer'=>$row_customer]);
        View::assign('customer_id',$customer_id);
        return $this->fetch();
    }

    public function create_orderno(){
        $numbering=$this->model->autoNo($this->system['order_format']);
        $this->success('生成成功','',['numbering'=>$numbering]);
    }
//我的订单编辑
    public function edit($id){
        if(!$id){
            $this->error(fy('Wrong request parameters').'!');
        }
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit`,`editinput`,`formtype` FROM `'.$prefix.'system_field` WHERE `form`=1 AND `table`="crm_order" order BY `sort` ASC,id ASC');
        $result = $this->model->field(array_unique(array_merge(array_column($fields, 'field'), ['id','owner_admin_id','pr_user','status','customer_id','orderno','ccontact','cphone','cname','address','money','freight','remark']))) ->where(['id' => $id])->where('pr_user','=',$this->admin['username'])->find();
        if(empty($result)){
            $this->error(fy('The order does not exist').'！');

        }
        if($result['status']!==-1){
            $this->error(fy('The current order status cannot be edited'));
        }
//
        $this->modifyPermissionsByIds($result['owner_admin_id']);


        if($this->request->isPost()){

            $data=$this->request->post();
            $data=$this->param_to_str($data);
            $this->verifyFields($data,$fields,'crm_order');
            foreach ($fields as $v){
                if($v['edit']){
//                    只读的数据无需保存
                    unset($data[$v['field']]);
                }
            }
            $data['update_time'] = time();
            $data['status'] = 0;
            unset($data['id']);

            $crmClientOrderModel=new \app\admin\model\CrmOrder();
            $data=post_convert($data,$fields);
            $customerInfo= \think\facade\Db::name('crm_customer')->field('name,phone')->where('id','=',$data['customer_id'])->find();
            if(empty($customerInfo)){
                $this->error('Customer does not exist');
            }
            $data['cname'] = $customerInfo['name'];
            $crmClientOrderModel->update($data,['id'=>$id],$crmClientOrderModel->getTableFields());;

            $audit_management_id=Db::name('audit_management')->insertGetId([
                'title'=>'Order review',
                'event'=>'Edit Order',

                'mess'=>$this->admin['username'].'编辑订单'.$data['orderno'],

                'url'=>'crm.order/audit.html?id='.$id,
                'createtime'=>time(),
                'result'=>'To be reviewed',
                'table_name'=>'crm_order',
                'table_id'=>$id,
                'create_username'=>$this->admin['username'],
                'create_admin_id'=>$this->admin['admin_id'],
                'auditor_group_ids'=>actiongroup('crm.order/audit'),
            ]);
            $this->model->where('id','=',$id)->update(['audit_management_id'=>$audit_management_id]);
            $this->success(fy('Edit successfully'));
        }
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$result]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);

        $userlist = \think\facade\Db::name('admin')->where('is_open','=', 1)->field('admin_id,username')->select();
        View::assign('result',$result);
        View::assign('userlist',$userlist);

        View::assign('username',$this->admin['username']);
        return View::fetch('edit');
    }

    public function detail(){
        $id=$this->request->param('id',0,'intval');
        if(!$id){
            $msg = ['code' => -200,'msg'=>fy('Wrong request parameters').'!','data'=>[]];
            return json($msg);
        }
        $result = $this->model ->where(['id' => $id])->find();
        if(empty($result)){
            $msg = ['code' => -200,'msg'=>fy('The order does not exist').'！','data'=>[]];
            return json($msg);
        }
        $this->modifyPermissionsByIds($result['owner_admin_id']);


        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `name`,`editinput` FROM `'.$prefix.'system_field` WHERE `form`=1 AND `table`="crm_order" AND `editinput` is not null order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$result]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);

        $userlist = \think\facade\Db::name('admin')->where('is_open','=', 1)->field('admin_id,username')->select();
        View::assign('result',$result);
        View::assign('userlist',$userlist);

        View::assign('username',$this->admin['username']);
        $this->assign('statusList',$this->model->getStatusList());
        return $this->fetch();
    }
    public function changeyewu(){
        if($this->request->isAjax()){
            $custphone = $this->request->post('cphone','','trim');
            if($custphone==''){
                $this->error(fy('客户手机号必须填写'));
            }
            $where=[];
            $where[] = ['phone','=',$custphone];
            $custinfo = Db::name('crm_customer')->field('*')->where($where)->find();
            if ($custinfo) {
                $res=$custinfo;
                    $res['code'] = 1;
                    $res['custname'] = $custinfo['name'];
                    $res['pr_user'] = $custinfo['pr_user'];
                    $res['contact'] = $custinfo['contact'];
                    $res['msg'] = fy('Client name').":".$custinfo['name'].",".fy('Salesman').":".$custinfo['pr_user'];

            }else{
                $res['code'] = 0;
                $res['msg'] = fy("It's useless to find the client information");
            }

            $this->success($res);
        }
    }


    //删除订单


    //我的订单删除
    public function delete(){
        $id = Request::param('id');
         // 对应的客户修改状态
            $orderinfo = $this->model->field('customer_id')->where('id','=',$id)->where('pr_user','=',$this->admin['username'])->find();
            if(empty($orderinfo)){
                $this->error(fy('The order you want to delete does not exist, or does not belong to you'));
            }
            $customer_id = $orderinfo['customer_id'];
        \app\service\CrmCustomerService::change_success_customer($customer_id);
        $result = \think\facade\Db::name('crm_order')->where('id',$id)->delete();
        //            如果删除了则没有审核完成的标记为完成
        Db::name('audit_management')->where('table_id',$id)->where('is_finish',0)->where('table_name','crm_order')->update(['is_finish'=>1]);
        if ($result){
            $this->success(fy('Delete succeeded'));
        }else{
            $this->error(fy('Delete failed'));

        }

    }

    //    订单审核是否通过
    public function audit(){
        //        status 1 审核通过  -1审核不通过
        $id = Request::param('id',0,'intval');
        if(!$id){
            return json(['code' => 0,'msg'=>fy('Parameter error'),'data'=>[]]);
        }
        if($this->request->isAjax()){
            $status= Request::param('status',-1);
            $audit_management_id= $this->request->post('audit_management_id',0);
            if(!$audit_management_id){
                return json(['code' => 0,'msg'=>fy('Parameter error'),'data'=>[]]);
            }
            $result_mess= $this->request->post('result_mess','','trim');


            $orderinfo = $this->model->where('id',$id)->find();
            if (empty($orderinfo)) {
                $msg = ['code' => 0,'msg'=>fy('订单不存在或已删除'),'data'=>[]];
                return json($msg);
            }
            if (trim($orderinfo['status']) != 0 ) {
                $msg = ['code' => 0,'msg'=>fy('The order has been approved'),'data'=>[]];
                return json($msg);
            }
//            加入事务处理过程
            Db::startTrans();
            try {
                if ($status > 0) {
                    //        status 1 审核通过  -1审核不通过
                    $updatearr = [];
                    $updatearr['issuccess'] = 1;
                    $updatearr['success_time'] = time();
                    Db::name('crm_customer')->where([['id','=',$orderinfo['customer_id']],['issuccess','=',0]])->update($updatearr);
                    $result = $this->model->where('id', $id)->update(['status' => 1, 'audit_feedback' => $result_mess]);
                    $todoDate = ['result' => 'Approved', 'result_mess' => $result_mess, 'is_finish' => 1];
                    $msg = ['code' => 1, 'msg' => fy('Submitted successfully'), 'data' => []];
                } else {
                    if($result_mess==''){
                        throw new \Exception('审核不通过必须填写原因', 0);
                    }
                    $result = $this->model->where('id', $id)->update(['status' => -1, 'audit_feedback' => $result_mess]);
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
        }else{

            $result = $this->model->where(['id' => $id])->find();
            $prefix=getDataBaseConfig('prefix');
            $fields=Db::query('SELECT `name`,`editinput` FROM `'.$prefix.'system_field` WHERE `form`=1 AND `table`="crm_order" AND `editinput` is not null order BY `sort` ASC,id ASC');
            $fields_str='';
            foreach ($fields as $v){
                $fields_str.=trim($v['editinput']);
            }
            $this->app->view->engine()->layout(false);
            $fields_str=$this->display($fields_str,['row'=>$result]);
            $this->app->view->engine()->layout($this->layout);
            $this->assign('fields_str', $fields_str);
            View::assign('result',$result);

            return $this->fetch();
        }
    }

    public function verify_orderno(){
        if($this->request->isPost()){
            $post=$this->request->post();
            if(empty($post['orderno'])){
                $msg = ['code' => -200,'msg'=>'订单号必须填写！','data'=>[]];
                return json($msg);
            }
            $where=[];
            if(!empty($post['id'])){
                $where[]=['id','<>',$post['id']];
            }
            $where[]=['orderno','=',$post['orderno']];
            $userExist = $this->model->field('orderno,pr_user')->where($where)->find();
            if ($userExist){
                $msg=fy('The current order number already exists').'！';
                $msg = ['code' => -200,'msg'=>$msg,'data'=>[]];
            }else{
                $msg = ['code' => 1,'msg'=>fy('Validation succeeded, adding is allowed')];
            }
            return json($msg);
        }
    }



    public function export()
    {
        @ini_set("memory_limit",'-1');
        @ini_set('max_execution_time', '0');
        $customer_id=$this->request->param('customer_id',0);
//        判断当前的登录者是否有操作权限
        if($customer_id){
            $owner_admin_id=\think\facade\Db::name('crm_customer')->where('id','=',$customer_id)->value('owner_admin_id');
            if(empty($owner_admin_id)){
                $this->error('当前客户不存在或已移入公海！');
            }
            $this->modifyPermissionsByIds($owner_admin_id);
        }

        list($page, $limit, $where,$sort) = $this->buildTableParames();
        if($customer_id){
            $where[]=['customer_id','=',$customer_id];
        }
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
        }else{
//                   限制展示自己的
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
        }


//        只导出客户数据非公海数据
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `field`,`name`,`xsname`,`width`,`rule`,`formtype`,`option` FROM `'.$prefix.'system_field` WHERE (`export`=1 OR `list`=1) AND `table`="crm_order" order BY `sort` ASC,id ASC');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $i = 0;
        $str_fields='';
        $arr_fields=[];
        $fields=array_merge($this->model->defaultField(),$fields);
        foreach ($fields as $k => $v) {
            $name=!empty($v['xsname'])?$v['xsname']:$v['name'];
            if ($i >= 26) {
                $cell = chr(65 + $i / 26 - 1) . chr(65 + $i % 26);
            } else {
                $cell = chr(65 + $i);
            }
            $worksheet->getStyle($cell . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('cdf79e');
            $worksheet->getColumnDimension($cell)->setWidth($v['width'],'px');

                $str_fields=$str_fields.',`'.$v['field'].'`';


            $arr_fields[$cell]=$v;
            $worksheet->setCellValue($cell . '1', $name);
            $i++;
        }
        $str_fields=trim($str_fields,',');
        $line=1;

        $cursor=$this->model->field($str_fields)
            ->where($where)
            ->order($sort)->cursor();
        $styleArray = array(
            'font' => array(
                'bold'  => false,
                'color' => array('rgb' => '000000'),
                'size'  => 12,
                'name'  => 'Microsoft Yahei'
            ));
//        循环输出
        foreach ($cursor as $item) {
            $line++;
            foreach ($arr_fields as $cell => $field) {
                $value=$item[$field['field']];
                if($field['field']=='status'){
                    $value=$this->model->getStatusList()[$value]; //订单状态
                }else{
                    $value=real_field_val($field,$value);
                }


                $worksheet->setCellValue($cell . $line, $value.' ');
                $worksheet->getStyle($cell . $line)->getNumberFormat()->setFormatCode('@');
                $worksheet->getCell($cell . $line)->getStyle()->applyFromArray($styleArray);
            }
        }
        ob_end_clean();
        $title = date("YmdHis");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
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
                send_email($param['to_email'], $param['subject'],$param['email_content'],true,1,$param['type']);
            } catch (\Exception $e) {
                $this->error('邮件发送失败：' . $e->getMessage());
            }
            $this->success('邮件发送成功');
        }else{
//            emailtpl
            $emailtpl=\think\facade\Db::name('emailtpl')->field('id,name')->order('sort asc')->where('type','in',['customer','client_order'])->select();
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
