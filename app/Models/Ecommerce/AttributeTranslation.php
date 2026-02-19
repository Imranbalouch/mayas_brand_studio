<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class AttributeTranslation extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'attribute_translations';

    protected $fillable = [
        'uuid',
        'attribute_id',
        'attribute_name',
        'lang',
        'language_id',
        'auth_id',
    ];


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Attribute Translation') // Set custom log name
        ->logOnly(['uuid', 'attribute_id', 'attribute_name', 'lang', 'language_id', 'auth_id', 'status','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Attribute Translation {$eventName} successfully"); 
    }

    public function attribute(){
        return $this->belongsTo(Attribute::class);
    }


}
