<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Brand extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'brand',
        'slug',
        'logo',
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
        ->useLogName('Brand') // Set custom log name
        ->logOnly(['uuid','brand','slug','logo','order_level','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Language {$eventName} successfully"); 
    }

    public function brand_translations()
    {
        return $this->hasMany(Brand_translation::class);
    }

     public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'uuid');
    }
}
