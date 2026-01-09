<?php

namespace App\Models;

/**
 * Payment Model
 */
class Payment extends BaseModel
{
    protected $table = "payments";

    protected $fillable = [
        'order_id',
        'payment_type',
        'gross_amount',
        'status',
        'payment_response',
        'snap_token'
    ];

}