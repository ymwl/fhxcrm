<?php

namespace app\common\model;

class CrmContract extends TimeModel
{

    protected $name = "crm_contract";

    protected $deleteTime = false;




    /**
     * 根据规则自动生成编码
     * @param $prefix
     * @return int|mixed|string
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



    public function ownerAdmin()
    {
        return $this->hasOne('app\admin\model\Admin', 'admin_id','owner_admin_id');
    }

    public function crmCustomer()
    {
        return $this->hasOne('app\admin\model\CrmCustomer', 'id','customer_id');
    }
    public function crmBusiness()
    {
        return $this->hasOne('app\admin\model\CrmBusiness', 'id','business_id');
    }

    //    合同默认字段
    public function defaultField(){
        return [
            ['field'=>'id','name'=>'ID','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'name','name'=>'合同名称','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'name','name'=>'合同编号','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'customer_signer','name'=>'客户签约人','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'sign_time','name'=>'签约时间','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'date','option'=>''],
            ['field'=>'money','name'=>'合同金额','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],

            ['field'=>'company_signer','name'=>'公司签约人','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'start_time','name'=>'合同生效时间','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'date','option'=>''],
            ['field'=>'end_time','name'=>'合同到期时间','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'date','option'=>'']

        ];
    }



}