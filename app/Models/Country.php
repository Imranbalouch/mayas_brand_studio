<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Country extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'name',  
        'code',  
        'image',  
        'auth_id',
        'is_default',
        'is_admin_default'
    ];


    protected $hidden = [
        
        'id',
        'auth_id',

    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Country') // Set custom log name
        ->logOnly(['uuid','name','auth_id','is_default','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Country {$eventName} successfully"); 
    }

}
