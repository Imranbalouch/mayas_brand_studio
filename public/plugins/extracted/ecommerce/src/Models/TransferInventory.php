<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class TransferInventory extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'uuid',
        'origin_location_id',
        'destination_location_id',  
        'estimated_date',
        'ship_carrier_id',
        'tracking_number',
        'reference_number',
        'note_to_supplier',
        'tags',
        'status', 
        'auth_id',
        'status'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Transfer Inventory') // Set custom log name
        ->logOnly(['uuid', 'name', 'description', 'status', 'auth_id', 'created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Transfer Inventory {$eventName} successfully"); 
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
            $model->ti_number = self::generateTINumber(); // Fixed static call
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public static function generateTINumber()
    {  // Get the last TI number
        $lastTI = TransferInventory::orderBy('id', 'desc')->first(); 
        // Extract the numeric part and increment it
        $lastNumber = $lastTI ? intval(substr($lastTI->ti_number, 3)) : 0;
        $nextNumber = $lastNumber + 1; 
        // Format the TI number
        return 'TI-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class,'supplier_id','uuid');
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class,'warehouse_id','uuid');
    }

     

    public function currency() {
        return $this->belongsTo(Currency::class,'supplier_currency_id','uuid');
    }

    public function shipcarrier() {
        return $this->belongsTo(Carrier::class,'ship_carrier_id','uuid');
    }

    public function transferInventoryitems() {
        return $this->hasMany(TransferInventoryItem::class,'ti_id','uuid');
    }

    public function transferInventoryitemReceiving() {
        return $this->hasMany(TIReceiving::class,'ti_id','uuid');
    }
}
