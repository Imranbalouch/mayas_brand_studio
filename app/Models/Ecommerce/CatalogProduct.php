<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogProduct extends Model
{
    use HasFactory;

    protected $table = 'catalog_products';

    protected $fillable = [
        'uuid',
        'catalog_id',
        'product_id',
        'variant_id',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }
}
