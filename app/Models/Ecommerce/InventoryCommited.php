<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCommited extends Model
{
    use HasFactory;
    protected $table =  'inventory_commited';
    protected $fillable = [
        'uuid',
        'inventory_id',
        'product_id',
        'sku',
        'qty',
        'order_id',
        'order_code',
        'status',
        'reason',
        'created_at',
        'updated_at',
    ];
}
