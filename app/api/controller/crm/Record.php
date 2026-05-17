<?php

namespace app\api\controller\crm;

use app\api\controller\Authority;
use think\App;
use think\facade\Db;
use think\facade\Request;

/**
 * @ControllerAnnotation(title="crm_record")
 */
class Record extends Authority
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\CrmRecord();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where) = $this->buildTableParames();
        $scope = $this->request->get('scope', 1, 'intval');
        $customer_id = $this->request->get('customer_id', 0, 'intval');

        if ($scope == 2) {
            $adminIds = (new \app\admin\model\Admin())->getViewAdminIds($this->admin);
            if (empty($adminIds)) {
                return json(['code' => 1, 'msg' => '', 'data' => ['rows' => [], 'count' => 0]]);
            }
            if ($adminIds !== 'ALL') {
                $where[] = ['admin_id', 'in', $adminIds];
            } elseif ($adminIds == 'ALL') {
                $where[] = ['admin_id', '<>', $this->admin['admin_id']];
            }
        } elseif ($scope == 3) {
            $adminIds = (new \app\admin\model\Admin())->getViewAdminIds($this->admin, true);
            if (empty($adminIds)) {
                return json(['code' => 1, 'msg' => '', 'data' => ['rows' => [], 'count' => 0]]);
            }
            if ($adminIds !== 'ALL') {
                $where[] = ['admin_id', 'in', $adminIds];
            }
        } else {
            $where[] = ['admin_id', '=', $this->admin['admin_id']];
        }

        if ($customer_id) {
            $where[] = ['customer_id', '=', $customer_id];
        }

        $count = $this->model->where($where)->count();
        $list = [];
        if ($count) {
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order('id DESC')
                ->select()
                ->toArray();
        }

        return json([
            'code' => 1,
            'msg'  => '', 'where'   => $where,
            'data' => ['rows' => $list, 'count' => $count],
        ]);
    }

    /**
     * @NodeAnotation(title="添加跟进记录")
     */
    public function add()
    {
        $data['customer_id'] = Request::param('customer_id', 0, 'intval');
        if (empty($data['customer_id'])) {
            $this->jsonError('缺少客户ID参数');
        }

        $customer_row = Db::name('crm_customer')->where(['id' => $data['customer_id']])->find();
        if (empty($customer_row)) {
            $this->jsonError('客户信息不存在');
        }

        if ($this->request->isPost()) {
            $data['admin_id'] = $this->admin['admin_id'];
            $data['khname'] = $customer_row['name'];
            $data['khphone'] = $customer_row['phone'];
            $data['pr_user'] = $this->admin['username'];
            $data['content'] = Request::param('content', '', 'trim');
            $data['attachs'] = Request::param('attachs', '', 'trim');
            $data['record_type'] = Request::param('record_type', '', 'trim');
            $data['create_time'] = time();

            // 更新客户跟进信息
            $genjin['last_up_records'] = $data['content'];
            $genjin['last_up_time'] = $data['create_time'];
            $next_time = Request::param('next_time', '', 'trim');
            $data['next_time'] = $genjin['next_time'] = $next_time ? strtotime($next_time) : 0;

            Db::name('crm_customer')->where(['id' => $data['customer_id']])->update($genjin);

            $result = Db::name('crm_record')->insert($data);
            $data['create_time'] = date("Y-m-d H:i", $data['create_time']);

            if ($result) {
                $this->jsonSuccess('提交成功', $data);
            } else {
                $this->jsonError('提交失败');
            }
        } else {
            $this->jsonError('请求方式错误');
        }
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $id = $this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        foreach ($row as $v) {
            $this->modifyPermissions($v['admin_id']);
        }

        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }
}
