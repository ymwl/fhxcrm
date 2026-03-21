<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use think\App;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

/**
 * @ControllerAnnotation(title="crm_record")
 */
class Record extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\CrmRecord();

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
            $scope=$this->request->get('scope', 1,'intval');
            $customer_id=$this->request->get('customer_id', 0,'intval');
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
                    $where[] = ['admin_id', 'in',$adminIds];
                }elseif($adminIds=='ALL'){
//                    展示其他的  不包括自己需要做排除
                    $where[] = ['admin_id', '<>',$this->admin['admin_id']];
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
                    $where[] = ['admin_id', 'in',$adminIds];
                }
            }else{
//                   限制展示自己的
                $where[] = ['admin_id', '=', $this->admin['admin_id']];
            }
            if($customer_id){
                $where[] = ['customer_id', '=', $customer_id];
            }
            $count = $this->model
                ->where($where)
                ->count();
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
        $typeList=Db::name('crm_record_type')->field('`name`')->where('status','=',1)->order('sort ASC')->column('name','name');
        $this->assignconfig('record_type',$typeList);
        return $this->fetch();
    }

    //写跟进
    public function dialogue(){
        $id=Request::param('id',0,'intval');
        $result = (new \app\admin\model\CrmCustomer())->where(['id'=>$id])->find();
        if (empty($result)){
            $this->error(fy("Non-existent client information"));

        }
        /*
        // 是否有查看手机号的权限
        if ($this->admin['isphone'] == 0) {
            $result['phone'] = mb_substr($result['phone'], 0, 3).'****'. mb_substr($result['phone'], 7, 11);
        }*/
        $prefix=getDataBaseConfig('prefix');
        $fields=Db::query('SELECT `editinput` FROM `'.$prefix.'system_field` WHERE (`edit`=1 AND `table`="crm_customer" AND `editinput` is not null) OR `field`="pr_user" OR `field`="at_user" OR `field`="last_up_time" OR `field`="last_up_records" OR `field`="at_user" OR `field`="create_time" order BY `sort` ASC,id ASC');
        $fields_str='';
        foreach ($fields as $v){
            $fields_str.=trim($v['editinput']);
        }
        $fields_str=str_replace(['>已成交<','>未成交<'],['>'.fy('已成交').'<','>'.fy('未成交').'<'],$fields_str);
        $this->app->view->engine()->layout(false);
        $fields_str=$this->display($fields_str,['row'=>$result]);
        $this->assign('fields_str', $fields_str);
        $this->assign('result',$result);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('next_url', $this->get_next_url($id));
        return $this->fetch();
    }

    protected function get_next_url($id){
        list($page, $limit, $where,$sort) = $this->buildTableParames();
        $scope=$this->request->get('scope', 1,'intval');

        if($scope==2){
//                    展示其他的  不包括自己
            $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin);
            if(empty($adminName)){
                return '';
            }
            if($adminName!=='ALL'){
                $where[] = ['pr_user', 'in',$adminName];
            }elseif($adminName=='ALL'){
//                    展示其他的  不包括自己需要做排除
                $where[] = ['pr_user', '<>',$this->admin['username']];
            }

        }elseif($scope==3){
//                    展示全部 包括自己
            $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin,true);
            if(empty($adminName)){
                return '';
            }
            if($adminName!=='ALL'){
                $where[] = ['pr_user', 'in',$adminName];
            }
        }elseif($scope==10){
// 待跟进
            $where[] = ['next_time', '>', 0];
            $where[] = ['next_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
            $where[] = ['pr_user', '=', $this->admin['username']];

        }elseif($scope==11){
// 今天已跟进
            $where[] = ['last_up_time', '>=', strtotime('today')];
            $where[] = ['last_up_time', '<', strtotime('tomorrow')];
// 待跟进限制展示自己的
            $where[] = ['pr_user', '=', $this->admin['username']];

        }elseif($scope==12){
// 从未跟进
            $where[] = ['last_up_time', '=', 0];
// 限制展示自己的
            $where[] = ['pr_user', '=', $this->admin['username']];

        }elseif($scope==20){

            //            展示自己分享给他人的
            $where[] = ['pr_user', '=', $this->admin['username']];
            $where[] = ['share_admin_ids', '<>', ''];

        }elseif($scope==21){
            //                    展示分享给我的
            $where[]=['','exp',\think\facade\Db::raw("FIND_IN_SET('{$this->admin['admin_id']}',share_admin_ids)")];
        }else{
//                   限制展示自己的
            $where[] = ['pr_user', '=', $this->admin['username']];
        }

        $where[]=['status','=',1];


        $list = Db::name('crm_customer')
            ->where($where)
            ->page($page, $limit)
            ->order($sort)
            ->column('id');
        //                为方便获取下一条数据
       $key= array_search($id,$list);
       if($key===false){
           return '';
       }else{
           $nextlist = Db::name('crm_customer')
               ->where($where)->where('id','<>',$list[0])
               ->page($page, $limit)
               ->order($sort)
               ->column('id');
           $next_id=$nextlist[$key]??'';
           if(empty($next_id)){
               return '';
           }
           if($key==count($list)-1){
               $page=$page+1;
           }
//http://crm.laikephp.cn/admin1.php/crm.record/dialogue?id=14&filter=%7B%7D&op=%7B%7D&scope=1
           $get = $this->request->get('', null, null);
           $get['page']=$page;
           $get['id']=$next_id;
           return myurl('dialogue').'?'.http_build_query($get);
       }







    }

    public function add(){
        if($this->request->post()){
            $data['customer_id'] = Request::param('customer_id');
            if(empty($data['customer_id'])){
                return json(['code'=>0,'msg'=>fy("Wrong request parameters")]);
            }
            $customer_row=Db::name('crm_customer')->field('`name`,`phone`')->where(['id'=>$data['customer_id']])->find();
            if(empty($customer_row)){
                return json(['code'=>0,'msg'=>fy("Non-existent client information")]);
            }
            $data['admin_id'] = $this->admin['admin_id'];
            $data['khname'] = $customer_row['name'];
            $data['khphone'] = $customer_row['phone'];
            $data['pr_user'] = $this->admin['username'];
            $data['content'] = Request::param('content');
            $data['attachs'] = Request::param('attachs');
            $data['record_type'] = Request::param('record_type','','trim');
            $data['create_time'] = time();

            //更新跟进记录
            $genjin['last_up_records'] = $data['content'];

            $genjin['last_up_time'] = $data['create_time'];
            $next_time = Request::param('next_time');
            $data['next_time']=$genjin['next_time'] = $next_time?strtotime($next_time):0;
            Db::name('crm_customer')->where(['id'=>$data['customer_id']])->update($genjin);

            $result = Db::name('crm_record')->insert($data);
            $data['create_time'] = date("Y-m-d H:i",$data['create_time']);

            if ($result){
                return json(['code'=> 1,'msg'=>fy("Submitted successfully"),'data'=>$data]);
            }else{
                return json(['code'=>0,'msg'=>fy("Submit failed")]);
            }
        }
    }

    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        foreach ($row as $v){
            $this->modifyPermissions($v['admin_id']);
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
