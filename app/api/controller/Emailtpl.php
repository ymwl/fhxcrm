<?php

namespace app\api\controller;

use app\api\controller\Authority;
use think\App;


class Emailtpl extends Authority
{

    protected $sort = [
        'sort'   => 'DESC',
        'id'   => 'asc'
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Emailtpl();
        
//        $this->assign('getStatusList', $this->model->getStatusList());
//        $this->assign('getTypeList', $this->model->getTypeList());

    }

    /**
     * 获取邮件模板内容
     */
    public function getTemplate()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id',0);
            $id=$id+0;
            $customer_id = $this->request->param('customer_id',0);
            $customer_id=$customer_id+0;
            $customer_contacts_id = $this->request->param('customer_contacts_id',0);
            $customer_contacts_id=$customer_contacts_id+0;
            $contract_id = $this->request->param('contract_id',0);
            $contract_id=$contract_id+0;

            $client_order_id = $this->request->param('client_order_id',0);
            $client_order_id=$client_order_id+0;
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '模板ID不能为空']);
            }

            $template = $this->model->field('id,type,tpl_title,tpl_content')->find($id);
            if (empty($template)) {
                return json(['code' => 0, 'msg' => '模板不存在']);
            }
            $row = [];
            $row['user_realname'] = $this->admin['realname'];
            $row['user_phone'] = $this->admin['phone'];
            $row['user_email'] = $this->admin['email'];
            $row['user_wechat'] = $this->admin['wechat'];
            $prefix=getDataBaseConfig('prefix');
            if ($customer_id) {
                $customer=\think\facade\Db::name('crm_customer')->find($customer_id);
                if ($customer) {
                    $customer_fields=\think\facade\Db::query('SELECT `name`,`formtype`,`field`,`option` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_customer" AND `addinput` is not null AND `field`!="id" AND `field`!="remark"  order BY `sort` ASC,id ASC');
                    foreach ($customer_fields as $key => $item) {
                        if(isset($customer[$item['field']])){
                            $row['customer_'.$item['field']] = real_field_val($item,$customer[$item['field']]);
                        }

                    }
                }
            }
            if ($customer_contacts_id) {
                $customer_contacts=\think\facade\Db::name('crm_customer_contacts')->find($customer_contacts_id);
                if ($customer_contacts) {
                    $contacts_fields=\think\facade\Db::query('SELECT `name`,`formtype`,`field`,`option` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_customer_contacts" AND `addinput` is not null AND `field`!="id" AND `field`!="customer_id" AND `field`!="remark" order BY `sort` ASC,id ASC');
                    foreach ($contacts_fields as $key => $item) {
                        if(isset($customer_contacts[$item['field']])){
                            $row['contacts_'.$item['field']] = real_field_val($item,$customer_contacts[$item['field']]);
                        }
                    }
                }
             }
            if ($contract_id) {
                $contract=\think\facade\Db::name('crm_contract')->find($contract_id);
                if ($contract) {
                    $contract_fields=\think\facade\Db::query('SELECT `name`,`formtype`,`field`,`option` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_contract" AND `addinput` is not null AND `field`!="id" AND `field`!="remark" order BY `sort` ASC,id ASC');
                    $contract_fields=array_merge((new \app\common\model\CrmContract())->defaultField(),$contract_fields);
                    foreach ($contract_fields as $key => $item) {
                        if(isset($contract[$item['field']])){
                            $row['contract_'.$item['field']] = real_field_val($item,$contract[$item['field']]);
                        }
                }
            }}
            if ($client_order_id) {
                $client_order=\think\facade\Db::name('crm_client_order')->find($client_order_id);
                if ($client_order) {
                    $client_order_fields=\think\facade\Db::query('SELECT `name`,`formtype`,`field`,`option` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`="crm_client_order" AND `addinput` is not null AND `field`!="id" AND `field`!="remark" order BY `sort` ASC,id ASC');
                    $client_order_fields=array_merge((new \app\admin\model\CrmClientOrder())->defaultField(),$client_order_fields);
                    foreach ($client_order_fields as $key => $item) {
                        if(isset($client_order[$item['field']])){
                            $row['order_'.$item['field']] = real_field_val($item,$client_order[$item['field']]);
                        }
                }
                }
            }


            $this->app->view->engine()->layout(false);
            $tpl_title=$this->app->view->display($template['tpl_title'],$row);
            $tpl_content=$this->app->view->display($template['tpl_content'],$row);

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'tpl_type' => $template['type'],
                    'tpl_title' => $tpl_title,
                    'tpl_content' => $tpl_content
                ]
            ]);
        }

        return json(['code' => 0, 'msg' => '非法请求']);
    }
    
}