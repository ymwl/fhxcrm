<?php

namespace app\index\controller;

use app\BaseController;
use think\facade\Db;


class Open extends BaseController
{


public function index()
{

    try {
        $id = $this->request->param('id', 0, 'intval');
        $token = $this->request->param('token', '', 'trim');
        if (!$id || !$token) {
            throw new  \exception('验证信息缺少！');
        }
        $count = \think\facade\Db::name('open_apps')->where(['id' => $id, 'token' => $token, 'status' => 1])->count();
        if ($count) {
//            说明验证提供
//                判断post请求还是get请求
            $model = new \app\admin\model\CrmCustomer();
            $prefix = getDataBaseConfig('prefix');
            //                获取字段对应类型关系
            $fields=Db::query('SELECT `field`,`formtype`,`option`,`lang` FROM `'.$prefix.'system_field` WHERE `export`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
            foreach ($fields as $v){
                $arr_fields[$v['field']]=$v;
            }
            if ($this->request->isPost()) {
                $post = $this->request->post('', null, 'trim');
                unset($post['id']);
                if ($post) {

                    $post['owner_admin_id']=0;
                    if (!empty($post['account'])) {

                        $post['owner_admin_id'] =Db::name('admin')->cache('admin_username_'.$post['account'],3600)->where(['username'=>$post['account']])->value('admin_id');
                    }
                    if($post['owner_admin_id']){
                        $post['pr_user']=$post['account'];
                        $post['status']=1;
                        $post['to_kh_time']=time();
                    }else{
                        $post['pr_user']='';
                        $post['owner_admin_id']=0;
                        $post['status']=2;
                        $post['to_gh_time']=time();
                    }
                    unset($post['account']);

//                        加入验证功能
                    $fields = Db::query('SELECT `name`,`xsname`,`rule`,`msg`,`field` FROM `' . $prefix . 'system_field` WHERE rule <> "" AND `edit`=1 AND `table`="crm_customer" order BY `sort` ASC,id ASC');
                    $rule = [];
                    foreach ($fields as $v) {
                        $msg = !empty(trim($v['xsname'])) ? '|' . fy($v['xsname']) : '|' . fy($v['name']);
                        $ruleKey = $v['field'] . $msg;
                        $rule[$ruleKey] = str_replace('unique', 'unique:crm_customer', str_replace(',', '|', trim($v['rule'], ',')));
                    }
                    if ($rule) {
                        parent::validate($post, $rule);
                    }


                    foreach ($post as $k=>$v){
                        if(isset($arr_fields[$k]['formtype'])){
                            switch($arr_fields[$k]['formtype']){
                                case 'datetime':
                                case 'date':
                                    if(!is_numeric($v)){
                                        $post[$k]=strtotime($v);
                                    }
                                    break;
                                case 'select':
                                case 'radio':
                                    $selectList=[];
                                    $option=explode(',',$arr_fields[$k]['option']);
                                    if($option){
                                        foreach ($option as $v1){
                                            $vv=explode(':',$v1);
                                            if($vv){
                                                $selectList[trim($vv[1])]=trim($vv[0]);
                                            }
                                        }
                                        if(isset($selectList[$v])){
                                            $post[$k]= $selectList[$v];
                                        }
                                    }
                            }
                        }
                    }


                    $post['update_time'] = $post['create_time'] = time();
                    $res = $model->allowField($model->getTableFields())->save($post);
                    if ($res) {
                        return json(['code' => 1, 'msg' => '添加成功']);
                    } else {
                        return json(['code' => 0, 'msg' => '添加失败']);
                    }
                } else {
                    throw new  \exception('添加数据不存在');
                }
            } elseif ($this->request->isGet()) {

//                    get请求则获取数据
                $account = $this->request->get('account', '', 'trim');
                $create_time = $this->request->get('create_time', '', 'trim');
                $page = $this->request->get('page', 1, 'intval');
                if (!$account || !$create_time) {
                    throw new  \exception('请求参数缺失！');
                }

                $fields = cache('crm_customer_fields');
                if (!$fields) {

                    $fields = Db::query("SELECT  `field`, `jscol`,`show` FROM `{$prefix}system_field` WHERE `table`='crm_customer' AND `show`=1 AND `jscol` is not null order BY `sort` ASC,id ASC");
                    $field_str = $jscol_str = '';
                    foreach ($fields as $key => $value) {
                        $field_str .= $value['field'] . ',';
                        if ($value['show'] == 1) {
                            $jscol_str .= $value['jscol'] . ',';
                        }
                    }
                    $fields = ['field_str' => trim($field_str, ','), 'jscol_str' => trim($jscol_str, ',')];
                    cache('crm_customer_fields', $fields);
                }
                $field_str = empty($fields['field_str']) ? '*' : $fields['field_str'];
                if (empty($fields['field_str'])) {
                    $field_str = '*';
                } else {
                    if (!in_array('id', explode(',', $fields['field_str']))) {
                        $field_str = 'id,' . $field_str;
                    }
                }
                $start_time = strtotime($create_time);
                $end_time = $start_time + 86400;
                $where = [];
                $where[] = ['create_time', '>=', $start_time];
                $where[] = ['create_time', '<', $end_time];
                $where[] = ['pr_user', '=', $account];

                $count = $model->where($where)->count();
                if ($count) {
                    $list = $model->field($field_str)
                        ->where($where)
                        ->page($page, 10)
                        ->order('create_time desc')
                        ->select()->toArray();
                    foreach ($list as $k => $v) {
                        foreach ($v as $key => $val){
                            if(isset($arr_fields[$key])){
                                $list[$k][$key] = real_field_val($arr_fields[$key],$val);
                            }
                        }
                    }
                    return json(['code' => 1, 'msg' => '获取成功', 'count' => $count, 'data' => $list]);
                }

                return json(['code' => 0, 'msg' => '没有数据']);
            }


        } else {
            throw new  \exception('token信息验证失败或当前应用已禁用！');
        }
    } catch (\Exception $e) {
        $msg = $e->getMessage();
        if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
            $msg = "入库失败，包含【{$matches[1]}】的记录已存在";
        };
        myjson(['code' => 0, 'msg' => $msg]);

    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
            $msg = "入库失败，包含【{$matches[1]}】的记录已存在";
        };
        myjson(['code' => 0, 'msg' => $msg]);

    }

}

}
