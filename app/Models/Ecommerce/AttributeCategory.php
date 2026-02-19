<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class AttributeCategory extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'category_id',
        'attribute_id',
        'auth_id'
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Attribute') // Set custom log name
        ->logOnly(['uuid', 'category_id', 'attribute_id' , 'auth_id' , 'status' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Attribute Category {$eventName} successfully"); 
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }


}
