<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;
use app\admin\service\ReminderService;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="crm_reminder")
 */
class Reminder extends AdminController
{
    protected $relationSearch = false;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\CrmReminder();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            
            list($page, $limit, $where, $sort) = $this->buildTableParames();

            $scope = $this->request->get('scope', 'pending', 'trim');

            // 只查看自己的提醒
            $where[] = ['admin_id', '=', $this->admin['admin_id']];

            // 根据 scope 筛选状态
            if ($scope == 'pending') {
                // 待提醒
                $where[] = ['status', '=', ReminderService::STATUS_PENDING];
            } elseif ($scope == 'read') {
                // 已读
                $where[] = ['status', '=', ReminderService::STATUS_READ];
            }

            $count = Db::name('crm_reminder')->where($where)->count();
            $list = [];

            if ($count > 0) {
                $list = Db::name('crm_reminder')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select()
                    ->toArray();

                // 批量获取关联信息（避免 N+1 查询）
                $list = $this->fillRelatedInfo($list);
            }

            $data = [
                'code' => 1,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }

        // 获取待处理提醒数量
        $service = new ReminderService();
        $pendingCount = $service->getPendingCount($this->admin['admin_id']);
        $this->assignconfig('pendingCount', $pendingCount);
        $this->assignconfig('scope', $this->request->get('scope', 'pending', 'trim'));

        return $this->fetch();
    }

    /**
     * 批量填充关联信息（优化 N+1 查询）
     * 支持 customer/contract/receivables_plan/contact 四种关联类型
     */
    private function fillRelatedInfo($list)
    {
        // 按 related_type 分组收集 related_id
        $customerIds = [];
        $contractIds = [];
        $planIds = [];
        $contactIds = [];

        foreach ($list as $item) {
            if (empty($item['related_id'])) continue;
            switch ($item['related_type']) {
                case 'customer':
                    $customerIds[] = $item['related_id'];
                    break;
                case 'contract':
                    $contractIds[] = $item['related_id'];
                    break;
                case 'receivables_plan':
                    $planIds[] = $item['related_id'];
                    break;
                case 'contact':
                    $contactIds[] = $item['related_id'];
                    break;
            }
        }

        // 批量查询关联数据
        $customerMap = [];
        if ($customerIds) {
            $customerMap = Db::name('crm_customer')
                ->whereIn('id', array_unique($customerIds))
                ->column('name', 'id');
        }

        $contractMap = [];
        $contractCustomerIds = [];
        if ($contractIds) {
            $contracts = Db::name('crm_contract')
                ->whereIn('id', array_unique($contractIds))
                ->column('name,customer_id', 'id');
            $contractMap = $contracts;
            foreach ($contracts as $c) {
                if (!empty($c['customer_id'])) {
                    $contractCustomerIds[] = $c['customer_id'];
                }
            }
            // 补充查询合同关联的客户名
            if ($contractCustomerIds) {
                $extraCustomers = Db::name('crm_customer')
                    ->whereIn('id', array_unique($contractCustomerIds))
                    ->column('name', 'id');
                $customerMap = $customerMap + $extraCustomers;
            }
        }

        $planMap = [];
        if ($planIds) {
            $planMap = Db::name('crm_contract_receivables_plan')
                ->alias('p')
                ->join('crm_customer c', 'p.customer_id = c.id', 'left')
                ->whereIn('p.id', array_unique($planIds))
                ->column('c.name', 'p.id');
        }

        $contactMap = [];
        if ($contactIds) {
            $contactMap = Db::name('crm_customer_contacts')
                ->alias('cc')
                ->join('crm_customer c', 'cc.customer_id = c.id', 'left')
                ->whereIn('cc.id', array_unique($contactIds))
                ->column('c.name', 'cc.id');
        }

        // 填充关联信息
        foreach ($list as &$item) {
            $item['customer_name'] = '';
            $item['contract_name'] = '';

            if (!empty($item['related_id'])) {
                switch ($item['related_type']) {
                    case 'customer':
                        $item['customer_name'] = $customerMap[$item['related_id']] ?? '';
                        break;
                    case 'contract':
                        $contract = $contractMap[$item['related_id']] ?? null;
                        if ($contract) {
                            $item['contract_name'] = $contract['name'] ?? '';
                            $item['customer_name'] = $customerMap[$contract['customer_id'] ?? 0] ?? '';
                        }
                        break;
                    case 'receivables_plan':
                        $item['customer_name'] = $planMap[$item['related_id']] ?? '';
                        break;
                    case 'contact':
                        $item['customer_name'] = $contactMap[$item['related_id']] ?? '';
                        break;
                }
            }

            $item['type_text'] = $this->getTypeText($item['type']);
            $item['status_text'] = $this->getStatusText($item['status']);
        }

        return $list;
    }

    /**
     * 获取提醒类型文本
     */
    private function getTypeText($type)
    {
        $types = [
            ReminderService::TYPE_FOLLOW_UP => '跟进提醒',
            ReminderService::TYPE_CONTRACT_EXPIRE => '合同到期',
            ReminderService::TYPE_RECEIVABLES => '回款提醒',
            ReminderService::TYPE_BIRTHDAY => '生日提醒',
        ];
        return $types[$type] ?? '未知类型';
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status)
    {
        $statuses = [
            ReminderService::STATUS_PENDING => '待处理',
            ReminderService::STATUS_SENT => '已发送',
            ReminderService::STATUS_READ => '已读',
        ];
        return $statuses[$status] ?? '未知状态';
    }

    /**
     * @NodeAnotation(title="标记已读")
     */
    public function markRead()
    {
        $this->checkPostRequest();
        $id = $this->request->param('id', 0, 'intval');

        if (empty($id)) {
            $this->error('参数错误');
        }

        $service = new ReminderService();
        $result = $service->markAsRead($id, $this->admin['admin_id']);

        if ($result) {
            $this->success('标记成功');
        } else {
            $this->error('标记失败');
        }
    }

    /**
     * @NodeAnotation(title="全部已读")
     */
    public function markAllRead()
    {
        $this->checkPostRequest();

        $service = new ReminderService();
        $count = $service->markAllAsRead($this->admin['admin_id']);

        $this->success("成功标记 {$count} 条提醒为已读");
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $this->checkPostRequest();
        $id = $this->request->param('ids', $this->request->param('id'));

        if (empty($id)) {
            $this->error('请选择要删除的数据');
        }

        $id = is_array($id) ? $id : explode(',', $id);
        // 安全过滤：确保所有 id 为正整数
        $id = array_filter(array_map('intval', $id));
        if (empty($id)) {
            $this->error('参数格式错误');
        }

        // 只能删除自己的提醒
        $count = Db::name('crm_reminder')
            ->whereIn('id', $id)
            ->where('admin_id', $this->admin['admin_id'])
            ->delete();

        if ($count > 0) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取未读提醒数量（用于头部消息通知）
     */
    public function getUnreadCount()
    {
        $service = new ReminderService();
        $count = $service->getPendingCount($this->admin['admin_id']);

        // 获取最新的5条提醒
        $list = $service->getUserReminders($this->admin['admin_id'], ReminderService::STATUS_PENDING, 5);

        return json([
            'code' => 1,
            'count' => $count,
            'data' => $list
        ]);
    }

    /**
     * @NodeAnotation(title="详情")
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');

        if (empty($id)) {
            $this->error('参数错误');
        }

        $reminder = Db::name('crm_reminder')
            ->where('id', $id)
            ->where('admin_id', $this->admin['admin_id'])
            ->find();

        if (empty($reminder)) {
            $this->error('提醒不存在');
        }

        // 自动标记为已读
        if ($reminder['status'] == ReminderService::STATUS_PENDING) {
            $service = new ReminderService();
            $service->markAsRead($id, $this->admin['admin_id']);
        }

        // 获取关联信息
        if ($reminder['related_type'] == 'customer' && $reminder['related_id']) {
            $customer = Db::name('crm_customer')
                ->where('id', $reminder['related_id'])
                ->find();
            $this->assign('customer', $customer);
        } elseif ($reminder['related_type'] == 'contract' && $reminder['related_id']) {
            $contract = Db::name('crm_contract')
                ->alias('c')
                ->join('crm_customer cu', 'c.customer_id = cu.id', 'left')
                ->where('c.id', $reminder['related_id'])
                ->field('c.*, cu.name as customer_name')
                ->find();
            $this->assign('contract', $contract);
        }

        $reminder['type_text'] = $this->getTypeText($reminder['type']);
        $reminder['status_text'] = $this->getStatusText($reminder['status']);

        $this->assign('row', $reminder);
        return $this->fetch();
    }
}
