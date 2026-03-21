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

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template
     * @param array $vars
     * @return mixed
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->app->view->fetch($template, $vars);
    }

    protected function display($template = '', $vars = [])
    {
        return $this->app->view->display($template, $vars);
    }

    /**
     * 重写验证规则
     * @param array $data
     * @param array|string $validate
     * @param array $message
     * @param bool $batch
     * @return array|bool|string|true
     */
    protected function validater(array $data, $validate, array $message = [], bool $batch = false)
    {
        try {
            parent::validate($data, $validate, $message, $batch);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return true;
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
        if(!empty($get['sort_by']) && !empty($get['sort_order'])){
            $sort = [
                $get['sort_by'] => $get['sort_order'],
            ];
        }else{
            $sort = $this->sort;
        }
       /* $sort_by = !empty($get['sort_by']) ? $get['sort_by'] : $this->sort_by ?? 'id';
        $sort_order = !empty($get['sort_order']) ? $get['sort_order'] : $this->sort_order ??'desc';*/
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
        $tableName = \tools\hs::humpToLine(lcfirst($this->model->getName()));

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
                $sort_by  = \tools\hs::humpToLine(lcfirst($sort_by));
            }
            if ($this->relationSearch && count(explode('.',  $key )) == 2) {
                $key  = \tools\hs::humpToLine(lcfirst($key));
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
     * 构建请求参数
     * @param array $excludeFields  附加scope
     * @return array
     */
    protected function buildTableParamesScopNew($excludeFields = [])
    {
        $get = $this->request->get('', null, null);
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $field = isset($get['field']) && !empty($get['field']) ? $get['field'] : 'id';
        $order = isset($get['order']) && !empty($get['order']) ? $get['order'] : 'desc';
        $scope = isset($get['scope']) && !empty($get['scope']) ? $get['scope'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;
        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $ops = isset($get['op']) && !empty($get['op']) ? $get['op'] : '{}';
//        field: create_time
//order: desc
        // json转数组
        $filters = json_decode($filters, true);
        $ops = json_decode($ops, true);
        $where = [];
        $excludes = [];
        if($scope==2){
//                    展示下属的
            $adminLst=(new \app\admin\model\Admin())->getChildrenAdminIds($this->admin);
            $where[] = ['owner_admin_id', 'in',$adminLst ];
        }elseif($scope==3){
//                    展示下属的和自己的
            if($this->admin['group_id']>1){
                $adminLst=(new \app\admin\model\Admin())->getChildrenAdminIds($this->admin,true);
                $where[] = ['owner_admin_id', 'in', $adminLst];
            }
        }else{
//                    展示自己的
            $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
        }

        // 判断是否关联查询
        $tableName = \tools\hs::humpToLine(lcfirst($this->model->getName()));
        foreach ($filters as $key => $val) {
            if (in_array($key, $excludeFields)) {
                $excludes[$key] = $val;
                continue;
            }
            $op = isset($ops[$key]) && !empty($ops[$key]) ? $ops[$key] : '%*%';
            if ($this->relationSearch && count(explode('.', $key)) == 1) {
                $key = "{$tableName}.{$key}";
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
        return [$page, $limit, $where,$field.' '.$order, $excludes];
    }

    /**
     * 构建请求参数
     * @param array $excludeFields  附加scope
     * @return array
     */
    protected function buildTableParamesScop($excludeFields = [])
    {
        $get = $this->request->get('', null, null);
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $sort_by = !empty($get['sort_by']) ? $get['sort_by'] : 'id';
        $sort_order = !empty($get['sort_order']) ? $get['sort_order'] : 'desc';
        $scope = isset($get['scope']) && !empty($get['scope']) ? $get['scope'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;
        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $ops = isset($get['op']) && !empty($get['op']) ? $get['op'] : '{}';
//        field: create_time
//order: desc
        // json转数组
        $filters = json_decode($filters, true);
        $ops = json_decode($ops, true);
        $where = [];
        $excludes = [];
        if($scope==2){
//                    展示下属的 不包括自己
            $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin);
            if(empty($adminName)){
//                没有数据
                $where[] = ['pr_user', '=','-1'];
            }elseif($adminName=='ALL'){
//                    展示其他的  不包括自己需要做排除
                $where[] = ['pr_user', '<>',$this->admin['username']];
            }else{
                $where[] = ['pr_user', 'in',$adminName];
            }


        }elseif($scope==3){
//                    展示下属的和自己的
            //                    展示全部 包括自己
            $adminName=(new \app\admin\model\Admin())->getViewAdminName($this->admin,true);
            if(empty($adminName)){
                $where[] = ['pr_user', '=','-1'];
            }if($adminName!=='ALL'){
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
// 今天已跟进限制展示自己的
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

        // 判断是否关联查询
        $tableName = \tools\hs::humpToLine(lcfirst($this->model->getName()));
        foreach ($filters as $key => $val) {
            if (in_array($key, $excludeFields)) {
                $excludes[$key] = $val;
                continue;
            }
            $op = isset($ops[$key]) && !empty($ops[$key]) ? $ops[$key] : '%*%';
            if ($this->relationSearch && count(explode('.', $key)) == 1) {
                $key = "{$tableName}.{$key}";
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
        return [$page, $limit, $where,$sort_by.' '.$sort_order, $excludes];
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

    protected function param_to_str($params){
        foreach ($params as $k => $v) {
            //数组的字段处理成字符串,主要处理是自定义字段多选的值
            if (!empty($v) && is_array($v)) $params[$k] = implode(',', $v);
        }
        return $params;
    }

}
