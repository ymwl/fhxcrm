<?php

namespace fhx;

/**
 * 树形结构工具类（静态方法）
 */
class Tree
{
    /**
     * 获取指定节点的所有子节点（递归）
     * @param array  $data      数据数组，每项需包含 id 和 pid 字段
     * @param mixed  $myid      节点ID
     * @param bool   $withself  是否包含自身
     * @param string $pidname   父ID字段名
     * @return array
     */
    public static function getChildren($data, $myid, $withself = false, $pidname = 'pid')
    {
        $newarr = [];
        foreach ($data as $value) {
            if (!isset($value['id'])) {
                continue;
            }
            if ((string)$value[$pidname] == (string)$myid) {
                $newarr[] = $value;
                $newarr = array_merge($newarr, self::getChildren($data, $value['id'], false, $pidname));
            } elseif ($withself && (string)$value['id'] == (string)$myid) {
                $newarr[] = $value;
            }
        }
        return $newarr;
    }

    /**
     * 获取指定节点的所有子节点ID
     * @param array  $data
     * @param mixed  $myid
     * @param bool   $withself
     * @param string $pidname
     * @return array
     */
    public static function getChildrenIds($data, $myid, $withself = false, $pidname = 'pid')
    {
        $childrenlist = self::getChildren($data, $myid, $withself, $pidname);
        $childrenids = [];
        foreach ($childrenlist as $v) {
            $childrenids[] = $v['id'];
        }
        return $childrenids;
    }

    /**
     * 获取树状嵌套数组
     * @param array  $data
     * @param mixed  $myid      起始节点ID
     * @param string $pidname   父ID字段名
     * @return array
     */
    public static function getTreeArray($data, $myid = 0, $pidname = 'pid')
    {
        $result = [];
        $n = 0;
        foreach ($data as $value) {
            if (!isset($value['id'])) {
                continue;
            }
            if ((string)$value[$pidname] == (string)$myid) {
                $result[$n] = $value;
                $result[$n]['childlist'] = self::getTreeArray($data, $value['id'], $pidname);
                $n++;
            }
        }
        return $result;
    }

    /**
     * 将树状嵌套数组展开为二维数组
     * @param array  $data     getTreeArray 返回的树状数组
     * @param string $field    显示字段名
     * @param string $pidname  父ID字段名（未使用，保留兼容）
     * @return array
     */
    public static function getTreeList($data = [], $field = 'name', $pidname = 'pid')
    {
        $arr = [];
        foreach ($data as $v) {
            $childlist = isset($v['childlist']) ? $v['childlist'] : [];
            unset($v['childlist']);
            $v['haschild'] = $childlist ? 1 : 0;
            if ($v['id']) {
                $arr[] = $v;
            }
            if ($childlist) {
                $arr = array_merge($arr, self::getTreeList($childlist, $field, $pidname));
            }
        }
        return $arr;
    }
}
