<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WarehouseTranslations extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'warehouse_translation';

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'warehouse_name',
        'prefix',
        'lang',
        'language_id',
        'auth_id',
    ];


    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Warehouse Translation') // Set custom log name
        ->logOnly(['uuid', 'warehouse_id', 'warehouse_name', 'prefix', 'lang', 'language_id', 'auth_id', 'status','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Warehouse Translation {$eventName} successfully"); 
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }

}
