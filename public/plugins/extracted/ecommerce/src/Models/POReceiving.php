<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class POReceiving extends Model
{
    use HasFactory;
    protected $table = "po_receiving";
    protected $fillable = ["uuid","po_id","po_item_id","product_id","variant_id","sku","accept_qty","reject_qty",'received_date'];

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

    public function purchaseOrderItem(){
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id','uuid');
    }
}
