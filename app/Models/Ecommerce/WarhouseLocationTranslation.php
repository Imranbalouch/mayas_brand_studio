<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarhouseLocationTranslation extends Model
{
    use HasFactory;

    protected $table = 'warehouse_location_translations';

    protected $fillable = [
        'uuid',
        'location_id',
        'language_id',
        'lang',
        'location_name',
        'location_address',
        'description',
        'meta_title',
        'meta_description',
        'auth_id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
