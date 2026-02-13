<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Plugins extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'plugins';
    protected $fillable = ['uuid', 'name', 'slug', 'icon', 'image', 'status', 'license_key', 'short_code', 'model_name', 'description', 'settings'];

    protected static $recordEvents = ['created','updated','deleted'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Plugin') // Set custom log name
        ->logOnly(['uuid', 'name', 'slug', 'icon', 'image', 'status', 'license_key', 'short_code', 'description','created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Plugin {$eventName} successfully");
    }
}
