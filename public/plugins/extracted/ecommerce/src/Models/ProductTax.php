<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTax extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'tax_id', 'tax', 'tax_type'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
