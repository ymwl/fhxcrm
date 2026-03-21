<?php
namespace app\admin\validate;

use think\Validate;

class SystemConfig extends Validate
{
/*    protected $rule = [
        ['name', 'require|unique:system_config_group', '分组名称必须填写|该分组名称已存在'],
        ['identification', 'require|unique:system_config_group', '标识必须填写|该标识已存在']
    ];*/

    protected $regex = [ 'regex_field' => '[a-zA-Z]\w*'];
    protected $rule =   [
        'name'  => 'require|unique:system_config',
        'field'   => 'require|regex_field|unique:system_config'
    ];

    protected $message  =   [
        'name.require' => '变量名称必须填写',
        'name.unique'     => '该变量名称已存在',
        'field.require' => '变量名必须填写',
        'field.regex_field' => '变量名以英文字母开头，后面支持数字和_',
        'field.unique'     => '该变量名已存在'
    ];

}