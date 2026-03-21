<?php

namespace app\admin\controller\email;

use app\common\controller\AdminController;

use think\App;


class Log extends AdminController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\EmailLog();
        
    }

    public function index()
    {
        if ($this->request->isAjax()) {

            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where,$sort) = $this->buildTableParames();
            /*
             * <li class="layui-this" data-value="mine" data-field="scope">我的</li>
                <li data-value="team" data-field="scope">团队的</li>
                <li data-value="all" data-field="scope">全部</li>*/
            $scope = $this->request->get('scope','mine','trim');
           if($scope=='team'){
               //                    展示其他的  不包括自己
               $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin);
               if(empty($adminIds)){
                   return json([
                       'code'  => 0,
                       'msg'   => '',
                       'count' => 0,
                       'data'  => [],
                   ]);
               }
               if($adminIds!=='ALL'){
                   $where[] = ['create_admin_id', 'in',$adminIds];
               }elseif($adminIds=='ALL'){
//                    展示其他的  不包括自己需要做排除
                   $where[] = ['create_admin_id', '<>',$this->admin['admin_id']];
               }
            }elseif($scope=='all'){
//               展示全部 包括自己
                    $adminIds=(new \app\admin\model\Admin())->getViewAdminIds($this->admin,true);
                    if(empty($adminIds)){
                        return json([
                            'code'  => 0,
                            'msg'   => '',
                            'count' => 0,
                            'data'  => [],
                        ]);
                    }
                    if($adminIds!=='ALL'){
                        $where[] = ['create_admin_id', 'in',$adminIds];
                    }
            }else{
               $where[] = ['create_admin_id', '=', $this->admin['admin_id']];
            }
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];
            if($count){
                $list = $this->model
                    ->where($where)
                    ->page($page, $limit)
                    ->order($sort)
                    ->select();
            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assignconfig('statusList',$this->model->getStatusList());
        $this->assignconfig('typeList',(new \app\admin\model\Emailtpl())->getTypeList());
        return $this->fetch();
    }

    public function edit($id)
    {
        $this->assign('statusList',$this->model->getStatusList());
        return parent::edit($id);
    }

    
}