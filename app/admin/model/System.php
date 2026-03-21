<?php
namespace app\admin\model;

use think\Model;

class System extends Model
{
	protected $name = 'system';
    public static function getCycleList()
    {
        return ['day' => fy('That day'), 'week' =>fy('The week'),'month'=>fy('Month')];
    }
}