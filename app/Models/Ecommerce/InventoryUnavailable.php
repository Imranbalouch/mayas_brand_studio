<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryUnavailable extends Model
{
    use HasFactory;

    protected $table =  'inventory_unavailable';
}
