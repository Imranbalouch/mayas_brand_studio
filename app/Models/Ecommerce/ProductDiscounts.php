<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class ProductDiscounts extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'di_id',
        'auth_id',
        'product_id',
        'variant_id',
        'collection_id',
        'countries_id',
        'quantity',
        'method',
        'value', 
        'type',
        'customer_buy_product_id',
        'customer_buy_variant_id',
        'customer_get_product_id',
        'customer_get_variant_id',
        'customer_buy_collection_id',
        'customer_get_collection_id',
    ];

    protected $hidden = [
        'id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id','uuid');
    }

    public function variant(){
        return $this->belongsTo(ProductStock::class, 'variant_id','uuid');
    }
 
}
