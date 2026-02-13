<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PermissionTranslation extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'permission_translations';

    protected $fillable = [
        'uuid',
        'name',
        'permission_id',
        'language_id',
        'auth_id',
        'status',
    ];

    protected $hidden = [
        'id',
        'auth_id',
        'permission_id',
        'language_id',
        'status',
    ];


    protected static $recordEvents = ['created','updated','deleted'];
    
    public function getActivitylogOptions(): LogOptions
    {
        
        return LogOptions::defaults()
        ->useLogName('Permission Translation')
        ->logOnly(['uuid', 'name', 'permission_id', 'language_id', 'auth_id', 'status','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Permission Translation {$eventName} successfully");
        
    } 



}
