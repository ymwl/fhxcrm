<?php

use think\facade\Db;




if (! function_exists('tp5ControllerToTp6Controller')) {
    /**
     * TP5二级目录转TP6二级目录.
     *
     * @param  string  $class
     *
     * @return string
     */
    function tp5ControllerToTp6Controller($class = '')
    {
        $_arr = explode('/', $class);
        $route = $class;
        if (count($_arr) >= 3) {
            $route = '';
            foreach ($_arr as $_k => $_v) {
                $route .= $_v;
                ($_k == 0) ? $route .= '.' : $route .= '/';
            }
            $route = rtrim($route, '/');
        } elseif (count($_arr) == 2) {
            $route = implode('.', $_arr).'/index';
        }

        return $route;
    }
}










function build_option_input($table,$primary_key,$foreign_key,$value){
    $res=Db::name($table)->field($primary_key.','.$foreign_key)->where('status','=',1)->order('sort ASC,id DESC')->cache($table)->select();
    $str='';
    foreach ($res as $v){
        if($v[$primary_key]==$value){
            $selected='selected';
        }else{
            $selected='';
        }
        $str .= '<option value="'.$v[$primary_key].'" '.$selected.'>'.$v[$foreign_key].'</option>';
    }
    return $str;
}
//生成联动复选框
function build_checkbox_input($table,$primary_key,$foreign_key,$field,$value){
    $order='sort ASC,id DESC';
    $where=[['status','=',1]];
    if($table=='admin'){
        $order='admin_id ASC';
        $where=[['is_open','=',1]];
    }
    $res=Db::name($table)->field($primary_key.','.$foreign_key)->where($where)->order($order)->cache($table)->select();
    $str='';
    foreach ($res as $v){
        if($v[$primary_key]==$value){
            $checked='checked';
        }else{
            $checked='';
        }
        $str .= '<input lay-skin="primary" type="checkbox" name="'.$field.'[]" title="'.$v[$primary_key].'" value="'.$v[$primary_key].'" '.$checked.'>';
    }
    return $str;
}

//实现删除指定目录下几天前的数据
function deleteFilesOlderThanDays($directory, $days = 1) {
    // 计算指定天数前的日期时间戳（秒）
    $cutoffTime = strtotime(date('Y-m-d 00:00:00') . ' -'.$days.' day');

    // 使用目录迭代器遍历文件夹
    $dirIterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
    $filesToDelete = [];

    foreach (new RecursiveIteratorIterator($dirIterator) as $file) {
        // 检查文件是否为文件且创建时间在指定日期之前
        if ($file->isFile() && $file->getCTime() < $cutoffTime) {
            unlink( $file->getPathname());
        }
    }

}