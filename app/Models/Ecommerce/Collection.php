<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
class Collection extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'channel_uuid',
        'condition_status',
        'conditions',
        'description',
        'featured',
        'top',
        'channel_uuid',
        'image',
        'smart',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image',
        'x_title',
        'x_description',
        'x_image',
        'auth_id',
        'status',
        'published_datetime'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Collection') // Set custom log name
        ->logOnly(['uuid', 'name', 'slug', 'description','featured','top','meta_title','meta_description','channel_uuid','image', 'status','auth_id', 'created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Collection {$eventName} successfully"); 
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
            // Cache::forget("filter_limit"); // Clear cache if needed
            Cache::flush();  
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
            // Cache::forget("filter_limit"); // Clear cache if needed 
            Cache::flush();  
        });
        static::deleting(function ($model) {    
            // Cache::forget("filter_limit"); // Clear cache if needed
            Cache::flush();   
        });
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_collections')->withPivot('product_uuid', 'collection_uuid');
    }
    public function product_simple()
    {
        return $this->belongsToMany(Product::class, 'product_collections','product_id', 'collection_id');
    }
    // public function channels() {
    //     return $this->belongsTo(Channel::class, 'slug','uuid');
    // }

    // public function getImageAttribute($value)
    // {
    //     $path = public_path($value);
    //     return (!empty($value) && file_exists($path))
    //         ? getConfigValue('APP_ASSET_PATH') . $value
    //         : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    // }

    // public function getOgImageAttribute($value)  
    // {
    //     $path = public_path($value);
    //     return (!empty($value) && file_exists($path))
    //         ? getConfigValue('APP_ASSET_PATH') . $value
    //         : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    // }

    // public function getXImageAttribute($value)
    // {
    //     $path = public_path($value);
    //     return (!empty($value) && file_exists($path))
    //         ? getConfigValue('APP_ASSET_PATH') . $value
    //         : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    // }

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('default_lang') : $lang;
        $collection_translation = $this->collection_translation->where('lang', $lang)->first();
        return $collection_translation != null ? $collection_translation->$field : $this->$field;
    }

    public function collection_translation(){
        return $this->hasMany(CollectionTranslation::class,'collection_uuid','uuid');
    }

}
