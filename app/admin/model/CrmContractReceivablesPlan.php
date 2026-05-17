<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\facade\Db;

class CrmContractReceivablesPlan extends TimeModel
{

    protected $name = "crm_contract_receivables_plan";

    protected $deleteTime = false;

    /**
     * 生成计划编号：合同编号-序号
     * @param int $contractId 合同ID
     * @return string
     */
    public function generatePlanNo($contractId)
    {
        // 获取合同编号
        $contractNo = Db::name('crm_contract')
            ->where('id', $contractId)
            ->value('numbering');

        if (!$contractNo) {
            $contractNo = 'HT' . date('Ymd');
        }

        // 查询该合同已有几期回款计划
        $count = $this->where('contract_id', $contractId)->count();
        $seq = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        return $contractNo . '-' . $seq;
    }

    public function getStatusList()
    {
        return ['0'=>'未回款','1'=>'已回款','2'=>'部分回款','3'=>'逾期',];
    }

    public function ownerAdmin()
    {
        return $this->hasOne('app\admin\model\Admin', 'admin_id','owner_admin_id');
    }

    public function crmCustomer()
    {
        return $this->hasOne('app\admin\model\CrmCustomer', 'id','customer_id');
    }
    public function crmContract()
    {
        return $this->hasOne('app\common\model\CrmContract', 'id','contract_id');
    }

}