<?php
namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFilter extends Model
{
    use HasFactory;
     

    protected $fillable = [
        'product_id','name','auth_id','uuid'  
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_filters', 'product_uuid');
    }
  }

