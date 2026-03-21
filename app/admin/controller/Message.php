<?php
namespace app\admin\controller;
use think\facade\View;
class Message extends Common
{
    public function index(){
        if($this->request->isPost()) {
            $key=input('post.key');
            $page =input('page',1,'intval');
            $pageSize =input('limit',config('app.pageSize'),'intval');
            $list = \think\facade\Db::name('message')
                ->where('name|tel|content', 'like', "%" . $key . "%")
                ->order('addtime desc')
                ->paginate(['list_rows'=>$pageSize,'page'=>$page])
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['addtime'] = date('Y-m-d H:s',$v['addtime']);
            }
            return json(['code'=>0,'msg'=>fy('Get successful').'!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1]);
        }
        return View::fetch();
    }
    //删除留言
    public function del(){
        $map['message_id']=input('message_id');
        \think\facade\Db::name('message')->where($map)->delete();
        return json(['code'=>1,'msg'=>fy("Delete succeeded").'!']);
    }
    public function delall(){
        $map[] =array('message_id','IN',input('param.ids/a'));
        \think\facade\Db::name('message')->where($map)->delete();
        $result['msg'] = fy('Delete succeeded').'！';
        $result['code'] = 1;
        $result['url'] = myurl('index');
        return json($result);
    }
}