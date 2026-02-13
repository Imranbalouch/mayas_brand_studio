<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;



class OtherMenuTranslation extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'other_menus_translations';

    protected $fillable = [
        'uuid',
        'name',
        'icon',
        'menudetail',
        'menu_id',
        'language_id',
        'status',
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
        ->useLogName('Other Menu Translation') // Set custom log name
        ->logOnly(['uuid', 'name', 'icon', 'menudetail', 'menu_id', 'language_id', 'status', 'auth_id', 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Other Menu Translation {$eventName} successfully");
    }

}
