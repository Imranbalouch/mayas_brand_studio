<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


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
    ];
    
 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Brand Translation')
        ->logOnly(['uuid','brand_id','language_id','brand','logo','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Brand Translation {$eventName} successfully"); 
    }

    public function brand(){
        return $this->belongsTo(Brand::class, 'brand_id', 'uuid');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }

}