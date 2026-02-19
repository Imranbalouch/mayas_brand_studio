<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = "cart";

    protected $fillable = [
        'uuid',
        'auth_id',
        'product_name',
        'varaint_name',
        'product_price',
        'product_qty',
        'order_id',
        'product_id',
        'variant_id',
        'custom_item_id',
        'product_sku',
        'product_img',
        'discount_amount',
        'coupon_uuid',
        'created_at',
        'updated_at',
        'rate',
        'total_rate_amount',
        'flat_discount',
        'percentage_discount',
        'each_discount',
        'product_discount_amount',
        'gross_amount',
        'coupon_percentage',
        'coupon_amount',
        'net_amount',
        'vat_percentage',
        'vat_amount',
        'shipping_vat_percent',
        'shipping_vat_amount',
        'total_amount',
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    public function product()
    {
        return $this->hasMany(Product::class, 'uuid', 'product_id');
    }

    public function products()
{
    return $this->belongsTo(Product::class, 'product_id', 'uuid');
}

    public function vat(){
        return $this->hasMany(Vat::class, 'uuid', 'product_id');
    }

    public function variant()
    {
        return $this->hasMany(ProductStock::class, 'uuid', 'variant_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'auth_id', 'uuid');
    }
    
    public function coupon()
    {
        return $this->belongsTo(Discount::class, 'coupon_uuid', 'uuid');
    }
}
