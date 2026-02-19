<?php
namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVendor extends Model
{
    use HasFactory;
     

    protected $fillable = [
        'vendor_uuid','product_uuid','name',  
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_vendors', 'product_uuid');
    }
  }

