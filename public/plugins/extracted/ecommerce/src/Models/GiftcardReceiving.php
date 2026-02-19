<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftcardReceiving extends Model
{
    use HasFactory;

    protected $table = 'giftcard_receiving';

    protected $fillable = [
        'uuid',
        'auth_id',
        'giftcard_id',
        'order_id',
        'order_detail_id',
        'grand_total',
        'balance',
    ];

}
