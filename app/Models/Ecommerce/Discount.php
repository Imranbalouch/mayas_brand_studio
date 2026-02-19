<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Discount extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'method',
        'code',
        'type',
        'value',
        'applies_to',
        'applies_to_value',
        'requirement_type',
        'requirement_value',
        'eligibility',
        'eligibility_value',
        'minimum_shopping',
        'maximum_discount_amount',
        'uses_customer_limit',
        'apply_on_pos',
        'discount_type',
        'uses_limit',
        'combination_type',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'status',
        'auth_id', 
        'specific_customer',
        'customer_segments',
        'shipping_rate',
        'exclude_shipping_rates',
        'customer_buys',
        'customer_buys_quantity',
        'customer_buys_amount',
        'customer_get_quantity',
        'customer_get_percentage',
        'customer_get_amount_off_each',
        'customer_get_free',
        'maximum_number_per_order',
    ];


    protected $hidden = [ 
        'id',
        'auth_id', 
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Discount') // Set custom log name
        ->logOnly(['uuid','name','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Discount {$eventName} successfully"); 
    }

    public function di_timeline() {
        return $this->hasMany(DiscountTimeLine::class,'di_id','uuid');
    }

    public function discountItems() {
        return $this->hasMany(ProductDiscounts::class,'di_id','uuid');
    }

}
