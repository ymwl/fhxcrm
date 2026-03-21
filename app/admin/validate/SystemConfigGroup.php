<?php
namespace app\admin\validate;

use think\Validate;

class SystemConfigGroup extends Validate
{


    protected $rule =   [
        'name'  => 'require|unique:system_config_group',
        'identification'   => 'require|unique:system_config_group'
    ];

    protected $message  =   [
        'name.require' => '分组名称必须填写',
        'name.unique'     => '该分组名称已存在',
        'identification.require' => '标识必须填写',
        'identification.unique'     => '该标识已存在'
    ];

}