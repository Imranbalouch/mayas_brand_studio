<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class DynamicForm extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'dynamic_forms';

    protected $fillable = [
        'uuid',
        'form_name',
        'status',
        'details',
        'short_code',
        'is_recaptcha',
        'auth_id',
        'language_code',
        'from_email',
        'to_email',
        'submission_message',
        'redirect_url',
        'theme_id'

    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Dynamic Form') // Set custom log name
        ->logOnly(['uuid', 'form_name', 'status', 'details', 'short_code', 'is_recaptcha', 'auth_id', 'language_code', 'from_email', 'to_email', 'submission_message', 'redirect_url', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Dynamic Form {$eventName} successfully");
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'uuid');
    }

    public function contactUs()
    {
        return $this->hasMany(ContactUs::class, 'form_id', 'uuid');
    }
}
