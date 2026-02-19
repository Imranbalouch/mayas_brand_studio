<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftcardProductVariant extends Model
{
    use HasFactory;

    protected $table = 'giftcard_product_variants';

    protected $fillable = [
        'uuid',
        'auth_id',
        'giftcard_product_id',
        'variant',
        'sku',
        'price',
        'qty',
        'image',
        'location_id',
    ];

    public function giftcardProduct()
    {
        return $this->belongsTo(GiftcardProduct::class, 'giftcard_product_id', 'uuid');
    }
}
