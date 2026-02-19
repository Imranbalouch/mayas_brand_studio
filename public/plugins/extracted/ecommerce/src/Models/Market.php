<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Market extends Model
{
    
    use HasFactory , LogsActivity;
    // public $incrementing = false; // Disable auto-incrementing IDs
    // protected $keyType = 'string'; // Set the key type to string (for UUIDs)
    // protected $primaryKey = 'uuid'; // Set the primary key to 'uuid'
    
    protected $fillable = [
        'uuid',
        'market_name',
        'country_id', 
        'country_names', 
        'country_images', 
        'language_id', 
        'currency_id', 
        'price_adjustment', 
        'percentage', 
        'order_level',
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
        'status',
        'featured',
    ];


    protected $hidden = [
        // 'id',
        'auth_id',
    ];

 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Market') // Set custom log name
        ->logOnly(['uuid','market_name','order_level','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Language {$eventName} successfully"); 
    }

    public function market_translations()
    {
        return $this->hasMany(Market_translation::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_markets', 'market_uuid', 'product_uuid');
    }

}
