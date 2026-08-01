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
            
            list($page, $limit, $where,$sort) = $this->buildTableParames();

            $scope=$this->request->get('scope','1','trim');
            $business_id=$this->request->get('business_id',0,'intval');
            if($scope==2){
//                    展示下属的
                $adminIds=\app\service\AdminService::getViewAdminIds($this->admin);
                if(empty($adminIds)){
                    return json([
                        'code'  => 1,
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
                $adminIds=\app\service\AdminService::getViewAdminIds($this->admin,true);
                if(empty($adminIds)){
                    return json([
                        'code'  => 1,
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

        $prefix=getDataBaseConfig('prefix');
        $fields=\think\facade\Db::query('SELECT `editinput`,`field`,`formtype` FROM `'.$prefix.'system_field` WHERE (`form`=1 AND `table`="crm_customer" AND `editinput` is not null AND  `field`<>"id") order BY `sort` ASC,id ASC');
        $result = \think\facade\Db::name('crm_customer')->field(array_column($fields, 'field'))->where(['id'=>$customer_id])->find();
        $input_str='';
        foreach ($fields as $value){
            $input_str.=trim($value['editinput']);
            if ($this->admin['isphone'] == 0 && $value['formtype']=='tel') {
                $result[$value['field']] = mb_substr($result[$value['field']], 0, 3).'****'. mb_substr($result[$value['field']], 7, 11);
            }
        }
        $this->app->view->engine()->layout(false);
//        $fields_str=str_replace('layui-form-item','layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3',$fields_str);
        $input_str=str_replace(['>已成交<','>未成交<'],['>'.fy('已成交').'<','>'.fy('未成交').'<'],$input_str);
        if($result){
            $input_str=$this->display($input_str,['row'=>$result]);
		}else{
            $input_str='未指定客户，或客户信息已经删除';
		}
        $this->assign('fields_str', $input_str);
        if(empty($business_id)){
            $this->error(fy("Wrong request parameters"));
        }
        $this->assign('business_id',$business_id);
        $this->app->view->engine()->layout($this->layout);
        return $this->fetch();
    }


    public function delete()
    {
        $id = parseIds();
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        foreach ($row as $v){
            $this->modifyPermissionsByIds($v['create_admin_id']);
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