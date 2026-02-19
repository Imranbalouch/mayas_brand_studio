<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fulfillment extends Model
{
    use HasFactory;
    protected $table = 'fulfillments';

    protected $fillable = [
        'uuid',
        'auth_id',
        'order_id',
        'order_detail_id',
        'status',
        'quantity',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'uuid');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id', 'uuid');
    }
}
