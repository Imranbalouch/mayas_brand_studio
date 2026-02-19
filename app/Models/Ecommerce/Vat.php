<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    use HasFactory;

    protected $table = 'vat';

    protected $fillable = [
        'uuid',
        'auth_id',
        'name', 
        'rate',
        'status',
    ];

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }
}
