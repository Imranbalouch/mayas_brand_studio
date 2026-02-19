<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTemp extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'uuid',
        'name',
        'slug',
        'warehouse_location',
        'unit_price',
        'compare_price',
        'cost_price',
        'current_stock',
        'description',
        'categories',
        'weight',
        'unit',
        'meta_title',
        'meta_description',
        'vendor_2',
        'country_name',
        'product_type',
        'physical_product',
        'tags',
        'channels',
        'markets',
        'hscode',
        'collections',
        'thumbnail_img',
        'variant_image',
        'variant_price',
        'option1_name',
        'option1_value',
        'option2_name',
        'option2_value',
        'option3_name',
        'option3_value',
        'status',
        'master_import_uuid',
        'created_at',
        'updated_at'
    ];
}