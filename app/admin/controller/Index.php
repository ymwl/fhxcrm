<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use think\App;
use think\facade\Cache;
use think\facade\Lang;
use think\facade\View;
use think\facade\Db;

class Index extends AdminController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

    }
    public function index(){
        $this->app->view->engine()->layout(false);
        return View::fetch();
    }
    public function main(){
//        增加自动清理日志功能
        $auto_clear_logs=config('app.auto_clear_logs');
        if($auto_clear_logs>0){
            $logs_time=time()-$auto_clear_logs*86400;
            Db::name('admin_log')->where('create_time','<',$logs_time)->delete();
            Db::name('login_log')->where('createtime','<',$logs_time)->delete();
        }
        //0 线索，1客户，2公海
        $cluesCount = Db::name('crm_customer')->where(['status'=> 0])->count();
        $clientCount = Db::name('crm_customer')->where(['status'=> 1])->count();
        $liberumCount = Db::name('crm_customer')->where(['status'=> 2])->count();
        // 区别管理员和业务员

        View::assign('cluesCount', $cluesCount);
        View::assign('clientCount', $clientCount);
        View::assign('liberumCount', $liberumCount);


        //获取本周线索 ->whereTime('at_time', 'week')
        $cluesCount_week = Db::name('crm_customer')->where(['status'=> 0,'pr_user'=>$this->admin['username']])->count();
        //获取本月转客户数据 ->whereTime('to_kh_time', 'month')
        $clientCount_month = Db::name('crm_customer')->cache('pr_user_customerCount'.$this->admin['username'],60)->where(['status'=> 1,'pr_user'=>$this->admin['username']])->count();
        //获取今年公海数据 ->whereTime('to_gh_time', 'year')
        $liberumCount_year = Db::name('crm_customer')->where(['status'=> 2])->count();
        //成交数 TODO

        //月度排名（名）、月目标（元）、已成交（元）、完成率（%）、已成交（单）、提成点（%）， ->order('money_month DESC') ['a.`admin_id`','<>', 1],
        //管理员添加业绩设置权限。

        $prefix=getDataBaseConfig('prefix');

// 获取当月开始时间戳
        $startTimestamp = strtotime(date('Y-m-01 00:00:00'));
//// 获取当月结束时间戳
        $endTimestamp = strtotime(date('Y-m-t 23:59:59'));


        $userlist=Db::query("SELECT o.*,a.admin_id,a.username,COALESCE(a.mubiao,0) mubiao,a.ticheng FROM {$prefix}admin a LEFT JOIN (SELECT SUM(`money`) AS money_month,COUNT( `id` ) AS number_month,SUM( `freight` ) AS freight_month,pr_user FROM `{$prefix}crm_client_order` WHERE `status` = 1 AND create_time between '{$startTimestamp}' AND '$endTimestamp' GROUP BY `pr_user` ORDER BY money_month DESC LIMIT 5) o ON a.username=o.pr_user WHERE a.is_open=1 ORDER BY o.money_month DESC LIMIT 5");
        /* 1. 一行代码：直接拿到 admin_id 列（返回索引数组） */
        $adminIds = array_column($userlist, 'admin_id');
        /* 2. 如果需要字符串 */
        $adminStr = implode(',', $adminIds);
//合同签约金额
        $ht_money_month_lst=Db::query("SELECT sum(money) as ht_money_month,owner_admin_id  from {$prefix}crm_contract where check_status=3 and sign_time between '{$startTimestamp}' AND '{$endTimestamp}' AND owner_admin_id in ({$adminStr})  GROUP BY `owner_admin_id`");
        if($ht_money_month_lst){
            $ht_money_month_lst=array_column($ht_money_month_lst, 'ht_money_month', 'owner_admin_id');
        }


//合同回款金额
        $hk_money_month_lst=Db::query("SELECT sum(money) as hk_money_month,owner_admin_id  from {$prefix}crm_contract_receivables where check_status=3 and return_time between '{$startTimestamp}' AND '{$endTimestamp}' AND owner_admin_id in ({$adminStr}) GROUP BY `owner_admin_id`");
        if($hk_money_month_lst){
            $hk_money_month_lst=array_column($hk_money_month_lst, 'hk_money_month', 'owner_admin_id');
        }

        $userlist=array_map(function($item) use($ht_money_month_lst,$hk_money_month_lst){
            $item['ht_money_month']=isset($ht_money_month_lst[$item['admin_id']])?$ht_money_month_lst[$item['admin_id']]:0;
            $item['hk_money_month']=isset($hk_money_month_lst[$item['admin_id']])?$hk_money_month_lst[$item['admin_id']]:0;
            return $item;
        },$userlist);

        //所有业务员
//        foreach ($userlist as $key => $value) {
            /*$wheretoday = [];
            $wheretoday['pr_user'] = $value['username'];
            $wheretoday['status'] = '审核通过';
            $monthOrder = Db::name('crm_client_order')->field('SUM(o.`money`) AS money_month,COUNT(o.`id`) AS number_month,SUM(o.`freight`) AS freight_month')
                            ->where($wheretoday)
                            ->whereTime('o.create_time','month')
                            ->find();*/

        /*    $value['money_month'] = $monthOrder['money_month']?$monthOrder['money_month']:0;
            if ($value['mubiao']>0) {
                $value['wanchenglv'] = round($monthOrder['money_month']/$value['mubiao']*100,2);
            }else{
                $value['wanchenglv']=0;
            }
            $value['number_month']=$monthOrder['number_month'];
            $value['freight_month']=$monthOrder['freight_month']?$monthOrder['freight_month']:0;
            $userlist[$key] = $value;*/
//        }

        // 数组排序
//        array_multisort(array_column($userlist,'money_month'),SORT_DESC,$userlist);
        View::assign('userlist', $userlist);

        //本人跟进动态
        //最近跟进动态
        $result = Db::name('crm_customer')
            ->alias('c')
            ->join('crm_record r','r.customer_id = c.id')
            ->join('admin a','r.admin_id = a.admin_id')
            ->field('c.id,a.username,a.avatar,c.name,r.content,r.create_time')
            ->order('r.id desc')
            ->where(['c.pr_user'=> $this->admin['username']])
            ->limit(10)->select();
        View::assign('result', $result);


      	$strTimeToString = "000111222334455556666667";
        $strWenhou = array('夜深了，','凌晨了，','早上好！','上午好！','中午好！','下午好！','晚上好！','夜深了，');
        //echo $strWenhou[(int)$strTimeToString[(int)date('G',time())]];
        View::assign('wenhou', '尊敬的管理员'. $strWenhou[(int)$strTimeToString[(int)date('G',time())]]);



        View::assign('cluesCount_week', $cluesCount_week);
        View::assign('clientCount_month', $clientCount_month);
        View::assign('liberumCount_year', $liberumCount_year);
        // 获取待办事项
        //今日已跟进客户*个，未跟进*个，跟进率*%
        //last_up_time

        $wheretoday = [];
        $wheretoday[] = ['pr_user','=',$this->admin['username']];
        //$wheretoday['status'] = 1;
        //$wheretoday['issuccess'] = -1;
        $today_followed_count = Db::name('crm_customer')->where($wheretoday)->whereTime('last_up_time','today')->count();//今日已经跟进个数

//        not followed

        View::assign('today_followed_count', $today_followed_count);

        // 提醒列表
//        $today_tiixng = Db::name('crm_customer')->where($wheretoday)->whereTime('next_time','today')->select();
//        没有跟进的列表
        $today_tiixng = Db::name('crm_customer')->field('`id`,`pr_user`,`name`,`next_time`')->where($wheretoday)->whereNotNull('next_time')->where('next_time','<>',0)->whereTime('next_time','<=','tomorrow -1second')->order('next_time asc')->limit(10)->select()->toArray();
//        待跟进数
        $not_followed_count=Db::name('crm_customer')->where($wheretoday)->whereNotNull('next_time')->where('next_time','<>',0)->whereTime('next_time','<=','tomorrow -1second')->count();
        View::assign('not_followed_count',$not_followed_count);
        $all_count=($today_followed_count+$not_followed_count);
        if ($all_count > 0) {
            $genjinlv = ($today_followed_count/$all_count)*100;
        }else{
            $genjinlv = 0;
        }
        View::assign('genjinlv', round($genjinlv,2));
        View::assign('today_tiixng', $today_tiixng);
        return $this->fetch();
    }


    public function clear(){
        Cache::clear();
        $this->success(lang('Clear cache successful'));

    }
    private function _deleteDir($R)
    {
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            if ($item != '.' and $item != '..') {
                if (is_dir($R . '/' . $item)) {
                    $this->_deleteDir($R . '/' . $item);
                } else {
                    if (!unlink($R . '/' . $item))
                        die('error!');
                }
            }
        }
        closedir($handle);
        return rmdir($R);
    }

    //退出登陆
    public function logout(){
        session(null);
        session('referer',$_SERVER["HTTP_REFERER"]);
        $this->redirect(myurl('login/index'));
    }


    /**
     * 切换语言
     */
    public function language()
    {
        $lang = input('get.lang');
        if (!in_array($lang, config('lang.allow_lang_list'))) {
            $lang = 'zh-cn';
        }
        cookie(config('lang.cookie_var'), $lang);
        Cache::clear();
        $this->success(lang('The change succeeded'));

    }
}
