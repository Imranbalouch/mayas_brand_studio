<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Tax extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'name',  
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
        ->useLogName('Tax') // Set custom log name
        ->logOnly(['uuid','name','auth_id','is_default','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Tax {$eventName} successfully"); 
    }

}
