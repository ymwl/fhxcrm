<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/5/2 0002
 * Time: 09:22
 */
namespace app\admin\controller\system;
use app\admin\model\publicuse\PublicUse;
use app\common\model\SystemField;
use app\admin\model\traits\TablHandle;
use app\common\controller\AdminController;

use think\App;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
/**
 * @ControllerAnnotation(title="字段管理")
 * Class Fields
 * @package app\admin\controller\system
 */
class Fields extends AdminController
{
    private $AllData;

    protected $allowModifyFields = [
        'show',
        'require',
        'edit',
        'edit',
        'search',
        'total',
        'export',
        'status',
        'unique',
        'is_key',
        'xsname','required','sort','width','list_sort'
    ];
    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'ASC',
    ];
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemField();
    }

    /**
     * 延迟加载AllData配置
     * @return array
     */
    private function getAllData()
    {
        if ($this->AllData === null) {
            $this->AllData = json_decode(file_get_contents($this->app->getAppPath().'controller/system/field/Fields.json'), true);
        }
        return $this->AllData;
    }

    public function index()
    {
        $table = request()->get('table','','trim');
        $filter=$this->request->get('filter', '{}','trim');
        $filter=json_decode($filter,true);

        if(empty($table)){
            $this->error(fy('Parameter error'));
        }
        if ($this->request->isAjax()) {
            
            $where=[];
            list($page, $limit, $where,$sort)= $this->buildTableParames();
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
                ->order($sort)
                ->select()->toArray();
            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assign('parameter','?table='.$table);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = $this->processPost($post);

            try {
                $bool = TablHandle::AddField($post);
            } catch (\Throwable $t) {
                $msg=$t->getMessage();
                if(strpos($msg,'Column already exists')!==false){
                    $msg='字段已存在';
                }
                $this->error($msg);
            }

            if(!$bool){
                $this->error('创建字段失败');
            }

            $post = $this->generateTemplateFields($post);
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
        $this->assign('alldata',$this->getAllData());
        $this->assign('row',$data);
        $this->assign('getTableList',$this->model->getTableList());
        return $this->fetch();
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
            $post = $this->processPost($post);

            $save = false;
            try {
                $bool = TablHandle::UpdateField($row->toArray(),$post);

                if(!$bool){
                    throw new \Exception('修改字段失败', 0);
                }
                $post = $this->generateTemplateFields($post);
                $save = $row->save($post);
            } catch (\Throwable $e) {
                $msg=$e->getMessage();
                if(strpos($msg,'Column already exists')!==false){
                    $msg='字段已存在';
                }
                $this->error($msg);
            }

            if($save){
                Cache::clear();
                cache($row['table'].'_fields', null);
                $this->success(fy('Save successfully'));
            }else{
                $this->error(fy('Save failed'));
            }
        }

        // 从 rule 字段中提取自定义正则（格式：regex:pattern）
        if (!empty($row['rule']) && preg_match('/regex:([^,]+)/', $row['rule'], $matches)) {
            $row['regex'] = '/' . $matches[1] . '/';
        }
        $this->assignconfig('ruleLst',$this->get_rule_list($row['rule']));
        $this->assign('row', $row);
        $this->assign('alldata',$this->getAllData());
        $this->assign('getTableList',$this->model->getTableList());
        return $this->fetch();
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
            // 先检查是否包含系统字段，再执行删除操作
            foreach ($row as $v){
                if($v['issystem']){
                    $this->error('系统字段禁止删除！');
                }
            }
            TablHandle::DeleteField($row->toArray());
            foreach ($row as $v){
                cache($v['table'].'_fields',null);
            }
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        if(!empty($save)){
            Cache::clear();
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

        $row[$post['field']]= $post['value'];
        $this->handleModifyFieldUpdate($row, $post);

        $row=array_intersect_key($row, array_flip($this->model->getTableFields()));
        \think\facade\Db::name('system_field')->where('id','=',$post['id'])->update($row);
        cache($row['table'].'_fields',null);
        $this->success(fy('Save successfully'));
    }

    /**
     * 处理POST数据：验证、字段转换、checkbox转换
     * @param array $post
     * @return array
     */
    private function processPost($post)
    {
        $rule = [
            'name|字段名称'    => 'require',
            'field|字段名'    => 'regex:[A-Za-z][A-Za-z0-9_]+'
        ];
        $this->validater($post, $rule);
        $allData = $this->getAllData();
        if(isset($allData['field'])&&!empty($allData['field'])){
            $post = PublicUse::Conversion($post,$allData['field']);
        }
        // 合并自定义正则到 rule 字段（格式：regex:pattern）
        if (!empty($post['regex'])) {
            $regexPattern = trim($post['regex']);
            // 如果用户输入了 /pattern/ 格式，去除前后的斜杠
            if (strlen($regexPattern) > 2 && $regexPattern[0] === '/' && $regexPattern[strlen($regexPattern) - 1] === '/') {
                $regexPattern = substr($regexPattern, 1, -1);
            }
            // 先移除已有的 regex: 规则，再追加新的
            $post['rule'] = isset($post['rule']) ? preg_replace('/,?regex:[^,]+/', '', $post['rule']) : '';
            $post['rule'] = trim($post['rule'], ',');
            $post['rule'] = $post['rule'] !== ''
                ? $post['rule'] . ',regex:' . $regexPattern
                : 'regex:' . $regexPattern;
        } elseif (isset($post['rule'])) {
            // 用户清空了自定义正则，移除已有的 regex: 规则
            $post['rule'] = preg_replace('/,?regex:[^,]+/', '', $post['rule']);
            $post['rule'] = trim($post['rule'], ',');
        }
        unset($post['regex']);
        $post['is_key'] = (isset($post['is_key']) && $post['is_key']=='on') ? 1 : 0;
        $post['list_sort'] = (isset($post['list_sort']) && $post['list_sort']=='on') ? 1 : 0;
        return $post;
    }

    /**
     * 生成模板字段（addinput、editinput、jscol）
     * @param array $post
     * @return array
     */
    private function generateTemplateFields($post)
    {
        $post['addinput']=TablHandle::create_add_input($post);
        $post['editinput']=TablHandle::create_edit_input($post);
        $post['jscol']=TablHandle::create_js_col($post);
        return $post;
    }

    /**
     * 处理modify方法中不同字段的更新逻辑
     * @param array $row 数据行（引用传递）
     * @param array $post POST数据
     */
    private function handleModifyFieldUpdate(&$row, $post)
    {
        $field = $post['field'];
        $value = $post['value'];

        if($field=='required' && $value==1){
            $row['show']=1;
            $row['edit']=1;
        }

        switch($field){
            case 'search':
            case 'show':
            case 'list_sort':
                $row['jscol']=TablHandle::create_js_col($row);
                break;
            case 'xsname':
                $row['addinput']=TablHandle::create_add_input($row);
                $row['editinput']=TablHandle::create_edit_input($row);
                $row['jscol']=TablHandle::create_js_col($row);
                break;
            case 'required':
                $row['addinput']=TablHandle::create_add_input($row);
                $row['editinput']=TablHandle::create_edit_input($row);
                break;
            case 'width':
                $row['jscol']=preg_replace('/"width":"\d+"/', '"width":"'.$value.'"', $row['jscol']);
                $row['jscol']=preg_replace('/"width":\d+/', '"width":'.$value, $row['jscol']);
                break;
            case 'require':
                $row['rule']=str_replace([',require','require,','require'],'',$row['rule']);
                if($value==1){
                    $row['rule']=$row['rule'].',require';
                }
                $row['rule']=trim($row['rule'],',');
                $row['addinput']=TablHandle::create_add_input($row);
                $row['editinput']=TablHandle::create_edit_input($row);
                break;
            case 'unique':
                $row['rule']=str_replace([',unique','unique,','unique'],'',$row['rule']);
                if($value==1){
                    $row['rule']=$row['rule'].',unique';
                }
                $row['rule']=trim($row['rule'],',');
                break;
            case 'edit':
                $row['editinput']=TablHandle::create_edit_input($row);
                break;
        }
    }
}
