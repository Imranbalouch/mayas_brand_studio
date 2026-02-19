<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected static $recordEvents = ['updated','deleted'];

    protected $fillable = [
        'uuid',
        'auth_id',
        'grand_total',
        'notes',
        'tags',
        'payment_due_later',
        'customer_id',
        'market_id',
        'location_id',
        'reserve_item',
        'code',
        'discount_code',
        'discount_type',
        'discount_value',
        'reason_for_discount',
        'auto_discount',
        'shipping_type',
        'shipping_price',
        'estimated_tax',
        'discount_amount',
        'total_coupon_amount',
        'mark_as_paid',
        'payment_method',
        'paid_at',
        'fulfilled_status',
        'store_pickup',
        'delivery_status',
        'channel_id', 
        'return_status', 
        'created_at',
        'updated_at',
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_address2',
        'billing_city',
        'billing_cities_id',
        'billing_countries_id',
        'billing_state',
        'billing_country',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_address2',
        'shipping_city',
        'shipping_cities_id',
        'shipping_countries_id',
        'shipping_state',
        'shipping_country',
        'tracking_status',
        'total_vat',
        'coupon_uuid',
        'discount_amount',
        'shipping_vat_percent',
        'shipping_vat_amount',
    ];

    protected $appends = ['payment_status'];

    public function getPaymentStatusAttribute()
    {
        return $this->mark_as_paid == 1 ? 'Paid' : 'Unpaid';
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('order')->logOnly(['id','code','payment_status','delivery_status']);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'uuid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function guest()
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'user_id', 'seller_id');
    }



    public function delivery_boy()
    {
        return $this->belongsTo(User::class, 'assign_delivery_boy', 'id');
    }

    public function orderTimeline()
    {
        return $this->hasMany(OrderTimeline::class, 'order_id', 'uuid');
    }
    public function customer()
{
    return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
}
public function channel(){
    return $this->belongsTo(Channel::class, 'channel_id', 'uuid');
}

public function tracking(){
    return $this->hasMany(Tracking::class, 'order_id', 'uuid');
}


public static function generateOrderCode() {
    // Get the latest order, ordered by creation time to ensure most recent
    $lastOrder = \App\Models\Order::orderBy('created_at', 'desc')->first();
    
    // If no previous orders exist, start from 1
    // Otherwise, extract the last number and increment
    $lastNumber = $lastOrder ? intval(substr($lastOrder->code, 4)) : 0;
    $nextNumber = $lastNumber + 1;
    
    // Format the order code with leading zeros
    return 'ORD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
}
  
}
