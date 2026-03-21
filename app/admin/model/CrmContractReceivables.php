<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class CrmContractReceivables extends TimeModel
{

    protected $name = "crm_contract_receivables";

    protected $deleteTime = false;

    public function getCheckStatus()
    {
//        -1审核未通过0待审核、1草稿、2审核中、3审核通过  待完善状态'1'=>'草稿','2'=>'审核中',
//        return ['-1'=>'审核未通过','0'=>'待审核','1'=>'草稿','2'=>'审核中','3'=>'审核通过'];
        return ['-1'=>'审核未通过','0'=>'待审核','3'=>'审核通过'];

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
        return $this->hasOne('app\admin\model\CrmContract', 'id','contract_id');
    }

    protected function setReturnTimeAttr($value){
        if($value==''){
            return null;
        }
        return strtotime($value);
    }
    protected function getReturnTimeAttr($value){
        if($value==''){
            return null;
        }if(is_numeric($value)){
            return date('Y-m-d H:i',$value);
        }
        return $value;
    }

    /**
     * 根据规则自动生成编码
     * @param $prefix
     * @return string
     */
    public function autoNo($prefix){
        $replace_data=['[Y]'=>date('Y'),'[m]'=>date('m'),'[d]'=>date('d'),'[h]'=>date("H"),'[i]'=>date("i"),'[s]'=>date("s"),'[rand]'=>rand(100000,999999)];
        $numbering=str_replace(array_keys($replace_data),$replace_data,$prefix);
        $id=$this->where(['numbering'=>$numbering])->lock(true)->value('id');
//        保证合同编号唯一
        if($id){
            return  $this->autoNo($prefix);
        }
        return $numbering;
    }
    
    

}