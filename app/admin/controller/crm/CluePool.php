<?php
namespace app\admin\controller\crm;

use app\common\controller\AdminController;
use think\facade\Db;

class CluePool extends AdminController
{
    // 领取线索
    public function rob()
    {
        $ids = $this->request->param('id');
        if(empty($ids)){
            $this->error(fy('请选择线索'));
        }

        $res = \app\service\CrmClueService::getGrabCount($this->system, $this->admin['admin_id']);
        if(!$res['code']){
            $this->error($res['msg']);
        }

        if((isset($res['count']) && $res['count']<1) || (isset($res['count']) && is_array($ids) && $res['count']<count($ids))){
            $this->error(fy("You can choose to claim up to %s clues",[$res['count']]));
        }

        Db::startTrans();
        try {
            // 行锁查询，防止并发抢
            $ids = Db::name('crm_clue')->where(['id' => $ids])->where(['status'=> 4])->lock(true)->column('id');
            if ($ids){
                $data['status'] = 0; // 待跟进
                $data['pr_user'] = $this->admin['username'];
                $data['owner_admin_id'] = $this->admin['admin_id'];
                $data['update_time'] = time();
                $result = Db::name('crm_clue')->where(['id'=>$ids])->update($data);
                if ($result){
                    if(is_array($ids)){
                        $ids = implode(',', $ids);
                    }
                    Db::name('crm_clue_grab')->insert([
                        'admin_id'   => $this->admin['admin_id'],
                        'clue_ids'   => $ids,
                        'createtime' => time(),
                        'nums'       => $result
                    ]);
                    Db::commit();
                }else{
                    throw new \Exception(fy("Failed to claim"), 0);
                }
            }else{
                throw new \Exception(fy("Sorry, the clue has been snatched away"), 0);
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
