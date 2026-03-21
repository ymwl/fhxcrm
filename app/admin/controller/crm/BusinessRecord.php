<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;

/**
 * @ControllerAnnotation(title="business_record")
 */
class BusinessRecord extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmBusinessRecord();
        
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            $scope=$this->request->get('scope',1,'intval');
            $business_id=$this->request->get('business_id',0,'intval');
            if($scope==2){
//                    展示下属的
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
                    $where[] = ['create_admin_id', 'in',$adminIds];
                }elseif($adminIds=='ALL'){
//                    展示其他的  不包括自己需要做排除
                    $where[] = ['create_admin_id', '<>',$this->admin['admin_id']];
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
                    $where[] = ['create_admin_id', 'in',$adminIds];
                }

            }else{
//                    展示自己的
                $where[] = ['create_admin_id', '=', $this->admin['admin_id']];
            }
            if($business_id){
                $where[] = ['business_id', '=', $business_id];
            }
            $count = $this->model->where($where)->count();
            $list=[];
            if($count){
                $list = $this->model
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
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if(empty($post['business_id'])){
                return json(['code'=>0,'msg'=>fy("Wrong request parameters")]);
            }
            if(empty($post['content'])){
                return json(['code'=>0,'msg'=>fy("Follow-up content is required")]);
            }
            $post['create_admin_id'] = $this->admin['admin_id'];
            $post['create_username'] = $this->admin['username'];
            $post['create_time'] = time();
            try {
                $genjin['next_time'] =$post['next_time']= $post['next_time']?strtotime($post['next_time']):null;
                $post['business_name']=\think\facade\Db::name('crm_business')->where(['id'=>$post['business_id']])->value('name');
                $save = $this->model->allowField($this->model->getTableFields())->save($post);
                //更新跟进记录
                $genjin['last_up_records'] = $post['content'];
                $genjin['last_up_time'] = time();


                \think\facade\Db::name('crm_business')->where(['id'=>$post['business_id']])->update($genjin);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        $business_id=$this->request->get('business_id',0,'intval');
        $customer_id=$this->request->get('customer_id',0,'intval');
        $result = \think\facade\Db::name('crm_customer')->where(['id'=>$customer_id])->find();
        if ($this->admin['isphone'] == 0) {
            $result['phone'] = mb_substr($result['phone'], 0, 3).'****'. mb_substr($result['phone'], 7, 11);
        }
        $prefix=getDataBaseConfig('prefix');
        $fields=\think\facade\Db::query('SELECT `editinput` FROM `'.$prefix.'system_field` WHERE (`edit`=1 AND `table`="crm_customer" AND `editinput` is not null AND  `field`<>"id") order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $this->app->view->engine()->layout(false);
//        $fields_str=str_replace('layui-form-item','layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3',$fields_str);
        $fields_str=str_replace(['>已成交<','>未成交<'],['>'.fy('已成交').'<','>'.fy('未成交').'<'],$fields_str);
        if($result){
			$fields_str=$this->display($fields_str,['row'=>$result]);
		}else{
			$fields_str='未指定客户，或客户信息已经删除';
		}
        $this->assign('fields_str', $fields_str);
        if(empty($business_id)){
            $this->error(fy("Wrong request parameters"));
        }
        $this->assign('business_id',$business_id);
        $this->app->view->engine()->layout($this->layout);
        return $this->fetch();
    }


    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        foreach ($row as $v){
            $this->modifyPermissions($v['create_admin_id']);
        }

        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    
}