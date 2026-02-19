<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class PurchaseOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'po_id',
        'product_id',
        'variant_id',
        'quantity',
        'sku',
        'unit_price',
        'tax',
        'total_amount',
    ];

    protected $hidden = [
        'id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id','uuid');
    }

    public function variant(){
        return $this->belongsTo(ProductStock::class, 'variant_id','uuid');
    }
    public function purchaseOrderitemReceiving() {
        return $this->hasMany(POReceiving::class,'po_item_id','uuid');
    }    
}
