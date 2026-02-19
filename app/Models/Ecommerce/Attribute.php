<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Attribute extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'attributes';

    protected $fillable = [
        'uuid',
        'attribute_name', 
        'description',
        'auth_id',
        'status'
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Attribute') // Set custom log name
        ->logOnly(['uuid', 'name', 'description', 'auth_id', 'status' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Attribute {$eventName} successfully");
    }

    public function attribute_translations(){
        return $this->hasMany(AttributeTranslation::class);
      }
  
      public function attribute_values() {
          return $this->hasMany(AttributeValue::class);
      }

}
