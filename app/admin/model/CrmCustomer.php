<?php

namespace app\admin\model;

use think\facade\Db;
use think\Model;

class CrmCustomer extends Model
{

    protected $name = "crm_customer";

    protected $deleteTime = false;

    public function allowCustomersNum($admin){
        $map['a.admin_id'] =  $admin['admin_id'];
        $username=$admin['username'];
        $max_customers_num=Db::name('admin')->alias('a')->cache('max_customers_num_'.$username)
            ->join(config('database.connections.mysql.prefix').'auth_group ag','a.group_id = ag.id','left')
            ->where($map)
            ->value('ag.max_customers_num');
        $yx_c=0;
        if( $max_customers_num > 0){
            //            验证业务员的最大容量
            $cz_c=Db::name('crm_customer')->whereRaw('pr_user=:username AND status=1 AND `issuccess` = 0',['username'=>$username])->count();
            $yx_c=$max_customers_num-$cz_c;


        }
        return ['max_customers_num'=>$max_customers_num,'yx_c'=>$yx_c];
    }

    public function autoRecycle($system){
        if($system['kfrecycleday']>0){
            //    说明启用了客户回收 to_kh_time  genjing_success
            $kfrecycledaytime=strtotime("-{$system['kfrecycleday']} day");
            $where="`status`=1 and  IFNULL(`to_kh_time`,0) < {$kfrecycledaytime} and IFNULL(`last_up_time`,0) < {$kfrecycledaytime} and IFNULL(`create_time`,0) < {$kfrecycledaytime}";
            if(!$system['genjing_success']){
//                不执行则需要排除
                $where=$where.' AND `issuccess`=0';
            }
            $ids=Db::name('crm_customer')->where($where)->column('id');
            if($ids){
                $res=Db::name('crm_customer')->where(['id'=>$ids])->update(['status'=>2,'to_gh_time'=>time()]);
                if($res){
                    Db::name('admin_log')->insert([
                        'title'     => '未跟进回收',
                        'content'   => json_encode($ids),
                        'url'       => substr(request()->url(), 0, 1500),
                        'admin_id'  => 0,
                        'realname'  => '系统自动',
                        'useragent' => substr(request()->server('HTTP_USER_AGENT'), 0, 255),
                        'ip'        => getRealIp(),
                        'create_time'=>time()
                    ]);
                }
            }

        }
        if(isset($system['wchjhuishouday']) && $system['wchjhuishouday']>0){
            //    说明启用了未成交回收
            $kfrecycledaytime=strtotime("-{$system['wchjhuishouday']} day");
            $where="`status`=1 and  IFNULL(`to_kh_time`,0) < {$kfrecycledaytime} and IFNULL(`create_time`,0) < {$kfrecycledaytime} AND `issuccess`=0";
            $ids=Db::name('crm_customer')->where($where)->column('id');
            if($ids){
                $res=Db::name('crm_customer')->where(['id'=>$ids])->update(['status'=>2,'to_gh_time'=>time()]);
                if($res){
                    Db::name('admin_log')->insert([
                        'title'     => '未成交回收',
                        'content'   => json_encode($ids),
                        'url'       => substr(request()->url(), 0, 1500),
                        'admin_id'  => 0,
                        'realname'  => '系统自动',
                        'useragent' => substr(request()->server('HTTP_USER_AGENT'), 0, 255),
                        'ip'        => getRealIp(),
                        'create_time'=>time()
                    ]);
                }
            }

        }
    }

    public function get_next_url($row,$admin){
        $id=$row['id'];
        $sort=cookie('sort');
        $scope=cookie('scope');
        $where=[];
        $where[]=['id','<>',$id];
        $where[]=['status','=',1];
        if($sort){
            list($field,$type)=explode(' ',$sort);
            $field=trim($field);
            $type=trim($type);
            $row[$field]=$row[$field]?$row[$field]:0;
            if($type=='desc'){
//IFNULL(1,0)
//                $where[]=[Db::raw("IFNULL($field,0)"),'<=', $row[$field]];
                $where[]=[$field,'<=', $row[$field]];
            }else{
//                $where[]=[Db::raw("IFNULL($field,0)"),'>=', $row[$field]];
                $where[]=[$field,'>=', $row[$field]];
            }
            $sort=$sort.',id DESC';

        }else{
//            id desc
            $sort='id DESC';
            $where[]=['id','<',$id];
        }
        if($scope==2){
//                    展示下属的
            $adminLst=(new \app\admin\model\Admin())->getChildrenAdminName($admin);
            $where[] = ['pr_user', 'in',$adminLst ];
        }elseif($scope==3){
//                    展示下属的和自己的
            if($admin['group_id']>1){
                $adminLst=(new \app\admin\model\Admin())->getChildrenAdminName($admin,true);
                $where[] = ['pr_user', 'in', $adminLst];
            }
        }else{
//                    展示自己的
            $where[] = ['pr_user', '=', $admin['username']];
        }
        $next=$this->field('id')->where($where)->order($sort)->find();
        $next_id=0;
        if($next) $next_id=$next['id'];
        return $next_id;
    }



}
