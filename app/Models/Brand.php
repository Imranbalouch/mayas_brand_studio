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
        'og_title',
        'og_description',
        'og_image',
        'x_title',
        'x_description',
        'x_image',
    ];


    protected $hidden = [
        'auth_id',
    ];

 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Brand')
        ->logOnly(['uuid','brand','slug','logo','order_level','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Brand {$eventName} successfully"); 
    }

    public function brand_translations()
    {
        return $this->hasMany(Brand_translation::class, 'brand_id', 'uuid');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'uuid');
    }

    /**
     * Get translation for a specific field and language
     */
    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('defaul_lang') : $lang;
        $brand_translation = $this->brand_translations->where('lang', $lang)->first();
        return $brand_translation != null ? $brand_translation->$field : $this->$field;
    }
}