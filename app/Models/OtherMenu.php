<?php

namespace App\Models;

use App\Models\CMS\Theme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class OtherMenu extends Model
{
    
    use HasFactory , LogsActivity;

    protected $table = 'other_menus';

    protected $fillable = [
        'uuid',
        'theme_id',
        'name',
        'icon',
        'url',
        'status',
        'auth_id',
        'sort_id',
        'parent_id',
        'menu_detail',
        'parent_array',
        'child_array',
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

 
    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Other Menu')
        ->logOnly(['uuid', 'name', 'icon', 'url', 'status', 'auth_id', 'sort_id', 'parent_id', 'menu_detail', 'parent_array', 'child_array' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Other Menu {$eventName} successfully");
    }

    public function theme(){
        return $this->belongsTo(Theme::class, 'theme_id', 'uuid');
    }

}
