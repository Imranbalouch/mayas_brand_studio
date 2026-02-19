<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Attribute_translation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'attribute_id',
        'language_id',
        'attribute_name',
        'description',
        'auth_id',
    ];


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Attribute Translation') // Set custom log name
        ->logOnly(['uuid', 'attribute_id', 'language_id', 'attribute_name', 'description', 'auth_id', 'status','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Attribute Translation {$eventName} successfully"); 
    }

}
