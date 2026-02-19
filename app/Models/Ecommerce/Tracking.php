<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    use HasFactory;

    protected $table = 'tracking';

    protected $fillable = [
        'uuid',
        'auth_id',
        'order_id',
        'fulfillment_id',
        'shipping_carrier',
        'tracking_number',
        'tracking_url',
        'created_at',
        'updated_at',
    ];

}
