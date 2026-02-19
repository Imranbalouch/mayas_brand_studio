<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnExchange extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'auth_id',
        'order_id',
        'order_detail_id',
        'qty',
        'reason_for_return',
        'return_file',
        'return_url',
        'tracking_number',
        'shipping_carrier',
        'exchange_products',
        'return_shipping_fees',
        'restocking_fees',
        'expected_return',
        'return_price',
        'restock_status',
        'restocked_qty',
        'restocked_at',
        'restocked_by'
    ];
    
}
