<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'transaction_id',
        'order_id',
        'amount',
        'description',
        'response_data',
        'transaction_url',
        'status',
        'response_message'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'uuid');
    }
}
