<?php

namespace App\Models\Ecommerce;

use DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $fillable = ['uuid','order_code', 'product_id','location_id','stock_id','status','reason', 'variant', 'sku', 'price', 'qty', 'image','auth_id','order_id'];


    public function product() {
        return $this->belongsTo(Product::class,'product_id','uuid');
    }

    public function product_stock() {
        return $this->belongsTo(ProductStock::class,'stock_id','uuid');
    }

    public function thumbnail()
    {
        return $this->belongsTo(Upload::class, 'product_image','id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('Inventory')->logOnly(['*']);
    }

    public function getThumbnailimgAttribute($value)
    {
        $path = public_path($value);
        return (!empty($value) && file_exists($path))
            ? getConfigValue('APP_ASSET_PATH') . $value
            : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    }
} 
