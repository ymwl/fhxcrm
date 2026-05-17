<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/5/2 0002
 * Time: 09:22
 */
namespace app\api\controller\system;
use app\admin\model\publicuse\PublicUse;
use app\common\model\SystemField;
use app\admin\model\traits\TablHandle;
use app\api\controller\Authority;

use think\App;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
/**
 * @ControllerAnnotation(title="字段管理")
 * Class Fields
 * @package app\admin\controller\system
 */
class Fields extends Authority
{
    private $AllData;

    protected $allowModifyFields = [
        'show',
        'require',
        'edit_readonly',
        'edit',
        'search',
        'total',
        'export',
        'status',
        'unique',
        'is_key',
        'xsname','required','sort','width','open_sort'
    ];
    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'ASC',
    ];
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemField();

        $this->AllData = json_decode(file_get_contents($this->app->getAppPath().'controller/system/field/Fields.json'),true);

    }

    public function index()
    {
//        $id = request()->param('id',null);
//        $field = request()->param('field',null);
        $table = request()->get('table','','trim');
        $filter=$this->request->get('filter', '{}','trim');
        $filter=json_decode($filter,true);

        if(empty($table)){
            $this->error(fy('Parameter error'));
        }
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            $where=[];
            list($page, $limit) = $this->buildTableParames();
            if(!empty($filter['name'])){
                $where[]=['name|xsname','like','%'.$filter['name'].'%'];
            }
            $where[]=['table','=',$table];
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()->toArray();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assign('parameter','?table='.$table);
        
    }
    public function add()
    {

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|字段名称'    => 'require',
                'field|字段名'    => 'regex:[A-Za-z][A-Za-z0-9_]+'
            ];
            $this->validater($post, $rule);
//            field  [^A-Za-z][^A-Za-z0-9_]+
            if(isset($this->AllData['field'])&&!empty($this->AllData['field'])){
                $post = PublicUse::Conversion($post,$this->AllData['field']);
            }
            if(isset($post['is_key'])&&$post['is_key']=='on'){
                $post['is_key'] = 1;
            }else{
                $post['is_key'] = 0;
            }
            if(isset($post['open_sort'])&&$post['open_sort']=='on'){
                $post['open_sort'] = 1;
            }else{
                $post['open_sort'] = 0;
            }
            try
            {
//    抛出异常信息和异常代码
                $bool = TablHandle::AddField($post);
            }
            catch (\Throwable $t)
            {
                $msg=$t->getMessage();
                if(strpos($msg,'Column already exists')!==false){
                    $msg='字段已存在';
                }
                $this->error($msg);

            }



            if(!$bool){
                $this->error('创建字段失败');
            }

            $post['addinput']=TablHandle::create_add_input($post);
            $post['editinput']=TablHandle::create_edit_input($post);
            $post['jscol']=TablHandle::create_js_col($post);
            $save = $this->model->save($post);
            if($save){
                Cache::clear();
                $this->success(fy('Save successfully'));
            }else{
                $this->error(fy('Save failed'));
            }


        }
        $this->assignconfig('ruleLst',$this->get_rule_list());
        $data = request()->param();
        $this->assign('alldata',$this->AllData);
        $this->assign('row',$data);
        $this->assign('getTableList',$this->model->getTableList());
        
    }
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        if($row['issystem']){
            $this->error('系统字段禁止修改！');
        }
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|字段名称'    => 'require',
                'field|字段名'    => 'regex:[A-Za-z][A-Za-z0-9_]+'
            ];
            $this->validater($post, $rule);
            if(isset($this->AllData['field'])&&!empty($this->AllData['field'])){
                $post = PublicUse::Conversion($post,$this->AllData['field']);
            }
            if(isset($post['is_key'])&&$post['is_key']=='on'){
                $post['is_key'] = 1;
            }else{
                $post['is_key'] = 0;
            }
            if(isset($post['open_sort'])&&$post['open_sort']=='on'){
                $post['open_sort'] = 1;
            }else{
                $post['open_sort'] = 0;
            }

            try {
                $bool = TablHandle::UpdateField($row->toArray(),$post);

                if(!$bool){
                    throw new \Exception('修改字段失败', 0);
                }
                $post['addinput']=TablHandle::create_add_input($post);
                $post['editinput']=TablHandle::create_edit_input($post);
                $post['jscol']=TablHandle::create_js_col($post);
                $save = $row->save($post);
//                var_dump($save);exit;
            } catch (\Throwable $e) {
                $msg=$e->getMessage();
                if(strpos($msg,'Column already exists')!==false){
                    $msg='字段已存在';
                }
                $this->error($msg);
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }


        $this->assignconfig('ruleLst',$this->get_rule_list($row['rule']));
        $this->assign('row', $row);
        $this->assign('alldata',$this->AllData);
        $this->assign('getTableList',$this->model->getTableList());
        
    }
    protected function get_rule_list($rule=''){
        $regexLst=config('regex');
        $ruleLst=[];
        $rule=explode(',',$rule);
        foreach ($regexLst as $k=>$v){
            if($rule && in_array($k,$rule)){
                $ruleLst[]=['name'=>$v,'value'=>$k,'selected'=>true];
            }else{
                $ruleLst[]=['name'=>$v,'value'=>$k];
            }

        }
        return $ruleLst;
    }
    public function delete()
    {
        $id=$this->request->param('id');
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));

        try {
            TablHandle::DeleteField($row->toArray());
            foreach ($row as $k=>$v){
                if($v['issystem']){
                    $this->error('系统字段禁止删除！');
                }
                cache($v['table'].'_fields',null);
            }

            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $this->checkPostRequest();
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require'
        ];
        $this->validater($post, $rule);

        $row = \think\facade\Db::name('system_field')->find($post['id']);
        if (!$row) {
            $this->error(fy('The data does not exist'));
        }
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error(fy('This field is not allowed to be modified').':' . $post['field']);
        }
//        $data=[];
        $row[$post['field']]= $post['value'];
        if($post['field']=='required' && $post['value']==1){
            $row['show']=1;
            $row['edit']=1;
        }
        if($post['field']=='search' || $post['field']=='show' || $post['field']=='open_sort'){
            $row['jscol']=TablHandle::create_js_col($row);
        }elseif($post['field']=='xsname'){
//                说明改的是显示名称则更新对应的模板数据
            $row['addinput']=TablHandle::create_add_input($row);
            $row['editinput']=TablHandle::create_edit_input($row);
            $row['jscol']=TablHandle::create_js_col($row);
        }elseif($post['field']=='required'){
            $row['addinput']=TablHandle::create_add_input($row);
            $row['editinput']=TablHandle::create_edit_input($row);
        }elseif($post['field']=='width'){
            $row['jscol']=preg_replace('/"width":"\d+"/', '"width":"'.$post['value'].'"', $row['jscol']);
            $row['jscol']=preg_replace('/"width":\d+/', '"width":'.$post['value'], $row['jscol']);
            }elseif($post['field']=='require'){
//            require,unique rule
//            先删除原来的require,unique require字符串 然后再添加 需要考虑逗号的问题
            $row['rule']=str_replace([',require','require,','require'],'',$row['rule']);
            if($post['value']==1){
                $row['rule']=$row['rule'].',require';
            }
            $row['rule']=trim($row['rule'],',');
            $row['addinput']=TablHandle::create_add_input($row);
            $row['editinput']=TablHandle::create_edit_input($row);
        }elseif($post['field']=='unique'){
            $row['rule']=str_replace([',unique','unique,','unique'],'',$row['rule']);
            if($post['value']==1){
                $row['rule']=$row['rule'].',unique';
            }
            $row['rule']=trim($row['rule'],',');
        }elseif($post['field']=='edit_readonly'){
            $row['editinput']=TablHandle::create_edit_input($row);
        }
        $row=array_intersect_key($row, array_flip($this->model->getTableFields()));
        \think\facade\Db::name('system_field')->where('id','=',$post['id'])->update($row);
//        $row->save();
        cache($row['table'].'_fields',null);
        $this->success(fy('Save successfully'));
    }
}
