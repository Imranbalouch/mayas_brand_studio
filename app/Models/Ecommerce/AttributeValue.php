<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class AttributeValue extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'attribute_values';

    protected $fillable = [
        'uuid',
        'attribute_id',
        'language_id',
        'value',
        'color_code',
        'auth_id',
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Attribute Value') // Set custom log name
        ->logOnly(['uuid', 'attribute_id', 'language_id' , 'value' , 'color_code' , 'auth_id' , 'status' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Attribute Value {$eventName} successfully"); 
    }

    public function attribute() {
        return $this->belongsTo(Attribute::class);
    }

}
