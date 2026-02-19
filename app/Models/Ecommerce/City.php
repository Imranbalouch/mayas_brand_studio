<?php

namespace App\Models\Ecommerce;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

     protected $table = 'cities';

    protected $fillable = [
        'uuid',
        'auth_id',
        'country_uuid',
        'name',
        'price',
        'min_price',
        'vat_percent',
    ];
     protected $hidden = [
        'id',
        'auth_id',
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('City') // Set custom log name
        ->logOnly(['uuid', 'country_uuid', 'name', 'price', 'min_price', 'vat_percent', 'auth_id', 'created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "City {$eventName} successfully"); 
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

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function country(){
        return $this->hasMany(Country::class,'uuid','country_uuid');
    }
}
