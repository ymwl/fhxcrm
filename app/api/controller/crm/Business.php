<?php

namespace app\api\controller\crm;

use app\api\controller\Authority;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="business")
 */
class Business extends Authority
{
    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmBusiness();

        $this->assign('getIsEndList', $this->model->getIsEndList());

    }
    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        $customer_id = $this->request->param('customer_id', 0);

        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where) = $this->buildTableParames();
        $scope = $this->request->get('scope', 1, 'intval');
        if ($customer_id) {
            $where[] = ['crm_business.customer_id', '=', $customer_id];
        } else {
            if ($scope == 2) {
                $adminIds = (new \app\admin\model\Admin())->getViewAdminIds($this->admin);
                if (empty($adminIds)) {
                    return json(['code' => 1, 'msg' => '', 'data' => ['rows' => [], 'count' => 0]]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['crm_business.owner_admin_id', 'in', $adminIds];
                } elseif ($adminIds == 'ALL') {
                    $where[] = ['crm_business.owner_admin_id', '<>', $this->admin['admin_id']];
                }
            } elseif ($scope == 3) {
                $adminIds = (new \app\admin\model\Admin())->getViewAdminIds($this->admin, true);
                if (empty($adminIds)) {
                    return json(['code' => 1, 'msg' => '', 'data' => ['rows' => [], 'count' => 0]]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['crm_business.owner_admin_id', 'in', $adminIds];
                }
            } else {
                $where[] = ['crm_business.owner_admin_id', '=', $this->admin['admin_id']];
            }
        }

        $count = $this->model->withJoin(['crmCustomer' => ['name'], 'ownerAdmin' => ['username']], 'LEFT')->where($where)->count();
        $list = [];
        if ($count) {
            $list = $this->model
                ->withJoin(['crmCustomer' => ['name'], 'ownerAdmin' => ['username']], 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
        }

        return json([
            'code' => 1,
            'msg'  => '',
            'data' => ['rows' => $list, 'count' => $count],
        ]);
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                'customer_id|必须选择对应客户'    => 'require',
                'name|'.fy("Opportunity Name") => 'require|unique:crm_business'
            ];

            $this->validater($post['business'], $rule);
            $count=Db::name('crm_customer')->where([['id','=',$post['business']['customer_id']],['pr_user','=',$this->admin['username']]])->count('id');
            if(!$count){
                $this->error('商机只能选择自己的客户');
            }
            Db::startTrans();
            try {
                $post['business']['next_time']=$post['business']['next_time']?strtotime($post['business']['next_time']):null;
                $post['business']['deal_time']=$post['business']['deal_time']?strtotime($post['business']['deal_time']):null;
                $post['business']['create_username']=$this->admin['username'];
                $post['business']['owner_admin_id']=$this->admin['admin_id'];
                $post['business']['update_time']=$post['business']['create_time']=time();
                $save = $this->model->allowField($this->model->getTableFields())->save($post['business']);  //$post['product']
                $total_price=0;
                if(!empty($post['product'])){
                    foreach ($post['product'] as &$row) {
                        unset($row['id']);
                        $row['product_extend']=json_encode($row['product_extend'],256);
                        $row['business_id'] =$this->model->id;
//                        $total_price=$total_price+$row['sale_price']*$row['nums']-$row['discount'];
                        $total_price=bcadd($total_price,bcsub(bcmul($row['sale_price'],$row['nums'],2),$row['discount'],2),2);
                    }
                    Db::name('crm_business_product')->insertAll($post['product']);
                }
                $this->model->save(['total_price'=>$total_price]);


            } catch (\Exception $e) {
                Db::rollback();
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            if($save ){
                Db::commit();
                $this->success(fy('Save successfully')) ;
            }else{
                Db::rollback();
                $this->error(fy('Save failed'));
            }

        }
        
    }

    /**
     * 编辑
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        $this->modifyPermissions($row['owner_admin_id']);
        empty($row) && $this->error(fy('The data does not exist'));
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if($row['is_end']>0){
                $this->error('当前商机状态不是洽淡中，无法修改商机信息');
            }
            $rule = [
                'customer_id|必须选择对应客户'    => 'require',
                'name|'.fy("Opportunity Name") => 'require|unique:crm_business',
            ];

            $this->validater($post['business']+['id'=>$id], $rule);
            $count=Db::name('crm_customer')->where([['id','=',$post['business']['customer_id']],['pr_user','=',$this->admin['username']]])->count('id');
            if(!$count){
                $this->error('商机只能选择自己的客户');
            }
            Db::startTrans();
            try {
                $post['business']['next_time']=$post['business']['next_time']?strtotime($post['business']['next_time']):null;
                $post['business']['deal_time']=$post['business']['deal_time']?strtotime($post['business']['deal_time']):null;
                $post['business']['update_time']=time();

                $total_price=0;
                if(!empty($post['product'])){
                    foreach ($post['product'] as &$row) {
                        if(!$row['id'])unset($row['id']);
                        $row['product_extend']=json_encode($row['product_extend'],256);
                        $row['business_id'] =$id;
                        $total_price=$total_price+$row['sale_price']*$row['nums']-$row['discount'];
                    }
                    (new \app\admin\model\CrmBusinessProduct())->saveAll($post['product']);
                }
                $post['business']['total_price']=$total_price;
                $save = $this->model->allowField($this->model->getTableFields())->where('id','=',$id)->update($post['business']);


            } catch (\Exception $e) {
                Db::rollback();
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            if($save ){
                Db::commit();
                $this->success(fy('Save successfully')) ;
            }else{
                Db::rollback();
                $this->error(fy('Save failed'));
            }

        }
        $this->assign('row', $row);
        
    }

    public function product_by_business()
    {
        $business_id = $this->request->get('business_id');
        $business_row = $this->model->withJoin(['ownerAdmin' => ['username']])->find($business_id);
        $this->modifyPermissions($business_row['owner_admin_id']);
        if ($business_id) {
            $info = Db::name('crm_business_product')->where('business_id', '=', $business_id)->order('create_time ASC')->select()->toArray();
            foreach ($info as &$row) {
                $row = $row + json_decode($row['product_extend'], true);
                unset($row['product_extend']);
            }
            $this->success(fy('Get successful'), '', ['business' => $business_row, 'product' => $info]);
        }
        $this->error('参数错误');
    }

    public function delproduct_by_business()
    {
        $business_product_id = $this->request->get('business_product_id');
        if ($business_product_id) {
            $res = Db::name('crm_business_product')->where('id', '=', $business_product_id)->delete();
            if ($res) {
                $this->success(fy('Delete succeeded'));
            } else {
                $this->error(fy('Delete failed'));
            }
        }
        $this->error('参数错误');
    }

    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        foreach ($row as $v){
            $this->modifyPermissions($v['owner_admin_id']);
        }

        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $save = $row->delete();
            Db::name('crm_business_product')->whereIn('business_id', $id)->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    
}