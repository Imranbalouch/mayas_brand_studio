<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomItem extends Model
{
    use HasFactory;

    protected $table = "custom_item";

    protected $fillable = [
        'uuid',
        'auth_id',
        'order_id',
        'item_name',
        'price',
        'qty',
        'item_taxable',
        'item_physical_product',
        'item_weight',
        'unit',
    ];


}
