<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// use Illuminate\Database\Eloquent\SoftDeletes;


class Giftcard_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'giftcard_id',
        'language_id',
        'lang',
        'giftcard',
        'value',
        'code',
        'note',
        'auth_id',
    ];

    protected $hidden = [
        'id',
        'uuid',
        'giftcard_id',
        'language_id',
        'lang', 
        'value',
        'code',
        'note',
        'auth_id',
    ];
    
 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Giftcard Translation') // Set custom log name
        ->logOnly(['uuid','giftcard_id','language_id','giftcard','code','value','note','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Giftcard Translation {$eventName} successfully"); 
    }

    public function giftcard(){
        return $this->belongsTo(Giftcard::class);
    }

} 
