<?php

namespace App\Models\CMS;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Theme extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'short_description',
        'version',
        'css_file',
        'js_file',
        'css_link',
        'css_link_rtl',
        'js_link_rtl',
        'js_link',
        'js_head_link',
        'theme_logo',
        'fav_icon',
        'thumbnail_img',
        'theme_path',
        'status',
        'auth_id',
        'theme_type'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Theme') // Set custom log name
        ->logOnly(['uuid', 'name', 'short_description', 'version', 'css_file', 'js_file', 'css_link', 'js_link', 'thumbnail_img', 'theme_path', 'status', 'auth_id', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Theme {$eventName} successfully"); 
    }


    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function widgets() {
        return $this->hasMany(Widget::class, 'theme_id', 'uuid');
    }

    public function dynamicforms() {
        return $this->hasMany(DynamicForm::class, 'theme_id', 'uuid');
    }

    public function pages() {
        return $this->hasMany(Page::class, 'theme_id', 'uuid');
    }
}
