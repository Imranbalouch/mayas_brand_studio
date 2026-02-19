<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'uuid',
        'company',
        'country_id',
        'address',
        'apart_suite',
        'city',
        'email',
        'phone_number',
        'postal_code',
        'contact_name',
        'auth_id',
        'status'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Supplier') // Set custom log name
        ->logOnly(['uuid', 'company', 'country_id', 'address','apart_suite','city','postal_code','contact_name','email','phone_number', 'status','auth_id', 'created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Supplier {$eventName} successfully"); 
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

    public function country() {
        return $this->belongsTo(Country::class, 'country_id','uuid');
    }
}
