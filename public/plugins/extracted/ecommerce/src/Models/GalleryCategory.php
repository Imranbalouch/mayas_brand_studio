<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;



class GalleryCategory extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'gallery_categories';

    protected $fillable = [
        'uuid',
        'category_name',
        'slug',
        'auth_id',
        'status',
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Gallery Category') // Set custom log name
        ->logOnly(['uuid', 'category_name', 'slug' , 'auth_id' , 'status' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Gallery Category {$eventName} successfully"); 
    }

}
