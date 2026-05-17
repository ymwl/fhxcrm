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
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where, $sort) = $this->buildTableParames();

            $scope = $this->request->get('scope', 1, 'intval');

            // 只查看自己的提醒
            $where[] = ['admin_id', '=', $this->admin['admin_id']];

            // 根据 scope 筛选状态
            if ($scope == 1) {
                // 待提醒
                $where[] = ['status', '=', ReminderService::STATUS_PENDING];
            } elseif ($scope == 2) {
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

                // 获取关联信息
                foreach ($list as &$item) {
                    if ($item['related_type'] == 'customer' && $item['related_id']) {
                        $item['customer_name'] = Db::name('crm_customer')
                            ->where('id', $item['related_id'])
                            ->value('name');
                    } elseif ($item['related_type'] == 'contract' && $item['related_id']) {
                        $contract = Db::name('crm_contract')
                            ->where('id', $item['related_id'])
                            ->field('name, customer_id')
                            ->find();
                        $item['contract_name'] = $contract['name'] ?? '';
                        $item['customer_name'] = Db::name('crm_customer')
                            ->where('id', $contract['customer_id'] ?? 0)
                            ->value('name');
                    }
                    $item['type_text'] = $this->getTypeText($item['type']);
                    $item['status_text'] = $this->getStatusText($item['status']);
                }
            }

            $data = [
                'code' => 0,
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

        return $this->fetch();
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
        $id = $this->request->param('id');

        if (empty($id)) {
            $this->error('请选择要删除的数据');
        }

        $id = is_array($id) ? $id : explode(',', $id);

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
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'count' => 0]);
        }

        $service = new ReminderService();
        $count = $service->getPendingCount($this->admin['admin_id']);

        // 获取最新的5条提醒
        $list = $service->getUserReminders($this->admin['admin_id'], ReminderService::STATUS_PENDING, 5);

        return json([
            'code' => 1,
            'count' => $count,
            'list' => $list
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
