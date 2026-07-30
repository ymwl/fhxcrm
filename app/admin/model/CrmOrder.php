<?php
namespace app\admin\model;

use think\Model;

class CrmOrder extends Model
{
    protected $deleteTime = false;
    public function getStatusList()
    {
        return ['-1'=>'审核失败','0'=>'待审核','1'=>'审核通过'];
    }


    /**
     * 根据规则自动生成编码
     * @param $prefix
     * @return int|mixed|string
     */
    public function autoNo($prefix){
        $replace_data=['[Y]'=>date('Y'),'[m]'=>date('m'),'[d]'=>date('d'),'[h]'=>date("H"),'[i]'=>date("i"),'[s]'=>date("s"),'[rand]'=>rand(100000,999999)];
        $orderno=str_replace(array_keys($replace_data),$replace_data,$prefix);
        $id=$this->where(['orderno'=>$orderno])->lock(true)->value('id');
//        保证合同编号唯一
        if($id){
            return  $this->autoNo($prefix);
        }
        return $orderno;
    }

//    订单默认字段
    public function defaultField(){
        return [
            ['field'=>'id','name'=>'ID','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'orderno','name'=>'订单号','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'cname','name'=>'客户名称','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'ccontact','name'=>'联系人','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'cphone','name'=>'客户电话','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'address','name'=>'收货地址','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'pr_user','name'=>'负责人','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'money','name'=>'金额','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'freight','name'=>'运费','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'','option'=>''],
            ['field'=>'status','name'=>'订单状态','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'radio','option'=>'-1:审核失败,0:待审核,1:审核通过'],
            ['field'=>'create_time','name'=>'订单创建时间','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'datetime','option'=>''],
            ['field'=>'update_time','name'=>'订单更新时间','xsname'=>'','width'=>100,'rule'=>'','formtype'=>'datetime','option'=>'']
        ];
    }
}
