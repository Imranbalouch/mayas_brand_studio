<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftcardProduct extends Model
{
    use HasFactory;

    protected $table = 'giftcard_product';

    protected $fillable = [
        'uuid',
        'auth_id',
        'title',
        'description',
        'status',
        'media',
        'short_desc',
        'page_title',
        'meta_description',
        'url_handle',
        'published_date',
        'theme_template',
        'giftcard_template',
        'type',
        'tags',
        'vendor',
    ];

    public function variants()
    {
        return $this->hasMany(GiftcardProductVariant::class, 'giftcard_product_id', 'uuid');
    }
    public function product()
    {
        return $this->hasOne(Product::class, 'giftcard_product_id', 'uuid');
    }
}
