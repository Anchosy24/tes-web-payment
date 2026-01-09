<?php

namespace App\Models;

/**
 * Order Items Model
 */
class OrderItems extends BaseModel
{
    protected $table = "order_items";

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'subtotal'
    ];

}