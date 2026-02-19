<?php
namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTag extends Model
{
    use HasFactory;
     

    protected $fillable = [
        'product_id','name',  
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags', 'tag_uuid', 'product_uuid');
    }
  }

