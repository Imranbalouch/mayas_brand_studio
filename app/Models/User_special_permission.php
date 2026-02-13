<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class User_special_permission extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [

        'uuid',
        'user_id',
        'permission_id',  
        'menu_id',
        'auth_id',
        'status'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];
    

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('User') // Set custom log name
        ->logOnly(['uuid','user_id','permission_id','menu_id','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Special Permission {$eventName} Successfully"); 
        
    } 

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
    
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
    

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'auth_id', 'uuid');
    }

}
