<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class WebsiteConfig extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'website_configs';

    protected $fillable = [
        'uuid',
        'site_name',
        'site_logo',
        'site_favicon',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'contact_email',
        'contact_phone',
        'contact_address',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'instagram_url',
        'youtube_url',
        'footer_text',
        'google_analytics_code',
        'maintenance_mode',
        'auth_id'
    ];

    protected $hidden = [
        
        'id',
        'auth_id',

    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Website Config') // Set custom log name
        ->logOnly(['uuid', 'site_name', 'site_logo', 'site_favicon', 'meta_title', 'meta_description', 'meta_keywords', 'contact_email', 'contact_phone', 'contact_address', 'facebook_url', 'twitter_url', 'linkedin_url', 'instagram_url', 'youtube_url', 'footer_text', 'google_analytics_code', 'maintenance_mode', 'auth_id', 'created_at', 'updated_at', 'deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Website Config {$eventName} successfully");
    }


}
