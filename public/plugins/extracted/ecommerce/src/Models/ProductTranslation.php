<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class ProductTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name', 'unit', 'description', 'short_description', 'lang'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
}
