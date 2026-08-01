<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use think\facade\View;

/**
 * @ControllerAnnotation(title="crm_clue")
 */
class Clue extends AdminController
{
    public $sort_by = 'id';
    public $sort_order = 'DESC';

    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'is_delete',
        'is_auth',
        'title',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\CrmClue();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        $fields = \tools\Cache::zdy_fields('crm_clue');

        if ($this->request->isAjax()) {
            
            list($page, $limit, $where, $sort) = $this->buildTableParames();
            $scope = $this->request->get('scope', 1, 'trim');
            $where[] = ['to_customer_id', '=', 0];
            // 排除已转化状态的线索（防止手动改状态导致 to_customer_id=0 的"假已转化"线索出现在列表中）
            $where[] = ['status', '<>', 2];
            if ($scope == 2) {
                // 下属的
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }else{
                    $where[] = ['owner_admin_id', '<>', $this->admin['admin_id']];
                }
            } elseif ($scope == 3) {
                // 全部
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin, true);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }
            } elseif ($scope == 10) {
                // 待跟进
                $where[] = ['next_time', '>', 0];
                if (isset($this->system['daigenjin']) && is_numeric($this->system['daigenjin'])) {
                    $where[] = ['next_time', '<', strtotime("+{$this->system['daigenjin']} day 00:00:00")];
                } else {
                    $where[] = ['next_time', '<', strtotime('tomorrow')];
                }
                $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
            } elseif ($scope == 11) {
                // 今天已跟进
                $where[] = ['last_up_time', '>=', strtotime('today')];
                $where[] = ['last_up_time', '<', strtotime('tomorrow')];
                $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
            } elseif ($scope == 12) {
                // 从未跟进  last_up_time是null
                $where[] = ['last_up_time', 'null', ''];
                $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
            } else {
                // 我的
                $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
            }
            $where[] = ['to_customer_id', '=', 0];
            // 排除已转化状态的线索（防止手动改状态导致 to_customer_id=0 的"假已转化"线索出现在列表中）
            $where[] = ['status', '<>', 2];
            $count = $this->model
                ->where($where)
                ->count();

            $list = [];
            if ($count) {
                $field_str = empty($fields['field_str']) ? '*' : $fields['field_str'];
                if (empty($fields['field_str'])) {
                    $field_str = '*';
                } else {
                    $field_str_arr = explode(',', $fields['field_str']);
                    if (!in_array('id', $field_str_arr)) {
                        $field_str = 'id,' . $field_str;
                    }
                }

                $list = $this->model->field($field_str)
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select()->toArray();

                if ($this->admin['isphone'] == 0) {
                    foreach ($list as $key => $value) {
                        foreach ($fields['tels'] as $tel_key => $tel_value) {
                            if ($value[$tel_value]) {
                                $value[$tel_value] = mb_substr($value[$tel_value], 0, 3) . '****' . mb_substr($value[$tel_value], 7, 11);
                            }
                        }
                        $list[$key] = $value;
                    }
                }
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];

            return json($data);
        }

        $jscol_str = $fields['jscol_str'];

        $this->app->view->engine()->layout(false);
        $jscol_str = $this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str = str_replace(['":"{"', '"}"'], ['":{"', '"}'], $jscol_str);
        $this->assignconfig('cols_fields', json_decode('[' . $jscol_str . ']', true));

        // 传递 scope 给前端，用于激活对应标签页
        $scope = $this->request->get('scope', 1, 'trim');
        $this->assignconfig('scope', $scope);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        $prefix = getDataBaseConfig('prefix');
        $fields = Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`addinput`,`formtype` FROM `' . $prefix . 'system_field` WHERE `form`=1 AND `table`="crm_clue" order BY `sort` ASC,id ASC');

        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post = $this->param_to_str($post);
            $this->verifyFields($post, $fields, 'crm_clue');

            try {
                $post = post_convert($post, $fields, 'add');
                $post['at_user'] = $this->admin['username'];
                $post['pr_user'] = $this->admin['username'];
                $post['owner_admin_id'] = $this->admin['admin_id'];
                $post['create_time'] = $post['update_time'] = time();
                $post = array_intersect_key($post, array_flip($this->model->getTableFields()));
                $id = $this->model->insertGetId($post);
            } catch (\Exception $e) {
                $msg = $e->getMessage();

                if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                    $msg = fy('A record containing %s already exists', [$matches[1]]);
                } elseif (preg_match("/Data too long for column '(.+)' at row/is", $msg, $matches)) {
                    $msg = fy('Fields %s Insufficient length', [$matches[1]]);
                }
                $this->error(fy('Save failed') . ':' . $msg);
            }
            $id ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

        $fields_str = '';
        foreach ($fields as $v) {
            $fields_str .= trim($v['addinput']);
        }

        $this->app->view->engine()->layout(false);
        $fields_str = $this->display($fields_str, ['row' => []]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $prefix = getDataBaseConfig('prefix');
        $fields = Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field`,`edit`,`editinput`,`formtype` FROM `' . $prefix . 'system_field` WHERE `form`=1 AND `table`="crm_clue" order BY `sort` ASC,id ASC');
        $row = $this->model->field(array_unique(array_merge(array_column($fields, 'field'), ['id', 'pr_user', 'owner_admin_id', 'phone', 'name'])))->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissionsByIds($row['owner_admin_id']);

        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post = $this->param_to_str($post);
            $this->verifyFields($post, $fields, 'crm_clue');

            Db::startTrans();
            try {
                $post = post_convert($post, $fields);
                $post['update_time'] = time();
                $save = $row->save($post);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $msg = $e->getMessage();
                if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                    $msg = fy('A record containing %s already exists', [$matches[1]]);
                } elseif (preg_match("/Data too long for column '(.+)' at row/is", $msg, $matches)) {
                    $msg = fy('Fields %s Insufficient length', [$matches[1]]);
                }
                $this->error(fy('Save failed') . ':' . $msg);
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

        $fields_str = '';
        foreach ($fields as $value) {
            $fields_str .= trim($value['editinput']);
            if ($this->admin['isphone'] == 0 && $value['formtype'] == 'tel') {
                $row[$value['field']] = mb_substr($row[$value['field']], 0, 3) . '****' . mb_substr($row[$value['field']], 7, 11);
            }
        }

        $this->app->view->engine()->layout(false);
        $fields_str = $this->display($fields_str, ['row' => $row]);
        $this->app->view->engine()->layout($this->layout);
        $this->assign('fields_str', $fields_str);
        $this->assign('id', $id);
        $this->assign('row', $row);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $id = parseIds();
        $this->checkPostRequest();
        $owner_admin_id = $this->model->whereIn('id', $id)->column('DISTINCT owner_admin_id');
        $this->modifyPermissionsByIds($owner_admin_id);

        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));

        try {
            $save = $this->model->whereIn('id', $id)->update(['delete_time' => time()]);
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    /**
     * @NodeAnotation(title="转化为客户")
     * 支持单条转化（行内按钮，id 为标量）与批量转化（工具栏勾选，id 为数组）
     */
    public function toCustomer($id = 0)
    {
        // 归一化 ID 列表：兼容单个ID、逗号分隔、数组提交（easy-admin 多选提交 ids，行内提交 id）
        $ids = $this->parseClueIds($this->request->param('ids', $id));
        if (empty($ids)) {
            $this->error(fy('Parameter error'));
        }

        // 查询线索并过滤已转化的（批量中跳过已转化线索，不中断整体流程）
        $clues = $this->model->whereIn('id', $ids)->select();
        if ($clues->isEmpty()) {
            $this->error(fy('Clue does not exist'));
        }
        $todoClues = [];
        foreach ($clues as $clue) {
            if ($clue['status'] != 2) {
                $todoClues[] = $clue;
            }
        }
        if (empty($todoClues)) {
            $this->error(fy('This clue has been converted to a customer'));
        }

        // 获取 crm_customer 表物理字段 与 自定义同名字段（循环外一次性查询）
        $customerFieldNames = array_column(Db::query('SHOW COLUMNS FROM `' . getDataBaseConfig('prefix') . 'crm_customer`'), 'Field');
        $mapFields = ['name', 'contact', 'phone', 'email', 'wechat', 'source', 'pr_user', 'owner_admin_id', 'at_user', 'remark'];
        $clueSysFields = Db::name('system_field')
            ->where('table', 'crm_clue')
            ->where('field', 'not in', $mapFields) // 排除已映射的业务字段
            ->column('field');
        $customerSysFields = Db::name('system_field')
            ->where('table', 'crm_customer')
            ->column('field');
        $commonFields = array_intersect($clueSysFields, $customerSysFields);

        // 唯一性验证：查询客户表 system_field 中配置了 unique 约束的字段（field => 显示名）
        $uniqueFields = [];
        $uniqueFieldRows = Db::name('system_field')
            ->where('table', 'crm_customer')
            ->whereRaw("CONCAT(',',rule,',') LIKE '%,unique,%'")
            ->select()
            ->toArray();
        foreach ($uniqueFieldRows as $row) {
            $uniqueFields[$row['field']] = fy($row['xsname'] ? $row['xsname'] : $row['name']);
        }

        // 插入前预验证：unique 字段必填 + 客户表唯一 + 同批不重复（事务外执行，失败直接终止，无需回滚）
        if (!empty($uniqueFields)) {
            $batchValues = [];
            try {
                foreach ($todoClues as $clue) {
                    $customerData = $this->buildCustomerData($clue, $customerFieldNames, $commonFields, $mapFields);
                    $this->validateCustomerUnique($customerData, $uniqueFields, $clue, $batchValues);
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        // 批量转化：整体事务，任一条失败则全部回滚
        $customerIds = [];
        Db::startTrans();
        try {
            foreach ($todoClues as $clue) {
                $customerIds[] = $this->convertClueToCustomer($clue, $customerFieldNames, $commonFields, $mapFields);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(fy('Conversion failed') . '：' . $e->getMessage());
        }

        // 部分线索已转化被跳过时，明确提示跳过的数量，避免用户误以为批量转化失败
        $skippedCount = count($clues) - count($todoClues);
        if ($skippedCount > 0) {
            $this->success(fy('Conversion successful, Customer ID: %s, skipped %d converted', [implode(',', $customerIds), $skippedCount]));
        }
        $this->success(fy('Conversion successful, Customer ID: %s', [implode(',', $customerIds)]));
    }

    /**
     * 构造新客户数据（提取公共逻辑，供唯一性验证与插入复用）
     * @param mixed $clue 线索模型记录
     * @param array $customerFieldNames 客户表物理字段名
     * @param array $commonFields 线索与客户自定义字段同名字段
     * @param array $mapFields 业务字段映射清单
     * @return array 待插入客户表的数据
     */
    private function buildCustomerData($clue, array $customerFieldNames, array $commonFields, array $mapFields)
    {
        $clueData = $clue->toArray();

        // 1. 业务字段硬编码映射（标准业务字段，字段名/语义可能不完全一致）
        $customerData = [];
        foreach ($mapFields as $field) {
            if (isset($clueData[$field]) && in_array($field, $customerFieldNames)) {
                $customerData[$field] = $clueData[$field];
            }
        }

        // 2. 动态映射自定义字段：线索表和客户表 system_field 中同名字段自动带入
        foreach ($commonFields as $field) {
            if (isset($clueData[$field]) && in_array($field, $customerFieldNames)) {
                $customerData[$field] = $clueData[$field];
            }
        }

        $customerData['status'] = 1;
        $customerData['to_kh_time'] = time();
        $customerData['create_time'] = time();
        $customerData['update_time'] = time();

        return $customerData;
    }

    /**
     * 唯一性验证：校验转化数据满足客户表 system_field 配置的 unique 约束
     * 必填验证 + 客户表唯一性验证 + 同批转化批次内查重，任一不满足抛出异常
     * @param array $customerData 构造好的客户数据
     * @param array $uniqueFields unique 字段配置（field => 显示名）
     * @param mixed $clue 线索记录
     * @param array $batchValues 同批已用值（field => [值 => true]）
     * @throws \Exception
     */
    private function validateCustomerUnique(array $customerData, array $uniqueFields, $clue, array &$batchValues)
    {
        $clueName = !empty($clue['name']) ? $clue['name'] : ('ID:' . $clue['id']);
        foreach ($uniqueFields as $field => $fieldName) {
            $value = isset($customerData[$field]) ? trim((string)$customerData[$field]) : '';
            // 必填验证：unique 约束字段值不能为空
            if ($value === '') {
                throw new \Exception(fy('Clue %s conversion failed: %s cannot be empty', [$clueName, $fieldName]));
            }
            // 唯一性验证：目标客户表中不能已存在相同值
            $exists = Db::name('crm_customer')->where($field, '=', $value)->count();
            if ($exists > 0) {
                throw new \Exception(fy('Clue %s conversion failed: %s value %s already exists', [$clueName, $fieldName, $value]));
            }
            // 批次内查重：同一次批量转化中不能出现相同值
            if (isset($batchValues[$field][$value])) {
                throw new \Exception(fy('Clue %s conversion failed: %s value %s duplicates within the batch', [$clueName, $fieldName, $value]));
            }
            $batchValues[$field][$value] = true;
        }
    }

    /**
     * 单条线索转化为客户（提取公共逻辑，供单条/批量转化复用）
     * @param mixed $clue 线索模型记录
     * @param array $customerFieldNames 客户表物理字段名
     * @param array $commonFields 线索与客户自定义字段同名字段
     * @param array $mapFields 业务字段映射清单
     * @return int 新客户ID
     * @throws \Exception
     */
    private function convertClueToCustomer($clue, array $customerFieldNames, array $commonFields, array $mapFields)
    {
        $customerData = $this->buildCustomerData($clue, $customerFieldNames, $commonFields, $mapFields);
        $clueData = $clue->toArray();

        $customerId = Db::name('crm_customer')->insertGetId($customerData);

        // 创建默认联系人
        $contactId = Db::name('crm_customer_contacts')->insertGetId([
            'customer_id'     => $customerId,
            'contact'         => !empty($clueData['contact']) ? $clueData['contact'] : $clueData['name'],
            'phone'           => $clueData['phone'] ?? '',
            'email'           => $clueData['email'] ?? '',
            'wechat'          => $clueData['wechat'] ?? '',
            'create_username' => $this->admin['username'],
            'owner_admin_id'  => $this->admin['admin_id'],
            'create_time'     => time(),
            'update_time'     => time(),
        ]);

        if ($contactId) {
            Db::name('crm_customer')->where('id', $customerId)->update(['contacts_id' => $contactId]);
        }

        // 更新当前线索为已转化（仅本条，避免批量时误更新其他线索）
        Db::name('crm_clue')->where('id', $clue['id'])->update([
            'status'           => 2,
            'to_customer_id'   => $customerId,
            'to_customer_time' => time(),
            'update_time'      => time(),
        ]);

        return $customerId;
    }

    /**
     * 归一化线索ID参数：兼容标量、逗号分隔字符串、数组
     * @param mixed $id
     * @return array
     */
    private function parseClueIds($id)
    {
        if (is_array($id)) {
            $ids = $id;
        } else {
            $ids = explode(',', (string)$id);
        }
        $ids = array_filter(array_map('intval', $ids), function ($v) {
            return $v > 0;
        });
        return array_values(array_unique($ids));
    }

    /**
     * @NodeAnotation(title="导入")
     */
    public function import()
    {
        if ($this->request->isPost()) {
            @ini_set("memory_limit", '-1');
            @ini_set('max_execution_time', '0');
            $params = $this->request->post();

            if (!$params['filepath']) {
                $this->error(fy("Parameter error"));
            }
            $filePath = $this->app->getRootPath() . 'public' . $params['filepath'];

            if (!is_file($filePath)) {
                $this->error(fy("The uploaded file was not found"));
            }

            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if (!in_array($ext, ['csv', 'xlsx'])) {
                $this->error(fy("Please select EXCEL format to import"));
            }
            if ($ext === 'xlsx') {
                $reader = new \OpenSpout\Reader\XLSX\Reader();
            } else {
                $reader = new \OpenSpout\Reader\CSV\Reader();
            }

            $insert = 0;
            try {
                $reader->open($filePath);

                $prefix = getDataBaseConfig('prefix');
                $fields = Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field` FROM `' . $prefix . 'system_field` WHERE (rule <> "" AND `form`=1 AND `table`="crm_clue" AND `editinput` is not null)  order BY `sort` ASC,id ASC');
                $rule = $arr_fields = [];
                foreach ($fields as $v) {
                    $name = $v['xsname'] ? $v['xsname'] : $v['name'];
                    $msg = !empty(trim($v['msg'])) ? '|' . $v['msg'] : '|' . $name;
                    $ruleKey = $v['field'] . $msg;
                    $rule[$ruleKey] = str_replace('unique', 'unique:crm_clue', str_replace(',', '|', $v['rule']));
                }

                $fields = Db::query('SELECT `field`,`formtype`,`option`,`lang` FROM `' . $prefix . 'system_field` WHERE `export`=1 AND `table`="crm_clue" order BY `sort` ASC,id ASC');
                foreach ($fields as $v) {
                    $arr_fields[$v['field']] = $v;
                }

                $fields = [];
                $insert = 0;
                $sql = '';

                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $index => $row) {
                        $data = [];
                        foreach ($row->getCells() as $column => $cell) {
                            $val = $cell->getValue();
                            $val = trim($val);
                            if ($index == 1) {
                                preg_match('/\(([a-zA-Z][a-zA-Z0-9_]*)\)$/', $val, $match);
                                if (!empty($match[1])) {
                                    $fields[$column] = $match[1];
                                } else {
                                    throw new \Exception(fy("Please use the correct import template"));
                                }
                            } else {
                                if (empty($val)) continue;
                                $data[$fields[$column]] = $val;
                            }
                        }

                        if (empty(array_filter($data))) {
                            continue;
                        }

                        $data['at_user'] = $this->admin['username'];
                        $data['update_time'] = $data['create_time'] = time();

                        if($params['pr_user']){
                            $data['pr_user']=$params['pr_user'];
                            $data['owner_admin_id'] = Db::name('admin')->cache('admin_username_'.$params['pr_user'],3600)->where(['username'=>$params['pr_user']])->value('admin_id');
                            $data['status']=0;
                        }else{
//                            没有选择负责人则入线索池
                            $data['pr_user']='';
                            $data['owner_admin_id']=0;
                            $data['status']=4;
                            $data['to_pool_time']=time();
                        }

                        try {
                            if ($rule) {
                                $this->validate($data, $rule);
                            }
                            foreach ($data as $k => $v) {
                                if (isset($arr_fields[$k]['formtype'])) {
                                    switch ($arr_fields[$k]['formtype']) {
                                        case 'datetime':
                                        case 'date':
                                            if (!is_numeric($v)) {
                                                $data[$k] = strtotime($v);
                                            }
                                            break;
                                        case 'select':
                                        case 'radio':
                                            $selectList = [];
                                            $option = explode(',', $arr_fields[$k]['option']);
                                            if ($option) {
                                                foreach ($option as $v1) {
                                                    $vv = explode(':', $v1);
                                                    if ($vv) {
                                                        $vv[0] = trim($vv[0]);
                                                        if (empty($vv[1])) $vv[1] = $vv[0];
                                                        $selectList[trim($vv[1])] = $vv[0];
                                                    }
                                                }
                                                if (isset($selectList[$v])) {
                                                    $data[$k] = $selectList[$v];
                                                }
                                            }
                                    }
                                }
                            }
                            $temp = Db::name('crm_clue')->fetchSql(true)->save($data) . ';';
                            if ($temp) {
                                $sql = $sql . $temp;
                                $insert++;
                                if ($insert % 600 == 0) {
                                    flush();
                                    ob_flush();
                                    \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
                                    $sql = '';
                                }
                            }
                        } catch (\Exception $e) {
                            if ($params['skip'] != 1) {
                                throw new \Exception($e->getMessage() . '，待插入的数据：' . var_export($data, true));
                            } else {
                                \think\facade\Log::write($e->getMessage(), 'error');
                                $sql = '';
                            }
                        }
                    }
                }
                $reader->close();

                if ($sql) {
                    \think\facade\Db::connect('mysql')->getPdo()->exec($sql);
                    $sql = '';
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
            if (!$insert) {
                $this->error(fy("No data was imported"));
            }
            $this->success(fy('%s imported successfully', [$insert]));
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        @ini_set("memory_limit", '-1');
        @ini_set('max_execution_time', '0');

        list($page, $limit, $where, $sort) = $this->buildTableParames();
        $scope = $this->request->get('scope', 1, 'trim');

        if ($scope == 2) {
            $adminIds = \app\service\AdminService::getViewAdminIds($this->admin);
            if (empty($adminIds)) {
                return json([
                    'code'  => 1,
                    'msg'   => '',
                    'count' => 0,
                    'data'  => [],
                ]);
            }
            if ($adminIds !== 'ALL') {
                $where[] = ['owner_admin_id', 'in', $adminIds];
            } else {
                $where[] = ['owner_admin_id', '<>', $this->admin['admin_id']];
            }
        } elseif ($scope == 3) {
            $adminIds = \app\service\AdminService::getViewAdminIds($this->admin, true);
            if (empty( $adminIds)) {
                $where[] = ['id', 'in', -1];
            } elseif ( $adminIds !== 'ALL') {
                $where[] = ['owner_admin_id', 'in',  $adminIds];
            }
        } elseif ($scope == 10) {
            $where[] = ['next_time', '>', 0];
            $where[] = ['next_time', '<', strtotime('tomorrow')];
            $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
        } elseif ($scope == 11) {
            $where[] = ['last_up_time', '>=', strtotime('today')];
            $where[] = ['last_up_time', '<', strtotime('tomorrow')];
            $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
        } elseif ($scope == 12) {
            $where[] = ['last_up_time', '=', 0];

            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];

        } else {
            $where[] = ['owner_admin_id', '=',$this->admin['admin_id']];
        }

        $str_fields = '`id`';
        $fields = Db::query('SELECT `field`, `name`, `xsname`, `width`, `rule`, `formtype`, `option` FROM `' . getDataBaseConfig('prefix') . 'system_field` WHERE (`export`=1 OR `list`=1) AND `table`="crm_clue" ORDER BY `sort` ASC, id ASC');

        $writer = new \OpenSpout\Writer\XLSX\Writer();

        $tempPath = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 755, true);
        }
        deleteFilesOlderThanDays($tempPath, 1);
        $tempFile = date('YmdHis') . 'uid' . $this->admin['admin_id'] . '_export.xlsx';
        $writer->openToFile($tempPath . $tempFile);

        $str_fields = '`id`';
        $cells = [];
        foreach ($fields as $k => $v) {
            $name = $v['xsname'] ? $v['xsname'] : $v['name'];
            $fields[$k]['xsname'] = $name;

            if ($v['field'] != 'id') {
                $str_fields = $str_fields . ',`' . $v['field'] . '`';
            }
            $cells[] = \OpenSpout\Common\Entity\Cell::fromValue($name);
        }

        if ($cells) {
            $singleRow = new \OpenSpout\Common\Entity\Row($cells);
            $writer->addRow($singleRow);
        }

        $cursor = $this->model->field(trim($str_fields, ','))->where($where)->order($sort)->cursor();

        foreach ($cursor as $item) {
            $rowData = [];
            foreach ($fields as $field) {
                $value = $item[$field['field']];
                $value = real_field_val($field, $value);
                $rowData[] = \OpenSpout\Common\Entity\Cell::fromValue($value);
            }
            if ($rowData) {
                $singleRow = new \OpenSpout\Common\Entity\Row($rowData);
                $writer->addRow($singleRow);
            }
        }

        $writer->close();
        $this->redirect(__MY_PUBLIC__ . '/temp/' . $tempFile);
    }

    /**
     * @NodeAnotation(title="获取导入模板")
     */
    public function getImportTpl()
    {
        $prefix = getDataBaseConfig('prefix');
        $fields = Db::query('SELECT `field`,`name`,`xsname`,`width`,`rule` FROM `' . $prefix . 'system_field` WHERE `export`=1 AND `table`="crm_clue" order BY `sort` ASC,id ASC');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $i = 0;
        foreach ($fields as $k => $v) {
            $name = $v['xsname'] ? $v['xsname'] : $v['name'];
            if ($i >= 26) {
                $cell = chr(65 + $i / 26 - 1) . chr(65 + $i % 26);
            } else {
                $cell = chr(65 + $i);
            }
            $spreadsheet->getActiveSheet()->getStyle($cell . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('cdf79e');
            $required = '';
            if (strpos($v['rule'], 'require') !== false) $required = '*';
            $sheet->getColumnDimension($cell)->setWidth($v['width'], 'px');
            $sheet->setCellValue($cell . '1', $required . $name . "({$v['field']})");
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=cluetpl.xlsx");
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

    /**
     * @NodeAnotation(title="转移")
     */
    public function alterPrUser()
    {
        $ids = $this->request->param('ids', $this->request->param('id'));
        $cus_lst = $this->model->field('id,name,pr_user,owner_admin_id')->where('id', 'in', $ids)->select();
        if ($cus_lst->isEmpty()) {
            $this->error(fy("Data does not exist"));
        }
        $owner_admin_ids = array_column($cus_lst->toArray(), 'owner_admin_id');
        $this->modifyPermissionsByIds($owner_admin_ids);

        if ($this->request->isAjax()) {
            $username = $this->request->param('username', '', 'trim');
            $type = $this->request->param('type', 1, 'intval');
            if (empty($username)) {
                $this->error(fy("The person in charge must choose"));
            }
            $idsArr = explode(",", $ids);

            $count = 0;
            $username_arr = explode(',', $username);
            $admin_count = count($username_arr);
            $i = 0;
            foreach ($idsArr as $value) {
                if ($type == 2) {
                    $username_val = $username_arr[mt_rand(0, $admin_count - 1)];
                } else {
                    $username_val = $username_arr[$i];
                    $i == ($admin_count - 1) ? $i = 0 : $i++;
                }

                $data['owner_admin_id'] = Db::name('admin')->cache('admin_username_' . $username_val, 1200)->where(['username' => $username_val])->value('admin_id');
                $data['pr_user'] = $username_val;
                $data['update_time'] = time();
                $result = $this->model->where(['id' => $value])->update($data);
                if ($result) {
                    $count++;
                }
            }
            if ($count > 0) {
                $this->success(fy("Transfer %s successfully", [$count]));
            } else {
                $this->error(fy("Failed"));
            }
        }

        $this->assign('cus_lst', $cus_lst);
        View::assign('ids', $ids);

        $adminResult = Db::name('admin')->where('is_open', '=', 1)->field('admin_id,username')->select();
        View::assign('adminResult', $adminResult);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="线索池")
     */
    public function pool()
    {
        $fields = \tools\Cache::zdy_fields('crm_clue');

        if ($this->request->isAjax()) {
            
            list($page, $limit, $where, $sort) = $this->buildTableParames();
            // 线索池：只显示 status=4
            $where[] = ['status', '=', 4];

            $count = $this->model
                ->where($where)
                ->count();

            $list = [];
            if ($count) {
                $field_str = empty($fields['field_str']) ? '*' : $fields['field_str'];
                if (empty($fields['field_str'])) {
                    $field_str = '*';
                } else {
                    $field_str_arr = explode(',', $fields['field_str']);
                    if (!in_array('id', $field_str_arr)) {
                        $field_str = 'id,' . $field_str;
                    }
                }

                $list = $this->model->field($field_str)
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select()->toArray();

                if ($this->admin['isphone'] == 0) {
                    foreach ($list as $key => $value) {
                        foreach ($fields['tels'] as $tel_key => $tel_value) {
                            if ($value[$tel_value]) {
                                $value[$tel_value] = mb_substr($value[$tel_value], 0, 3) . '****' . mb_substr($value[$tel_value], 7, 11);
                            }
                        }
                        $list[$key] = $value;
                    }
                }
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];

            return json($data);
        }

        $jscol_str = $fields['jscol_str'];

        $this->app->view->engine()->layout(false);
        $jscol_str = $this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str = str_replace(['":"{"', '"}"'], ['":{"', '"}'], $jscol_str);
        $this->assignconfig('cols_fields', json_decode('[' . $jscol_str . ']', true));
        View::assign('grabCountMsg', \app\service\CrmClueService::getGrabCount($this->system, $this->admin['admin_id'])['msg']);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="已转线索")
     */
    public function converted()
    {
        $where = "(`list` = 1 OR `field` IN ('to_customer_time')) AND `field` NOT IN ('pr_user')";
        $fields = \tools\Cache::zdy_fields('crm_clue',$where );

        if ($this->request->isAjax()) {
            
            list($page, $limit, $where, $sort) = $this->buildTableParames();
            $scope = $this->request->get('scope', 1, 'trim');

            // 核心过滤：仅查询已转线索
            $where[] = ['to_customer_id', '>', 0];

            if ($scope == 2) {
                // 团队的（下属的）
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }else{
                    $where[] = ['owner_admin_id', '<>', $this->admin['admin_id']];
                }
            } elseif ($scope == 3) {
                // 全部
                $adminIds = \app\service\AdminService::getViewAdminIds($this->admin, true);
                if (empty($adminIds)) {
                    return json([
                        'code'  => 1,
                        'msg'   => '',
                        'count' => 0,
                        'data'  => [],
                    ]);
                }
                if ($adminIds !== 'ALL') {
                    $where[] = ['owner_admin_id', 'in', $adminIds];
                }
            } else {
                // 我的
                $where[] = ['owner_admin_id', '=', $this->admin['admin_id']];
            }

            $count = $this->model
                ->where($where)
                ->count();

            $list = [];
            if ($count) {
                $field_str = empty($fields['field_str']) ? '*' : $fields['field_str'];
                if (empty($fields['field_str'])) {
                    $field_str = '*';
                } else {
                    $field_str_arr = explode(',', $fields['field_str']);
                    if (!in_array('id', $field_str_arr)) {
                        $field_str = 'id,' . $field_str;
                    }
                    if (!in_array('to_customer_time', $field_str_arr)) {
                        $field_str .= ',to_customer_time';
                    }
                }

                $list = $this->model->field($field_str)
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select()->toArray();

                if ($this->admin['isphone'] == 0) {
                    foreach ($list as $key => $value) {
                        foreach ($fields['tels'] as $tel_key => $tel_value) {
                            if ($value[$tel_value]) {
                                $value[$tel_value] = mb_substr($value[$tel_value], 0, 3) . '****' . mb_substr($value[$tel_value], 7, 11);
                            }
                        }
                        $list[$key] = $value;
                    }
                }
            }

            $data = [
                'code'  => 1,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];

            return json($data);
        }


        $jscol_str = $fields['jscol_str'];


        $this->app->view->engine()->layout(false);
        $jscol_str = $this->display($jscol_str);

        $this->app->view->engine()->layout($this->layout);
        $jscol_str = str_replace(['":"{"', '"}"'], ['":{"', '"}'], $jscol_str);
        $this->assignconfig('cols_fields', json_decode('[' . $jscol_str . ']', true));
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="移入线索池")
     */
    public function toPool()
    {
        $ids = $this->request->param('ids', $this->request->param('id'));
        if (empty($ids)) {
            $this->error('请选择线索');
        }
        
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        
        // 检查权限
        $clues = $this->model->whereIn('id', $ids)->where('status', '<>', 2)->select();
        if ($clues->isEmpty()) {
            $this->error(fy('No records found to move into the clue pool'));
        }
        
        $owner_admin_ids = array_column($clues->toArray(), 'owner_admin_id');
        $this->modifyPermissionsByIds($owner_admin_ids);
        
        $count = 0;
        foreach ($clues as $clue) {
            $updateData = [
                'status'      => 4,
                'pr_user'     => '',
                'owner_admin_id' => 0,
                'pr_user_bef' => $clue['pr_user'],
                'to_pool_time' => time(),
                'update_time' => time(),
            ];
            $result = $this->model->where('id', $clue['id'])->update($updateData);
            if ($result) {
                $count++;
            }
        }
        
        if ($count > 0) {
            $this->success(fy("Successfully moved %s clues to the pool", [$count]));
        } else {
            $this->error(fy("Failed"));
        }
    }

    public function sendEmail($id){
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        $this->modifyPermissionsByIds($row['owner_admin_id']);
        if ($this->request->isPost()) {
            $param = $this->request->param();

            // 验证输入参数
            $validate = new \think\Validate([
                'to_email' => 'require|email',
                'subject' => 'require|max:200',
                'email_content' => 'require'
            ], [
                'to_email.require' => '收件邮箱不能为空',
                'to_email.email' => '收件邮箱格式不正确',
                'subject.require' => '邮件标题不能为空',
                'subject.max' => '邮件标题不能超过200字符',
                'email_content.require' => '邮件内容不能为空'
            ]);

            if (!$validate->check($param)) {
                $this->error($validate->getError());
            }

            try {
                send_email($param['to_email'], $param['subject'],$param['email_content'],true,1,'customer');
            } catch (\Exception $e) {
                $this->error('邮件发送失败：' . $e->getMessage());
            }
            $this->success('邮件发送成功');
        }else{
//            emailtpl
            $emailtpl=\think\facade\Db::name('emailtpl')->field('id,name')->order('sort asc')->where('type','clue')->select();
            $this->assign('emailtpl',$emailtpl);
            $this->assign('id',$id);
            $this->assign('row',$row);
            return $this->fetch();
        }
    }

}
