<?php
namespace app\admin\controller\process;
use app\common\controller\AdminController;
use think\facade\Db;
use think\facade\View;
class Audit extends AdminController
{
    public $model;
    public function initialize(){
        parent::initialize();
        $this->model=new \app\common\model\AuditManagement();

    }
    //信息提醒
    public function index(){
        if($this->request->isAjax()){
            $group_id=$this->admin['group_id'];
            $todoModel=$this->model->fieldRaw("`id`,event,`title`,`mess`,`url`,`editurl`,`createtime`,`result`,`result_mess`,CONCAT(',',`auditor_group_ids`,',') auditor_group_ids,CONCAT(',',`auditor_admin_ids`,',') auditor_admin_ids,`is_finish`,`audittime`,`reviewer`,create_username");
            if($this->admin['group_id']>1){
                $todoModel= $todoModel->whereRaw('`create_admin_id` = :aid OR FIND_IN_SET(:group_id,`auditor_group_ids`) OR FIND_IN_SET(:admin_id,`auditor_admin_ids`)',['aid'=>$this->admin['admin_id'],'group_id'=>$group_id,'admin_id'=>$this->admin['admin_id']]);
            }


            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $scope=$this->request->param('scope','all');
            if($scope=='audit'){
                $where[]=['is_finish','=',0];
            }

            $list = $todoModel->where($where)->order($sort)
                ->paginate(['list_rows'=>$limit,'page'=>$page])
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['title']=fy($v['title']);
                $list['data'][$k]['event']=fy($v['event']);
                $list['data'][$k]['result']=fy($v['result']);
                if(empty($list['data'][$k]['mess'])){
                    $list['data'][$k]['mess']=$v['create_username'].' '.$list['data'][$k]['event'];
                }


            }
            return json(['code'=>1,'msg'=>fy('Get successful').'!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1]);
        }
        $titleLst=\think\facade\Db::name('audit_management')->field('title')->group('title')->column('title','title');
        foreach ($titleLst as $k=>$v){
            $titleLst[$k]=fy($v);
        }
        $this->assignconfig('titleLst',$titleLst);
        $resultLst=\think\facade\Db::name('audit_management')->group('result')->column('result','result');
        foreach ($resultLst as $k=>$v){
            $resultLst[$k]=fy($v);
        }
        $this->assignconfig('resultLst',$resultLst);
        // 传递 scope 给前端，用于激活对应标签页
        $this->assignconfig('scope', $this->request->get('scope', 1, 'trim'));
        return $this->fetch();
    }
}