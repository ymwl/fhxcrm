<?php

namespace app\api\controller;

use app\api\controller\Authority;

use think\App;
use think\facade\Db;
use app\api\controller\Authority;

class Violation extends Authority
{
    protected $noNeedLogin = ['more'];

    protected $searchFields = 'event,community,zhengjianbianhao';
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Violation();

    }

//    针对某一个证件号查询所有违章记录
    public function more()
    {

        list($where, $sort, $offset, $limit) = $this->buildTableParames();
        $unicode=$this->request->get('unicode',0,'trim');
        if(empty($unicode)){
            $this->error('查询指定证书必须携带参数');
        }
        $zhengshu=Db::name('chaxun_zhengshu')->field('id,zhengjianbianhao')->where('unicode','=',$unicode)->find();
        if(empty($zhengshu['id'])){
            $this->error('该证书不存在');
        }
        $where[]=['zhengshu_id','=',$zhengshu['id']];
//        $where[]=['create_username','=',$this->admin['username']];
//            前台只展示自己创建的记录
        /*  if($this->admin['group_id']!=1){
              $community_id=Db::name('admin')->where(['admin_id'=>$this->admin['admin_id']])->value('community_id');
              $name=Db::name('community')->where(['id'=>$community_id])->value('name');
              $where[]=['community','=',$name];
          }*/
        $count = $this->model
            ->where($where)
            ->count();
        $list=[];

        if($count){
            $list = $this->model
                ->where($where)
                ->limit($offset, $limit)
                ->order($sort)
                ->select()->toArray();
            foreach ($list as $k=>$v){
                $list[$k]['create_time']=$v['create_time']?strtotime($v['create_time']):'';
                $list[$k]['update_time']=$v['update_time']?strtotime($v['update_time']):'';
                $list[$k]['image']=real_resourse($this->system['domain'],$v['image']);
            }
        }

        $data = [
            'code'  => 1,
            'msg'   => '',
            'count' => $count,
            'data'  => $list,'zhengshu'=>$zhengshu
        ];
        return json($data);

    }


    public function index()
    {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($where, $sort, $offset, $limit) = $this->buildTableParames();
            $zhengshu_id=$this->request->get('zhengshu_id',0,'intval');
            if($zhengshu_id){
                $where[]=['zhengshu_id','=',$zhengshu_id];

            }
        $where[]=['create_username','=',$this->admin['username']];
//            前台只展示自己创建的记录
          /*  if($this->admin['group_id']!=1){
                $community_id=Db::name('admin')->where(['admin_id'=>$this->admin['admin_id']])->value('community_id');
                $name=Db::name('community')->where(['id'=>$community_id])->value('name');
                $where[]=['community','=',$name];
            }*/
            $count = $this->model
                ->where($where)
                ->count();
            $list=[];

            if($count){
                $list = $this->model
                    ->where($where)
                    ->limit($offset, $limit)
                    ->order($sort)
                    ->select()->toArray();
                foreach ($list as $k=>$v){
                    $list[$k]['create_time']=$v['create_time']?strtotime($v['create_time']):'';
                    $list[$k]['update_time']=$v['update_time']?strtotime($v['update_time']):'';
                    $list[$k]['image']=real_resourse($this->system['domain'],$v['image']);
                }
            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);

    }



    public function add()
    {
        $zhengshu_id=input('zhengshu_id',0,'intval');
        if(empty($zhengshu_id)){
            $this->error('证件参数不存在');
        }
        $zhengjian=Db::name('chaxun_zhengshu')->field('id,community,zhengjianbianhao')->where('id','=',$zhengshu_id)->find();
        if(empty($zhengjian['id'])){
            $this->error('需要提交违章记录对应证件不存在');
        }
        if ($this->request->isPost()) {
            $post = $this->request->post();

            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;

            try {
                validate($validate)->check($post);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            try {
                $post['time'] = strtotime($post['time']);
                $post['community'] = $zhengjian['community'];
                $post['create_time'] = time();
                $post['create_username'] = $this->admin['username'];
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error(fy('Save failed').':'.$e->getMessage());
            }
            $save ? $this->success(fy('Save successfully')) : $this->error(fy('Save failed'));
        }

        $this->success('获取成功', null,['zhengjianbianhao'=>$zhengjian['zhengjianbianhao'],'zhengshu_id'=>$zhengshu_id]);

    }

    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error(fy('The data does not exist'));
        if ($this->request->isPost()) {
            $post = $this->request->post();

                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                try {
                    validate($validate)->check(array_merge($post,['id'=>$id]));
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }

            try {
                $data=[];
                $data['time']=strtotime($post['time']);
                $data['update_time']=time();
                $data['event']=$post['event'];
                $data['image']=$post['image'];
                $save = $row->save($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success(fy('Save successfully'));
        }
//        $row['image']=real_resourse($this->system['domain'],$row['image']);
        $this->success('获取成功', '',$row);

    }


}
