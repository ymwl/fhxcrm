<?php

namespace app\admin\controller\crm;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use think\facade\View;

/**
 * @ControllerAnnotation(title="crm_clue_record")
 */
class ClueRecord extends AdminController
{
    public $sort_by = 'id';
    public $sort_order = 'DESC';

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\CrmClueRecord();
    }

    /**
     * @NodeAnotation(title="线索跟进列表")
     */
    public function index()
    {
        $clue_id = $this->request->param('clue_id', 0, 'intval');

        if ($this->request->isAjax()) {
            list($page, $limit, $where, $sort) = $this->buildTableParames();
            
            // 按线索ID过滤
            if ($clue_id) {
                $where[] = ['clue_id', '=', $clue_id];
            }

            // scope 筛选
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
                    $where[] = ['admin_id', 'in', $adminIds];
                }else{
                    $where[] = ['admin_id', '<>', $this->admin['admin_id']];
                }
            } elseif ($scope == 3) {
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
                    $where[] = ['admin_id', 'in', $adminIds];
                }
            } else {
                $where[] = ['admin_id', '=', $this->admin['admin_id']];
            }

            $count = $this->model->where($where)->count();
            $list = [];
            if ($count) {
                $list = $this->model->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
            }

            $data = ['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list];
            return json($data);
        }

        // 加载跟进类型
        $recordTypes = Db::name('crm_record_type')->where('status', 1)->column('name');
        $this->assignconfig('record_types', $recordTypes);
        
        $this->assign('clue_id', $clue_id);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="写跟进")
     */
    public function add()
    {
        $clue_id = $this->request->param('clue_id', 0, 'intval');
        
        if ($this->request->isPost()) {
            $post = $this->request->post();
            
            // 获取线索信息
            $clue = Db::name('crm_clue')->find($clue_id);
            if (!$clue) {
                $this->error(fy('线索不存在'));
            }

            // 检查权限：只有线索负责人可以写跟进
            $this->modifyPermissionsByIds($clue['owner_admin_id']);

            $data = [
                'clue_id'    => $clue_id,
                'clue_name'  => $clue['name'],
                'clue_phone' => $clue['phone'] ?? '',
                'admin_id'   => $this->admin['admin_id'],
                'pr_user'    => $this->admin['username'],
                'content'    => $post['content'] ?? '',
                'record_type'=> $post['record_type'] ?? '',
                'next_time'  => isset($post['next_time']) ? strtotime($post['next_time']) : 0,
                'attachs'    => $post['attachs'] ?? '',
                'create_time'=> time(),
            ];

            try {
                $result = $this->model->save($data);
                
                // 同步更新线索表
                $clueUpdate = [
                    'last_up_records' => $data['content'],
                    'last_up_time' => time(),
                    'next_time'    => $data['next_time'],
                    'update_time'  => time(),
                ];
                // 非已转化和无效的状态改为跟进中
                if (!in_array($clue['status'], [2, 3])) {
                    $clueUpdate['status'] = 1;
                }
                Db::name('crm_clue')->where('id', $clue_id)->update($clueUpdate);
                
            } catch (\Exception $e) {
                $this->error(fy('Save failed') . ':' . $e->getMessage());
            }
            
            $result ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

        $this->assign('clue_id', $clue_id);
        
        // 加载跟进类型
        $recordTypes = Db::name('crm_record_type')->where('status', 1)->select();
        $this->assign('recordTypes', $recordTypes);
        
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除跟进")
     */
    public function delete()
    {
        $id = parseIds();
        $this->checkPostRequest();
        
        $admin_ids = $this->model->whereIn('id', $id)->column('DISTINCT admin_id');
        $this->modifyPermissionsByIds($admin_ids);

        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));

        try {
            $save = $this->model->whereIn('id', $id)->delete();
        } catch (\Exception $e) {
            $this->error(fy('Delete failed'));
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }
}
