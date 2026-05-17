<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\api\traits;

use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use think\facade\Db;

/**
 * API CURD复用
 * Trait Curd
 * @package app\admin\traits
 */
trait Curd
{

    /**
     * 字段排序
     * @var array
     */
    protected $sort = [
        'id'   => 'DESC',
    ];
    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'id';
    protected $relationSearch = false;

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where, $sort) = $this->buildTableParames();
        $count = $this->model
            ->where($where)
            ->count();
        $list = $this->model
            ->where($where)
            ->page($page, $limit)
            ->order($sort)
            ->select();
        $data = [
            'code'  => 1,
            'msg'   => '',
            'data'  => ['rows' => $list, 'count' => $count],
        ];
        return json($data);
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [];
            $this->validater($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

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
            $rule = [];
            $this->validater($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed'));
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }
        $this->assign('row', $row);

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

        list($page, $limit, $where) = $this->buildTableParames();
        $tableName = $this->model->getName();
        $tableName = CommonTool::humpToLine(lcfirst($tableName));
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
     * 构建请求参数
     * @param array $excludeFields 忽略构建搜索的字段
     * @return array
     */
    protected function buildTableParames($excludeFields = [])
    {
        $get = $this->request->get('', null, null);

        // 兼容前端分页参数：offset（偏移量）转换为 page（页码）
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;
        if (isset($get['offset']) && $get['offset'] !== '') {
            $page = max(1, floor((int)$get['offset'] / $limit) + 1);
        } else {
            $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        }

        // 兼容前端排序参数：sort + order（同时保留 sort_by + sort_order）
        $sortBy = !empty($get['sort_by']) ? $get['sort_by'] : (!empty($get['sort']) ? $get['sort'] : '');
        $sortOrder = !empty($get['sort_order']) ? $get['sort_order'] : (!empty($get['order']) ? $get['order'] : '');
        if (!empty($sortBy) && !empty($sortOrder)) {
            $sort = [
                $sortBy => $sortOrder,
            ];
        } else {
            $sort = $this->sort;
        }

        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $ops = isset($get['op']) && !empty($get['op']) ? $get['op'] : '{}';
        // json转数组
        $filters = json_decode($filters, true);
        $ops = json_decode($ops, true);
        $where = [];
        $excludes = [];
        $filters=$this->transformArray($filters);

        // 关键词搜索（使用 searchFields 配置）
        if (!empty($get['search']) && !empty($this->searchFields)) {
            $keyword = $get['search'];
            $where[] = [$this->searchFields, 'LIKE', "%{$keyword}%"];
        }


        // 判断是否关联查询
        $tableName = \tools\Hs::humpToLine(lcfirst($this->model->getName()));

        foreach ($filters as $key => $val) {
            if (in_array($key, $excludeFields)) {
                $excludes[$key] = $val;
                continue;
            }
            if(is_array($val)){
                if(isset($val[0])){
                    $where[] = [$key, '>=', $val[0]];
                }
                if(isset($val[1])){
                    $where[] = [$key, '<=', $val[1]];
                }
                continue;

            }
            $op = isset($ops[$key]) && !empty($ops[$key]) ? $ops[$key] : '%*%';
            if ($this->relationSearch && count(explode('.', $key)) == 1) {
                $key = "{$tableName}.{$key}";
            }
            /*if ($this->relationSearch && count(explode('.',  $sortBy )) == 2) {
                $sortBy  = \tools\Hs::humpToLine(lcfirst($sortBy));
            }*/
            if ($this->relationSearch && count(explode('.',  $key )) == 2) {
                $key  = \tools\Hs::humpToLine(lcfirst($key));
            }

            switch (strtolower($op)) {
                case '=':
                    $where[] = [$key, '=', $val];
                    break;
                case 'in':
                    $where[] = [$key, 'in', $val];
                    break;
                case '%*%':
                    $where[] = [$key, 'LIKE', "%{$val}%"];
                    break;
                case '*%':
                    $where[] = [$key, 'LIKE', "{$val}%"];
                    break;
                case '%*':
                    $where[] = [$key, 'LIKE', "%{$val}"];
                    break;
                case 'range':
                    [$beginTime, $endTime] = explode(' - ', $val);
                    $where[] = [$key, '>=', strtotime($beginTime)];
                    $where[] = [$key, '<=', strtotime($endTime)];
                    break;
                default:
                    $where[] = [$key, $op, "%{$val}"];
            }
        }
        return [(int)$page, (int)$limit, $where, $sort, $excludes];
    }
    protected function transformArray($input) {
        $output = [];

        foreach ($input as $key => $value) {
            // 使用正则表达式匹配类似 ceshi[0] 这样的键
            if (preg_match('/^(.+)\[(\d+)\]$/', $key, $matches)) {
                // $matches[1] 是数组名称（例如 'ceshi'）
                // $matches[2] 是索引（例如 '0' 或 '1'）
                $arrayName = $matches[1];
                $index = intval($matches[2]);

                // 如果目标数组不存在，则初始化它
                if (!isset($output[$arrayName])) {
                    $output[$arrayName] = [];
                }

                // 将值添加到对应的数组中
                $output[$arrayName][$index] = $value;
            } else {
                // 对于不符合模式的键，直接复制到输出数组中
                $output[$key] = $value;
            }
        }

        // 移除数组中的空隙（如果有的话），确保索引连续
        foreach ($output as $key => &$subArray) {
            if (is_array($subArray)) {
                $subArray = array_values($subArray);
            }
        }

        return $output;
    }
    protected function modifyPermissionsByName($modifiedByAdminName){
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
    protected function modifyPermissionsByIds($modifiedByAdminIds){
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


}
