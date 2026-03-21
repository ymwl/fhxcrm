<?php
namespace app\admin\controller;
use think\facade\View;
use think\facade\Db;
use think\facade\Request;
class System extends Common
{

    public function email(){
        if($this->request->isAjax()) {
            $datas = $this->request->post();
            
            foreach ($datas as $k=>$v){
               Db::name('config')->where([['name','=',$k],['inc_type','=','smtp']])->update(['value'=>$v]);
            }
            return json(['code' => 1, 'msg' => fy("Setting succeeded"), 'url' => myurl('system/email')]);
        }else{
            $smtp = Db::name('config')->where('inc_type','smtp')->select();
            $info = convert_arr_kv($smtp,'name','value');
            View::assign('info', json_encode($info,true));
            return View::fetch();
        }
    }
    public function trySend(){
        $sender = input('email');
        //检查是否邮箱格式
        if (!is_email($sender)) {
            return json(['code' => 0, 'msg' => '测试邮箱码格式有误']);
        }
        $arr = \think\facade\Db::name('config')->where('inc_type','smtp')->select();
        $config = convert_arr_kv($arr,'name','value');
        $content = $config['test_eamil_info'];
        $send = send_email($sender, '测试邮件',$content);
        if ($send) {
            return json(['code' => 1, 'msg' => '邮件发送成功！']);
        } else {
            return json(['code' => 0, 'msg' => '邮件发送失败！']);
        }
    }

}
