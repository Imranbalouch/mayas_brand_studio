<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxType extends Model
{
    use HasFactory;

    protected $table = 'tax_types';

    protected $fillable = [
        'uuid',
        'auth_id',
        'name',
        'value',
        'status',
    ];
    

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }
}
