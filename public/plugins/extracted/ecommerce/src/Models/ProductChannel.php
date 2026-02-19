<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductChannel extends Model
{
    use HasFactory;

    protected $table = 'product_channels';

     protected $fillable = [
        'product_uuid', 'channel_uuid'
    ];
}
