<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Testimonial extends Model
{
    use HasFactory , LogsActivity;

    
    protected $fillable = [
        'uuid',
        'name',
        'position',
        'company',
        'image',
        'auth_id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    protected $hidden = [
        
        'id',
        'auth_id',

    ];


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Testimonial') // Set custom log name
        ->logOnly(['uuid', 'name', 'position', 'company', 'image', 'auth_id', 'status', 'created_at', 'updated_at', 'deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Testimonial {$eventName} successfully");
    }


}
