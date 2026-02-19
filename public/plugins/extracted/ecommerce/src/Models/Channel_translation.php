<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// use Illuminate\Database\Eloquent\SoftDeletes;


class Channel_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'channel_id',
        'language_id',
        'lang',
        'channel', 
        'auth_id',
    ];

    protected $hidden = [
        'id',
        'channel_id',
        'language_id', 
        'auth_id',
    ];
    
 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Channel Translation') // Set custom log name
        ->logOnly(['uuid','channel_id','language_id','channel','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Channel Translation {$eventName} successfully"); 
    }

    public function channel(){
        return $this->belongsTo(Channel::class);
    }

} 
