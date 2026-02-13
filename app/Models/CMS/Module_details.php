<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Module_details extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'module_details';

    protected $fillable = [
        'uuid',
        'module_id',
        'details', 
        'view',
        'status',
        'auth_id',
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
        ->useLogName('Module details') // Set custom log name
        ->logOnly(['uuid', 'module_id', 'details', 'view', 'status', 'auth_id', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Module details {$eventName} successfully");
    }


    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    
    public function moduleDetails()
    {
        return $this->hasOne(ModuleField::class, 'module_id', 'uuid');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'uuid');
    }

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('defaul_lang') : $lang;
        $moduleDetailsTranslation = $this->moduleDetailsTranslation->where('lang', $lang)->first();
        return $moduleDetailsTranslation != null ? $moduleDetailsTranslation->$field : $this->$field;
    }

    public function moduleDetailsTranslation()
    {
        return $this->hasMany(Module_details_translation::class, 'module_detail_id', 'uuid');
    }
}
