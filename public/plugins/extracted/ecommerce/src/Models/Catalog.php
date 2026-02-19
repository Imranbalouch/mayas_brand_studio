<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Catalog extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'caltalog',
        'currency',
        'percentage',
        'price_adjustment',
        'company_location',
        'slug', 
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
        ->useLogName('caltalog') // Set custom log name
        ->logOnly(['uuid','caltalog','slug', 'order_level','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Language {$eventName} successfully"); 
    }

    public function catalog_translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function products()
    {
        return $this->hasMany(CatalogProduct::class, 'catalog_id', 'uuid');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'uuid');
    }

}
