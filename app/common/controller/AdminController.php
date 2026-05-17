<?php


namespace app\common\controller;



use app\admin\controller\Common;

/**
 * Class AdminController
 * @package app\common\controller
 */
class AdminController extends Common
{
    use \app\admin\traits\Curd;

    /**
     * 当前模型
     * @Model
     * @var object
     */
    protected $model;

    /**
     * 字段排序
     * @var array
     */
    protected $sort = [
        'id'   => 'DESC',
    ];
    protected $sort_by = 'id';
    protected $sort_order = 'DESC';

    /**
     * 允许修改的字段
     * @var array
     */
    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'is_delete',
        'is_auth',
        'title',
    ];

    /**
     * 不导出的字段信息
     * @var array
     */
    protected $noExportFields = ['delete_time', 'update_time'];

    /**
     * 下拉选择条件
     * @var array
     */
    protected $selectWhere = [];

    /**
     * 是否关联查询
     * @var bool
     */
    protected $relationSearch = false;

    /**
     * 模板布局, false取消
     * @var string|bool
     */
    protected $layout = 'layout/default';
    protected $view;


    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $this->layout && $this->app->view->engine()->layout($this->layout);

    }
    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return mixed
     */
    protected function assign($name, $value = null)
    {
        return $this->app->view->assign($name, $value);
    }

    protected function display($template = '', $vars = [])
    {
        return $this->app->view->display($template, $vars);
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
    /**
     * 构建请求参数
     * @param array $excludeFields 忽略构建搜索的字段
     * @return array
     */
    protected function buildTableParames($excludeFields = [])
    {
        $get = $this->request->get('', null, null);
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $sort_by = input('sort_order') ? input('sort_by') : 'id';
        $sort_order = input('sort_order') ? input('sort_order') : 'asc';
        if(!empty($get['sort_by']) && !empty($get['sort_order'])){
            $sort = [
                $get['sort_by'] => $get['sort_order'],
            ];
        }else{
            $sort = $this->sort;
        }
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;
        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $ops = isset($get['op']) && !empty($get['op']) ? $get['op'] : '{}';
        // json转数组
        $filters = json_decode($filters, true);
        $ops = json_decode($ops, true);
        $where = [];
        $excludes = [];
        $filters=$this->transformArray($filters);


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
            if ($this->relationSearch && count(explode('.',  $sort_by )) == 2) {
                $sort_by  = \tools\Hs::humpToLine(lcfirst($sort_by));
            }
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
    /**
     * 下拉选择列表
     * @return \think\response\Json
     */
    protected function selectList()
    {
        $fields = input('selectFields');
        $data = $this->model
            ->where($this->selectWhere)
            ->field($fields)
            ->select();
        $this->success(fy('Get successful'),'' ,$data);
    }



    /**
     * 严格校验接口是否为POST请求
     */
    protected function checkPostRequest(){
        if (!$this->request->isPost()) {
            $this->error("当前请求不合法！");
        }
    }



}
