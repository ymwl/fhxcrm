<?php
namespace app\admin\controller;
use app\common\controller\AdminController;
use think\db\Query;
use think\facade\Db;
use think\facade\Filesystem;
use think\facade\Lang;


class Ajax extends AdminController{
    public function getRegion(){
        $Region=\think\facade\Db::name("region");
        $map['pid']=input("pid");
        $map['type']=input("type");
        $list=$Region->where($map)->select();
        echo json_encode($list);

    }

    /**
     * 加载语言包
     */
    public function lang()
    {

        $header = ['Content-Type' => 'application/javascript'];
        if (!env('APP_DEBUG', false)) {
            $offset = 30 * 60 * 60 * 24; // 缓存一个月
            $header['Cache-Control'] = 'public';
            $header['Pragma'] = 'cache';
            $header['Expires'] = gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
        }

        $controller = $this->request->get('controller');

        //默认只加载了控制器对应的语言名，你还根据控制器名来加载额外的语言包
        $this->loadlang($controller);
        return jsonp(Lang::get(), 200, $header, ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
    }

    public function initAdmin(){
        $lang = Lang::getLangSet();
        $cache_key='initAdmin_' .$this->admin['admin_id'].$lang;
        $cacheData = cache($cache_key);
        if (!empty($cacheData)) {
            return json($cacheData);
        }
        $where=[];
        $where[]=['menustatus','=','1'];
        $where[]=['status','=','1'];
        if($this->admin['group_id']>1){
            $rules=\think\facade\Db::name('auth_group')->where('id','=',$this->admin['group_id'])->value('rules');
            if(!$rules){
                $rules='';
            }
            $rules=trim($rules,',');
            $where[]=['id','in',$rules];
        }
        $authRule = \think\facade\Db::name('auth_rule')->field('id,title,icon,href,pid,target')->where($where)->order('sort ASC,id ASC')->select()->toArray();
        $home_title=\think\facade\Db::name('auth_rule')->where('href','=',$this->system['fixedpage'])->value('title');
        $data = [
            'homeInfo' => ['title'=>$home_title?$home_title:fy('Home'), 'icon'=>"fa fa-home",'href'=> myurl($this->system['fixedpage'])],
            'menuInfo' => $this->getMenu(0,$authRule),
        ];
        cache($cache_key,$data);
        return json($data);
    }

    protected function getMenu($pid,$authRule){

        $treeList = [];
        foreach ($authRule as &$v) {
            $check = true;
            if(!empty($v['href']) && $this->admin['group_id']!=1){
                if($this->adminRules && !in_array($v['id'],$this->adminRules)){
                    $check=false;
                }
            }

            if ($pid == $v['pid'] && $check) {
                !empty($v['href']) && $v['href'] = myurl($v['href']);
                $v['title']=fy($v['title']);
                $node = $v;
                $child = $this->getMenu($v['id'],$authRule);
                if (!empty($child)) {
                    $node['child'] = $child;
                }
                if (!empty($v['href']) || !empty($child)) {
                    $treeList[] = $node;
                }
            }
        }
        return $treeList;
    }


    /**
     * 上传文件
     */
    public function upload()
    {
        $this->checkPostRequest();
        $data = [
            'type' => $this->request->post('type'),
            'file'        => $this->request->file('file'),
        ];


        // 读取文件内容
        $fileContent = file_get_contents($data['file']->getRealPath());

// 检测文件内容中是否包含 PHP 代码
//
        // 包含php代码
        // 包含script脚本
        // 包含src引入文件
        // 包含href跳转地址
        // 包含iframe引入外部地址
//        || preg_match('#src=#i', $value) || preg_match('#href=#i', $value)
        if(preg_match('#<\?php#i', $fileContent) || preg_match('#<script#i', $fileContent) || preg_match('#<iframe#i', $fileContent))
        {
            file_put_contents('upload_error.txt',date('Y-m-d H:i:s').'上传文件包含非法脚本,上传者ip:'.getRealIp().PHP_EOL,FILE_APPEND);
            return json(['code'=>0,'msg' => '含有非法脚本']);
        }
        try {
            $uploadConfig = config('upload');
            empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
            $rule = [
                'type|指定上传类型有误' => "in:{$uploadConfig['upload_allow_mime']}",
                'file|文件'              => "require|file|fileExt:{$uploadConfig['upload_allow_ext']}|fileSize:{$uploadConfig['upload_allow_size']}",
            ];

            parent::validate($data, $rule);
            // 上传到本地服务器  /  Filesystem::disk('public')

            $types = '.gif|.jpeg|.png|.bmp|.jpg';  //定义检查的图片类型
            $file = substr(strrchr($data['file']->getOriginalName(), "."), 1);//截取最后一个.之后的数据
            if(strpos($types, $file) !== false){
//                $data['is_pic'] = '图片';
                $path='upload/images';
            } else {
//                $data['is_pic'] = '非图片';
                $path='upload/attach';
            }
            $savename = \think\facade\Filesystem::disk('public')->putFile($path, $data['file']);
            $savename=str_replace(DIRECTORY_SEPARATOR, '/', $savename);
            if(strpos($savename,'/')!==0){
                $savename='/'.$savename;
        }
            $post=$this->request->post();

//            object(think\file\UploadedFile)#73 (8) {
//  ["test":"think\file\UploadedFile":private]=>
//  bool(false)
//  ["originalName":"think\file\UploadedFile":private]=>
//  string(23) "测试上传资料.xlsx"
//  ["mimeType":"think\file\UploadedFile":private]=>
//  string(24) "application/octet-stream"
//  ["error":"think\file\UploadedFile":private]=>
//  int(0)
//  ["hash":protected]=>
//  array(0) {
//  }
//  ["hashName":protected]=>
//  string(41) "20220902\9ad87f87f35779ba6453861cbb56eff0"
//  ["pathName":"SplFileInfo":private]=>
//  string(22) "C:\Windows\php4E24.tmp"
//  ["fileName":"SplFileInfo":private]=>
//  string(11) "php4E24.tmp"
//}
            if(!empty($post['zoneTotalCount'])){
//                说明需要分片
//                	`id` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
//	`upload_type` VARCHAR(20) NOT NULL DEFAULT 'local' COMMENT '存储位置' COLLATE 'utf8_general_ci',
//	`original_name` VARCHAR(255) NULL DEFAULT NULL COMMENT '文件原名' COLLATE 'utf8_general_ci',
//	`url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '网络资源路径' COLLATE 'utf8_general_ci',
//	`file_path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '物理路径' COLLATE 'utf8_general_ci',
//	`image_width` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '宽度' COLLATE 'utf8_general_ci',
//	`image_height` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '高度' COLLATE 'utf8_general_ci',
//	`image_type` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '图片类型' COLLATE 'utf8_general_ci',
//	`image_frames` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '图片帧数',
//	`mime_type` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'mime类型' COLLATE 'utf8_general_ci',
//	`file_size` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件大小',
//	`file_ext` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
//	`md5` VARCHAR(40) NOT NULL DEFAULT '' COMMENT '文件md5' COLLATE 'utf8_general_ci',
//	`total_md5` VARCHAR(40) NOT NULL DEFAULT '' COMMENT '总文件md5' COLLATE 'utf8_general_ci',
//	`create_time` bigint(16) NULL DEFAULT NULL COMMENT '创建日期',
//	`update_time` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
//	`upload_time` INT(10) NULL DEFAULT NULL COMMENT '上传时间',

//                id: WU_FILE_0
//name: 测试上传资料.xlsx
//type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
//lastModifiedDate: Thu Sep 01 2022 11:27:27 GMT+0800 (中国标准时间)
//size: 15246796
//chunk: 1
//fileMd5: 400acb064361b4df59b351f16f204abf
//contentType: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
//zoneTotalMd5: 400acb064361b4df59b351f16f204abf
//zoneMd5: 35d5368808b47f6d041c9208e7333c6a
//zoneTotalCount: 3
//zoneNowIndex: 1
//zoneTotalSize: 15246796
//zoneStartSize: 5242880
//zoneEndSize: 10485760
//file: （二进制）
                $res=Db::name('system_zone_uploadfile')->insert([
                    'upload_type'   => 'local',
                    'original_name' => $data['file']->getOriginalName(),
                    'mime_type' => $data['file']->getOriginalMime(),
                    'file_ext'      => strtolower($file),
                    'url'           => $savename,
                    'file_path'           => $savename,
                    'create_time'   => time(),
                    'md5'   =>  $post['zoneMd5'],
                    'chunk_index'   =>  $post['zoneNowIndex'],
                    'chunk_count'   =>  $post['zoneTotalCount'],
                    'total_md5'   =>  $post['zoneTotalMd5'],
                ]);
        } else {
//                不需要分片

//                $data['file']->md5()
                $res=Db::name('system_uploadfile')->insert(
                    [
                        'upload_type'   => 'local',
                        'original_name' => $data['file']->getOriginalName(),
                        'mime_type' => $data['file']->getOriginalMime(),
                        'file_ext'      => strtolower($file),
                        'url'           => $savename,
                        'file_path'           => $savename,
                        'create_time'   => time(),
                        'admin_id'   => $this->admin['admin_id'],
                        'md5'   =>  $data['file']->md5()
                    ]);

            }
           return json(['code'=>1,'msg' => '上传成功','data'=>['url'=>$savename]]);
        } catch (\Exception $e) {
//            获取报错的具体文件和行号
            return json(['code'=>0,'msg' => $e->getMessage().'。错误文件和行号：'.$e->getFile().':'.$e->getLine()]);

        }
    }


    public function uploadUeditor(){
        require $this->app->getRootPath().'public/static/ueditor/php/controller.php';
        }
    /**
     * 获取上传文件列表
     */
    public function getUploadFiles()
    {
        $this->model = new \app\admin\model\SystemUploadfile();
        list($page, $limit, $where,$sort) = $this->buildTableParames();

        $where[] = ['admin_id', '=', $this->admin['admin_id']];
        $count = $this->model
            ->where($where)
            ->count();
        $list=[];
        if($count ){
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($sort)
                ->select();
        }

        $data = [
            'code'  => 1,
            'msg'   => '',
            'count' => $count,
            'data'  => $list,
        ];
        return json($data);
    }

    /**
     * 汉字转拼音
     */
    public function pinyin(){
        if($this->request->isPost()){
            $name=$this->request->post('name','','trim');
            $res=\fhx\Pinyin::result($name);
            $this->success('转换成功','',$res);
        }
    }


}
