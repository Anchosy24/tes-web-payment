<?php

namespace App\Models;

/**
 * Product Model
 */
class Product extends BaseModel
{
    protected $table = "products";

    protected $fillable = [
        'product_name',
        'description',
        'price',
        'stock'
    ];

}