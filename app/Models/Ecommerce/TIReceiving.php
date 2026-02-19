<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class TIReceiving extends Model
{
    use HasFactory;
    protected $table = "ti_receiving";
    protected $fillable = ["uuid","ti_id","ti_item_id","product_id","variant_id","accept_qty","reject_qty",'received_date'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function transferInventoryItem(){
        return $this->belongsTo(TransferInventoryItem::class, 'ti_item_id','uuid');
    }
}
