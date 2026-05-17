<?php

namespace app\admin\controller\analysis;

use app\common\controller\AdminController;

use think\App;
use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\exception\ValidateException;
use think\exception;
use think\facade\Request;
use think\facade\View;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;


class Admin extends AdminController
{

    public $customerModel;
    public $sort_by = 'admin_id';
    public $sort_order = 'DESC';
    protected $sort = [
        'admin_id'   => 'DESC',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->customerModel = new \app\admin\model\CrmCustomer();
        $this->model = new \app\admin\model\Admin();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if ($this->request->param('ajax')) {
                return $this->getOrderEchartData();
            }


            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->where($where)
                    ->field('admin_id,username')
                    ->order($sort)
                    ->page($page, $limit)
                    ->select()->toArray();
                foreach ($list as &$row) {
                    //获取新增客户总量
                    $row['customer_count'] = $this->customerModel->where('pr_user', $row['username'])->count();
                    $row['deal_count'] = $this->customerModel->where('pr_user', $row['username'])->where('issuccess', 1)->count();//成交客户
                    $row['deal_rate'] = ($row['customer_count'] != 0 ? round($row['deal_count'] / $row['customer_count'] * 100, 2) : 0) . "%";//成交率
                }
            }


            $data = [
                'code'  =>0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }

        $this->assignconfig('erchart', $this->getOrderEchartData(false));

        return $this->fetch();
    }

    /**
     * 获取最近增量和成交量
     * @return bool
     */
    private function getOrderEchartData($is_ajax = true)
    {

        $date_range = $this->request->param('date_range', '', 'trim');
        $startDate=$endDate=null;
        if($date_range){
            list($startDate, $endDate) = explode(' - ', $date_range);
        }

        $owner_admin_id = $this->request->param('admin_id','', 'intval');
        // 生成查询的开始和结束时间，默认取30日
        if(!is_numeric($startDate) && $startDate){
            $starttime = strtotime($startDate);
        }
        if(!is_numeric($endDate) && $endDate){
            $endtime = strtotime($endDate);
        }
        $isnotrangeDate = empty($starttime) && empty($endtime);
        $nearly = '30';
        if ($isnotrangeDate) {
            $endtime = time();
            $nearly -= 1;
            $starttime = strtotime("-{$nearly} day");  // 最近30天日期
        } elseif ($starttime > $endtime) {
            $this->error = '起始时间要小于终止时间';
            return false;
        }
        list($format,$column)=\tools\Hs::format_lx_time($starttime,$endtime);
        $where=[];
        //客户增量
        if ($owner_admin_id) {
            $where[]=['owner_admin_id','=',$owner_admin_id];

        }
        $lists = $this->customerModel->where($where)->where('to_kh_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(to_kh_time), "' . $format . '") AS add_date')
            ->group('add_date')->fetchSql(false)
            ->select();

        //客户成交量
        $listsd = $this->customerModel->where($where)->where('success_time', 'between time', [$starttime, $endtime])
            ->field('COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(success_time), "' . $format . '") AS add_date')
            ->group('add_date')->fetchSql(false)
            ->select();
//        var_dump($lists );
//        var_dump($listsd );
//        exit();




        $c_count = $d_count = array_fill_keys($column, 0);

        foreach ($lists as $k => $v) {
            $c_count[$v['add_date']] = $v['nums'];//客户增量
        }

        foreach ($listsd as $k => $v) {
            $d_count[$v['add_date']] = $v['nums'];//客户成交量
        }

        $result = [
            'date' => array_keys($c_count),
            'd_count' => array_values($d_count),
            'c_count' => array_values($c_count),
        ];

        $data = [
            'date' => $result['date'],
            'data' => [
                "客户增量" => $result['d_count'],
                "客户成交量" => $result['c_count'],
            ],
        ];
        if ($is_ajax) {
            $this->success('', '', [
                'order' => $data
            ]);
        } else {
            return $data;
        }

    }
//跟进记录分析
    public function record()
    {
        if ($this->request->isAjax()) {
            if ($this->request->param('ajax') == '1') {
                return $this->getRecordEchartData();
            } elseif ($this->request->param('ajax') == '2') {
                return $this->getRecordTypeEchartData();
            }

            $recordModel = new \app\admin\model\CrmRecord();
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();

            $list = $this->model
                ->where($where)
                ->field('admin_id,username')
                ->order($sort)
                ->page($page, $limit)
                ->select();
            foreach ($list as &$row) {
                //跟进总次数
                $row['record_count'] = $recordModel->where('admin_id', $row->admin_id)->count();
                $row['customer_count'] = $recordModel->where('admin_id', $row->admin_id)->group('customer_id')->count();//跟进客户数
            }
            $result = ['code'  =>0, 'msg'   => '',"count" => $count, "data" => $list];
            return json($result);
        }

        $erchart = [
            'record' => $this->getRecordEchartData(false),
            'record_type' => $this->getRecordTypeEchartData(false)

        ];
        $this->assignconfig('erchart' ,$erchart);
        return $this->fetch();
    }

    /**
     * 跟进趋势
     * @return bool
     */
    private function getRecordEchartData($is_ajax = true)
    {
        $date_range = $this->request->param('date_range', '', 'trim');
        $startDate=$endDate=null;
        if($date_range){
            list($startDate, $endDate) = explode(' - ', $date_range);
        }

        $owner_admin_id = $this->request->param('admin_id','', 'intval');

        $recordModel = new \app\admin\model\CrmRecord();
        $data=$recordModel->getRecordEchartData($startDate,$endDate, $owner_admin_id);
        if ($is_ajax) {
            $this->success('', '', [
                'record' => $data
            ]);
        } else {
            return $data;
        }
    }

    /**
     * 跟进分析
     * @param bool $is_ajax
     * @return mixed
     * @throws \think\Exception
     */
    private function getRecordTypeEchartData($is_ajax = true)
    {
        $date_range = $this->request->param('date_range', '', 'trim');
        $startDate=$endDate=null;
        if($date_range){
            list($startDate, $endDate) = explode(' - ', $date_range);
        }

        $owner_admin_id = $this->request->param('admin_id','', 'intval');
        // 生成查询的开始和结束时间，默认取30日
        if(!is_numeric($startDate) && $startDate){
            $starttime = strtotime($startDate);
        }
        if(!is_numeric($endDate) && $endDate){
            $endtime = strtotime($endDate);
        }

        $isnotrangeDate = empty($starttime) && empty($endtime);

        if ($isnotrangeDate) {
            $endtime = time();
            $starttime = strtotime("-1 month");  // 最近30天日期
        } if ($starttime && ($starttime > $endtime)) {
            $this->error = '起始时间要小于终止时间';
            return false;
        }


        $recordModel = new \app\admin\model\CrmRecord();
$where=[];
        if ($starttime && $endtime) {
            $where[]=['create_time', 'between time', [$starttime, $endtime]];

        }
        if($owner_admin_id){
            $where[]=['admin_id','=',$owner_admin_id];
        }
      /*  $lists = $recordModel->where($where)
            ->field('COUNT(*) AS nums, record_type as name')
            ->group('record_type')->select();*/
        $typeList=Db::name('crm_record_type')->field('`id`,`name`')->where('status','=',1)->order('sort ASC')->select();
        $alldata=[];
        foreach ($typeList as $k => $v) {
$r=[];
            $r['name'] = $v['name'];
            $r['value'] =  $recordModel->where($where)->where('record_type', $v['name'])->count();
            $alldata[] = $r;
        }
        if ($is_ajax) {
            $this->success('', '', [
                'record_type' => $alldata
            ]);
        } else {
            return $alldata;
        }

    }






}
