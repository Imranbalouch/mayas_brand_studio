<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Menu_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'menu_id',
        'name',
        // 'description',
        // 'icon',
        'language_id',
        'auth_id'
    ];


    protected $hidden = [
        'id',
        'auth_id',
        'menu_id',
        'description',
        'icon',
        'status',
        'language_id',
    ];


    protected static $recordEvents = ['created','updated','deleted'];
    
    public function getActivitylogOptions(): LogOptions
    {
        
        return LogOptions::defaults()
        ->useLogName('Menu Translation') //Set custom log name
        ->logOnly(['uuid','menu_id','name','description','icon','language_id','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Menu Translation {$eventName} successfully");
        
    } 

}
