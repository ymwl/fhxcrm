<?php

namespace app\admin\traits;



use jianyan\excel\Excel;
use think\facade\Db;

/**
 * 后台CURD复用
 * Trait Curd
 * @package app\admin\traits
 */
trait Curd
{
    protected $modelValidate=false;

    protected $selectpageFields = '`id`,`name`';
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
            $post = xss_clean($post);
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
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post = xss_clean($post);
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
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        @ini_set("memory_limit",'-1');
        @ini_set('max_execution_time', '0');

        list($page, $limit, $where,$sort) = $this->buildTableParames();
        if(empty($this->export_fields)){
            $tableName = $this->model->getName();
            $tableName = \tools\Hs::humpToLine(lcfirst($tableName));
            $prefix = config('database.connections.mysql.prefix');
            $dbList = Db::query("show full columns from {$prefix}{$tableName}");
            $header = [];
            foreach ($dbList as $vo) {
                $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
                if (!in_array($vo['Field'], $this->noExportFields)) {
                    $header[] = [$comment, $vo['Field']];
                }
            }
            $list = $this->model
                ->where($where)
                ->order('id', 'desc')
                ->select()
                ->toArray();
            $fileName = time();
            return \tools\excel\Excel::exportData($list, $header, $fileName, 'xlsx');
        }else{
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $i = 0;
            $str_fields='';
            $arr_fields=[];
            foreach ($this->export_fields as $k => $v) {
                $name=!empty($v['xsname'])?$v['xsname']:$v['name'];
                if ($i >= 26) {
                    $cell = chr(65 + $i / 26 - 1) . chr(65 + $i % 26);
                } else {
                    $cell = chr(65 + $i);
                }
                $worksheet->getStyle($cell . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('cdf79e');
                $worksheet->getColumnDimension($cell)->setWidth($v['width'],'px');

                $str_fields=$str_fields.',`'.$v['field'].'`';


                $arr_fields[$cell]=$v;
                $worksheet->setCellValue($cell . '1', $name);
                $i++;
            }
            $str_fields=trim($str_fields,',');
            $line=1;

            $cursor=$this->model->field($str_fields)
                ->where($where)
                ->order($sort)->cursor();
            $styleArray = array(
                'font' => array(
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 12,
                    'name'  => 'Microsoft Yahei'
                ));
//        循环输出
            foreach ($cursor as $item) {
                $line++;
                foreach ($arr_fields as $cell => $field) {
                    $value=$item[$field['field']];

                    $value=real_field_val($field,$value);



                    $worksheet->setCellValue($cell . $line, $value.' ');
                    $worksheet->getStyle($cell . $line)->getNumberFormat()->setFormatCode('@');
                    $worksheet->getCell($cell . $line)->getStyle()->applyFromArray($styleArray);
                }
            }
            ob_end_clean();
            $title = date("YmdHis");
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: cache, must-revalidate');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            try {
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            exit();
        }

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
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validater($post, $rule);

        $row = $this->model->find($post['id']);
        if (!$row) {
            $this->error(fy('The data does not exist'));
        }
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error(fy('This field is not allowed to be modified').':' . $post['field']);
        }
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(fy('Save successfully'));
    }

    /**
     * Selectpage的实现方法.
     * 当前方法只是一个比较通用的搜索匹配,请按需重载此方法来编写自己的搜索逻辑,$where按自己的需求写即可
     * 这里示例了所有的参数，所以比较复杂，实现上自己实现只需简单的几行即可.
     */
    public function selectpage()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);
        $where=[];
        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array) $this->request->request('q_word/a');
        //当前页
        $page = $this->request->request('pageNumber', 1, 'int');
        //分页大小
        $pagesize = $this->request->request('pageSize');
        //搜索条件
        $andor = $this->request->request('andOr', 'and', 'strtoupper');
        //排序方式
        $orderby = (array) $this->request->request('orderBy/a');
        //显示的字段
        $field = $this->request->request('showField');
        //主键
        $primarykey = $this->request->request('keyField');
        //主键值
        $primaryvalue = $this->request->request('keyValue');
        //搜索字段
        $searchfield = (array) $this->request->request('searchField/a');
        //自定义搜索条件
        $custom = (array) $this->request->request('custom/a');


        $order = [];
        foreach ($orderby as $k => $v) {
            $order[$v[0]] = $v[1];
        }
        $field = $field ? $field : 'name';


        //如果有primaryvalue,说明当前是初始化传值
        if ($primaryvalue !== null) {
            $where = [$primarykey => explode(',', $primaryvalue)];
            $pagesize = null;
        } else {
            $where = function ($query) use ($word, $andor, $field, $searchfield, $custom) {
                $logic = $andor == ' AND ' ? ' & ' : ' | ';
                $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
                foreach ($word as $k => $v) {
                    $v=trim($v);
                    if($v){
                        $query->where(str_replace(',', $logic, $searchfield), 'like', "%{$v}%");
                    }
                }
                if ($custom && is_array($custom)) {
                    foreach ($custom as $k => $v) {
                        if (is_array($v) && 2 == count($v)) {
                            $query->where($k, trim($v[0]), $v[1]);
                        } else {
                            $query->where($k, '=', $v);
                        }
                    }
                }
            };
        }


        $list = [];
        $total = $this->model->where($where)->count();
        if ($total > 0) {
            $list = $this->model->where($where)
                ->order($order)
                ->page($page, $pagesize)
                ->field($this->selectpageFields)
                ->select()->toArray();
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        return json(['list' => $list, 'total' => $total]);
    }
//    判断是否具有修改权限
//传入被修改者的管理员ID
    public function modifyPermissions($modifiedByAdminId,$withself=true){
        $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin,$withself);
        if($adminIds=='ALL'){
            return true;
        }
        if(!in_array($modifiedByAdminId,$adminIds)){
            $this->error(fy("无操作当前数据权限").'!');
        }
        return true;


    }

    //    判断是否具有修改权限
//传入被修改者的管理员用户名
    public function modifyPermissionsByName($modifiedByAdminName){
        $adminNames=(new \app\admin\model\Admin())->getViewAdminName($this->admin,true);
        if($adminNames=='ALL'){
            return true;
        }
        if(is_string($modifiedByAdminName)){
            if(!in_array($modifiedByAdminName,$adminNames)){
                $this->error(fy("无操作当前数据权限").'!');
            }
        }elseif (is_array($modifiedByAdminName)){
            $diff = array_diff($modifiedByAdminName,$adminNames);
            if (!empty($diff)) {
                $this->error(fy("无操作当前数据权限").'!');
            }
        }
        return true;
    }
    //传入被修改者的管理员用户id集合
    public function modifyPermissionsByIds($modifiedByAdminIds){
        $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin,true);
        if($adminIds=='ALL'){
            return true;
        }
        if(is_numeric($modifiedByAdminIds) || is_string($modifiedByAdminIds)){
            if(!in_array($modifiedByAdminIds,$adminIds)){
                $this->error(fy("无操作当前数据权限").'!');
            }
        }elseif (is_array($modifiedByAdminIds)){
            $diff = array_diff($modifiedByAdminIds,$adminIds);
            if (!empty($diff)) {
                $this->error(fy("无操作当前数据权限").'!');
            }
        }
        return true;
    }

}
