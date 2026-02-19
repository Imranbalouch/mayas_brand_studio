<?php
namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
     

    protected $fillable = [
        'product_id','name',  
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_markets', 'market_uuid', 'product_uuid');
    }
  }

