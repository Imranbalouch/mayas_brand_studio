<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Channel extends Model
{
    use HasFactory , LogsActivity;

    use LogsActivity;
    // public $incrementing = false; // Disable auto-incrementing IDs
    // protected $keyType = 'string'; // Set the key type to string (for UUIDs)
    // protected $primaryKey = 'uuid'; // Set the primary key to 'uuid'
    

    // protected $with = ['category_translations'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('channel')->logOnly(['id','name','created_at','updated_at']);
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_channels', 'channel_uuid', 'product_uuid');
    }
    
    protected $fillable = [
        'uuid',  
        'name',
        'order_level', 
        'status',
        'featured', 
    ];
    
    protected $hidden = [
        // 'id', 
    ];

}
