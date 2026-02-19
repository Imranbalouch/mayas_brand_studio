<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WarehouseValues extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'warehouse_locations';

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'language_id',
        'location_name',
        'auth_id',
        'location_address',
        'contact_number',
        'status',
        'manager_id',
        'featured',
        'capacity',
        'current_stock_level',
        'country',
        'apartment',
        'city',
        'postal_code',
        'phone',
        'is_default',
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('warehouse Value') // Set custom log name
        ->logOnly(['uuid', 'warehouse_id', 'language_id' , 'value' , 'color_code' , 'auth_id' , 'status' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "warehouse Value {$eventName} successfully"); 
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
}
