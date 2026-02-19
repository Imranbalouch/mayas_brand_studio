<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// use Illuminate\Database\Eloquent\SoftDeletes;


class catalog_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'brand_id',
        'language_id',
        'lang',
        'catalog', 
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
    ];

    protected $hidden = [
        'id',
        'catalog_id',
        'language_id', 
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
    ];
    
 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('catalog Translation') // Set custom log name
        ->logOnly(['uuid','catalog_id','language_id','catalog', 'description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "catalog Translation {$eventName} successfully"); 
    }

    public function brand(){
        return $this->belongsTo(catalog::class);
    }

} 
