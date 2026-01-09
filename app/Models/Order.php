<?php

namespace App\Models;

/**
 * Order Model
 */
class Order extends BaseModel
{
    protected $table = "orders";

    protected $fillable = [
        'user_id',
        'order_number',
        'total_amount',
        'status'
    ];

}