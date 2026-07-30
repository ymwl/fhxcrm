<?php
namespace fhx;

class Leftnav
{
    /**
     * 自定义菜单排列
     * @param array  $cate      分类数组
     * @param string $lefthtml  层级前缀
     * @param int    $pid       父ID
     * @param int    $lvl       当前层级
     * @param int    $leftpin   左偏移
     * @param string $id        主键字段名
     * @return array
     */
    static public function menu($cate, $lefthtml = '|— ', $pid = 0, $lvl = 0, $leftpin = 0, $id = 'id')
    {
        $arr = [];
        foreach ($cate as $v) {
            if ($v['pid'] == $pid) {
                $v['lvl'] = $lvl + 1;
                $v['leftpin'] = $leftpin + 0;
                $v['lefthtml'] = str_repeat($lefthtml, $lvl);
                $v['ltitle'] = $v['lefthtml'] . $v['title'];
                $arr[] = $v;
                $arr = array_merge($arr, self::menu($cate, $lefthtml, $v[$id], $lvl + 1, $leftpin + 20, $id));
            }
        }
        return $arr;
    }

    /**
     * 权限节点勾选匹配
     * @param array  $cate  权限规则数组
     * @param int    $pid   父ID
     * @param string $rules 已选中的权限ID，逗号分隔
     * @return array
     */
    static public function auth($cate, $pid = 0, $rules = '')
    {
        $arr = [];
        $rules = is_null($rules) ? '' : $rules;
        $rulesArr = explode(',', $rules);
        foreach ($cate as $v) {
            if ($v['pid'] == $pid) {
                if (in_array($v['id'], $rulesArr)) {
                    $v['checked'] = true;
                }
                $v['open'] = true;
                $v['title'] = fy($v['title']);
                $arr[] = $v;
                $arr = array_merge($arr, self::auth($cate, $v['id'], $rules));
            }
        }
        return $arr;
    }
}
