<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Report extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'auth_id', 
    ];


    protected $hidden = [ 
        'id',
        'auth_id',
        'deleted_at',
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Report') // Set custom log name
        ->logOnly(['uuid','name','auth_id','type','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Report {$eventName} successfully"); 
    }

}
