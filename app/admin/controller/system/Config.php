<?php

namespace app\admin\controller\system;

use app\admin\model\traits\TablHandle;
use app\common\controller\AdminController;

use think\App;
use think\facade\Db;


class Config extends AdminController
{

    //    是否开启验证
    protected $modelValidate=true;
    protected $relationSearch=true;
//    是否开启场景验证
    protected $modelSceneValidate=false;

    public $allData=[];
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\SystemConfig();

        $this->allData = [
            "input"=> "单行文本",
            "textarea"=> "多行文本",
            'password'=>'密码框',
            "file"=> "单文件",
            "files"=> "多文件",
            "image"=> "单图片",
            "images"=> "多图片",
            "radio"=> "单选框",
            "checkbox"=> "多选框",
            "select"=> "下拉框",
            "editor"=> "编辑框",
            "switch"=> "开关",
            "datetime"=> "年月日时",
            "date"=> "年月日",
            "time"=> "时间"
        ];

        $groupList=\think\facade\Db::name('system_config_group')->where('status','=',1)->order('sort ASC,id ASC')->column('name','identification');
        $this->assign('groupList',$groupList);
        $this->assignconfig('groupList',$groupList);

    }

    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();
            $status=$this->request->get('status',0,'intval');

            $count = $this->model->withJoin(['group'=>['identification']], 'LEFT')
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model->withJoin(['group'=>['identification']], 'LEFT')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($this->sort)  //->fetchSql()
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


    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                try {
                    validate($validate)->check($post);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            try {
                $post['input']=TablHandle::create_edit_input($post);
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            cache('System',null);
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }



        $this->assign('allData', $this->allData);
        return $this->fetch();
    }

    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                try {
                    validate($validate)->check(array_merge($post,['id'=>$id]));
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            try {
                $post['input']=TablHandle::create_edit_input($post);
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed'));
            }
            cache('System',null);
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

        $this->assign('row', $row);
        $this->assign('allData', $this->allData);
        return $this->fetch();
    }

    public function config(){

        $identification = $this->request->param('identification','basic','trim');


        if ($this->request->isPost()) {
            $post = $this->request->post();
            $prefix=getDataBaseConfig('prefix');
            foreach ($post as $k=>$v){
                if($k=='customer_unique'){
//先删除unique
                    Db::name('system_field')
                        ->whereRaw('`edit`=1 AND `type`="varchar" AND `formtype` IN ("input","tel") AND `table`="crm_customer" AND `addinput` is not null')
                        ->update([
                            'rule'		=>	Db::raw("trim(',' from (replace(concat(',',`rule`,','), ',unique,', ',' )))")
                        ]);
                    if(!empty($v)){
//                        再给勾选条件的加上
                        Db::name('system_field')
                            ->where([['table','=','crm_customer'],['field','in',$v]])
                            ->update([
                                'rule'		=>	Db::raw("TRIM(',' FROM CONCAT(`rule`,',unique'))")
                            ]);
                    }
                }elseif ($k=='license_key'){
                    cache('license_name',null);
                }
                \think\facade\Db::name('system_config')->where('field','=',$k)->update(['value'=>$v]);
            }
            cache('System',null);
            $this->success(fy('Save successfully'));
        }

        $list = $this->model->field('`input`,`field`,`value`')->where('identification','=',$identification)->where('status','=',1)->order('sort ASC,id ASC')->select();
        $fields_str='';
        $row=[];
        foreach ($list as $v){
            $fields_str.=trim($v['input']);
            $row[$v['field']]=$v['value'];
        }
        $this->app->view->engine()->layout(false);


        $fields_str=$this->display($fields_str,['row'=>$row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);




        return $this->fetch();
    }

    public function input_list(){

        if ($this->request->isPost()) {
            $identification = $this->request->param('identification','basic','trim');
            $list = $this->model->field('`input`,`field`,`value`')->where('identification','=',$identification)->where('status','=',1)->order('sort ASC,id ASC')->select();
            $fields_str='';
            $row=[];
            foreach ($list as $v){
                $fields_str.=trim($v['input']);
                if($v['field']=='customer_unique'){
//                    // SELECT `field` FROM ymwl_system_field WHERE `table`='crm_customer' AND CONCAT(',',rule,',') LIKE '%,unique,%';
                    $selected_fields=Db::name('system_field')
                        ->whereRaw("`table`='crm_customer' AND CONCAT(',',rule,',') LIKE '%,unique,%'")
                        ->column('field');
                    if($selected_fields){
                        $v['value']=implode(',',$selected_fields);
                    }else{
                        $v['value']='';
                    }
                }
                $row[$v['field']]=$v['value'];
            }
            $this->app->view->engine()->layout(false);
            $fields_lst=$record_fields_lst=$big_fields_lst=[];
            if($identification=='crm'){
                $fields=Db::query('SELECT `name`,`xsname`,`field` FROM `'.getDataBaseConfig('prefix').'system_field` WHERE `edit`=1 AND `type`="varchar" AND `formtype` IN ("input","tel") AND `table`="crm_customer" AND `addinput` is not null order BY `sort` ASC,id ASC');
                foreach ($fields as $f){
                    $fields_lst[]=['name'=>empty($f['xsname'])?$f['name']:$f['xsname'],'field'=>$f['field']];
                }
                $record_fields_lst=\app\service\SystemFieldService::getRecordFields();
                $big_fields_lst=\app\service\SystemFieldService::getBigShowFields();
            }
            $fields_str=$this->display($fields_str,['row'=>$row,'fields_lst'=>$fields_lst,'record_fields_lst'=>$record_fields_lst,'big_fields_lst'=>$big_fields_lst]);
            $this->success('获取成功','',$fields_str);
        }
    }

    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));
        $c=$this->model->whereIn('id', $id)->where('issystem',1)->count();
        if($c>0){
            $this->error('包含系统字段不能删除!');
        }
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }



}
