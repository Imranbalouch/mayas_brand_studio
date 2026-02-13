<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Menu extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'sort_id',
        'icon',
        'auth_id',
        'status',
        'parent_id',
        'url',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];
    

    protected static $recordEvents = ['created','updated','deleted'];
    
    public function getActivitylogOptions(): LogOptions
    {
        
        return LogOptions::defaults()
        ->useLogName('Menu') //Set custom log name
        ->logOnly(['uuid','name','sort_id','icon','auth_id','parent_id', 'url' , 'status', 'ischild' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Menu {$eventName} successfully");
        
    } 

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_id');
    }


}
