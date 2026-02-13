<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class RoleTranslation extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'role_translations';

    protected $fillable = [
        'uuid',
        'name',
        'role_id',
        'language_id',
        'auth_id',
        'status',
    ];

    protected $hidden = [
        'id',
        'auth_id',
        'role_id',
        'language_id',
        'status',
    ];

    protected static $recordEvents = ['created','updated','deleted'];
    
    public function getActivitylogOptions(): LogOptions
    {
        
        return LogOptions::defaults()
        ->useLogName('Role Translation')
        ->logOnly(['uuid', 'name', 'role_id', 'language_id', 'auth_id', 'status','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Role Translation {$eventName} successfully");
        
    }

}
