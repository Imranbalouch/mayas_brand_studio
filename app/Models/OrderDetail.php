<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{


    protected $fillable = [
        'uuid',
        'auth_id',
        'product_name',
        'variant',
        'product_price',
        'product_qty',
        'order_id',
        'product_id',
        'variant_id',
        'custom_item_id',
        'fulfilled_status',
        'created_at',
        'updated_at',
        'image',
    ];

    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function channel(){
        return $this->belongsTo(Channel::class, 'channel_id', 'uuid');
    }

    public function pickup_point()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function refund_request()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }
}
