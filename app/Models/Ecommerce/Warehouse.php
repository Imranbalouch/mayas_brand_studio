<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Warehouse extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'warehouse';

    protected $fillable = [
        'uuid',
        'warehouse_name', 
        'prefix', 
        'featured',
        'description',
        'auth_id',
        'status'
    ];

    protected static $recordEvents = ['created','updated','deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Warehouse') // Set custom log name
        ->logOnly(['uuid', 'name', 'description', 'auth_id', 'status' , 'created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Warehouse {$eventName} successfully");
    }

    public function warehouse_translations(){
        return $this->hasMany(WarehouseTranslations::class);
      }
  
      public function warehouse_values() {
          return $this->hasMany(WarehouseValues::class);
      }

      // In WarehouseValues model
public function manager()
{
    return $this->belongsTo(User::class, 'manager_id');
}

public function warehouse()
{
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
}

}
