<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class ModuleField extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'module_fields';

    protected $fillable = [
        'uuid',
        'module_id',
        'field_name',
        'field_id',
        'field_type',
        'field_options',
        'is_required',
        'status',
        'auth_id'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
        });
        static::updating(function ($model) {
            $model->auth_id = Auth::user()->uuid;
        });
    }


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Module Fields') // Set custom log name
        ->logOnly(['uuid', 'module_id', 'field_name', 'field_id', 'field_type', 'field_options', 'status', 'auth_id', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Module Fields {$eventName} successfully"); 
    }


    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'uuid');
    }
}
