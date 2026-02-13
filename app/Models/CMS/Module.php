<?php

namespace App\Models\CMS;

use App\Models\CMS\ModuleTranslation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Module extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'modules';

    protected $fillable = [
        'uuid',
        'name',
        'theme_id',
        'html_code',
        'api_url',
        'moduletype',
        'moduleclass',
        'status',
        'shortkey',
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
        ->useLogName('Module') // Set custom log name
        ->logOnly(['uuid', 'name', 'theme_id', 'html_code', 'status', 'shortkey', 'auth_id', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Module {$eventName} successfully"); 
    }


    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    
    public function moduleFields()
    {
        return $this->hasMany(ModuleField::class, 'module_id', 'uuid');
    }

    public function moduleDetails()
    {
        return $this->hasMany(Module_details::class, 'module_id', 'uuid');
    }


    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'uuid');
    }

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('defaul_lang') : $lang;
        $module_translation = $this->module_translations->where('lang', $lang)->first();
        return $module_translation != null ? $module_translation->$field : $this->$field;
    }

    public function module_translations(){
        return $this->hasMany(ModuleTranslation::class,'module_id','uuid');
    }
}
