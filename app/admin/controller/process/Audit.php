<?php
namespace app\admin\controller\process;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;
use app\admin\controller\Common;
class Audit extends Common
{
    public $model;
    public function initialize(){
        parent::initialize();
        $this->model=\think\facade\Db::name('audit_management');

    }
    //信息提醒
    public function index(){
        if($this->request->isPost()){
            $group_id=Db::name('admin')->where('admin_id',$this->admin['admin_id'])->cache('group_id_'.$this->admin['admin_id'])->value('group_id');
            $todoModel=$this->model->fieldRaw("t.`id`,t.event,t.`title`,t.`mess`,t.`url`,t.`editurl`,t.`createtime`,t.`result`,t.`result_mess`,t.`admin_id`,CONCAT(',',t.`show_auth_group_id`,',') show_auth_group_id,CONCAT(',',t.`show_auth_admin_id`,',') show_auth_admin_id,t.`is_finish`,t.`audittime`,t.`reviewer`,a.username")->alias('t')->join('admin a','t.admin_id=a.admin_id','left');
            if($group_id){
                $todoModel= $todoModel->whereRaw('t.`admin_id` = :aid OR FIND_IN_SET(:group_id,t.`show_auth_group_id`) OR FIND_IN_SET(:admin_id,t.`show_auth_admin_id`)',['aid'=>$this->admin['admin_id'],'group_id'=>$group_id,'admin_id'=>$this->admin['admin_id']]);
            }


            $keyword = $this->request->post('keyword');
            if(!empty($keyword['title'])){
                $todoModel=$todoModel->where('t.title','=',$keyword['title']);
            }
            if(!empty($keyword['result'])){
                $todoModel=$todoModel->where('t.result','=',$keyword['result']);
            }

            $page =input('page',1,'intval');
            $pageSize =input('limit',config('app.pageSize'),'intval');
            $list = $todoModel->order('t.createtime desc')
                ->paginate(['list_rows'=>$pageSize,'page'=>$page])
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['title']=fy($v['title']);
                $list['data'][$k]['event']=fy($v['event']);
                $list['data'][$k]['result']=fy($v['result']);
                if(empty($list['data'][$k]['mess'])){
                    $list['data'][$k]['mess']=$v['username'].' '.$list['data'][$k]['event'];
                }


            }
            return json(['code'=>0,'msg'=>fy('Get successful').'!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1]);
        }

        return View::fetch();
    }
    public function del(){
        if($this->request->isAjax()){
            $id = $this->request->post('id','','trim');
            if(!$id){
                $msg = ['code' => -200,'msg'=>fy('Wrong request parameters').'!','data'=>[]];
                return json($msg);
            }
            $where=[];
            if(is_string($id) && !empty($id)){
                $where=[['id','=',$id]];
            }elseif(is_array($id)){
                $where=[['id','in',$id]];
            }else{
                $msg = ['code' => -200,'msg'=>fy('Wrong request parameters').'!','data'=>[]];
                return json($msg);
            }
            $result = $this->model->where($where)->delete();
            if ($result){
                $msg = ['code' => 0,'msg'=>fy('Delete succeeded').'！','data'=>[]];
                return json($msg);
            }else{
                $msg = ['code' => -200,'msg'=>fy('Delete failed').'！','data'=>[]];
                return json($msg);
            }
        }

    }
}