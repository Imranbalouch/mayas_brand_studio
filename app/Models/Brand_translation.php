<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// use Illuminate\Database\Eloquent\SoftDeletes;


class Brand_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'brand_id',
        'language_id',
        'lang',
        'brand',
        'logo',
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
    ];

    protected $hidden = [
        'id',
        'brand_id',
        'language_id',
        'logo',
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
    ];
    
 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Brand Translation') // Set custom log name
        ->logOnly(['uuid','brand_id','language_id','brand','logo','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Brand Translation {$eventName} successfully"); 
    }

    public function brand(){
        return $this->belongsTo(Brand::class);
    }

} 
