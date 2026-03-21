<?php
namespace app\api\controller;
use app\admin\model\publicuse\PublicUse;
use app\admin\model\traits\TablHandle;
use EasyAdmin\upload\Uploadfile;
use fast\Tree;
use think\App;
use think\db\Query;
use think\facade\Db;
use think\facade\Lang;
use app\common\model\SystemField;
use think\Model;

class Fields extends Common {

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemField();
/*        $array = PublicUse::getConfigDir(__CLASS__);
        $this->AllData = json_decode(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'field'.DIRECTORY_SEPARATOR.$array['class'].'.json'),true);*/

    }
   public function get_fields(){
       $source=$this->request->request('source','');

       $id = $this->request->param('id', 0, 'intval');
       $prefix=getDataBaseConfig('prefix');
       $fields=Db::query('SELECT `id`,`default` as value,`formtype`,`foreign_key`,`relationship_primary_key`,`field`,`rule`,if(`xsname`<>"",`xsname`,`name`) title,`option` FROM `'.$prefix.'system_field` WHERE `edit`=1 AND `table`=:table AND `editinput` is not null AND `is_key`<>1 order BY `sort` ASC,id ASC',['table'=>$source]);
        foreach($fields as $ko => $vo){
            if($vo['formtype']=='lselect'){

            }elseif($vo['formtype']=='select' || $vo['formtype']=='checkbox' || $vo['formtype']=='radio'){
                if(isset($vo['option'])&&$vo['option']){
                    $vo['content_list'] = TablHandle::optionToArray($vo['option']);
                }else{
                    $vo['content_list'] = [];
                }
            }
//            rule
//:
//"require"  把require替换成required
            $vo['rule']=str_replace('require','required',$vo['rule']);
            $fields[$ko]=$vo;
        }

       $this->jsonSuccess('',['fields'=> $fields]);
   }

    /**
     * 关联表联动
     * @internal
     */
    public function selectpage()
    {
        $id = $this->request->get("id/d", 0);
        $fieldInfo = $this->model->find($id);
        if (!$fieldInfo) {
            $this->jsonError("未找到指定字段");
        }

        if (empty($fieldInfo['join_table'])) {
            $this->jsonError("字段配置不正确");
        }
        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array)$this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize",10,'intval');
        //搜索条件
        $andor = $this->request->request("andOr", "and", "strtoupper");
        //排序方式
        $orderby = (array)$this->request->request("orderBy/a");
        //显示的字段

        $field = $fieldInfo['foreign_key'];
        //主键
        $primarykey = $fieldInfo['relationship_primary_key'];
        //主键值
        $primaryvalue = $this->request->request("keyValue");
        //搜索字段
        $searchfield = [$field, $primarykey];
        //自定义搜索条件

        $custom = isset($setting['conditions']) ? (array)json_decode($setting['conditions'], true) : [];
        $custom = array_filter($custom);

        $admin_id = 0;
        $user_id = 0;


        //是否返回树形结构
        $istree = $this->request->request("isTree", 0);
        $ishtml = $this->request->request("isHtml", 0);
        if ($istree) {
            $word = [];
            $pagesize = 99999;
        }
        $order = [];
        foreach ($orderby as $k => $v) {
            $order[$v[0]] = $v[1];
        }
        $field = $field ? $field : 'name';

        //如果有primaryvalue,说明当前是初始化传值
        if ($primaryvalue !== null) {
            $where = [$primarykey => ['in', $primaryvalue]];
            $where = function ($query) use ($primaryvalue, $custom, $admin_id, $user_id) {
                $query->where('id', 'in', $primaryvalue);
                if ($custom && is_array($custom)) {
                    //替换暂位符
                    $search = ["{admin_id}", "{user_id}"];
                    $replace = [$admin_id, $user_id];
                    foreach ($custom as $k => $v) {
                        if (is_array($v) && 2 == count($v)) {
                            $query->where($k, trim($v[0]), str_replace($search, $replace, $v[1]));
                        } else {
                            $query->where($k, '=', str_replace($search, $replace, $v));
                        }
                    }
                }
            };
            $pagesize = 99999;
        } else {
            $where = function ($query) use ($word, $andor, $field, $searchfield, $custom, $admin_id, $user_id) {
                $logic = $andor == 'AND' ? '&' : '|';
                $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
                $word = array_filter($word);
                if ($word) {
                    foreach ($word as $k => $v) {
                        $query->where(str_replace(',', $logic, $searchfield), "like", "%{$v}%");
                    }
                }
                if ($custom && is_array($custom)) {
                    //替换暂位符
                    $search = ["{admin_id}", "{user_id}"];
                    $replace = [$admin_id, $user_id];
                    foreach ($custom as $k => $v) {
                        if (is_array($v) && 2 == count($v)) {
                            $query->where($k, trim($v[0]), str_replace($search, $replace, $v[1]));
                        } else {
                            $query->where($k, '=', str_replace($search, $replace, $v));
                        }
                    }
                }
            };
        }
        $list = [];
        $total = Db::name($fieldInfo['join_table'])->where($where)->count();
        if ($total > 0) {
            $datalist = Db::name($fieldInfo['join_table'])->where($where)
                ->order($order)
                ->page($page, $pagesize)
                ->field($primarykey . "," . $field . ($istree ? ",pid" : ""))
                ->select();
            foreach ($datalist as $index => &$item) {
                unset($item['password'], $item['salt']);
                $list[] = [
                    $primarykey => isset($item[$primarykey]) ? $item[$primarykey] : '',
                    $field      => isset($item[$field]) ? $item[$field] : '',
                    'pid'       => isset($item['pid']) ? $item['pid'] : 0
                ];
            }
            if ($istree && !$primaryvalue) {
                $tree = Tree::instance();
                $tree->init($list, 'pid');
                $list = $tree->getTreeList($tree->getTreeArray(0), $field);
                if (!$ishtml) {
                    foreach ($list as &$item) {
                        $item = str_replace('&nbsp;', ' ', $item);
                    }
                    unset($item);
                }
            }
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        $this->jsonSuccess(fy('Get successful'), ['list' => $list, 'total' => $total]);
    }



}