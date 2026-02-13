<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    use HasFactory;

    protected $table = "page_templates";

   protected $fillable = [
    'uuid',
    'theme_uuid',
    'name',
    'shortkey',
    'api_url',
    'product_cart_html',
    'product_cart_slider_html',
    'page_html',
    'html_variant',
    'page_type',
    'product_class',
    'product_slug',
    'auth_id',
    'status',
];


}
