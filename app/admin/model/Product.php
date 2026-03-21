<?php

namespace app\admin\model;

use think\Model;

class Product extends Model
{

    protected $name = "product";

    protected $deleteTime =  false;
    public function type()
    {
        return $this->belongsTo('app\admin\model\ProductType', 'product_type_id', 'id');
    }

    
    

}