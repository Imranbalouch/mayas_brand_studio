<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Widget extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'widget_image',
        'theme_id',
        'html_code',
        'widget_type',
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
        ->useLogName('Widget') // Set custom log name
        ->logOnly(['uuid', 'name', 'theme_id', 'html_code', 'status', 'shortkey', 'auth_id', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Widget {$eventName} successfully");
    }


    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    
    public function widgetFields()
    {
        return $this->hasMany(WidgetField::class, 'widget_id', 'uuid');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'uuid');
    }

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('defaul_lang') : $lang;
        $widget_translation = $this->widget_translations->where('lang', $lang)->first();
        return $widget_translation != null ? $widget_translation->$field : $this->$field;
    }

    public function widget_translations(){
        return $this->hasMany(WidgetTranslation::class,'widget_uuid','uuid');
    }
}
