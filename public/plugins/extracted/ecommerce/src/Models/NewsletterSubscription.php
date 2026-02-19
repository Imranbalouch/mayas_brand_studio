<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class NewsletterSubscription extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'status',
    ];

    protected $hidden = [
        'id',
        'auth_id'
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Inquiry') // Set custom log name
        ->logOnly(['uuid', 'name', 'email', 'status', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Inquiry {$eventName} successfully");
    }


}
