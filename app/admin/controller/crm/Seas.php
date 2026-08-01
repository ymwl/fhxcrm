<?php
namespace app\admin\controller\crm;

use app\common\controller\AdminController;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Cell;


use think\facade\Db;
use think\facade\View;


class Seas  extends AdminController{
    //领取客户
    public function robClient(){
        $ids = parseIds();
//        技术QQ3623820285 开启事务,防止2人同时抢造成混乱
        if(empty($ids)){
            $this->error('请选择客户');
        }

        $res = \app\service\CrmCustomerService::getGrabCount($this->system, $this->admin['admin_id']);
        if(!$res['code']){
            $this->error($res['msg']);

        }

        if((isset($res['count']) && $res['count']<1) || (isset($res['count']) && is_array($ids) && $res['count']<count($ids))){

            $this->error(fy("You can choose to claim up to %s customers",[$res['count']]));

        }
        $allowCustomersNum=(new \app\admin\model\CrmCustomer())->allowCustomersNum($this->admin);
        if($allowCustomersNum['max_customers_num']>0){
//                大于零说明要验证
            $yx_c=$allowCustomersNum['yx_c'];
            if($yx_c<=0){

                $this->error(fy("The maximum number of customers you can currently have is full and cannot be claimed"));

            }
            if(is_array($ids) && $yx_c<count($ids)){
                $this->error(fy("You can currently choose to claim up to %s customers",[$yx_c]));
            }
        }


        Db::startTrans();
        try {
            //然后查询的时候加锁,
            //抢客户之前，先去判断是否可抢
            $ids = Db::name('crm_customer')->where(['id' => $ids])->where(['status'=> 2])->lock(true)->column('id');
            if ($ids){
                $data['to_kh_time'] = time();
                $data['status'] = 1;//0-线索，1客户，2公海
                $data['pr_user'] = $this->admin['username'];
                $data['owner_admin_id'] = $this->admin['admin_id'];
//                返回的是影响的条数
                $result = Db::name('crm_customer')->where(['id'=>$ids])->update($data);
                if ($result){
                    if(is_array($ids)){
                        $ids=implode(',',$ids);
                    }
                    Db::name('crm_grab')->insert(['admin_id'=>$this->admin['admin_id'],'customer_ids'=>$ids,'createtime'=>time(),'nums'=>$result]);
                    Db::commit();

                }else{
                    throw new \Exception(fy("Failed to claim"),0);
                }
            }else{
                throw new \Exception(fy("Sorry, the client has been snatched away"), 0);

            }

        }catch (\Throwable $t){
            Db::rollback();
            $this->error($t->getMessage());
        }catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());

        }
        $this->success(fy("Claim successful"));


    }





}
