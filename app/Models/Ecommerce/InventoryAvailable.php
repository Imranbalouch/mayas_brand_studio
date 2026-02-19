<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAvailable extends Model
{
    use HasFactory;

    protected $table =  'inventory_available';
    
    protected $fillable = [
        'uuid',
        'inventory_id',
        'status',
        'reason',
        'qty',
        'auth_id',
        'created_at',
        'updated_at',
    ];
}
