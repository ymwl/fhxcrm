<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021-05-01
 * Time: 11:31
 */
namespace app\api\controller\system;
use app\admin\model\publicuse\PublicUse;
use app\admin\model\SystemModels;
use app\admin\model\traits\ModelHandle;
use app\admin\model\traits\TablHandle;
use app\api\controller\Authority;
use app\Request;

use think\App;
use think\facade\Db;
/**
 * @ControllerAnnotation(title="模型管理")
 * Class Models
 * @package app\admin\controller\system
 */
class Models extends Authority
{


    protected $sort = [
        'sort' => 'ASC',
        'id'   => 'DESC',
    ];
    protected $allowModifyFields = [
        'is_page',
        'status'
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemModels();
        $array = PublicUse::getConfigDir(__CLASS__);
        if(is_file(__DIR__.DIRECTORY_SEPARATOR.'field'.DIRECTORY_SEPARATOR.$array['class'].'.json')){
            $this->AllData = json_decode(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'field'.DIRECTORY_SEPARATOR.$array['class'].'.json'),true);
        }
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
            $count = $this->model
                ->where($where)
                ->count();
//            echo '<pre>';
//            print_r($this->sort);
//            exit;
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        
    }
    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validater($post, $rule);
            if(isset($this->AllData['field'])&&!empty($this->AllData['field'])){
                $post = PublicUse::Conversion($post,$this->AllData['field']);
            }
            try {
                ModelHandle::createTable($post);
            } catch (\Exception $e) {
                var_dump($e);
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $this->success(fy('Save successfully'));
        }
        $newdata = PublicUse::getApp();
        $this->assign('app',$newdata);
        $this->assign('getEngineList',$this->model->getEngineList());
        $this->assign('getTabletypeList',$this->model->getTabletypeList());
        $this->assign('getTableList',$this->model->getTableList());
        $connections = config('database.connections');
        $databases = [];
        foreach ($connections as $key=>$value){
            $databases[] = $key;
        }
        $this->assign('databases',$databases);
        
    }
    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validater($post, $rule);
            if(isset($this->AllData['field'])&&!empty($this->AllData['field'])){
                $post = PublicUse::Conversion($post,$this->AllData['field']);
            }
            $bool = ModelHandle::updateTable($row->toArray(),$post);
            if(!$bool){
                return $this->error(fy("Update failed"));
            }
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed'));
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        $newdata = PublicUse::getApp();
        $this->assign('app',$newdata);
        $this->assign('row', $row);
        $this->assign('alldata',$this->AllData);
        $this->assign('getEngineList',$this->model->getEngineList());
        $this->assign('getTabletypeList',$this->model->getTabletypeList());
        $this->assign('getTableList',$this->model->getTableList());
        
    }
    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $id=$this->request->param('id');
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));
//        echo '<pre>';
//        print_r($row->toArray());
//        exit;
        $bool = ModelHandle::deleteTable($row->toArray());
        if(!$bool){
            return $this->error('删除表失败');
        }
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }
    /**
     * @NodeAnotation(title="清除表内容")
     */
    public function clieartable($id){
        $row = $this->model->find($id)->toArray();
        $prefix = getDataBaseConfig('prefix');
        $table = $prefix.$row['table'];
        $sql = "truncate table `$table`";
//        dump($row);exit;
        try{
            Db::connect($row['databases'])->query($sql);
        }catch (\Exception $e){
            return $this->error('清空失败');
        }
        return $this->success('清除成功');
    }
    /**
     * @NodeAnotation(title="全表更新")
     */
    public function update_all_table(){
//        全表更新没必要
        exit();
        $mysql = getDataBaseConfig('database');
        $qianzui = getDataBaseConfig('prefix');
        $connections = config('database.connections');
        foreach ($connections as $key=>$v){
            $sql = "select TABLE_NAME  AS 'table',TABLE_COMMENT as 'name',engine as 'engine' from information_schema.tables where table_schema='".$v['database']."'";
            $array = Db::connect($key)->query($sql);
//        dump($array);die();
            foreach ($array as $value){
                if(strpos($value['table'],'system_log')!==false){
                    continue;
                }
                $table = $value['table'];
                $tables = str_replace($qianzui,'',$table);
                $bool = TablHandle::updatetable($tables,$qianzui,$key,$v['database']);
                if(!$bool){
                    return $this->error(fy("Update failed"));
                }
            }
        }
        return $this->success(fy("Update succeeded"));
    }
    /**
     * @NodeAnotation(title="获取所有的数据库表")
     */
    public function get_tables(){
        $prefix = getDataBaseConfig('prefix');

        $sql = "select TABLE_NAME  AS 'table',TABLE_COMMENT as 'name' from information_schema.tables where table_schema='".$database."'";
        $array = Db::query($sql);
        $lst=[];
        foreach ($array as $k=>$v){
            $name=$v['name']?'('.$v['name'].')':'';
            $lst[]=['name'=>$v['table'].$name,'value'=>$v['table']];
        }
        $this->success(fy('Get successful'),'',$lst);
    }
    /**
     * @NodeAnotation(title="选择表更新")
     */
    public function update_select_table(){

        if($this->request->isPost()){
            $tables=$this->request->post('tables','','trim');
            if(empty($tables)){
                $this->error('更新表没有选择');
            }
            $tables=explode(',',$tables);
            $prefix = getDataBaseConfig('prefix');

            $database = config('database.connections.'.$default.'.database');
            foreach ($tables as $table){
                $bool = TablHandle::updatetable($table,$prefix,$database);
                if(!$bool){
                    $this->error(fy("Update failed"));
                }
                $this->success(fy("Update succeeded"));
            }
        }
        
    }
    /**
     * @NodeAnotation(title="单表更新")
     */
    public function updateTable($id){
        $model = Db::name('system_model')
            ->where('id',$id)
            ->find();
        if(empty($model)){
            $this->error('需要更新的模型不存在');
        }
        $table = $model['table'];

        try{
            $bool = TablHandle::updatetable($table,getDataBaseConfig('prefix'),getDataBaseConfig('database'));
        }catch(\Exception $e){
            var_dump($e);
            $this->error('更新失败');
        }

        if(!$bool){
             $this->error(fy("Update failed"));
        }
        $this->success(fy("Update succeeded"));
    }
}