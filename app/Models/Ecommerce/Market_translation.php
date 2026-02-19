<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// use Illuminate\Database\Eloquent\SoftDeletes;


class Market_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'market_id',
        'language_id',
        'lang',
        'market',
        'logo',
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
    ];

    protected $hidden = [
        'id',
        'market_id',
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
        ->useLogName('Market Translation') // Set custom log name
        ->logOnly(['uuid','market_id','language_id','market','logo','description','meta_title','meta_description','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Market Translation {$eventName} successfully"); 
    }

    public function market(){
        return $this->belongsTo(Market::class);
    }

} 
