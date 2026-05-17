<?php

namespace app\api\controller\crm;

use app\api\controller\Authority;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="crm_record_type")
 */
class RecordType extends Authority
{

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if (input('selectFields')) {
            $list = Db::name('crm_record_type')
                ->field('id, name')
                ->where('status', '=', 1)
                ->order('sort ASC, id DESC')
                ->select()
                ->toArray();

            $this->jsonSuccess('', $list);
        }

        $this->jsonError('缺少参数');
    }
}
