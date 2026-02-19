<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;
class PaymentTerms extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'paymentterms';
    
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'url',
        'status',
        'auth_id',
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Payment Term') // Set custom log name
        ->logOnly(['uuid', 'name', 'description', 'status', 'auth_id', 'created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Payment Term {$eventName} successfully"); 
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
}
