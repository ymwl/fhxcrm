<?php

namespace app\api\controller\system;

use app\api\controller\Authority;

use think\App;


class Uploadfile extends Authority
{


    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\SystemUploadfile();
        
    }

    public function delete()
    {
        $id=$this->request->param('id');
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error(fy('The data does not exist'));
        try {
            $config =\think\facade\Filesystem::getDiskConfig('public');
            $aRow=$row->toArray();

            $save = $row->delete();
            foreach ($aRow as $r){
                if(!empty( $r['file_path']) && $r['upload_type']=='local' && file_exists($config['root'].$r['file_path'])){
                    @unlink($config['root'].$r['file_path']);
                }
            }


        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $save ? $this->success(fy('Delete succeeded')) : $this->error(fy('Delete failed'));
    }

    
}